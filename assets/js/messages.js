/**
 * messages.js - Modern Messaging System
 * Optimizovan za mobilne uređaje
 */

class MessagingApp {
    constructor() {
        this.currentConversation = null;
        this.currentReceiver = null;
        this.pollingInterval = null;
        this.lastMessageId = 0;
        this.isMobile = window.innerWidth <= 992;
        this.isLoading = false;
        this.isOpen = false;
        
        this.init();
    }

    init() {
        console.log('MessagingApp init...');
        this.bindEvents();
        this.handleResize();
        this.loadUnreadCount();
        this.loadConversations();
        
        // Auto-refresh svakih 10 sekundi
        this.startPolling();
        
        // Inicijalno otvori prvi razgovor ako postoji
        setTimeout(() => {
            this.openFirstConversation();
        }, 500);
    }

    bindEvents() {
        // Klik na konverzaciju
        document.addEventListener('click', (e) => {
            const item = e.target.closest('.conversation-item');
            if (item) {
                e.preventDefault();
                const convId = item.dataset.conversationId;
                const userId = item.dataset.otherUserId;
                if (convId && userId) {
                    this.openConversation(convId, userId);
                }
            }
        });

        // Slanje poruke
        const form = document.getElementById('sendMessageForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
        }

        // Enter za slanje
        const input = document.getElementById('messageInput');
        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }

        // Back to list (mobile)
        const backToList = document.getElementById('backToList');
        if (backToList) {
            backToList.addEventListener('click', (e) => {
                e.preventDefault();
                this.showConversationsList();
            });
        }

        const backToList2 = document.getElementById('back-to-list');
        if (backToList2) {
            backToList2.addEventListener('click', (e) => {
                e.preventDefault();
                this.showConversationsList();
            });
        }

        // View profile
        const viewProfileBtn = document.getElementById('viewProfileBtn');
        if (viewProfileBtn) {
            viewProfileBtn.addEventListener('click', () => {
                if (this.currentReceiver) {
                    window.open(`?page=profile&id=${this.currentReceiver}`, '_blank');
                }
            });
        }

        // Delete chat
        const deleteChatBtn = document.getElementById('deleteChatBtn');
        if (deleteChatBtn) {
            deleteChatBtn.addEventListener('click', () => {
                if (confirm('Da li ste sigurni da želite da obrišete ovaj razgovor?')) {
                    this.deleteConversation();
                }
            });
        }

        // Search
        const searchInput = document.getElementById('search-conversations');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterConversations(e.target.value);
            });
        }

        // New message
        const newMsgBtn = document.getElementById('new-message-btn');
        if (newMsgBtn) {
            newMsgBtn.addEventListener('click', () => {
                alert('Započnite razgovor klikom na "Kontaktiraj prodavca" na oglasu.');
            });
        }

        // Window resize
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                this.handleResize();
            }, 250);
        });

        console.log('Events bound successfully');
    }

    handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth <= 992;
        
        if (this.isMobile && !wasMobile) {
            if (this.isOpen) {
                this.hideMobileHeader();
                document.querySelector('.conversations-panel')?.classList.add('hidden');
                document.querySelector('.chat-panel')?.classList.add('active');
                document.body.classList.add('chat-open');
            }
        } else if (!this.isMobile && wasMobile) {
            this.showMobileHeader();
            document.querySelector('.conversations-panel')?.classList.remove('hidden');
            document.querySelector('.chat-panel')?.classList.remove('active');
            document.body.classList.remove('chat-open');
        }
    }

    openFirstConversation() {
        const firstItem = document.querySelector('.conversation-item');
        if (firstItem && !this.currentConversation) {
            const convId = firstItem.dataset.conversationId;
            const userId = firstItem.dataset.otherUserId;
            if (convId && userId) {
                this.openConversation(convId, userId);
            }
        }
    }

    hideMobileHeader() {
        const mobileHeader = document.getElementById('mobileHeader');
        if (mobileHeader) {
            mobileHeader.style.display = 'none';
        }
        document.querySelector('.mobile-header')?.classList.add('hidden');
    }

    showMobileHeader() {
        const mobileHeader = document.getElementById('mobileHeader');
        if (mobileHeader) {
            mobileHeader.style.display = 'flex';
        }
        document.querySelector('.mobile-header')?.classList.remove('hidden');
    }

    openConversation(conversationId, otherUserId) {
        console.log('Opening conversation:', conversationId, otherUserId);
        
        if (this.isLoading) {
            console.log('Already loading, skipping...');
            return;
        }
        
        if (this.currentConversation == conversationId) {
            console.log('Already open, skipping...');
            return;
        }

        this.isLoading = true;
        this.currentConversation = conversationId;
        this.currentReceiver = otherUserId;
        this.lastMessageId = 0;
        this.isOpen = true;

        try {
            document.querySelectorAll('.conversation-item').forEach(el => {
                el.classList.toggle('active', el.dataset.conversationId == conversationId);
            });

            const emptyChat = document.getElementById('emptyChat');
            const chatContainer = document.getElementById('chatContainer');
            
            if (emptyChat) emptyChat.classList.add('d-none');
            if (chatContainer) chatContainer.classList.remove('d-none');

            const convIdInput = document.getElementById('chatConversationId');
            const receiverIdInput = document.getElementById('chatReceiverId');
            if (convIdInput) convIdInput.value = conversationId;
            if (receiverIdInput) receiverIdInput.value = otherUserId;

            if (this.isMobile) {
                const panel = document.querySelector('.conversations-panel');
                const chatPanel = document.querySelector('.chat-panel');
                if (panel) panel.classList.add('hidden');
                if (chatPanel) chatPanel.classList.add('active');
                
                this.hideMobileHeader();
                document.body.classList.add('chat-open');
            }

            this.loadMessages(conversationId);
            this.markAsRead(conversationId);

            // FOKUSIRANJE - POBOLJŠANO ZA MOBILE
            setTimeout(() => {
                this.focusMessageInput();
            }, 500);

        } catch (error) {
            console.error('Error opening conversation:', error);
            this.showNotification('danger', 'Greška pri otvaranju razgovora');
        } finally {
            this.isLoading = false;
        }
    }

    // NOVA FUNKCIJA ZA FOKUSIRANJE
    focusMessageInput() {
        const input = document.getElementById('messageInput');
        if (input) {
            // Za mobilne, moramo da koristimo malo drugačiji pristup
            if (this.isMobile) {
                // Na mobile, fokusiramo ali ne otvaramo tastaturu automatski
                input.focus();
                // Ovo pomaže da se input prikaže na iOS
                input.click();
            } else {
                // Na desktop, normalan fokus
                input.focus();
            }
            console.log('Input focused');
        }
    }

    loadMessages(conversationId) {
        const area = document.getElementById('messagesArea');
        if (!area) return;

        area.innerHTML = this.getLoadingSpinner();

        fetch(`${SITE_CONFIG.url}/api/chat/conversation.php?id=${conversationId}`, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Messages loaded:', data);
            
            if (data.success) {
                if (data.other_user) {
                    const avatar = document.getElementById('chatAvatar');
                    if (avatar) {
                        avatar.src = data.other_user.avatar || SITE_CONFIG.url + '/assets/images/defaults/avatar.svg';
                        avatar.alt = data.other_user.name || 'Korisnik';
                    }
                    
                    const userName = document.getElementById('chatUserName');
                    if (userName) {
                        userName.textContent = data.other_user.name || 'Korisnik';
                    }
                    
                    const status = document.getElementById('chatStatus');
                    if (status) {
                        status.textContent = 'Nedavno aktivan';
                    }
                    
                    const mobileTitle = document.getElementById('mobile-chat-title');
                    if (mobileTitle) {
                        mobileTitle.textContent = data.other_user.name || 'Poruke';
                    }
                }
                
                this.renderMessages(data.messages || []);
                
                const messages = data.messages || [];
                if (messages.length > 0) {
                    this.lastMessageId = messages[messages.length - 1].id;
                }
                
                this.scrollToBottom();
                
                // FOKUSIRAJ PONOVO NAKON UCITAVANJA PORUKA
                setTimeout(() => {
                    this.focusMessageInput();
                }, 300);
                
            } else {
                area.innerHTML = this.getErrorMessage(data.message || 'Greška pri učitavanju');
            }
        })
        .catch(error => {
            console.error('Error loading messages:', error);
            area.innerHTML = this.getErrorMessage('Greška pri učitavanju poruka');
        });
    }

    renderMessages(messages) {
        const area = document.getElementById('messagesArea');
        if (!area) return;
        
        const currentUserId = SITE_CONFIG.userId;

        if (!messages || messages.length === 0) {
            area.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-comment-smile fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Nema poruka</p>
                    <p class="small text-muted">Pošaljite prvu poruku!</p>
                </div>
            `;
            return;
        }

        let html = '';
        let lastDate = null;

        messages.forEach(message => {
            const messageDate = new Date(message.created_at).toLocaleDateString('sr-RS', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            if (lastDate !== messageDate) {
                html += `
                    <div class="date-separator">
                        <span>${messageDate}</span>
                    </div>
                `;
                lastDate = messageDate;
            }

            const isOwn = message.sender_id == currentUserId;
            const time = new Date(message.created_at).toLocaleTimeString('sr-RS', {
                hour: '2-digit',
                minute: '2-digit'
            });

            const senderName = message.sender_username || 'Korisnik';
            const messageText = this.escapeHtml(message.message || '').replace(/\n/g, '<br>');

            html += `
                <div class="message-item ${isOwn ? 'message-own' : 'message-other'}">
                    <div class="message-bubble">
                        ${!isOwn ? `
                        <div class="message-sender">
                            ${senderName}
                        </div>
                        ` : ''}
                        <div class="message-content">
                            ${messageText}
                        </div>
                        <div class="message-time">
                            ${time}
                            ${isOwn ? (message.is_read ? ' <i class="fas fa-check-double"></i>' : ' <i class="fas fa-check"></i>') : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        area.innerHTML = html;
    }

    sendMessage() {
        const input = document.getElementById('messageInput');
        if (!input) return;
        
        const message = input.value.trim();
        
        if (!message) return;
        if (!this.currentConversation || !this.currentReceiver) {
            this.showNotification('warning', 'Izaberite razgovor');
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', SITE_CONFIG.csrfToken);
        formData.append('receiver_id', this.currentReceiver);
        formData.append('conversation_id', this.currentConversation);
        formData.append('message', message);

        const sendBtn = document.getElementById('sendBtn');
        if (sendBtn) {
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            sendBtn.disabled = true;
        }

        fetch(`${SITE_CONFIG.url}/api/chat/send.php`, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Send response:', data);
            
            if (data.success) {
                input.value = '';
                
                const newMessage = {
                    id: data.message_id || Date.now(),
                    sender_id: SITE_CONFIG.userId,
                    message: message,
                    created_at: data.created_at || new Date().toISOString(),
                    is_read: false,
                    sender_username: 'Vi'
                };
                
                this.addMessageToChat(newMessage);
                this.lastMessageId = data.message_id || newMessage.id;
                this.scrollToBottom();
                this.loadConversations();
                
                // FOKUSIRAJ PONOVO NAKON SLANJA
                setTimeout(() => {
                    this.focusMessageInput();
                }, 100);
                
            } else {
                this.showNotification('danger', data.message || 'Greška pri slanju');
            }
        })
        .catch(error => {
            console.error('Error sending:', error);
            this.showNotification('danger', 'Greška pri slanju poruke');
        })
        .finally(() => {
            if (sendBtn) {
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                sendBtn.disabled = false;
            }
        });
    }

    addMessageToChat(message) {
        const area = document.getElementById('messagesArea');
        if (!area) return;
        
        const isOwn = message.sender_id == SITE_CONFIG.userId;
        const time = new Date(message.created_at).toLocaleTimeString('sr-RS', {
            hour: '2-digit',
            minute: '2-digit'
        });

        const today = new Date(message.created_at).toLocaleDateString('sr-RS');
        const separator = area.querySelector('.date-separator:last-child');
        if (!separator || separator.textContent !== today) {
            area.insertAdjacentHTML('beforeend', `
                <div class="date-separator">
                    <span>${today}</span>
                </div>
            `);
        }

        const messageHtml = `
            <div class="message-item ${isOwn ? 'message-own' : 'message-other'}">
                <div class="message-bubble">
                    ${!isOwn ? `
                    <div class="message-sender">
                        ${message.sender_username || 'Korisnik'}
                    </div>
                    ` : ''}
                    <div class="message-content">
                        ${this.escapeHtml(message.message).replace(/\n/g, '<br>')}
                    </div>
                    <div class="message-time">
                        ${time}
                        ${isOwn ? ' <i class="fas fa-check"></i>' : ''}
                    </div>
                </div>
            </div>
        `;

        area.insertAdjacentHTML('beforeend', messageHtml);
    }

    markAsRead(conversationId) {
        console.log('Marking as read:', conversationId);
        
        fetch(`${SITE_CONFIG.url}/api/chat/mark-read.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: SITE_CONFIG.csrfToken,
                conversation_id: conversationId
            }),
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Mark as read response:', data);
            if (data.success) {
                this.loadUnreadCount();
                this.loadConversations();
            }
        })
        .catch(error => console.error('Error marking as read:', error));
    }

    loadConversations() {
        fetch(`${SITE_CONFIG.url}/api/chat/list.php`, {
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Conversations loaded:', data);
            if (data.success) {
                this.renderConversations(data.conversations || []);
                if (data.total_unread !== undefined) {
                    this.updateUnreadBadge(data.total_unread);
                }
            }
        })
        .catch(error => console.error('Error loading conversations:', error));
    }

    renderConversations(conversations) {
        const list = document.getElementById('conversationList');
        if (!list) return;

        if (!conversations || conversations.length === 0) {
            list.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                    <h5>Još nemate poruka</h5>
                    <p class="text-muted small">Kada kontaktirate prodavce,<br>vaši razgovori će se pojaviti ovde</p>
                </div>
            `;
            return;
        }

        let html = '';
        conversations.forEach(conv => {
            const otherUser = conv.other_user || {};
            const isUnread = conv.unread_count > 0;
            const lastMessage = conv.last_message || {};
            const timeAgo = lastMessage.time_ago || '';
            const isOwn = lastMessage.is_own || false;
            
            const userName = otherUser.name || 'Nepoznati korisnik';
            const userAvatar = otherUser.avatar || SITE_CONFIG.url + '/assets/images/defaults/avatar.png';
            const messageText = lastMessage.message || '';

            html += `
                <div class="conversation-item ${isUnread ? 'unread' : ''}"
                     data-conversation-id="${conv.id}"
                     data-other-user-id="${otherUser.id || 0}">
                    <div class="d-flex align-items-center">
                        <div class="avatar-wrapper">
                            <img src="${userAvatar}" 
                                 alt="${userName}"
                                 class="avatar"
                                 onerror="this.src='${SITE_CONFIG.url}/assets/images/defaults/avatar.png'">
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">
                                    ${this.escapeHtml(userName)}
                                    ${otherUser.is_verified ? '<i class="fas fa-check-circle text-primary verified-icon"></i>' : ''}
                                </h6>
                                <small class="time-text">${timeAgo}</small>
                            </div>
                            ${conv.ad ? `
                            <small class="ad-title">
                                <i class="fas fa-tag me-1"></i>
                                ${this.escapeHtml(conv.ad.title)}
                            </small>
                            ` : ''}
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="last-message mb-0">
                                    ${isOwn ? '<span class="text-muted">Vi: </span>' : ''}
                                    ${this.escapeHtml(messageText)}
                                </p>
                                ${isUnread ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        list.innerHTML = html;
        
        if (this.currentConversation) {
            document.querySelectorAll('.conversation-item').forEach(el => {
                if (el.dataset.conversationId == this.currentConversation) {
                    el.classList.add('active');
                }
            });
        }
    }

    filterConversations(search) {
        const items = document.querySelectorAll('.conversation-item');
        const term = search.toLowerCase().trim();
        
        items.forEach(item => {
            const name = item.querySelector('h6')?.textContent?.toLowerCase() || '';
            const ad = item.querySelector('.ad-title')?.textContent?.toLowerCase() || '';
            const match = name.includes(term) || ad.includes(term);
            item.style.display = match ? '' : 'none';
        });
    }

    loadUnreadCount() {
        fetch(`${SITE_CONFIG.url}/api/chat/unread-count.php`, {
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Unread count:', data);
            if (data.success) {
                this.updateUnreadBadge(data.count || 0);
            }
        })
        .catch(error => console.error('Error loading unread:', error));
    }

    updateUnreadBadge(count) {
        const badge = document.querySelector('.panel-header .badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        }
    }

    startPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
        
        this.pollingInterval = setInterval(() => {
            if (this.currentConversation) {
                this.checkNewMessages();
            }
            this.loadUnreadCount();
            this.loadConversations();
        }, 10000);
    }

    checkNewMessages() {
        if (!this.currentConversation) return;

        fetch(`${SITE_CONFIG.url}/api/chat/check-new.php?conversation_id=${this.currentConversation}&last_message_id=${this.lastMessageId}`, {
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            console.log('New messages check:', data);
            if (data.success && data.new_messages && data.new_messages.length > 0) {
                data.new_messages.forEach(msg => {
                    this.addMessageToChat(msg);
                    if (msg.id > this.lastMessageId) {
                        this.lastMessageId = msg.id;
                    }
                });
                this.markAsRead(this.currentConversation);
                this.scrollToBottom();
            }
        })
        .catch(error => console.error('Error checking new messages:', error));
    }

    deleteConversation() {
        if (!this.currentConversation) return;

        fetch(`${SITE_CONFIG.url}/api/chat/delete.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: SITE_CONFIG.csrfToken,
                conversation_id: this.currentConversation
            }),
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Delete response:', data);
            if (data.success) {
                this.currentConversation = null;
                this.currentReceiver = null;
                this.isOpen = false;
                
                const chatContainer = document.getElementById('chatContainer');
                const emptyChat = document.getElementById('emptyChat');
                if (chatContainer) chatContainer.classList.add('d-none');
                if (emptyChat) emptyChat.classList.remove('d-none');
                
                this.showMobileHeader();
                document.body.classList.remove('chat-open');
                
                this.loadConversations();
                this.showNotification('success', 'Razgovor je obrisan');
            } else {
                this.showNotification('danger', data.message || 'Greška pri brisanju');
            }
        })
        .catch(error => {
            console.error('Error deleting:', error);
            this.showNotification('danger', 'Greška pri brisanju');
        });
    }

    showConversationsList() {
        const panel = document.querySelector('.conversations-panel');
        const chatPanel = document.querySelector('.chat-panel');
        const mobileTitle = document.getElementById('mobile-chat-title');
        
        if (panel) panel.classList.remove('hidden');
        if (chatPanel) chatPanel.classList.remove('active');
        if (mobileTitle) mobileTitle.textContent = 'Poruke';
        
        this.showMobileHeader();
        document.body.classList.remove('chat-open');
        this.isOpen = false;
    }

    scrollToBottom() {
        const area = document.getElementById('messagesArea');
        if (area) {
            requestAnimationFrame(() => {
                area.scrollTo({
                    top: area.scrollHeight,
                    behavior: 'smooth'
                });
            });
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getLoadingSpinner() {
        return `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Učitavanje...</span>
                </div>
                <p class="mt-2 text-muted">Učitavam poruke...</p>
            </div>
        `;
    }

    getErrorMessage(message) {
        return `
            <div class="text-center py-5">
                <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                <p class="text-muted">${message}</p>
                <button class="btn btn-primary btn-sm mt-3" onclick="location.reload()">
                    <i class="fas fa-redo me-2"></i>Pokušaj ponovo
                </button>
            </div>
        `;
    }

    showNotification(type, message) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(type, message);
            return;
        }

        const colors = {
            success: '#28a745',
            danger: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };

        document.querySelectorAll('.custom-notification').forEach(el => el.remove());

        const div = document.createElement('div');
        div.className = 'custom-notification';
        div.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: ${colors[type] || '#007bff'};
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            z-index: 9999;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: fadeIn 0.3s ease;
            max-width: 90%;
            text-align: center;
            font-weight: 500;
        `;
        div.textContent = message;
        document.body.appendChild(div);
        
        setTimeout(() => {
            div.style.opacity = '0';
            div.style.transition = 'opacity 0.3s';
            setTimeout(() => div.remove(), 300);
        }, 3000);
    }
}

// Inicijalizacija
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing MessagingApp...');
    try {
        window.messagingApp = new MessagingApp();
        console.log('MessagingApp initialized successfully');
    } catch (error) {
        console.error('Error initializing MessagingApp:', error);
    }
});

// Cleanup na logout
window.addEventListener('beforeunload', () => {
    if (window.messagingApp && window.messagingApp.pollingInterval) {
        clearInterval(window.messagingApp.pollingInterval);
    }
});

// Dodaj CSS za notifikacije
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }
    .custom-notification {
        animation: fadeIn 0.3s ease;
    }
`;
document.head.appendChild(style);