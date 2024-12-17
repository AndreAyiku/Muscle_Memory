<?php
session_start();

// Database Connection
require_once '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    $_SESSION['access_error'] = "Please log in to view nutritionists.";
    header("Location: ../view/login.php");
    exit();
}

// Function to fetch nutritionists with optional filtering
function fetchNutritionists($conn, $filter = null, $searchQuery = null) {
    $query = "
        SELECT 
            u.user_id, u.first_name, u.last_name, u.username, 
            u.profile_picture, u.bio,
            n.specialization, n.primary_specialty, 
            n.secondary_specialties, n.years_of_experience,
            n.consultation_rate, n.consultation_type
        FROM MM_Users u
        JOIN MM_Nutritionist n ON u.user_id = n.user_id
        WHERE u.user_type = 'nutritionist'
    ";

    // More robust filtering logic
    $validSpecialties = ['weight-loss', 'weight loss', 'Weight Loss', 'muscle-gain', 'muscle gain', 'Muscle Gain', 'sports', 'Sports', 'vegan', 'Vegan'];
    
    if ($filter) {
        $query .= " AND (
            LOWER(REPLACE(n.primary_specialty, ' ', '-')) = '" . mysqli_real_escape_string($conn, strtolower(str_replace(' ', '-', $filter))) . "' 
            OR LOWER(REPLACE(n.primary_specialty, ' ', '-')) LIKE '%" . mysqli_real_escape_string($conn, strtolower(str_replace(' ', '-', $filter))) . "%'
            OR LOWER(REPLACE(n.secondary_specialties, ' ', '-')) LIKE '%" . mysqli_real_escape_string($conn, strtolower(str_replace(' ', '-', $filter))) . "%'
        )";
    }

    // Add search query if present
    if ($searchQuery) {
        $searchTerm = mysqli_real_escape_string($conn, $searchQuery);
        $query .= " AND (
            u.first_name LIKE '%{$searchTerm}%' 
            OR u.last_name LIKE '%{$searchTerm}%' 
            OR u.username LIKE '%{$searchTerm}%'
        )";
    }

    $query .= " ORDER BY n.years_of_experience DESC";

    $result = mysqli_query($conn, $query);
    
    $nutritionists = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Convert profile picture to base64 if exists
        if ($row['profile_picture']) {
            $row['profile_picture_base64'] = base64_encode($row['profile_picture']);
        } else {
            // Fallback to a default image if no profile picture
            $row['profile_picture_base64'] = base64_encode(file_get_contents('../assests/images/default-profile.png'));
        }

        // Parse secondary specialties
        $row['secondary_specialties_array'] = $row['secondary_specialties'] 
            ? explode(',', $row['secondary_specialties']) 
            : [];

        $nutritionists[] = $row;
    }

    return $nutritionists;
}

// Handle search and filter
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : null;
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : null;

// Fetch nutritionists based on search and category
$nutritionists = fetchNutritionists($conn, $categoryFilter, $searchQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muscle Memory - Nutritionists</title>
    <link rel="icon" type="image/x-icon" href="../assests/images/dumbell.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Slab:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reusing the exact same CSS from trainers.php */
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
        }

        .search-input {
            width: 100%;
            max-width: 600px;
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

        .nutritionists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .nutritionist-card {
    background: rgba(20, 0, 40, 0.9);
    border-radius: 15px;
    padding: 25px;  /* Increased padding */
    transition: all 0.3s ease;
    box-shadow: 0 0 20px rgba(128, 0, 128, 0.3);
    display: flex;
    flex-direction: column;
    gap: 15px;  /* Add gap between flex items */
}

.nutritionist-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;  /* More margin */
}

.nutritionist-avatar {
    width: 100px;  /* Slightly larger avatar */
    height: 100px;
    border-radius: 50%;
    margin-right: 20px;  /* More space between avatar and text */
    object-fit: cover;
}

.nutritionist-details {
    flex-grow: 1;
    margin-bottom: 15px;  /* Add some space before action button */
}

.nutritionist-specialties {
    display: flex;
    flex-wrap: wrap;  /* Allow wrapping of specialty tags */
    gap: 10px;
    margin-top: 10px;
}

.specialty-tag {
    background: rgba(128, 0, 128, 0.2);
    color: #fff;
    padding: 6px 12px;  /* Slightly larger tags */
    border-radius: 20px;
    font-size: 0.8rem;
    margin-bottom: 5px;
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

        .nutritionist-filters {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        .nutritionist-filters a{
            text-decoration: none;
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
        .workout-details a{
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
                <input type="text" name="search" class="search-input" 
                       placeholder="Search nutritionists by name..." 
                       value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>">
            </form>
        </div>

        <div class="nutritionist-filters">
            <a href="?category=" class="filter-button <?php echo !$categoryFilter ? 'active' : ''; ?>">All Nutritionists</a>
            <a href="?category=weight-loss" class="filter-button <?php echo $categoryFilter === 'weight-loss' ? 'active' : ''; ?>">Weight Loss</a>
            <a href="?category=muscle-gain" class="filter-button <?php echo $categoryFilter === 'muscle-gain' ? 'active' : ''; ?>">Muscle Gain</a>
            <a href="?category=sports" class="filter-button <?php echo $categoryFilter === 'sports' ? 'active' : ''; ?>">Sports Nutrition</a>
            <a href="?category=vegan" class="filter-button <?php echo $categoryFilter === 'vegan' ? 'active' : ''; ?>">Vegan Nutrition</a>
        </div>

        <div class="nutritionists-grid">
            <?php if (empty($nutritionists)): ?>
                <div style="width: 100%; text-align: center; color: white;">
                    No nutritionists found. Try a different search or filter.
                </div>
            <?php else: ?>
                <?php foreach ($nutritionists as $nutritionist): ?>
                    <div class="nutritionist-card" data-category="<?php echo htmlspecialchars(strtolower($nutritionist['primary_specialty'])); ?>">
                        <div class="nutritionist-header">
                            <img src="data:image/jpeg;base64,<?php echo $nutritionist['profile_picture_base64']; ?>" 
                                 alt="<?php echo htmlspecialchars($nutritionist['first_name'] . ' ' . $nutritionist['last_name']); ?>" 
                                 class="nutritionist-avatar">
                            <div>
                                <h3><?php echo htmlspecialchars($nutritionist['first_name'] . ' ' . $nutritionist['last_name']); ?></h3>
                                <p><?php echo htmlspecialchars($nutritionist['primary_specialty'] . ' Specialist'); ?></p>
                            </div>
                        </div>
                        <div class="nutritionist-details">
                            <div class="nutritionist-specialties">
                                <span class="specialty-tag"><?php echo htmlspecialchars($nutritionist['primary_specialty']); ?></span>
                                <?php foreach ($nutritionist['secondary_specialties_array'] as $specialty): ?>
                                    <span class="specialty-tag"><?php echo htmlspecialchars(trim($specialty)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="workout-details">
                            <a href="../view/profile.php?id=<?php echo $nutritionist['user_id']; ?>" class="action-button">View Profile</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

        <script>
 document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input');
    const filterButtons = document.querySelectorAll('.filter-button');
    const trainerCards = document.querySelectorAll('.trainer-card');

    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        trainerCards.forEach(card => {
            const trainerName = card.querySelector('h3').textContent.toLowerCase();
            const isVisible = trainerName.includes(searchTerm);
            card.style.display = isVisible ? 'flex' : 'none';
        });
    });

    // Filter functionality
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            
            trainerCards.forEach(card => {
                const category = card.getAttribute('data-category');
                
                if (filter === 'all' || category === filter) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Bubble animation script
    

    // Create bubbles periodically
    
});

function toggleUserDropdown() {
    const dropdown = document.getElementById('user-dropdown');
    dropdown.classList.toggle('show');
}
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
    // Recreate bubbles periodically
    setInterval(createBubbles, 30000);

    </script>
</body>
</html>

        

