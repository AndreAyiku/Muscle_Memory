
<?php
session_start();

// Database Connection
require_once '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    $_SESSION['access_error'] = "Please log in to access workouts.";
    header("Location: ../view/login.php");
    exit();
}

// Function to fetch workouts with optional filtering
function fetchWorkouts($conn, $filter = null) {
    $query = "
        SELECT w.workout_id, w.workout_name, w.description, 
               w.duration, w.difficulty_level, w.category,
               u.username AS creator
        FROM MM_Workouts w
        JOIN MM_Users u ON w.user_id = u.user_id
        WHERE w.is_public = 1
    ";

    // Add category filter if specified
    if ($filter && in_array($filter, ['strength', 'cardio', 'yoga'])) {
        $query .= " AND w.category = '" . mysqli_real_escape_string($conn, $filter) . "'";
    }

    $query .= " ORDER BY w.created_at DESC";

    $result = mysqli_query($conn, $query);
    
    $workouts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $workouts[] = $row;
    }

    return $workouts;
}

// Handle search functionality
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : null;
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : null;

// Fetch workouts based on search and category
$workouts = fetchWorkouts($conn, $categoryFilter);

// If search query is present, filter results
if ($searchQuery) {
    $workouts = array_filter($workouts, function($workout) use ($searchQuery) {
        return stripos($workout['workout_name'], $searchQuery) !== false || 
               stripos($workout['description'], $searchQuery) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muscle Memory - Workouts</title>
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

        .search-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            width: 100%; /* Ensure full width */

        }

        .search-input {
            width: 100%;
            max-width: 800px;
            padding: 15px;
            border-radius: 50px;
            border: none;
            background: rgba(30, 0, 50, 0.7);
            color: #fff;
            font-size: 1.1rem;
            font-family: 'Josefin Slab', serif;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        .workouts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .workout-card {
            background: rgba(20, 0, 40, 0.9);
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 0 20px rgba(128, 0, 128, 0.3);
        }

        .workout-card:hover {
            transform: translateY(-5px);
            background: rgba(30, 0, 50, 0.9);
        }

        .workout-card h3 {
            margin: 0 0 15px 0;
            color: #800080;
        }

        .workout-details {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .action-button {
            padding: 10px 20px;
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


        .workout-filters {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .filter-button {
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            background: rgba(30, 0, 50, 0.7);
            color: #fff;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-button.active {
            background: linear-gradient(45deg, #800080, #4B0082);
        }
        .workout-filters a {
    text-decoration: none;
}

.workout-details a {
    text-decoration: none;
}
    </style>
</head>
<body>
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
        <div class="search-container">
            <form method="GET" action="">
                <input type="text" name="search" class="search-input" placeholder="Search workouts...                           " value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>">
            </form>
        </div>

        <div class="workout-filters">
            <a href="?category=" class="filter-button <?php echo !$categoryFilter ? 'active' : ''; ?>">All Workouts</a>
            <a href="?category=strength" class="filter-button <?php echo $categoryFilter === 'strength' ? 'active' : ''; ?>">Strength</a>
            <a href="?category=cardio" class="filter-button <?php echo $categoryFilter === 'cardio' ? 'active' : ''; ?>">Cardio</a>
            <a href="?category=yoga" class="filter-button <?php echo $categoryFilter === 'yoga' ? 'active' : ''; ?>">Yoga</a>
        </div>

        <div class="workouts-grid">
            <?php if (empty($workouts)): ?>
                <div style="width: 100%; text-align: center; color: white;">
                    No workouts found. Try a different search or filter.
                </div>
            <?php else: ?>
                <?php foreach ($workouts as $workout): ?>
                    <div class="workout-card" data-category="<?php echo htmlspecialchars($workout['category']); ?>">
                        <h3><?php echo htmlspecialchars($workout['workout_name']); ?></h3>
                        <p><?php echo htmlspecialchars($workout['description']); ?></p>
                        <div class="workout-details">
                            <span>Duration: <?php echo htmlspecialchars($workout['duration']); ?> mins</span>
                            <span>Difficulty: <?php echo htmlspecialchars($workout['difficulty_level']); ?></span>
                        </div>
                        <div class="workout-details">
                            <span>Created by: @<?php echo htmlspecialchars($workout['creator']); ?></span>
                        </div>
                        <div class="workout-details">
                            <a href="../view/workout_detail.php?id=<?php echo $workout['workout_id']; ?>" class="action-button">View Workout</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // User Dropdown Toggle
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

    // Create bubbles when page loads
    window.addEventListener('load', createBubbles);
</script>
</body>
</html>