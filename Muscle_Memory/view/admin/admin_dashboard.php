<?php
session_start();
require_once '../../db/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['admin_status'] !== '1') {
    header("Location: ../login.php");
    exit();
}
if (isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    $delete_query = "DELETE FROM MM_Users WHERE user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: admin_dashboard.php");
    exit();
}

// Fetch admin details
$user_id = $_SESSION['user_id'];
$admin_query = "SELECT * FROM MM_Users WHERE user_id = ? AND admin_status = '1'";
$admin_stmt = $conn->prepare($admin_query);
$admin_stmt->bind_param("i", $user_id);
$admin_stmt->execute();
$admin_data = $admin_stmt->get_result()->fetch_assoc();

// Get counts
$users_query = "SELECT COUNT(*) as total FROM MM_Users";
$trainers_query = "SELECT COUNT(*) as total FROM MM_Users WHERE user_type = 'trainer'";
$nutritionists_query = "SELECT COUNT(*) as total FROM MM_Users WHERE user_type = 'nutritionist'";
$workouts_query = "SELECT COUNT(*) as total FROM MM_Workouts";

$total_users = $conn->query($users_query)->fetch_assoc()['total'];
$total_trainers = $conn->query($trainers_query)->fetch_assoc()['total'];
$total_nutritionists = $conn->query($nutritionists_query)->fetch_assoc()['total'];
$total_workouts = $conn->query($workouts_query)->fetch_assoc()['total'];

$users_list_query = "SELECT * FROM MM_Users WHERE user_type = 'regular'";
$trainers_list_query = "SELECT * FROM MM_Users WHERE user_type = 'trainer'";
$nutritionists_list_query = "SELECT * FROM MM_Users WHERE user_type = 'nutritionist'";

$users_list = $conn->query($users_list_query);
$trainers_list = $conn->query($trainers_list_query);
$nutritionists_list = $conn->query($nutritionists_list_query);


// Profile picture handling
$profile_pic = !empty($admin_data['profile_picture']) 
    ? "data:image;base64," . base64_encode($admin_data['profile_picture'])
    : "../assests/images/default-profile.jpg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muscle Memory - Admin Dashboard</title>
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
            background-color: #000000;    
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
            background-color: rgba(219, 51, 219, 0.5);
            border-radius: 50%;
            animation: float-up linear infinite;
            box-shadow: 0 0 20px rgba(128, 0, 128, 0.3);
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

        .container {
            position: relative;
            z-index: 10;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .welcome-card {
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            padding: 25px;
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 0 20px rgba(128, 0, 128, 0.3);
        }

        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #800080;
        }

        .welcome-content {
            flex-grow: 1;
        }

        .welcome-title {
            font-size: 2.5em;
            margin: 0 0 10px 0;
            background: linear-gradient(45deg, #800080, #4B0082);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-subtitle {
            font-size: 1.2em;
            color: #ddd;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .dashboard-card {
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            padding: 25px;
            transition: transform 0.3s;
            box-shadow: 0 0 20px rgba(128, 0, 128, 0.3);
            position: relative;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .card-icon {
            font-size: 3rem;
            position: absolute;
            top: 10px;
            right: 10px;
            color: rgba(128, 0, 128, 0.3);
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

        .section-title {
            font-size: 2em;
            margin-bottom: 30px;
            background: linear-gradient(45deg, #800080, #4B0082);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .action-button {
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            background: linear-gradient(45deg, #800080, #4B0082);
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(128, 0, 128, 0.4);
        }

        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .nav-card {
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
            text-decoration: none;
            color: #fff;
        }

        .nav-card:hover {
            transform: translateY(-5px);
            background: rgba(50, 0, 70, 0.9);
        }

        .nav-card i {
            font-size: 2rem;
            color: #800080;
        }

        .quick-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .stat-box {
            background: rgba(30, 0, 50, 0.7);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            flex: 1;
            margin: 0 10px;
        }

        .stat-number {
            font-size: 2rem;
            color: #800080;
            font-weight: bold;
        }
        .welcome-card {
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #800080;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 30px 0;
        }

        .stat-box {
            background: rgba(30, 0, 50, 0.9);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #800080;
            margin-bottom: 10px;
        }
        .management-sections {
    margin-top: 30px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.user-list {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
}

.user-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid rgba(128, 0, 128, 0.2);
    margin-bottom: 10px;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.delete-btn {
    background: linear-gradient(45deg, #800000, #640000);
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.delete-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(128, 0, 0, 0.4);
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
        <div class="welcome-card">
            <img src="<?php echo $profile_pic; ?>" alt="Profile Picture" class="profile-picture">
            <div class="welcome-content">
                <h2 class="welcome-title">Welcome, <?php echo htmlspecialchars($admin_data['first_name']); ?>!</h2>
                <p class="welcome-subtitle">Admin Dashboard</p>
            </div>
        </div>

        <div class="quick-stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div>Total Users</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $total_trainers; ?></div>
                <div>Active Trainers</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $total_nutritionists; ?></div>
                <div>Total Nutritionists</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $total_workouts; ?></div>
                <div>Total Workouts</div>
            </div>
        </div>
    </div>
    <div class="management-sections">
    <!-- Users Management -->
    <div class="stat-box">
        <h2>User Management</h2>
        <div class="user-list">
            <?php while ($user = $users_list->fetch_assoc()): ?>
                <div class="user-item">
                    <div class="user-info">
                        <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        <button type="submit" name="delete_user" class="delete-btn">Delete</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Trainers Management -->
    <div class="stat-box">
        <h2>Trainer Management</h2>
        <div class="user-list">
            <?php while ($trainer = $trainers_list->fetch_assoc()): ?>
                <div class="user-item">
                    <div class="user-info">
                        <strong><?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?></strong>
                        <span><?php echo htmlspecialchars($trainer['email']); ?></span>
                    </div>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this trainer?');">
                        <input type="hidden" name="user_id" value="<?php echo $trainer['user_id']; ?>">
                        <button type="submit" name="delete_user" class="delete-btn">Delete</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Nutritionists Management -->
    <div class="stat-box">
        <h2>Nutritionist Management</h2>
        <div class="user-list">
            <?php while ($nutritionist = $nutritionists_list->fetch_assoc()): ?>
                <div class="user-item">
                    <div class="user-info">
                        <strong><?php echo htmlspecialchars($nutritionist['first_name'] . ' ' . $nutritionist['last_name']); ?></strong>
                        <span><?php echo htmlspecialchars($nutritionist['email']); ?></span>
                    </div>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this nutritionist?');">
                        <input type="hidden" name="user_id" value="<?php echo $nutritionist['user_id']; ?>">
                        <button type="submit" name="delete_user" class="delete-btn">Delete</button>
                    </form>
                </div>
            <?php endwhile; ?>
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