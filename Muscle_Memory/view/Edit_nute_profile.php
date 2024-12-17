<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add error logging
function logError($message) {
    error_log($message);
}

// Check if user is logged in and is a nutritionist
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'nutritionist') {
    header("Location: ../view/login.php");
    exit();
}

// Database connection
require_once '../db/config.php';

// Initialize variables
$profile_picture_updated = false;
$error = '';

// Fetch existing profile data from Users table
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM MM_Users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_profile = $user_result->fetch_assoc();
$stmt->close();

// Fetch nutritionist details
$stmt = $conn->prepare("SELECT * FROM MM_Nutritionist WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// If no profile exists, redirect to create profile
if ($result->num_rows === 0) {
    header("Location: create_nute_profile.php");
    exit();
}

$profile = $result->fetch_assoc();
$stmt->close();

// Profile picture upload handling
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_file_size = 5 * 1024 * 1024; // 5MB

    // Log file details for debugging
    logError("File upload details: " . print_r($_FILES['profile_picture'], true));

    // Check file type and size
    if (in_array($_FILES['profile_picture']['type'], $allowed_types) && 
        $_FILES['profile_picture']['size'] <= $max_file_size) {
        
        try {
            // Read file contents
            $new_profile_picture = file_get_contents($_FILES['profile_picture']['tmp_name']);
            
            if ($new_profile_picture === false) {
                throw new Exception("Failed to read uploaded file");
            }

            // Prepare statement to update profile picture
            $stmt = $conn->prepare("UPDATE MM_Users SET profile_picture = ? WHERE user_id = ?");
            
            // Bind parameters with correct type
            $stmt->bind_param("bi", $new_profile_picture, $user_id);
            
            if ($stmt->execute()) {
                $profile_picture = $new_profile_picture;
                $profile_picture_updated = true;
                logError("Profile picture updated successfully");
            } else {
                throw new Exception("Database update failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Upload failed: " . $e->getMessage();
            logError($error);
        }
    } else {
        $error = "Invalid file type or size. Allowed types: JPEG, PNG, GIF (max 5MB)";
        logError($error);
    }
}

// Main form submission handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $specialization = $conn->real_escape_string($_POST['specialization']);
    $years_experience = intval($_POST['years_experience']);
    $certifications = $conn->real_escape_string($_POST['certifications']);
    $consultation_rate = floatval($_POST['consultation_rate']);
    $consultation_type = $conn->real_escape_string($_POST['consultation_type']);
    $availability_hours = $conn->real_escape_string($_POST['availability_hours']);
    $primary_specialty = $conn->real_escape_string($_POST['primary_specialty']);
    
    // Convert secondary specialties array to string
    $secondary_specialties = isset($_POST['secondary_specialties']) ? 
        implode(', ', $_POST['secondary_specialties']) : '';
    $secondary_specialties = mysqli_real_escape_string($conn, $secondary_specialties);

    // Update Nutritionist table
    $stmt = $conn->prepare("UPDATE MM_Nutritionist SET 
        specialization = ?, 
        years_of_experience = ?, 
        certifications = ?, 
        consultation_rate = ?, 
        consultation_type = ?, 
        availability_hours = ?, 
        primary_specialty = ?, 
        secondary_specialties = ?
        WHERE user_id = ?");
    
    $stmt->bind_param(
        "sissssssi", 
        $specialization, 
        $years_experience, 
        $certifications, 
        $consultation_rate, 
        $consultation_type, 
        $availability_hours, 
        $primary_specialty, 
        $secondary_specialties,
        $user_id
    );

    if ($stmt->execute()) {
        // Redirect if form submission is successful
        header("Location: profile.php");
        exit();
    } else {
        $error = "Update failed: " . $stmt->error;
        logError($error);
    }
    $stmt->close();
}

// Current profile picture
$current_profile_picture = !empty($user_profile['profile_picture']) 
    ? "data:image/" . (isset($user_profile['profile_picture_type']) ? $user_profile['profile_picture_type'] : 'jpeg') . ";base64," . base64_encode($user_profile['profile_picture'])
    : "../assets/images/default-profile.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Nutritionist Profile - Muscle Memory</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Slab:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4B0082;
            --secondary-color: #800080;
            --background-color: #f4f4f4;
            --text-color: #333;
            --form-bg-color: rgba(255, 255, 255, 0.9);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        body {
            font-family: 'Josefin Slab', serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text-color);
        }

        .profile-form-container {
            width: 100%;
            max-width: 800px;
            background: var(--form-bg-color);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            backdrop-filter: blur(10px);
        }

        .profile-form-container h1 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 2.5rem;
            position: relative;
            padding-bottom: 10px;
        }

        .profile-form-container h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-color);
            font-weight: 600;
        }

        .form-group input, 
        .form-group textarea, 
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(75, 0, 130, 0.2);
            border-radius: 8px;
            font-family: 'Josefin Slab', serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus, 
        .form-group textarea:focus, 
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(75, 0, 130, 0.1);
        }

        .profile-picture-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .current-profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid var(--primary-color);
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            opacity: 0.9;
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .error-message {
            background-color: #ffdddd;
            color: #ff0000;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        @media (max-width: 600px) {
            .profile-form-container {
                padding: 20px;
            }

            .profile-picture-container {
                flex-direction: column;
                text-align: center;
            }

            .current-profile-picture {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
<div id="bubble-container" class="bubble-container"></div>


    <div class="profile-form-container">
        <h1>Edit Nutritionist Profile</h1>
        
        <?php if(isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group profile-picture-container">
                <img src="<?php echo $current_profile_picture; ?>" alt="Current Profile" class="current-profile-picture">
                <div>
                    <label for="profile_picture">Update Profile Picture</label>
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif">
                </div>
            </div>

            <div class="form-group">
                <label for="specialization">Specialization</label>
                <input type="text" name="specialization" id="specialization" 
                       value="<?php echo htmlspecialchars($profile['specialization'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="years_experience">Years of Experience</label>
                <input type="number" name="years_experience" id="years_experience" 
                       value="<?php echo htmlspecialchars($profile['years_of_experience'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="certifications">Certifications</label>
                <textarea name="certifications" id="certifications" required><?php 
                    echo htmlspecialchars($profile['certifications'] ?? ''); 
                ?></textarea>
            </div>

            <div class="form-group">
                <label for="consultation_rate">Consultation Rate ($)</label>
                <input type="number" name="consultation_rate" id="consultation_rate" 
                       value="<?php echo htmlspecialchars($profile['consultation_rate'] ?? ''); ?>" required step="0.01">
            </div>

            <div class="form-group">
                <label for="consultation_type">Consultation Type</label>
                <select name="consultation_type" id="consultation_type" required>
                    <option value="online" <?php echo ($profile['consultation_type'] == 'online' ? 'selected' : ''); ?>>Online Only</option>
                    <option value="in-person" <?php echo ($profile['consultation_type'] == 'in-person' ? 'selected' : ''); ?>>In-Person Only</option>
                    <option value="both" <?php echo ($profile['consultation_type'] == 'both' ? 'selected' : ''); ?>>Both Online and In-Person</option>
                </select>
            </div>

            <div class="form-group">
                <label for="availability_hours">Availability Hours</label>
                <input type="text" name="availability_hours" id="availability_hours" 
                       value="<?php echo htmlspecialchars($profile['availability_hours'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="primary_specialty">Primary Specialty</label>
                <select name="primary_specialty" id="primary_specialty" required>
                    <option value="Weight Loss" <?php echo ($profile['primary_specialty'] == 'Weight Loss' ? 'selected' : ''); ?>>Weight Loss</option>
                    <option value="Sports Nutrition" <?php echo ($profile['primary_specialty'] == 'Sports Nutrition' ? 'selected' : ''); ?>>Sports Nutrition</option>
                    <option value="Muscle Gain" <?php echo ($profile['primary_specialty'] == 'Muscle Gain' ? 'selected' : ''); ?>>Muscle Gain</option>
                    <option value="Vegan Nutrition" <?php echo ($profile['primary_specialty'] == 'Vegan Nutrition' ? 'selected' : ''); ?>>Vegan Nutrition</option>
                </select>
            </div>

            <div class="form-group">
                <label>Secondary Specialties</label>
                <div class="specialty-options">
                    <?php $secondary_specs = explode(', ', $profile['secondary_specialties']); ?>
                    <label><input type="checkbox" name="secondary_specialties[]" value="Weight Loss" 
                        <?php echo in_array('Weight Loss', $secondary_specs) ? 'checked' : ''; ?>> Weight Loss</label>
                    <label><input type="checkbox" name="secondary_specialties[]" value="Sports Nutrition"
                        <?php echo in_array('Sports Nutrition', $secondary_specs) ? 'checked' : ''; ?>> Sports Nutrition</label>
                    <label><input type="checkbox" name="secondary_specialties[]" value="Muscle Gain"
                        <?php echo in_array('Muscle Gain', $secondary_specs) ? 'checked' : ''; ?>> Muscle Gain</label>
                    <label><input type="checkbox" name="secondary_specialties[]" value="Vegan Nutrition"
                        <?php echo in_array('Vegan Nutrition', $secondary_specs) ? 'checked' : ''; ?>> Vegan Nutrition</label>
                </div>
            </div>

            <button type="submit" class="submit-btn">Update Profile</button>
        </form>
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