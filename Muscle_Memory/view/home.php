<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Connection
require_once '../db/config.php';

// Function to get featured experts
function getFeaturedExperts($conn) {
    $experts = [];
    
    // Fetch Featured Nutritionists
    $nutritionist_query = "
        SELECT u.first_name, u.last_name, n.primary_specialty 
        FROM MM_Users u 
        JOIN MM_Nutritionist n ON u.user_id = n.user_id 
        WHERE n.primary_specialty IS NOT NULL 
        LIMIT 1
    ";
    $nutritionist_result = mysqli_query($conn, $nutritionist_query);
    
    // Fetch Featured Trainers
    $trainer_query = "
        SELECT u.first_name, u.last_name, t.primary_specialty 
        FROM MM_Users u 
        JOIN MM_Trainers t ON u.user_id = t.user_id 
        WHERE t.primary_specialty IS NOT NULL 
        LIMIT 2
    ";
    $trainer_result = mysqli_query($conn, $trainer_query);
    
    while ($row = mysqli_fetch_assoc($nutritionist_result)) {
        $row['type'] = 'Nutritionist';
        $experts[] = $row;
    }
    
    while ($row = mysqli_fetch_assoc($trainer_result)) {
        $row['type'] = 'Trainer';
        $experts[] = $row;
    }
    
    return $experts;
}

// Function to get community workouts
function getCommunityWorkouts($conn) {
    $workouts_query = "
        SELECT w.workout_name, u.username, w.duration 
        FROM MM_Workouts w 
        JOIN MM_Users u ON w.user_id = u.user_id 
        ORDER BY w.created_at DESC 
        LIMIT 3
    ";
    $workouts_result = mysqli_query($conn, $workouts_query);
    
    $workouts = [];
    while ($row = mysqli_fetch_assoc($workouts_result)) {
        $workouts[] = $row;
    }
    
    return $workouts;
}

// Function to get success stories
function getSuccessStories($conn) {
    $stories_query = "
        SELECT ss.story_title, ss.story_content, u.first_name, u.last_name 
        FROM MM_Success_Stories ss 
        JOIN MM_Users u ON ss.user_id = u.user_id 
        WHERE ss.is_featured = 1 
        LIMIT 3
    ";
    $stories_result = mysqli_query($conn, $stories_query);
    
    $stories = [];
    while ($row = mysqli_fetch_assoc($stories_result)) {
        $stories[] = $row;
    }
    
    return $stories;
}

// Function to get platform statistics
function getPlatformStatistics($conn) {
    $stats = [
        'trainers' => 0,
        'nutritionists' => 0,
        'community_members' => 0
    ];
    
    // Count active trainers
    $trainer_query = "SELECT COUNT(*) as trainer_count FROM MM_Trainers";
    $trainer_result = mysqli_query($conn, $trainer_query);
    $trainer_row = mysqli_fetch_assoc($trainer_result);
    $stats['trainers'] = $trainer_row['trainer_count'];
    
    // Count nutritionists
    $nutritionist_query = "SELECT COUNT(*) as nutritionist_count FROM MM_Nutritionist";
    $nutritionist_result = mysqli_query($conn, $nutritionist_query);
    $nutritionist_row = mysqli_fetch_assoc($nutritionist_result);
    $stats['nutritionists'] = $nutritionist_row['nutritionist_count'];
    
    // Count total users (community members)
    $users_query = "SELECT COUNT(*) as user_count FROM MM_Users WHERE user_type != 'admin'";
    $users_result = mysqli_query($conn, $users_query);
    $users_row = mysqli_fetch_assoc($users_result);
    $stats['community_members'] = $users_row['user_count'];
    
    return $stats;
}

// Fetch data
$featured_experts = getFeaturedExperts($conn);
$community_workouts = getCommunityWorkouts($conn);
$success_stories = getSuccessStories($conn);
$platform_stats = getPlatformStatistics($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muscle Memory - Home</title>
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
            background: #000000; /* Changed from background-image to solid black */
            position: relative;
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
            background-color: rgba(128, 0, 128, 0.5);
            border-radius: 50%;
            animation: float-up linear infinite;
            box-shadow: 0 0 20px rgba(128, 0, 128, 0.3);
            z-index: 1001;
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

        .container {
            position: relative;
            z-index: 10;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-bar {
            background: rgba(30, 0, 50, 0.9);
            padding: 20px;
            border-radius: 15px;
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            box-shadow: 0 0 20px rgba(128, 0, 128, 0.3);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2em;
            color: #800080;
            font-weight: bold;
        }

        .featured-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .expert-card {
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            padding: 20px;
            transition: transform 0.3s;
            box-shadow: 0 0 20px rgba(128, 0, 128, 0.3);
        }

        .expert-card:hover {
            transform: translateY(-5px);
        }

        .community-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .workout-share {
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            padding: 15px;
            transition: all 0.3s;
        }

        .workout-share:hover {
            background: rgba(50, 0, 70, 0.9);
        }

        .quick-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .action-button {
            text-decoration: none;
            display: inline-block;
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            background: linear-gradient(45deg, #800080, #4B0082);
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(128, 0, 128, 0.4);
        }

        .success-stories {
            display: flex;
            overflow-x: auto;
            gap: 20px;
            padding: 20px 0;
        }

        .story-card {
            min-width: 300px;
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(128, 0, 128, 0.3);
        }

        .section-title {
            font-size: 2em;
            margin-bottom: 20px;
            background: linear-gradient(45deg, #800080, #4B0082);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .audio-controls {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 10px;
}

.audio-toggle {
    background: rgba(128, 0, 128, 0.7);
    color: #fff;
    border: none;
    padding: 10px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.audio-toggle:hover {
    background: rgba(128, 0, 128, 0.9);
    transform: scale(1.1);
}
    </style>
</head>
<body>
    <!-- Background Video (Replace with your desired video) -->
    
    <!-- Bubble Container -->
    <div id="bubble-container" class="bubble-container"></div>

    <!-- Header -->
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
    // Check if user is logged in
    if(isset($_SESSION['user_id'])) {
        // Admin status takes precedence
        if($_SESSION['admin_status'] === '1') {
            // Admin dashboard
            echo '<a href="../view/admin/admin_dashboard.php">Admin Dashboard</a>';
        } else {
            // Check user type for appropriate dashboard
            switch($_SESSION['user_type']) {
                case 'trainer':
                    echo '<a href="../view/admin/trainer_dashboard.php">Trainer Dashboard</a>';
                    break;
                case 'nutritionist':
                    echo '<a href="../view/admin/nutritionist_dashboard.php">Nutritionist Dashboard</a>';
                    break;
                default: // 'regular' or any other type
                    echo '<a href="../view/admin/user_dashboard.php">User Dashboard</a>';
                    break;
            }
        }
        // Logout option always present for logged-in users
        echo '<a href="../actions/logout.php">Logout</a>';
    } else {
        // Not logged in - show login and signup options
        echo '<a href="../view/login.php">Login</a>';
        echo '<a href="../view/sign-up.php">Sign Up</a>';
    }
    ?>
</div>
            </div>
        </div>
    </header>

    <div class="container">
    <div class="stats-bar">
    <div class="stat-item">
        <div class="stat-number"><?php echo $platform_stats['trainers'] > 150 ? '150+' : $platform_stats['trainers']; ?>+</div>
        <div>Active Trainers</div>
    </div>
    <div class="stat-item">
        <div class="stat-number"><?php echo $platform_stats['nutritionists'] > 75 ? '75+' : $platform_stats['nutritionists']; ?>+</div>
        <div>Nutritionists</div>
    </div>
    <div class="stat-item">
        <div class="stat-number"><?php echo $platform_stats['community_members'] > 10000 ? '10k+' : $platform_stats['community_members']; ?>+</div>
        <div>Community Members</div>
    </div>
</div>

<h2 class="section-title">Featured Experts</h2>
<div class="featured-section">
    <?php foreach ($featured_experts as $expert): ?>
    <div class="expert-card">
        <h3><?php echo htmlspecialchars($expert['first_name'] . ' ' . $expert['last_name']); ?></h3>
        <p><?php echo $expert['type']; ?></p>
        <p>Specializes in <?php echo htmlspecialchars($expert['primary_specialty']); ?></p>
        <button class="action-button">Connect</button>
    </div>
    <?php endforeach; ?>
</div>

<h2 class="section-title">Community Workouts</h2>
<div class="community-grid">
    <?php foreach ($community_workouts as $workout): ?>
    <div class="workout-share">
        <h3><?php echo htmlspecialchars($workout['workout_name']); ?></h3>
        <p>Shared by @<?php echo htmlspecialchars($workout['username']); ?></p>
        <p>Duration: <?php echo htmlspecialchars($workout['duration']); ?> mins</p>
    </div>
    <?php endforeach; ?>
</div>

        <div class="quick-actions">
            <a href="../view/trainers.php" class="action-button">Find a Trainer</a>
            <a href="../view/nutritionist.php" class="action-button">Connect with Nutritionist</a>
            <a href="../view/create_workout.php" class="action-button">Share Your Workout</a>
        </div>

        
    </div>

    <script src="../assests/jss/home.js"></script>
</body>
</html>