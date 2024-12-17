<?php
session_start();
require_once '../db/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's connections based on their role
if ($_SESSION['user_type'] === 'regular') {
    // For regular users/clients - get all their active professionals
    $query = "SELECT 
        u.user_id, 
        u.first_name, 
        u.last_name, 
        u.profile_picture,
        cc.professional_type,
        cc.status
    FROM MM_ClientConnections cc
    JOIN MM_Users u ON u.user_id = cc.professional_id
    WHERE cc.client_id = ? 
    AND cc.status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
} else {
    // For professionals - get all their active clients
    $query = "SELECT 
        u.user_id, 
        u.first_name, 
        u.last_name, 
        u.profile_picture,
        cc.status
    FROM MM_ClientConnections cc
    JOIN MM_Users u ON u.user_id = cc.client_id
    WHERE cc.professional_id = ? 
    AND cc.professional_type = ?
    AND cc.status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['user_type']);
}

$stmt->execute();
$connections = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Muscle Memory</title>
    <link rel="icon" type="image/x-icon" href="../assests/images/dumbell.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Slab:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
                body, html {
            margin: 0;
            padding: 0;
            font-family: 'Josefin Slab', serif;
            color: #fff;
            min-height: 100vh;
            background-color: #000000;
        }
                .logo a {
            color: #fff;
            text-decoration: none;
            font-size: 2rem;
            font-weight: bold;
            font-family: 'Josefin Slab', serif;
            background: linear-gradient(45deg, #800080, #4B0082);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 30px rgba(128, 0, 128, 0.5);
        }
        
        nav ul {
            list-style-type: none;
            display: flex;
            gap: 2rem;
            padding: 0;
        }
        
        nav ul li a {
            color: #fff;
            text-decoration: none;
            font-size: 1.1rem;
            font-family: 'Josefin Slab', serif;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        
        nav ul li a:hover {
            background: rgba(128, 0, 128, 0.2);
            transform: translateY(-2px);
            color: #800080;
        }

        .user-menu {
            position: relative;
        }

        .user-icon-container {
            position: relative;
        }

        .user-icon {
            font-size: 2rem;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-icon:hover {
            transform: scale(1.1);
            color: #800080;
        }

        .user-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(30, 0, 50, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            min-width: 200px;
            box-shadow: 0 8px 32px rgba(128, 0, 128, 0.2);
            z-index: 1000;
            padding: 0.5rem 0;
            margin-top: 0.5rem;
            border: 1px solid rgba(128, 0, 128, 0.1);
        }

        .user-dropdown a {
            display: block;
            color: #fff;
            text-decoration: none;
            padding: 0.7rem 1.2rem;
            font-family: 'Josefin Slab', serif;
            transition: all 0.3s ease;
        }

        .user-dropdown a:hover {
            background: rgba(128, 0, 128, 0.2);
            color: #800080;
        }

        .user-dropdown.show {
            display: block;
        }
        header {
            background-color: rgba(30, 0, 50, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(128, 0, 128, 0.2);
            padding: 0.3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }

        .chat-container {
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            padding: 20px;
            height: 70vh;
            display: flex;
            flex-direction: column;
        }

        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 10px;
            max-width: 70%;
        }

        .sent {
            background: linear-gradient(45deg, #800080, #4B0082);
            margin-left: auto;
        }

        .received {
            background: rgba(255, 255, 255, 0.1);
            margin-right: auto;
        }

        .message-input {
            display: flex;
            gap: 10px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .message-input textarea {
            flex-grow: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-family: 'Josefin Slab', serif;
            resize: none;
        }

        .send-button {
            background: linear-gradient(45deg, #800080, #4B0082);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Josefin Slab', serif;
            font-weight: bold;
        }

        .send-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(128, 0, 128, 0.4);
        }

        .chat-layout {
            display: flex;
            gap: 20px;
            height: 80vh;
        }

        .contacts-sidebar {
            width: 300px;
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            padding: 20px;
            overflow-y: auto;
        }

        .contact-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .contact-card:hover {
            background: rgba(128, 0, 128, 0.2);
        }

        .contact-card.active {
            background: linear-gradient(45deg, #800080, #4B0082);
        }

        .contact-picture {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .chat-window {
            flex-grow: 1;
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid rgba(128, 0, 128, 0.2);
        }

        .messages-container {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .message-input {
            padding: 20px;
            border-top: 1px solid rgba(128, 0, 128, 0.2);
        }
    </style>
</head>
<body>
<div id="bubble-container" class="bubble-container"></div>

<header>
        <div class="logo">
            <a href="../index.php">Muscle Memory</a>
        </div>
        <nav>
            <ul>
                <li><a href="../view/home.php">Home</a></li>
                <li><a href="../view/workouts.php">Workouts</a></li>
                <li><a href="../view/trainers.php">Trainers</a></li>
                <li><a href="../view/nutritionist.php">Nutritionists</a></li>

            </ul>
        </nav>
        <div class="user-menu">
            <div class="user-icon-container">
                <i class='bx bx-user-circle user-icon' onclick="toggleUserDropdown()"></i>
                <div id="user-dropdown" class="user-dropdown">
                    <?php 
                    // Reusing the user dropdown logic from previous dashboard
                    if(isset($_SESSION['user_id'])) {
                        if($_SESSION['admin_status'] === '1') {
                            echo '<a href="../admin/admin_dashboard.php">Admin Dashboard</a>';
                        } else {
                            switch($_SESSION['user_type']) {
                                case 'trainer':
                                    echo '<a href="../view/admin/trainer_dashboard.php">Trainer Dashboard</a>';
                                    break;
                                case 'nutritionist':
                                    echo '<a href="../view/admin/nutritionist_dashboard.php">Nutritionist Dashboard</a>';
                                    break;
                                default:
                                    echo '<a href="../view/admin/user_dashboard.php">User Dashboard</a>';
                                    break;
                            }
                        }
                        echo '<a href="../actions/logout.php">Logout</a>';
                    } else {
                        echo '<a href="../Muscle_Memory/view/login.php">Login</a>';
                        echo '<a href="../Muscle_Memory/view/sign-up.php">Sign Up</a>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="chat-layout">
            <div class="contacts-sidebar">
                <?php while ($contact = $connections->fetch_assoc()): ?>
                    <div class="contact-card" onclick="loadChat(<?php echo $contact['user_id']; ?>)">
                        <img src="<?php echo !empty($contact['profile_picture']) ? 
                            'data:image;base64,' . base64_encode($contact['profile_picture']) : 
                            '../assests/images/default_profile.png'; ?>" 
                            alt="Profile" class="contact-picture">
                        <div>
                            <h3><?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?></h3>
                            <?php if (isset($contact['professional_type'])): ?>
                                <small><?php echo ucfirst($contact['professional_type']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="chat-window" id="chat-window">
                <div class="chat-header" id="chat-header">
                    <h2>Select a contact to start chatting</h2>
                </div>
                <div class="messages-container" id="messages-container"></div>
                <form class="message-input" id="message-form" style="display: none;">
                    <textarea 
                        placeholder="Type your message..." 
                        rows="2"
                        required></textarea>
                    <button type="submit" class="send-button">Send</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let activeContactId = null;

        async function loadChat(contactId) {
            activeContactId = contactId;
            const messagesContainer = document.getElementById('messages-container');
            const messageForm = document.getElementById('message-form');
            
            try {
                const response = await fetch(`../view/get_messages.php?contact_id=${contactId}`);
                const messages = await response.json();
                
                // Update messages display
                messagesContainer.innerHTML = messages.map(msg => `
                    <div class="message ${msg.sender_id == <?php echo $_SESSION['user_id']; ?> ? 'sent' : 'received'}">
                        <div class="message-content">${msg.message_text}</div>
                        <small>${new Date(msg.created_at).toLocaleTimeString()}</small>
                    </div>
                `).join('');
                
                messageForm.style.display = 'flex';
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            } catch (error) {
                console.error('Error:', error);
            }
        }



        // Bubble Animation
        function createBubbles() {
            const container = document.getElementById('bubble-container');
            container.innerHTML = ''; // Clear existing bubbles
            const bubbleCount = 100; // Number of bubbles

            for (let i = 0; i < bubbleCount; i++) {
                const bubble = document.createElement('div');
                bubble.classList.add('bubble');

                // Random positioning
                bubble.style.left = `${Math.random() * 100}%`;

                // Random sizes between 3px and 25px
                const size = Math.random() * 22 + 3;
                bubble.style.width = `${size}px`;
                bubble.style.height = `${size}px`;

                // Random animation duration
                const duration = Math.random() * 5 + 3;
                bubble.style.animationDuration = `${duration}s`;

                // Random delay to stagger animations
                const delay = Math.random() * 10;
                bubble.style.animationDelay = `-${delay}s`;

                container.appendChild(bubble);
            }
        }

        // Handle message sending
        document.getElementById('message-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!activeContactId) return;

            const textarea = e.target.querySelector('textarea');
            const message = textarea.value.trim();
            if (!message) return;

            try {
                const response = await fetch('../view/send_messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        receiver_id: activeContactId,
                        message: message
                    })
                });

                if (response.ok) {
                    textarea.value = '';
                    loadChat(activeContactId);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
        function toggleUserDropdown() {
        const dropdown = document.getElementById('user-dropdown');
        dropdown.classList.toggle('show');
    }

    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('user-dropdown');
        const userIcon = document.querySelector('.user-icon');
        
        if (!dropdown.contains(e.target) && e.target !== userIcon) {
            dropdown.classList.remove('show');
        }
    });
    </script>
</body>
</html>