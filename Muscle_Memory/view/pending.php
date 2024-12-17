<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db/config.php';

// Check if user is logged in and is a professional
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['trainer', 'nutritionist'])) {
    header("Location: ../view/login.php");
    exit();
}

// Handle accept/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept'])) {
        $connection_id = intval($_POST['connection_id']);
        $update_query = "UPDATE MM_ClientConnections SET status = 'active' WHERE connection_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $connection_id);
        $stmt->execute();
    } elseif (isset($_POST['reject'])) {
        $connection_id = intval($_POST['connection_id']);
        $delete_query = "DELETE FROM MM_ClientConnections WHERE connection_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $connection_id);
        $stmt->execute();
    }
    
    // Redirect to refresh the page
    header("Location: pending.php");
    exit();
}

// Fetch pending requests
$query = "SELECT cc.*, u.first_name, u.last_name, u.profile_picture, u.email 
          FROM MM_ClientConnections cc
          JOIN MM_Users u ON cc.client_id = u.user_id
          WHERE cc.professional_id = ? 
          AND cc.professional_type = ?
          AND cc.status = 'pending'
          ORDER BY cc.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['user_type']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Requests - Muscle Memory</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .requests-container {
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            padding: 30px;
            margin-top: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(128, 0, 128, 0.2);
        }

        .request-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-picture {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #800080;
        }

        .request-details {
            flex-grow: 1;
        }

        .request-details h3 {
            margin: 0 0 10px 0;
            color: #800080;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .accept-btn, .reject-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Josefin Slab', serif;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .accept-btn {
            background: linear-gradient(45deg, #008000, #006400);
            color: white;
        }

        .reject-btn {
            background: linear-gradient(45deg, #800000, #640000);
            color: white;
        }

        .accept-btn:hover, .reject-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(128, 0, 128, 0.4);
        }

        .no-requests {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 1.2rem;
        }

        /* Copy header styles from profile.php */
    </style>
</head>
<body>
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
        <div class="requests-container">
            <h1 style="margin-bottom: 30px; color: #800080;">Pending Requests</h1>
            
            <?php if ($result->num_rows > 0): ?>
                <?php while ($request = $result->fetch_assoc()): ?>
                    <div class="request-card">
                        <img src="<?php 
                            echo !empty($request['profile_picture']) 
                                ? 'data:image;base64,' . base64_encode($request['profile_picture'])
                                : '../assests/images/default_profile.png'; 
                        ?>" alt="Client Profile" class="profile-picture">
                        
                        <div class="request-details">
                            <h3><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h3>
                            <p>Email: <?php echo htmlspecialchars($request['email']); ?></p>
                            <p>Requested: <?php echo date('F j, Y', strtotime($request['created_at'])); ?></p>
                        </div>

                        <div class="action-buttons">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="connection_id" value="<?php echo $request['connection_id']; ?>">
                                <button type="submit" name="accept" class="accept-btn">Accept</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="connection_id" value="<?php echo $request['connection_id']; ?>">
                                <button type="submit" name="reject" class="reject-btn">Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-requests">
                    <p>No pending requests at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    function toggleUserDropdown() {
    const dropdown = document.getElementById('user-dropdown');
    dropdown.classList.toggle('show');
}

document.addEventListener('DOMContentLoaded', function() {
    // Close dropdown when clicking outside
    window.addEventListener('click', function(e) {
        const dropdown = document.getElementById('user-dropdown');
        const userIcon = document.querySelector('.user-icon');
        
        if (dropdown.classList.contains('show') && 
            !dropdown.contains(e.target) && 
            e.target !== userIcon) {
            dropdown.classList.remove('show');
        }
    });


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

        
        setInterval(createBubbles, 30000); // Recreate every 30 seconds
    });
</script>
</body>
</html>