-- ============================================
-- Rasprodaja.rs - migracija: 2026-09 (features paket)
-- Pokrenuti JEDNOM u phpMyAdmin / mysql klijentu.
-- Obuhvata: cookie consent, remember-me, user settings,
--           recenzije (procena prodavca/kupca), notifikacije.
-- ============================================
SET NAMES utf8mb4;

-- ============================================
-- 1. COOKIE CONSENT - izbor korisnika se cuva u bazi
-- ============================================
CREATE TABLE IF NOT EXISTS `cookie_consents` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `consent_token` char(36) CHARACTER SET latin1 DEFAULT NULL COMMENT 'UUID uredjaja za anonime posetioce',
  `necessary` tinyint(1) NOT NULL DEFAULT 1,
  `functional` tinyint(1) NOT NULL DEFAULT 0,
  `analytics` tinyint(1) NOT NULL DEFAULT 0,
  `marketing` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user` (`user_id`),
  UNIQUE KEY `uq_token` (`consent_token`),
  CONSTRAINT `fk_cc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. REMEMBER ME - sigurni tokeni (hash, rotacija)
-- ============================================
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) CHARACTER SET latin1 NOT NULL COMMENT 'SHA-256 hash tokena iz kolacica',
  `expires_at` datetime NOT NULL,
  `created_ip` varchar(45) DEFAULT NULL,
  `created_ua` varchar(255) DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  KEY `idx_user` (`user_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_rt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. PODESAVAANJA KORISNIKA (privatnost + notifikacije)
-- ============================================
CREATE TABLE IF NOT EXISTS `user_settings` (
  `user_id` int(11) NOT NULL,
  `settings` json DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. RECENZIJE - veza sa konverzacijom + dedup + indeks
-- (tim user_reviews vec postoji; dodajemo kolone)
-- NAPOMENKA: "IF NOT EXISTS" na kolonama radi na MariaDB 10.2+ (cPanel standard).
-- Ako si na CISTOM MySQL 8.x, skloni "IF NOT EXISTS" iz tog ALTER-a i
-- porucicu izvrsi samo jednom.
-- ============================================
ALTER TABLE `user_reviews`
  ADD COLUMN IF NOT EXISTS `conversation_id` int(11) DEFAULT NULL AFTER `ad_id`,
  ADD COLUMN IF NOT EXISTS `review_type` enum('seller','buyer','general') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general' AFTER `conversation_id`,
  ADD UNIQUE KEY IF NOT EXISTS `uq_review_per_conv` (`reviewer_id`, `user_id`, `conversation_id`),
  ADD KEY IF NOT EXISTS `idx_user_approved` (`user_id`, `is_approved`, `created_at`);

-- ============================================
-- 5. NOTIFIKACIJE - indeks za brzo citanje
-- ============================================
ALTER TABLE `notifications`
  ADD KEY IF NOT EXISTS `idx_user_read` (`user_id`, `is_read`, `created_at`);

-- ============================================
-- 6. NOTIFIKACIJE - prosiriti ENUM tip o 'review'
--    (postoji samo u novijim instalacijama; ako 'review' vec postoji,
--     ova linija je bezopasna jer prepisuje isti ENUM)
-- ============================================
ALTER TABLE `notifications`
  MODIFY COLUMN `type` ENUM('message','ad_approved','ad_rejected','ad_expiring','system','newsletter','review') NOT NULL DEFAULT 'system';

-- Gotovo. Proveri: SHOW TABLES LIKE 'cookie_consents'; itd.
