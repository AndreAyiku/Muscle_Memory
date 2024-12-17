<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../view/login.php");
    exit();
}

// Database connection
require_once '../db/config.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $workout_name = mysqli_real_escape_string($conn, $_POST['workout_name']);
    $workout_type = mysqli_real_escape_string($conn, $_POST['workout_type']);
    $difficulty_level = mysqli_real_escape_string($conn, $_POST['difficulty_level']);
    $duration = intval($_POST['duration']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $equipment_needed = isset($_POST['equipment_needed']) ? 
        mysqli_real_escape_string($conn, implode(', ', $_POST['equipment_needed'])) : '';
    $target_muscles = isset($_POST['target_muscles']) ? 
        mysqli_real_escape_string($conn, implode(', ', $_POST['target_muscles'])) : '';
    
    // Validate inputs
    $errors = [];
    if (empty($workout_name)) $errors[] = "Workout name is required";
    if (empty($workout_type)) $errors[] = "Workout type is required";
    if ($duration <= 0) $errors[] = "Duration must be positive";
    if (empty($description)) $errors[] = "Description is required";

    // If no errors, insert into database
    if (empty($errors)) {
        $query = "INSERT INTO MM_Workouts (
            user_id,
            workout_name,
            category,
            difficulty_level,
            duration,
            description,
            equipment_needed,
            muscle_groups_targeted,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "isssssss", 
            $_SESSION['user_id'],
            $workout_name,
            $workout_type,
            $difficulty_level,
            $duration,
            $description,
            $equipment_needed,
            $target_muscles
        );

        if ($stmt->execute()) {
            header("Location: workouts.php");
            exit();
        } else {
            $errors[] = "Error creating workout: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Workout - Muscle Memory</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Slab:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Josefin Slab', serif;
            background: linear-gradient(135deg, #4B0082, #800080);
            color: #fff;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
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

        .form-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 600px;
            backdrop-filter: blur(10px);
        }

        h1 {
            text-align: center;
            color: #fff;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #fff;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }

        button {
            background: linear-gradient(45deg, #800080, #4B0082);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 20px;
            transition: transform 0.3s ease;
        }

        button:hover {
            transform: translateY(-2px);
        }

        .error {
            color: #ff4444;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
<div id="bubble-container" class="bubble-container"></div>


    <div class="form-container">
        <h1>Create New Workout</h1>
        
        <?php if(!empty($errors)): ?>
            <div class="error">
                <?php foreach($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="workout_name">Workout Name</label>
                <input type="text" name="workout_name" id="workout_name" required>
            </div>

            <div class="form-group">
                <label for="workout_type">Workout Type</label>
                <select name="workout_type" id="workout_type" required>
                    <option value="">Select Type</option>
                    <option value="Strength">Strength Training</option>
                    <option value="Cardio">Cardio</option>
                    <option value="HIIT">HIIT</option>
                    <option value="Yoga">Yoga</option>
                    <option value="CrossFit">CrossFit</option>
                </select>
            </div>

            <div class="form-group">
                <label for="difficulty_level">Difficulty Level</label>
                <select name="difficulty_level" id="difficulty_level" required>
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Advanced">Advanced</option>
                </select>
            </div>

            <div class="form-group">
                <label for="duration">Duration (minutes)</label>
                <input type="number" name="duration" id="duration" required min="1">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" required rows="4"></textarea>
            </div>

            <div class="form-group">
                <label>Equipment Needed</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="equipment_needed[]" value="Dumbbells"> Dumbbells</label>
                    <label><input type="checkbox" name="equipment_needed[]" value="Barbell"> Barbell</label>
                    <label><input type="checkbox" name="equipment_needed[]" value="Resistance Bands"> Resistance Bands</label>
                    <label><input type="checkbox" name="equipment_needed[]" value="Yoga Mat"> Yoga Mat</label>
                    <label><input type="checkbox" name="equipment_needed[]" value="Kettlebell"> Kettlebell</label>
                    <label><input type="checkbox" name="equipment_needed[]" value="None"> No Equipment</label>
                </div>
            </div>

            <div class="form-group">
                <label>Target Muscle Groups</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="target_muscles[]" value="Chest"> Chest</label>
                    <label><input type="checkbox" name="target_muscles[]" value="Back"> Back</label>
                    <label><input type="checkbox" name="target_muscles[]" value="Legs"> Legs</label>
                    <label><input type="checkbox" name="target_muscles[]" value="Arms"> Arms</label>
                    <label><input type="checkbox" name="target_muscles[]" value="Core"> Core</label>
                    <label><input type="checkbox" name="target_muscles[]" value="Full Body"> Full Body</label>
                </div>
            </div>

            <button type="submit">Create Workout</button>
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