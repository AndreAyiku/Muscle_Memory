<?php
session_start();

// Enable error reporting for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
require_once '../../db/config.php';

// Security: Check if user is logged in and is a nutritionist
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'nutritionist') {
    // Redirect to login if not authenticated
    header("Location: ../view/login.php");
    exit();
}
if (isset($_POST['delete_client'])) {
    $client_connection_id = intval($_POST['connection_id']);
    $delete_query = "DELETE FROM MM_ClientConnections 
                    WHERE connection_id = ? 
                    AND professional_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $client_connection_id, $_SESSION['user_id']);
    $delete_stmt->execute();
    header("Location: nutritionist_dashboard.php");
    exit();
}

// Prepare a secure query to fetch user details
$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);

// User details query with prepared statement
$user_query = "SELECT u.user_id, u.profile_picture, u.first_name, u.last_name, 
               n.primary_specialty, n.specialization, n.years_of_experience 
               FROM MM_Users u
               LEFT JOIN MM_Nutritionist n ON u.user_id = n.user_id
               WHERE u.user_id = ?";

$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);

// After user data fetch, add nutritionist data fetch
$nutritionist_query = "
    SELECT *
    FROM MM_Nutritionist
    WHERE user_id = ?
";

$nutritionist_stmt = mysqli_prepare($conn, $nutritionist_query);
mysqli_stmt_bind_param($nutritionist_stmt, "i", $user_id);
mysqli_stmt_execute($nutritionist_stmt);
$nutritionist_result = mysqli_stmt_get_result($nutritionist_stmt);
$nutritionist_data = mysqli_fetch_assoc($nutritionist_result);

// Profile picture handling with base64
$profile_pic = '';
if (!empty($user_data['profile_picture'])) {
    // Convert BLOB to base64 with MIME type
    $profile_pic = 'data:image;base64,' . base64_encode($user_data['profile_picture']);
} else {
    // Fallback to default profile picture
    $profile_pic = '../assests/images/default_profile.png';
}

// Check if nutritionist profile is complete
$profile_exists = !empty($user_data['primary_specialty']);

// Fetch client count
$clients_query = "SELECT COUNT(*) as client_count 
                 FROM MM_ClientConnections 
                 WHERE professional_id = ? 
                 AND professional_type = 'nutritionist' 
                 AND status = 'active'";

$active_clients_query = "SELECT COUNT(*) as active_count 
                        FROM MM_ClientConnections 
                        WHERE professional_id = ? 
                        AND professional_type = 'nutritionist' 
                        AND status = 'active'";

$pending_clients_query = "SELECT COUNT(*) as pending_count 
                         FROM MM_ClientConnections 
                         WHERE professional_id = ? 
                         AND professional_type = 'nutritionist' 
                         AND status = 'pending'";

$clients_stmt = mysqli_prepare($conn, $clients_query);
mysqli_stmt_bind_param($clients_stmt, "i", $user_id);
mysqli_stmt_execute($clients_stmt);
$clients_result = mysqli_stmt_get_result($clients_stmt);
$clients_data = mysqli_fetch_assoc($clients_result);

$active_stmt = mysqli_prepare($conn, $active_clients_query);
mysqli_stmt_bind_param($active_stmt, "i", $user_id);
mysqli_stmt_execute($active_stmt);
$active_result = mysqli_stmt_get_result($active_stmt);
$active_count = mysqli_fetch_assoc($active_result)['active_count'];

// Get pending clients count
$pending_stmt = mysqli_prepare($conn, $pending_clients_query);
mysqli_stmt_bind_param($pending_stmt, "i", $user_id);
mysqli_stmt_execute($pending_stmt);
$pending_result = mysqli_stmt_get_result($pending_stmt);
$pending_count = mysqli_fetch_assoc($pending_result)['pending_count'];
// Fetch clients details
$clients_detail_query = "
    SELECT 
        u.first_name, 
        u.last_name,
        u.profile_picture,
        cc.created_at,
        u.user_id,
        cc.status,
        cc.connection_id  
    FROM MM_ClientConnections cc
    JOIN MM_Users u ON cc.client_id = u.user_id
    WHERE cc.professional_id = ? 
    AND cc.professional_type = 'nutritionist'
    AND cc.status = 'active'
    ORDER BY cc.created_at DESC
";

$clients_detail_stmt = mysqli_prepare($conn, $clients_detail_query);
mysqli_stmt_bind_param($clients_detail_stmt, "i", $user_id);
mysqli_stmt_execute($clients_detail_stmt);
$clients_detail_result = mysqli_stmt_get_result($clients_detail_stmt);

// Default to 0 if no data found
$client_count = $clients_data['client_count'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muscle Memory - Nutritionist Dashboard</title>
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

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }

        .profile-card, .dashboard-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }

        .section-title {
            color: #ffffff;
            margin-bottom: 20px;
            font-size: 1.8rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 10px;
        }

        .professional-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-picture {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ffffff;
            margin-bottom: 15px;
        }

        .grid-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .grid-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }

        .grid-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        .card-icon {
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

        .nutrition-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
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
            <!-- Professional Profile Section -->
            <div class="professional-profile">
    <img src="<?php echo $profile_pic; ?>" alt="Nutritionist Profile" class="profile-picture">
    <h2><?php 
        echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); 
    ?></h2>
    <p><?php echo ($nutritionist_data && isset($nutritionist_data['primary_specialty'])) ? 
        htmlspecialchars($nutritionist_data['primary_specialty']) : 
        'Nutritionist'; ?></p>
    
    <div class="nutrition-stats">
        <div class="stat-item">
            <div class="stat-number"><?php echo $client_count; ?></div>
            <div class="stat-label">Clients</div>
        </div>
    </div>
</div>
            
            <!-- Quick Stats and Actions -->
            <div class="profile-card">
                <h2 class="section-title">Dashboard Overview</h2>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; text-align: center; margin-bottom: 15px;">
                    <div>
                        <div style="font-size: 1.5rem; font-weight: bold;"><?php echo $active_count; ?></div>
                        <div style="font-size: 0.9rem;">Active Consultations</div>
                    </div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: bold;"><?php echo $pending_count; ?></div>
                        <div style="font-size: 0.9rem;">Pending Requests</div>
                    </div>
                
                </div>
                
                <!-- Dynamic Profile Button -->
                <?php if ($profile_exists): ?>
                    <a href="../profile.php" class="public-profile-button" style="
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
                        View Public Profile
                    </a>
                    
        
                    <a href="../pending.php" class="pending-button" style="
                        display: inline-block;
                        background: linear-gradient(45deg, #800080, #4B0082);
                        color: white;
                        text-align: center;
                        padding: 12px 20px;
                        text-decoration: none;
                        border-radius: 10px;
                        font-weight: bold;
                        transition: transform 0.3s ease, box-shadow 0.3s ease;
                    " onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 15px rgba(128, 0, 128, 0.3)'" 
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        View Pending Requests (<?php echo $pending_count; ?>)
                    </a>
                    <a href="../chat.php" class="pending-button" style="
                        display: inline-block;
                        background: linear-gradient(45deg, #800080, #4B0082);
                        color: white;
                        text-align: center;
                        padding: 12px 20px;
                        text-decoration: none;
                        border-radius: 10px;
                        font-weight: bold;
                        transition: transform 0.3s ease, box-shadow 0.3s ease;
                    " onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 15px rgba(128, 0, 128, 0.3)'" 
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        View chat
                    </a>
                <?php else: ?>
                    <a href="../create_nute_profile.php" class="public-profile-button" style="
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
                        Create Nutritionist Profile
                    </a>
                <?php endif; ?>
            </div>

        <!-- Clients Section -->
        <div class="dashboard-section">
    <h2 class="section-title">My Clients</h2>
    <div class="client-list">
        <?php if(mysqli_num_rows($clients_detail_result) > 0): ?>
            <?php while ($client = mysqli_fetch_assoc($clients_detail_result)) { ?>
                <div class="client-card">
                    <img src="<?php echo !empty($client['profile_picture']) ? 'data:image;base64,' . base64_encode($client['profile_picture']) : '../assests/images/default_profile.png'; ?>" alt="Client Profile" class="profile-picture">
                    <div>
                        <h3><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h3>
                        <p>Joined on <?php echo date('F j, Y', strtotime($client['created_at'])); ?></p>
                        <form method="POST" style="display: inline;">
                        <input type="hidden" name="connection_id" value="<?php echo $client['connection_id']; ?>">
                        <button type="submit" 
                                name="delete_client" 
                                class="delete-btn" 
                                style="
                                    display: inline-block;
                                    background: linear-gradient(45deg, #800000, #640000);
                                    color: white;
                                    padding: 8px 15px;
                                    border: none;
                                    border-radius: 8px;
                                    font-size: 14px;
                                    cursor: pointer;
                                    transition: all 0.3s ease;
                                    font-family: 'Josefin Slab', serif;
                                    font-weight: bold;
                                    margin-left: 10px;
                                "
                                onclick="return confirm('Are you sure you want to remove this client?')"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(128, 0, 0, 0.4)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            Remove Client
                        </button>
                    </form>
                    </div>
                    
                </div>
            <?php } ?>
        <?php else: ?>
            <div class="empty-state">
                <i class='bx bx-user-plus'></i>
                <p>No active clients yet. Start growing your client base!</p>
            </div>
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
            container.innerHTML = ''; 
            const bubbleCount = 100;

            for (let i = 0; i < bubbleCount; i++) {
                const bubble = document.createElement('div');
                bubble.classList.add('bubble');

                bubble.style.left = `${Math.random() * 100}%`;

                const size = Math.random() * 22 + 3;
                bubble.style.width = `${size}px`;
                bubble.style.height = `${size}px`;

                const duration = Math.random() * 5 + 3;
                bubble.style.animationDuration = `${duration}s`;

                const delay = Math.random() * 10;
                bubble.style.animationDelay = `-${delay}s`;

                container.appendChild(bubble);
            }
        }

        window.addEventListener('load', createBubbles);
    </script>
</body>
</html>