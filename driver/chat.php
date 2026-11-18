<?php
$page_title = 'Chat with Passenger';
$page_header_title = 'Chat';
$active_tab = 'chat';
$show_back_btn = true;
$back_url = 'status.php';

require_once '../config.php';
require_login(['Driver']);

$user_id = $_SESSION['user_id'];

// Find active ride
$ride_sql = "SELECT r.*, u.fullname AS passenger_name FROM rides r 
             JOIN users u ON r.user_id = u.id 
             WHERE r.driver_id = ? AND r.status NOT IN ('Completed', 'Cancelled') 
             ORDER BY r.id DESC LIMIT 1";
$stmt = $conn->prepare($ride_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ride_res = $stmt->get_result();

$active_ride = null;
if ($ride_res->num_rows > 0) {
    $active_ride = $ride_res->fetch_assoc();
}

require_once 'header.php';
?>

<div class="app-content" style="padding: 0; display: flex; flex-direction: column; height: 100%;">
    
    <?php if (!$active_ride): ?>
        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; text-align: center; background: #f1f5f9;">
            <span style="font-size: 3rem; margin-bottom: 1rem;">💬</span>
            <h4 class="text-muted">No Active Chat</h4>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Chat is only available during an active ride booking.</p>
        </div>
    <?php else: ?>
        
        <!-- Passenger info Header Bar -->
        <div style="background: white; border-bottom: 1px solid var(--border-color); padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
            <div style="width: 35px; height: 35px; border-radius: 50%; background: var(--primary-light); color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 700;">
                👩🏻
            </div>
            <div>
                <strong style="font-size: 0.85rem; display: block;"><?php echo htmlspecialchars($active_ride['passenger_name']); ?></strong>
                <span style="font-size: 0.7rem; color: var(--accent-green); font-weight: 600;"><i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> Active Passenger</span>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="chat-messages-container" id="chat-messages">
            <!-- Dynamically populated by polling -->
        </div>

        <!-- Chat Form -->
        <form class="chat-input-area" id="chat-form" onsubmit="sendMessage(event)">
            <input type="hidden" id="chat_ride_id" value="<?php echo $active_ride['id']; ?>">
            <input type="text" id="chat-message-input" class="form-control" placeholder="Type a message..." required autocomplete="off">
            <button type="submit" class="btn-send">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>

    <?php endif; ?>

</div>

<script>
    let lastChatCount = 0;

    function onSyncUpdate(data) {
        const chatContainer = document.getElementById('chat-messages');
        if (!chatContainer) return;

        const ride = data.active_ride;
        if (!ride || !ride.chats) {
            chatContainer.innerHTML = '<div class="text-muted" style="text-align:center; padding: 2rem; font-size:0.8rem;">Start of chat log</div>';
            return;
        }

        const chats = ride.chats;
        if (chats.length !== lastChatCount) {
            lastChatCount = chats.length;
            
            chatContainer.innerHTML = '';
            chats.forEach(c => {
                const isSent = c.sender === 'Driver';
                const bubble = document.createElement('div');
                bubble.className = `chat-bubble ${isSent ? 'sent' : 'received'}`;
                bubble.innerHTML = `${escapeHtml(c.message)}<span class="chat-time">${c.timestamp}</span>`;
                chatContainer.appendChild(bubble);
            });

            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    }

    function sendMessage(e) {
        e.preventDefault();
        const input = document.getElementById('chat-message-input');
        const rideId = document.getElementById('chat_ride_id').value;
        const msg = input.value.trim();
        if (!msg) return;

        input.value = '';

        const chatContainer = document.getElementById('chat-messages');
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble sent';
        bubble.innerHTML = `${escapeHtml(msg)}<span class="chat-time">Sending...</span>`;
        chatContainer.appendChild(bubble);
        chatContainer.scrollTop = chatContainer.scrollHeight;

        fetch('../api/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ride_id: rideId, message: msg })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                syncState();
            } else {
                alert(data.message);
            }
        });
    }

    function escapeHtml(str) {
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>

<?php require_once 'footer.php'; ?>
