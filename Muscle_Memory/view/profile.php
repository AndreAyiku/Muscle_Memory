<?php
session_start(); 

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection 
require_once '../db/config.php';

// Check if a specific nutritionist ID is provided
$nutritionist_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null);

// If no nutritionist ID, redirect
if (!$nutritionist_id) {
    header("Location: ../view/nutritionist.php");
    exit();
}

// First fetch the profile
$stmt = $conn->prepare("
    SELECT n.*, u.first_name, u.last_name, u.email, u.profile_picture, u.user_id, u.user_type
    FROM MM_Nutritionist n
    JOIN MM_Users u ON n.user_id = u.user_id 
    WHERE n.user_id = ?
");
$stmt->bind_param("i", $nutritionist_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if profile exists
if ($result->num_rows === 0) {
    header("Location: ../view/nutritionist.php");
    exit();
}

// Fetch profile details
$profile = $result->fetch_assoc();
$stmt->close();

// Now handle connection request after profile is fetched
// Handle connection request
if (isset($_POST['request_connection']) && isset($_SESSION['user_id'])) {
    $client_id = $_SESSION['user_id'];
    $professional_id = $profile['user_id'];
    $professional_type = 'nutritionist';

    // Check only for active or pending connections
    $check_query = "SELECT * FROM MM_ClientConnections 
                   WHERE client_id = ? 
                   AND professional_id = ? 
                   AND professional_type = ?
                   AND status IN ('active', 'pending')";
    
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("iis", $client_id, $professional_id, $professional_type);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->num_rows > 0;

    if (!$exists) {
        // Insert new connection request
        $insert_query = "INSERT INTO MM_ClientConnections 
                        (client_id, professional_id, professional_type, status) 
                        VALUES (?, ?, ?, 'pending')";
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iis", $client_id, $professional_id, $professional_type);
        $insert_stmt->execute();
    }
}

// Check if current user can edit this profile
$can_edit = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $nutritionist_id && $_SESSION['user_type'] === 'nutritionist');

// Check if connection exists for current user
if (isset($_SESSION['user_id'])) {
    $check_connection = $conn->prepare("
        SELECT status 
        FROM MM_ClientConnections 
        WHERE client_id = ? 
        AND professional_id = ? 
        AND professional_type = 'nutritionist'
        AND status IN ('pending', 'active')
    ");
    $check_connection->bind_param("ii", $_SESSION['user_id'], $profile['user_id']);
    $check_connection->execute();
    $connection_result = $check_connection->get_result();
    $connection_exists = $connection_result->num_rows > 0;
    if ($connection_exists) {
        $connection_status = $connection_result->fetch_assoc()['status'];
    }
}

// Profile picture handling
$profile_picture = !empty($profile['profile_picture']) 
    ? "data:image/jpeg;base64," . base64_encode($profile['profile_picture'])
    : "../assests/images/default-profile.jpg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muscle Memory - Nutritionist Profile</title>
    <link rel="icon" type="image/x-icon" href="../../assests/images/dumbell.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Slab:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reusing the existing CSS from nutritionist dashboard */
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

       

        /* Additional specific styles for profile page */
        .profile-container {
            max-width: 800px;
            margin: 30px auto;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 20px;
        }

        .profile-picture-large {
            width: 250px;
            height: 250px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid #ffffff;
            margin-right: 30px;
        }

        .profile-details {
            flex-grow: 1;
        }

        .profile-details h1 {
            margin: 0 0 10px 0;
            font-size: 2.5rem;
        }

        .profile-details p {
            margin: 5px 0;
            color: #e0e0e0;
        }

        .profile-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .profile-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
        }

        .edit-profile-btn {
            display: inline-block;
            background: linear-gradient(45deg, #800080, #4B0082);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .edit-profile-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(128, 0, 128, 0.3);
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
        <div class="profile-container">
            <div class="profile-header">
                <img src="<?php echo $profile_picture; ?>" alt="Nutritionist Profile" class="profile-picture-large">
                <div class="profile-details">
                    <h1><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
                    <p><i class='bx bx-medal'></i> <?php echo htmlspecialchars($profile['primary_specialty']); ?></p>
                    <p><i class='bx bx-time'></i> <?php echo htmlspecialchars($profile['years_of_experience']); ?> Years of Experience</p>
                    <p><i class='bx bx-map'></i> <?php echo htmlspecialchars($profile['consultation_type']); ?> Consultations</p>
                    
                    <?php 
                    // Only show edit button if logged in as this nutritionist
                    if($can_edit) {
                        echo '<a href="../view/Edit_nute_profile.php" class="edit-profile-btn">Edit Profile</a>';
                    }
                    ?>
                </div>
            </div>

            <div class="profile-sections">
                <div class="profile-section">
                    <h2 class="section-title">Specializations</h2>
                    <ul>
                        <li><?php echo htmlspecialchars($profile['primary_specialty']); ?></li>
                        <?php 
                        // Split and display secondary specialties
                        if (!empty($profile['secondary_specialties'])) {
                            $secondaries = explode(',', $profile['secondary_specialties']);
                            foreach ($secondaries as $specialty) {
                                echo '<li>' . htmlspecialchars(trim($specialty)) . '</li>';
                            }
                        }
                        ?>
                    </ul>
                </div>

                <div class="profile-section">
                    <h2 class="section-title">Certifications</h2>
                    <ul>
                        <?php 
                        // Split and display certifications
                        $certs = explode(',', $profile['certifications']);
                        foreach ($certs as $cert) {
                            echo '<li>' . htmlspecialchars(trim($cert)) . '</li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <div class="profile-section" style="margin-top: 20px;">
                <h2 class="section-title">About Me</h2>
                <p><?php echo !empty($profile['about_me']) ? htmlspecialchars($profile['about_me']) : 'No additional information provided.'; ?></p>
            </div>

            <div class="profile-sections" style="margin-top: 20px;">
                <div class="profile-section">
                    <h2 class="section-title">Consultation Details</h2>
                    <p><strong>Rate:</strong> $<?php echo number_format($profile['consultation_rate'], 2); ?> per session</p>
                    <p><strong>Types:</strong> <?php echo htmlspecialchars($profile['consultation_type']); ?></p>
                    <p><strong>Availability:</strong> <?php echo htmlspecialchars($profile['availability_hours']); ?></p>
                </div>

                <div class="profile-section">
                    <h2 class="section-title">Contact</h2>
                    <p><i class='bx bx-envelope'></i> <?php echo htmlspecialchars($profile['email']); ?></p>
                    
                    <?php if(!$can_edit && isset($_GET['id']) && $_SESSION['user_id'] !== $profile['user_id']): ?>
                        <form method="POST" action="" style="margin-top: 20px;">
    <button type="submit" 
            name="request_connection" 
            class="request-btn" 
            style="
                display: inline-block;
                background: linear-gradient(45deg, #800080, #4B0082);
                color: white;
                padding: 12px 25px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                text-align: center;
                font-family: 'Josefin Slab', serif;
                font-weight: bold;
                <?php 
                if (isset($connection_exists) && $connection_exists) {
                    echo 'background: #666; cursor: not-allowed;';
                }
                ?>
            "
            <?php if (isset($connection_exists) && $connection_exists) echo 'disabled'; ?>>
        <?php 
        if (isset($connection_exists) && $connection_exists) {
            echo ($connection_status === 'pending') ? 'Request Pending' : 'Already Connected';
        } else {
            echo 'Request Consultation';
        }
        ?>
    </button>
</form>
                    <?php endif; ?>
                </div>
            </div>
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