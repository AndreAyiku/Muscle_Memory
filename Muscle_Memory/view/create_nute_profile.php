<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is a nutritionist
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'nutritionist') {
    header("Location: ../view/login.php");
    exit();
}

// Database connection
require_once '../db/config.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $years_experience = intval($_POST['years_experience']);
    $certifications = mysqli_real_escape_string($conn, $_POST['certifications']);
    $consultation_rate = floatval($_POST['consultation_rate']);
    $consultation_type = mysqli_real_escape_string($conn, $_POST['consultation_type']);
    $availability_hours = mysqli_real_escape_string($conn, $_POST['availability_hours']);
    $primary_specialty = mysqli_real_escape_string($conn, $_POST['primary_specialty']);
    $secondary_specialties = isset($_POST['secondary_specialties']) ? 
        mysqli_real_escape_string($conn, implode(', ', $_POST['secondary_specialties'])) : '';
    
    // Validate inputs
    $errors = [];
    if (empty($specialization)) $errors[] = "Specialization is required";
    if ($years_experience < 0) $errors[] = "Years of experience must be non-negative";
    if (empty($certifications)) $errors[] = "Certifications are required";
    if ($consultation_rate <= 0) $errors[] = "Consultation rate must be positive";
    if (empty($consultation_type)) $errors[] = "Consultation type is required";

    // If no errors, insert into database
    if (empty($errors)) {
        $query = "INSERT INTO MM_Nutritionist (
            user_id, 
            specialization, 
            years_of_experience, 
            certifications, 
            consultation_rate, 
            consultation_type, 
            availability_hours, 
            primary_specialty, 
            secondary_specialties
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "isisssiss", 
            $_SESSION['user_id'],
            $specialization,
            $years_experience,
            $certifications,
            $consultation_rate,
            $consultation_type,
            $availability_hours,
            $primary_specialty,
            $secondary_specialties
        );

        if ($stmt->execute()) {
            header("Location: ../view/admin/nutritionist_dashboard.php");
            exit();
        } else {
            $errors[] = "Error creating profile: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Nutritionist Profile - Muscle Memory</title>
    <link rel="icon" type="image/x-icon" href="../../assests/images/dumbell.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Slab:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Josefin Slab', serif;
            background-color: #4B0082;
            color: #fff;
            margin: 0;
            padding: 20px;
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
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            color: #fff;
        }

        .submit-btn {
            display: inline-block;
            background: linear-gradient(45deg, #800080, #4B0082);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
        }

        .error-message {
            color: #ff4444;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div id="bubble-container" class="bubble-container"></div>


    <div class="profile-form-container">
        <h1>Create Your Nutritionist Profile</h1>
        
        <?php
        // Display errors if any
        if (!empty($errors)) {
            echo "<div class='error-message'>";
            foreach ($errors as $error) {
                echo "<p>$error</p>";
            }
            echo "</div>";
        }
        ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="specialization">Specialization</label>
                <input type="text" name="specialization" id="specialization" required placeholder="e.g., Sports Nutrition, Weight Management">
            </div>

            <div class="form-group">
                <label for="years_experience">Years of Experience</label>
                <input type="number" name="years_experience" id="years_experience" required min="0" max="50">
            </div>

            <div class="form-group">
                <label for="certifications">Certifications</label>
                <textarea name="certifications" id="certifications" required placeholder="List your professional certifications"></textarea>
            </div>

            <div class="form-group">
                <label for="consultation_rate">Consultation Rate ($)</label>
                <input type="number" name="consultation_rate" id="consultation_rate" required step="0.01" min="0">
            </div>

            <div class="form-group">
                <label for="consultation_type">Consultation Type</label>
                <select name="consultation_type" id="consultation_type" required>
                    <option value="online">Online Only</option>
                    <option value="in-person">In-Person Only</option>
                    <option value="both">Both Online and In-Person</option>
                </select>
            </div>

            <div class="form-group">
                <label for="availability_hours">Availability Hours</label>
                <input type="text" name="availability_hours" id="availability_hours" required placeholder="e.g., Monday-Friday: 9am-5pm">
            </div>

            <div class="form-group">
                <label for="primary_specialty">Primary Specialty</label>
                <select name="primary_specialty" id="primary_specialty" required>
                    <option value="">Select Primary Specialty</option>
                    <option value="Weight Loss">Weight Loss</option>
                    <option value="Sports Nutrition">Sports Nutrition</option>
                    <option value="Muscle Gain">Muscle Gain</option>
                    <option value="Vegan Nutrition">Vegan Nutrition</option>
                </select>
            </div>

            <div class="form-group">
                <label>Secondary Specialties (Optional)</label>
                <div class="specialty-options">
                    <label><input type="checkbox" name="secondary_specialties[]" value="Weight Loss"> Weight Loss</label>
                    <label><input type="checkbox" name="secondary_specialties[]" value="Sports Nutrition"> Sports Nutrition</label>
                    <label><input type="checkbox" name="secondary_specialties[]" value="Muscle Gain"> Muscle Gain</label>
                    <label><input type="checkbox" name="secondary_specialties[]" value="Vegan Nutrition"> Vegan Nutrition</label>
                </div>
            </div>

            <button type="submit" class="submit-btn">Create Profile</button>
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