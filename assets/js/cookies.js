/**
 * assets/js/cookies.js - Globalni cookie consent
 *
 * - Pokazuje banner dok korisnik ne izabere opcije
 * - Izbor se cuva u localStorage (za trenutni browser) I SALJE NA SERVER
 *   (api/consent/save.php -> SQL tabela cookie_consents)
 * - Open-source API za ostale skripte:
 *     window.rasproCookies.allowed('analytics')
 *     window.rasproCookies.onChange(fn)
 *     window.openCookieSettings()  // otvara modal (koristi ga i /cookies stranica i side meni)
 * - Analytics (gtag) se ucitava SAMO ako je korisnik prihvatio analiticke kolacice.
 */
(function () {
    'use strict';

    var LS_KEY = 'cookiesSettings';
    var LS_ACCEPTED_KEY = 'cookiesAccepted';
    var LS_DEVICE_KEY = 'rasproDeviceId';
    var listeners = [];

    function getDeviceId() {
        var id = localStorage.getItem(LS_DEVICE_KEY);
        if (!id) {
            id = (window.crypto && crypto.randomUUID)
                ? crypto.randomUUID()
                : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                    var r = Math.random() * 16 | 0;
                    return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
                });
            localStorage.setItem(LS_DEVICE_KEY, id);
        }
        return id;
    }

    function currentSettings() {
        try {
            var raw = localStorage.getItem(LS_KEY);
            if (raw) return JSON.parse(raw);
        } catch (e) { /* ignore */ }
        return null;
    }

    function hasConsent() {
        // 'true' je migracija sa starog /cookies page sistema ('1' je novi format)
        var v = localStorage.getItem(LS_ACCEPTED_KEY);
        return (v === '1' || v === 'true') && currentSettings() !== null;
    }

    function allowed(name) {
        if (name === 'necessary') return true;
        var s = currentSettings();
        return !!(s && s[name]);
    }

    function saveSettings(settings, closeBanner) {
        settings.necessary = true;
        localStorage.setItem(LS_KEY, JSON.stringify(settings));
        if (closeBanner) {
            localStorage.setItem(LS_ACCEPTED_KEY, '1');
            hideBanner();
        }
        // Posalji izbor na server (SQL) - fire & forget
        try {
            var body = {
                functional: settings.functional ? 1 : 0,
                analytics: settings.analytics ? 1 : 0,
                marketing: settings.marketing ? 1 : 0,
                device_id: getDeviceId()
            };
            var payload = JSON.stringify(body);
            // sendBeacon je najbolji nacin (radi i pri zatvaranju stranice)
            if (navigator.sendBeacon) {
                var blob = new Blob([payload], { type: 'application/json' });
                navigator.sendBeacon('/api/consent/save.php', blob);
            } else {
                fetch('/api/consent/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: payload,
                    credentials: 'same-origin'
                }).catch(function () {});
            }
        } catch (e) {
            console.warn('Consent not synced to server:', e);
        }

        // Obavesti pretplatnike + ponovo pokreni analytics gate
        listeners.forEach(function (fn) { try { fn(settings); } catch (e) {} });
        loadAnalyticsIfAllowed();
    }

    function showBanner() {
        var banner = document.getElementById('cookieConsentBanner');
        if (banner) banner.classList.remove('d-none');
    }

    function hideBanner() {
        var banner = document.getElementById('cookieConsentBanner');
        if (banner) banner.classList.add('d-none');
    }

    function openModal() {
        var el = document.getElementById('cookieSettingsModal');
        if (!el) return;
        // Popuni prethodnim izborom
        var s = currentSettings();
        var f = document.getElementById('cookieFunctional');
        var a = document.getElementById('cookieAnalytics');
        var m = document.getElementById('cookieMarketing');
        if (f) f.checked = s ? !!s.functional : true;
        if (a) a.checked = s ? !!s.analytics : false;
        if (m) m.checked = s ? !!s.marketing : false;

        if (window.bootstrap && window.bootstrap.Modal) {
            new window.bootstrap.Modal(el).show();
        } else {
            // fallback: jednostavan prikaz
            el.classList.add('show');
            el.style.display = 'block';
        }
    }

    // ============================================
    // ANALYTICS GATE - gtag se ucitava samo uz pristanak
    // ============================================
    var analyticsLoaded = false;
    function loadAnalyticsIfAllowed() {
        if (analyticsLoaded || !allowed('analytics')) return;
        var id = window.GOOGLE_ANALYTICS_ID;
        if (!id || /XXXX/.test(id)) return; // nije konfigurisano
        analyticsLoaded = true;

        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('config', id);
    }

    // ============================================
    // INIT
    // ============================================
    function init() {
        if (!hasConsent()) {
            setTimeout(showBanner, 800);
        } else {
            loadAnalyticsIfAllowed();
        }

        var acceptBtn = document.getElementById('cookieAcceptBtn');
        var rejectBtn = document.getElementById('cookieRejectBtn');
        var manageBtn = document.getElementById('cookieManageBtn');
        var saveBtn = document.getElementById('cookieSaveBtn');

        if (acceptBtn) acceptBtn.addEventListener('click', function () {
            saveSettings({ functional: true, analytics: true, marketing: true }, true);
        });
        if (rejectBtn) rejectBtn.addEventListener('click', function () {
            saveSettings({ functional: false, analytics: false, marketing: false }, true);
        });
        if (manageBtn) manageBtn.addEventListener('click', openModal);
        if (saveBtn) saveBtn.addEventListener('click', function () {
            var settings = {
                functional: !!(document.getElementById('cookieFunctional') || {}).checked,
                analytics: !!(document.getElementById('cookieAnalytics') || {}).checked,
                marketing: !!(document.getElementById('cookieMarketing') || {}).checked
            };
            saveSettings(settings, true);
            var el = document.getElementById('cookieSettingsModal');
            if (el && window.bootstrap && window.bootstrap.Modal.getInstance(el)) {
                window.bootstrap.Modal.getInstance(el).hide();
            }
        });

        // Bilo koji element sa data-open-cookie-settings otvara modal
        document.addEventListener('click', function (e) {
            var t = e.target.closest('[data-open-cookie-settings]');
            if (t) {
                e.preventDefault();
                openModal();
            }
        });
    }

    // Javni API
    window.rasproCookies = {
        allowed: allowed,
        get: currentSettings,
        hasConsent: hasConsent,
        onChange: function (fn) { listeners.push(fn); },
        openSettings: openModal,
        reset: function () {
            localStorage.removeItem(LS_KEY);
            localStorage.removeItem(LS_ACCEPTED_KEY);
            showBanner();
        }
    };
    window.openCookieSettings = openModal;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
