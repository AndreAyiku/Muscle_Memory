<?php
session_start();
require_once '../../db/config.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);

// Fetch user details
$user_query = "SELECT user_id, first_name, last_name, profile_picture 
               FROM MM_Users 
               WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);

// Fetch pending requests
$pending_query = "SELECT cc.*, 
                 u.first_name, u.last_name, u.profile_picture,
                 cc.professional_type
                 FROM MM_ClientConnections cc
                 JOIN MM_Users u ON cc.professional_id = u.user_id
                 WHERE cc.client_id = ? 
                 AND cc.status = 'pending'";
$pending_stmt = mysqli_prepare($conn, $pending_query);
mysqli_stmt_bind_param($pending_stmt, "i", $user_id);
mysqli_stmt_execute($pending_stmt);
$pending_result = mysqli_stmt_get_result($pending_stmt);
$pending_count = mysqli_num_rows($pending_result);

// Fetch active connections
$active_query = "SELECT COUNT(*) as active_count 
                FROM MM_ClientConnections 
                WHERE client_id = ? 
                AND status = 'active'";
$active_stmt = mysqli_prepare($conn, $active_query);
mysqli_stmt_bind_param($active_stmt, "i", $user_id);
mysqli_stmt_execute($active_stmt);
$active_count = mysqli_fetch_assoc(mysqli_stmt_get_result($active_stmt))['active_count'];

// Profile picture handling
$profile_pic = !empty($user_data['profile_picture']) 
    ? 'data:image;base64,' . base64_encode($user_data['profile_picture'])
    : '../assests/images/default_profile.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muscle Memory - User Dashboard</title>
    <link rel="icon" type="image/x-icon" href="../../assests/images/dumbell.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Slab:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Josefin Slab', serif;
            color: #fff;
            height: 100%;
            overflow-x: hidden;
            position: relative;
            background-color: #4B0082;  /* Deep Purple Background */
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
        
        .bubble-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .bubble {
            position: relative;
            background-color: rgba(0, 0, 0, 0.5);  /* Black Bubbles */
            border-radius: 50%;
            animation: float-up linear infinite;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            z-index: 1;
        }

        @keyframes float-up {
            0% {
                opacity: 0.4;
                transform: translateY(100%) scale(0);
            }
            50% {
                opacity: 0.6;
            }
            100% {
                opacity: 0;
                transform: translateY(-100vh) scale(1.5);
            }
        }

        /* Rest of the styling similar to admin dashboard */
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

        

        .container {
            position: relative;
            z-index: 10;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .profile-overview {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .profile-picture {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ffffff;
            margin: 0 auto 20px;
            display: block;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            text-align: center;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 8px;
        }

        .stat-number {
            font-size: 1.5rem;
            color: #ffffff;
            font-weight: bold;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #e0e0e0;
        }

        .dashboard-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .section-title {
            color: #ffffff;
            margin-bottom: 20px;
            font-size: 1.8rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 10px;
        }

        .workout-list, .connections-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .workout-card, .connection-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }

        .workout-card:hover, .connection-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        .card-icon {
            font-size: 2rem;
            color: #ffffff;
        }

        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .nav-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
            text-decoration: none;
            color: #fff;
            backdrop-filter: blur(10px);
        }

        .nav-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }

        .nav-card i {
            font-size: 2rem;
            color: #ffffff;
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
    </style>
</head>
<body>
    <!-- Bubble Container -->
    <div id="bubble-container" class="bubble-container"></div>

    <header>
        <div class="logo">
            <a href="../../index.php">Muscle Memory</a>
        </div>
        <nav>
        <ul>
                <li><a href="../home.php">Home</a></li>
                <li><a href="../workouts.php">Workouts</a></li>
                <li><a href="../trainers.php">Trainers</a></li>
                <li><a href="../nutritionist.php">Nutritionists</a></li>
                
            </ul>
        </nav>
        <div class="user-menu">
            <div class="user-icon-container">
                <i class='bx bx-user-circle user-icon' onclick="toggleUserDropdown()"></i>
                <div id="user-dropdown" class="user-dropdown">
    <?php 
    // Check if user is logged in
    if(isset($_SESSION['user_id'])) {
        // Admin status takes precedence
        if($_SESSION['admin_status'] === '1') {
            // Admin dashboard
            echo '<a href="../admin/admin_dashboard.php">Admin Dashboard</a>';
        } else {
            // Check user type for appropriate dashboard
            switch($_SESSION['user_type']) {
                case 'trainer':
                    echo '<a href="../admin/trainer_dashboard.php">Trainer Dashboard</a>';
                    break;
                case 'nutritionist':
                    echo '<a href="../admin/nutritionist_dashboard.php">Nutritionist Dashboard</a>';
                    break;
                default: // 'regular' or any other type
                    echo '<a href="../admin/user_dashboard.php">User Dashboard</a>';
                    break;
            }
        }
        // Logout option always present for logged-in users
        echo '<a href="../../actions/logout.php">Logout</a>';
    } else {
        // Not logged in - show login and signup options
        echo '<a href="../Muscle_Memory/view/login.php">Login</a>';
        echo '<a href="../Muscle_Memory/view/sign-up.php">Sign Up</a>';
    }
    ?>
</div>
            </div>
        </div>
    </header>
    <div class="container">
        <div class="dashboard-grid">
            <!-- Profile Section -->
            <div class="professional-profile">
                <img src="<?php echo $profile_pic; ?>" alt="Profile Picture" class="profile-picture">
                <h2><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h2>
                
                <!-- Chat Button -->
                <a href="../chat.php" class="chat-button" style="
                    display: inline-block;
                    background: linear-gradient(45deg, #800080, #4B0082);
                    color: white;
                    text-align: center;
                    padding: 12px 20px;
                    text-decoration: none;
                    border-radius: 10px;
                    font-weight: bold;
                    transition: transform 0.3s ease, box-shadow 0.3s ease;
                    margin-top: 20px;
                " onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 15px rgba(128, 0, 128, 0.3)'" 
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    View Messages
                </a>
            </div>

            <!-- Dashboard Overview -->
            <div class="profile-card">
                <h2 class="section-title">Dashboard Overview</h2>
                <div class="nutrition-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $active_count; ?></div>
                        <div class="stat-label">Active Connections</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $pending_count; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Requests Section -->
        <div class="dashboard-section">
            <h2 class="section-title">Pending Requests</h2>
            <div class="client-list">
                <?php while ($request = mysqli_fetch_assoc($pending_result)): ?>
                    <div class="client-card">
                        <img src="<?php echo !empty($request['profile_picture']) ? 
                            'data:image;base64,' . base64_encode($request['profile_picture']) : 
                            '../assests/images/default_profile.png'; ?>" 
                            alt="Professional Profile" class="profile-picture">
                        <div>
                            <h3><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h3>
                            <p>Professional Type: <?php echo ucfirst(htmlspecialchars($request['professional_type'])); ?></p>
                            <p>Requested on: <?php echo date('F j, Y', strtotime($request['created_at'])); ?></p>
                            <p>Status: <span style="color: #FFA500;">Pending</span></p>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php if ($pending_count === 0): ?>
                    <p>No pending requests at this time.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleUserDropdown() {
        const dropdown = document.getElementById('user-dropdown');
        dropdown.classList.toggle('show');
    }

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
            const duration = Math.random() * 5 + 3; // Faster: 3-8 seconds
            bubble.style.animationDuration = `${duration}s`;

            // Random delay to stagger animations
            const delay = Math.random() * 10;
            bubble.style.animationDelay = `-${delay}s`;

            container.appendChild(bubble);
        }
    }

    // Create bubbles when page loads
    window.addEventListener('load', createBubbles);
    </script>
</body>
</html>