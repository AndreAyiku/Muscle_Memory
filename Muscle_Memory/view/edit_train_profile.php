<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is a trainer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'trainer') {
    header("Location: ../view/login.php");
    exit();
}

require_once '../db/config.php';

$error = '';
$user_id = $_SESSION['user_id'];

// Fetch trainer details
$stmt = $conn->prepare("SELECT * FROM MM_Trainers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: create_train_profile.php");
    exit();
}

$profile = $result->fetch_assoc();
$stmt->close();

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $years_experience = intval($_POST['years_experience']);
    $certifications = mysqli_real_escape_string($conn, $_POST['certifications']);
    $session_rate = floatval($_POST['session_rate']);
    $consultation_type = mysqli_real_escape_string($conn, $_POST['consultation_type']);
    $availability_hours = mysqli_real_escape_string($conn, $_POST['availability_hours']);
    $primary_specialty = mysqli_real_escape_string($conn, $_POST['primary_specialty']);
    
    // Convert secondary specialties array to string
    $secondary_specialties = isset($_POST['secondary_specialties']) ? 
        implode(', ', $_POST['secondary_specialties']) : '';
    $secondary_specialties = mysqli_real_escape_string($conn, $secondary_specialties);

    // Update Trainer profile
    $stmt = $conn->prepare("UPDATE MM_Trainers SET 
        specialization = ?, 
        years_of_experience = ?, 
        certifications = ?, 
        session_rate = ?, 
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
        $session_rate, 
        $consultation_type, 
        $availability_hours, 
        $primary_specialty, 
        $secondary_specialties,
        $user_id
    );

    if ($stmt->execute()) {
        header("Location: trainer_profile.php");
        exit();
    } else {
        $error = "Update failed: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trainer Profile - Muscle Memory</title>
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
        <h1>Edit Trainer Profile</h1>
        
        <?php if(isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
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
                <label for="session_rate">Session Rate ($)</label>
                <input type="number" name="session_rate" id="session_rate" 
                       value="<?php echo htmlspecialchars($profile['session_rate'] ?? ''); ?>" required step="0.01">
            </div>

            <div class="form-group">
                <label for="consultation_type">Training Type</label>
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
                    <option value="Strength Training" <?php echo ($profile['primary_specialty'] == 'Strength Training' ? 'selected' : ''); ?>>Strength Training</option>
                    <option value="Cardio" <?php echo ($profile['primary_specialty'] == 'Cardio' ? 'selected' : ''); ?>>Cardio</option>
                    <option value="Weight Loss" <?php echo ($profile['primary_specialty'] == 'Weight Loss' ? 'selected' : ''); ?>>Weight Loss</option>
                    <option value="Yoga" <?php echo ($profile['primary_specialty'] == 'Yoga' ? 'selected' : ''); ?>>Yoga</option>
                </select>
            </div>

            <div class="form-group">
                <label>Secondary Specialties</label>
                <div class="specialty-options">
                    <?php $secondary_specs = explode(', ', $profile['secondary_specialties']); ?>
                    <label><input type="checkbox" name="secondary_specialties[]" value="Strength Training" 
                        <?php echo in_array('Strength Training', $secondary_specs) ? 'checked' : ''; ?>> Strength Training</label>
                    <label><input type="checkbox" name="secondary_specialties[]" value="Cardio"
                        <?php echo in_array('Cardio', $secondary_specs) ? 'checked' : ''; ?>> Cardio</label>
                    <label><input type="checkbox" name="secondary_specialties[]" value="Weight Loss"
                        <?php echo in_array('Weight Loss', $secondary_specs) ? 'checked' : ''; ?>> Weight Loss</label>
                    <label><input type="checkbox" name="secondary_specialties[]" value="Yoga"
                        <?php echo in_array('Yoga', $secondary_specs) ? 'checked' : ''; ?>> Yoga</label>
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