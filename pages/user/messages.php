<?php
/**
 * pages/user/messages.php - Sistem za poruke (Modern UI)
 */
require_once __DIR__ . '/../../includes/messages.php';

// FIX: error_reporting/display_errors uklonjeni - greske idu SAMO u error_log,
// stranicu posecuju i neprijavljeni posetioci (ne curi putanja/sadrzaj).

// Proveri da li je korisnik ulogovan
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    redirect('/login');
}

$userId = $_SESSION['user_id'];


// Dohvati sve razgovore
$conversations = getUserConversations($userId);
$unreadCount = getUnreadMessageCount($userId);

// Postavi title
$pageTitle = 'Poruke - Rasprodaja.rs';
$pageDescription = 'Vaš inbox i razgovori sa drugim korisnicima.';
$pageSpecificCSS = ['messages.css'];
$pageSpecificJS = ['messages.js'];

// Generiši CSRF token
$csrfToken = generateCSRFToken();

$inlineScripts = "
    window.SITE_CONFIG = {
        url: '" . SITE_URL . "',
        userId: '$userId',
        csrfToken: '$csrfToken'
    };
";
?>

<div class="messages-app">
    <!-- MOBILE HEADER -->
    <div class="mobile-header d-lg-none" id="mobileHeader">
        <button class="btn btn-link" id="back-to-list">
            <i class="fas fa-arrow-left"></i>
        </button>
        <span id="mobile-chat-title">Poruke</span>
        <button class="btn btn-link" id="mobile-options">
            <i class="fas fa-ellipsis-v"></i>
        </button>
    </div>

    <div class="messages-container">
        <!-- LEFT PANEL - LISTA KONVERZACIJA -->
        <div class="conversations-panel" id="conversationsPanel">
            <!-- Header -->
            <div class="panel-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-comment-dots me-2 text-primary"></i>
                        Razgovori
                        <?php if ($unreadCount > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo (int)$unreadCount; ?></span>
                        <?php endif; ?>
                    </h5>
                    <button class="btn btn-sm btn-outline-primary" id="new-message-btn">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>
                
                <!-- Search -->
                <div class="search-box mt-3">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-0" 
                               id="search-conversations"
                               placeholder="Pretraži razgovore...">
                    </div>
                </div>
            </div>

            <!-- Lista -->
            <div class="conversation-list" id="conversationList">
                <?php if (empty($conversations)): ?>
                <div class="empty-state">
                    <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                    <h5>Još nemate poruka</h5>
                    <p class="text-muted small">Kada kontaktirate prodavce,<br>vaši razgovori će se pojaviti ovde</p>
                </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conversation): 
                        // Dohvati drugog korisnika
                        $otherUser = getOtherUserInConversation($conversation, $userId);
                        
                        // SIGURNO DOHVATANJE IMENA
                        $userName = 'Nepoznati korisnik';
                        if (is_array($otherUser)) {
                            if (isset($otherUser['name']) && is_string($otherUser['name']) && !empty($otherUser['name'])) {
                                $userName = $otherUser['name'];
                            } elseif (isset($otherUser['username']) && is_string($otherUser['username']) && !empty($otherUser['username'])) {
                                $userName = $otherUser['username'];
                            }
                        }

                        // SIGURNO DOHVATANJE AVATARA
                        $userAvatar = SITE_URL . '/assets/images/defaults/avatar.svg';
                        if (is_array($otherUser) && isset($otherUser['avatar']) && is_string($otherUser['avatar']) && !empty($otherUser['avatar'])) {
                            $userAvatar = $otherUser['avatar'];
                        }

                        // SIGURNO DOHVATANJE VERIFIKACIJE
                        $isVerified = false;
                        if (is_array($otherUser) && isset($otherUser['is_verified']) && !empty($otherUser['is_verified'])) {
                            $isVerified = true;
                        }

                        // SIGURNO DOHVATANJE ID-ja
                        $otherUserId = 0;
                        if (is_array($otherUser) && isset($otherUser['id'])) {
                            $otherUserId = (int)$otherUser['id'];
                        }

                        // SIGURNO DOHVATANJE POSLEDNJE PORUKE
                        $lastMessage = '';
                        if (isset($conversation['last_message'])) {
                            if (is_string($conversation['last_message'])) {
                                $lastMessage = $conversation['last_message'];
                            } elseif (is_array($conversation['last_message']) && isset($conversation['last_message']['message'])) {
                                $lastMessage = $conversation['last_message']['message'];
                            }
                        }

                        // SIGURNO DOHVATANJE VREMENA
                        $lastMessageCreated = date('Y-m-d H:i:s');
                        if (isset($conversation['last_message']) && is_array($conversation['last_message']) && isset($conversation['last_message']['created_at'])) {
                            $lastMessageCreated = $conversation['last_message']['created_at'];
                        } elseif (isset($conversation['last_activity'])) {
                            $lastMessageCreated = $conversation['last_activity'];
                        }

                        // SIGURNO DOHVATANJE ID-ja POSLEDNJEG POŠILJAOCA
                        $lastSenderId = 0;
                        if (isset($conversation['last_message']) && is_array($conversation['last_message']) && isset($conversation['last_message']['sender_id'])) {
                            $lastSenderId = (int)$conversation['last_message']['sender_id'];
                        }

                        // SIGURNO DOHVATANJE BROJA NEPROČITANIH
                        $unreadCountConv = isset($conversation['unread_count']) ? (int)$conversation['unread_count'] : 0;
                        $isUnread = $unreadCountConv > 0;

                        // SIGURNO DOHVATANJE NASLOVA OGLASA
                        $adTitle = '';
                        if (isset($conversation['ad_title']) && is_string($conversation['ad_title'])) {
                            $adTitle = $conversation['ad_title'];
                        }

                        // SIGURNO DOHVATANJE CENE OGLASA
                        $adPrice = '';
                        if (isset($conversation['ad_price']) && is_numeric($conversation['ad_price'])) {
                            $adPrice = number_format($conversation['ad_price'], 0, ',', '.') . ' RSD';
                        }
                    ?>
                    <div class="conversation-item <?php echo $isUnread ? 'unread' : ''; ?>"
                         data-conversation-id="<?php echo (int)$conversation['id']; ?>"
                         data-other-user-id="<?php echo (int)$otherUserId; ?>">
                        <div class="d-flex align-items-center">
                            <!-- Avatar -->
                            <div class="avatar-wrapper">
                                <img src="<?php echo htmlspecialchars($userAvatar); ?>" 
                                     alt="<?php echo htmlspecialchars($userName); ?>"
                                     class="avatar"
                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/images/defaults/avatar.svg'">
                            </div>
                            
                            <!-- Info -->
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">
                                        <?php echo htmlspecialchars($userName); ?>
                                        <?php if ($isVerified): ?>
                                        <i class="fas fa-check-circle text-primary verified-icon"></i>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="time-text">
                                        <?php 
                                        try {
                                            echo timeAgo($lastMessageCreated);
                                        } catch (Exception $e) {
                                            echo 'Pre neki dan';
                                        }
                                        ?>
                                    </small>
                                </div>
                                
                                <?php if (!empty($adTitle)): ?>
                                <small class="ad-title">
                                    <i class="fas fa-tag me-1"></i>
                                    <?php echo htmlspecialchars($adTitle); ?>
                                </small>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="last-message mb-0">
                                        <?php if ($lastSenderId == $userId): ?>
                                        <span class="text-muted">Vi: </span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($lastMessage); ?>
                                    </p>
                                    <?php if ($isUnread): ?>
                                    <span class="unread-badge"><?php echo $unreadCountConv; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT PANEL - CHAT -->
        <div class="chat-panel" id="chatPanel">
            <!-- Empty State -->
            <div class="empty-chat" id="emptyChat">
                <i class="fas fa-comment-dots fa-5x text-muted mb-4"></i>
                <h4>Izaberite razgovor</h4>
                <p class="text-muted">Kliknite na razgovor sa liste<br>da biste videli poruke</p>
            </div>

            <!-- Chat Container (Hidden) -->
            <div class="chat-container d-none" id="chatContainer">
                <!-- Chat Header -->
                <div class="chat-header">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-link d-lg-none me-2" id="backToList">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <img id="chatAvatar" class="avatar-sm me-3" 
                             src="<?php echo SITE_URL; ?>/assets/images/defaults/avatar.svg">
                        <div>
                            <h6 class="mb-0 fw-bold" id="chatUserName">Korisnik</h6>
                            <small class="text-muted" id="chatStatus">Nedavno aktivan</small>
                        </div>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary me-1" id="viewProfileBtn">
                            <i class="fas fa-user"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" id="deleteChatBtn">
                            <i class="fas fa-trash"></i>
                        </button>
                        <!-- OCENI KORISNIKA (vidljivo kad su ispunjeni uslovi) -->
                        <button class="btn btn-sm btn-outline-warning d-none" id="rateUserBtn"
                                title="Oceni nakon razmene poruka">
                            <i class="fas fa-star"></i>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div class="messages-area" id="messagesArea">
                    <!-- Messages will be loaded here -->
                </div>

                <!-- Message Input -->
                <div class="message-input">
                    <form id="sendMessageForm" class="d-flex w-100">
                        <input type="hidden" id="chatConversationId">
                        <input type="hidden" id="chatReceiverId">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <input type="text" class="form-control" 
                               id="messageInput" 
                               placeholder="Napišite poruku..."
                               autocomplete="off">
                        
                        <button type="submit" class="btn btn-primary" id="sendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL: OCENI KORISNIKA ============ -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-star text-warning me-2"></i> Oceni sagovornika
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Ocenite <strong id="reviewTargetName">korisnika</strong> na osnovu međusobne komunikacije.
                    Vaša ocena biće javno prikazana na njegovom profilu.
                </p>

                <div class="text-center mb-3">
                    <div class="star-rating-input" id="reviewStarInput" role="radiogroup" aria-label="Ocena zvezdicama">
                        <i class="far fa-star" data-value="1" role="radio" aria-checked="false" tabindex="0"></i>
                        <i class="far fa-star" data-value="2" role="radio" aria-checked="false" tabindex="0"></i>
                        <i class="far fa-star" data-value="3" role="radio" aria-checked="false" tabindex="0"></i>
                        <i class="far fa-star" data-value="4" role="radio" aria-checked="false" tabindex="0"></i>
                        <i class="far fa-star" data-value="5" role="radio" aria-checked="false" tabindex="0"></i>
                    </div>
                    <div class="small text-muted mt-1" id="reviewRatingText">Izaberite ocenu</div>
                </div>

                <form id="reviewForm">
                    <input type="hidden" name="conversation_id" id="reviewConversationId">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="mb-3">
                        <label for="reviewTitle" class="form-label">Naslov (opciono)</label>
                        <input type="text" class="form-control" id="reviewTitle" name="title"
                               maxlength="100" placeholder="Npr. Sve super, brz dogovor!">
                    </div>
                    <div class="mb-2">
                        <label for="reviewComment" class="form-label">Obrazloženje *</label>
                        <textarea class="form-control" id="reviewComment" name="comment" rows="3"
                                  maxlength="1000" required
                                  placeholder="Kako je protekla saradnja?"></textarea>
                        <div class="form-text"><span id="reviewCharCount">0</span>/1000</div>
                    </div>
                    <div id="reviewError" class="text-danger small mt-2 d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Otkaži</button>
                <button type="submit" form="reviewForm" class="btn btn-warning text-white" id="reviewSubmitBtn">
                    <i class="fas fa-paper-plane me-2"></i> Pošalji ocenu
                </button>
            </div>
        </div>
    </div>
</div>