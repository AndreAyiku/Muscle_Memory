<?php
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muscle Memory - Fitness Tracking</title>
    <link rel="icon" type="image/x-icon" href="../Muscle_Memory/assests/images/dumbell.ico">
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
            
        }
        .video-background {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: -1;
            background-color: black;
            opacity: 1; /* Slightly reduce opacity for better readability */
        }
        
        header {
            background-color: rgba(30, 0, 50, 0.9); /* Deep purple with opacity */
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
            background-color: rgba(128, 0, 128, 0.5); /* Transparent purple */
            border-radius: 50%;
            animation: float-up linear infinite;
            box-shadow: 0 0 20px rgba(128, 0, 128, 0.3); /* Softer purple glow */
            z-index: 1001; /* Ensure bubbles are on top */
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
            background: linear-gradient(45deg, #800080, #4B0082); /* Purple gradient */
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
            background: rgba(128, 0, 128, 0.2); /* Purple hover effect */
            transform: translateY(-2px);
            color: #800080;
        }
        
        .main-content {
            display: flex;
            align-items: center;
            height: calc(100vh - 100px);
            padding: 0 4rem;
        }
        
        .text-content {
            max-width: 600px;
        }
        
        .text-content h1 {
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            font-family: 'Josefin Slab', serif;
            background: linear-gradient(45deg, #800080, #4B0082);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .text-content h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-family: 'Josefin Slab', serif;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .text-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
            font-family: 'Josefin Slab', serif;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .cta-button {
            display: inline-block;
            color: #fff;
            background: linear-gradient(45deg, #800080, #4B0082);
            color: #fff;
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-family: 'Josefin Slab', serif;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(128, 0, 128, 0.4);
        }
        
        .socials {
            position: absolute;
            bottom: 20px;
            left: 20px;
            display: flex;
            gap: 15px;
            z-index: 1;
        }
        
        .socials a {
            color: rgba(255, 255, 255, 0.8);
            font-size: 2rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .socials a:hover {
            color: #800080;
            transform: translateY(-2px);
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
        .main-content {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            height: calc(100vh - 100px);
            padding: 0 4rem;
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
<video autoplay loop muted playsinline class="video-background">
        <source src="../Muscle_Memory/assests/images/workout.mp4" type="video/mp4">
    </video>
<div id="bubble-container" class="bubble-container"></div>
<audio id="track1" class="music-track">
        <source src="../Muscle_Memory/assests/songs/song1.mp3" type="audio/mpeg">
    </audio>
    <audio id="track2" class="music-track">
        <source src="../Muscle_Memory/assests/songs/song2.mp3" type="audio/mpeg">
    </audio>
    <audio id="track3" class="music-track">
        <source src="../Muscle_Memory/assests/songs/song3.mp3" type="audio/mpeg">
    </audio>
    <audio id="track4" class="music-track">
        <source src="../Muscle_Memory/assests/songs/song4.mp3" type="audio/mpeg">
    </audio>
    <div class="audio-controls">
        <button id="audio-toggle" class="audio-toggle">
            <i class='bx bx-volume-full'></i>
        </button>
    </div>
<header>
        <div class="logo">
            <a href="../Muscle_Memory/index.php">Muscle Memory</a>
        </div>
        <nav>
        <ul>
                <li><a href="../Muscle_Memory/view/home.php">Home</a></li>
                <li><a href="../Muscle_Memory/view/workouts.php">Workouts</a></li>
                <li><a href="../Muscle_Memory/view/trainers.php">Trainers</a></li>
                <li><a href="../Muscle_Memory/view/nutritionist.php">Nutritionists</a></li>
  
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
                        echo '<a href="../Muscle_Memory/view/admin/admin_dashboard.php">Admin Dashboard</a>';
                    } else {
                        // Check user type for appropriate dashboard
                        switch($_SESSION['user_type']) {
                            case 'trainer':
                                echo '<a href="../Muscle_Memory/view/admin/trainer_dashboard.php">Trainer Dashboard</a>';
                                break;
                            case 'nutritionist':
                                echo '<a href="../Muscle_Memory/view/admin/nutritionist_dashboard.php">Nutritionist Dashboard</a>';
                                break;
                            default: // 'regular' or any other type
                                echo '<a href="../Muscle_Memory/view/admin/user_dashboard.php">User Dashboard</a>';
                                break;
                        }
                    }
                    // Logout option always present for logged-in users
                    echo '<a href="../Muscle_Memory/actions/logout.php">Logout</a>';
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

    <div class="main-content">
        <div class="text-content">
            <h1>CONNECT WITH EXPERTS</h1>
            <h2>JOIN OUR FITNESS COMMUNITY</h2>
            <p>Welcome to Muscle Memory, your ultimate platform to connect with fitness instructors, nutritionists, and fellow fitness enthusiasts. Share your workout plans, get expert advice, and stay motivated on your fitness journey. Join us and transform your fitness experience!</p>
            <a href="../Muscle_Memory/view/workouts.php" class="cta-button">Get Started</a>
        </div>
    </div>

    <div class="socials">
        <a href="https://www.instagram.com/muscle_memory" target="_blank"><i class='bx bxl-instagram'></i></a>
        <a href="https://twitter.com/MuscleMemoryApp" target="_blank"><i class='bx bxl-twitter'></i></a>
        <a href="https://snapchat.com/t/MuscleMemoryFitness" target="_blank"><i class='bx bxl-snapchat'></i></a>
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
    document.addEventListener('DOMContentLoaded', () => {
        const audioToggle = document.getElementById('audio-toggle');
        const musicTracks = Array.from(document.getElementsByClassName('music-track'));
        
        let currentTrackIndex = 0;
        let isMuted = false;
        let isPlaying = true;

        // Function to play the next track
        function playNextTrack() {
            musicTracks[currentTrackIndex].pause();
            musicTracks[currentTrackIndex].currentTime = 0;
            currentTrackIndex = (currentTrackIndex + 1) % musicTracks.length;

            if (!isMuted && isPlaying) {
                musicTracks[currentTrackIndex].play()
                    .catch(error => console.log("Error playing track:", error));
            }
        }

        // Setup event listeners for track ending
        musicTracks.forEach(track => {
            track.addEventListener('ended', playNextTrack);
            track.volume = 0.5;
        });

        // Initial play
        function startMusic() {
            isPlaying = true;
            if (!isMuted) {
                musicTracks[currentTrackIndex].play()
                    .catch(error => {
                        console.log("Autoplay was prevented:", error);
                        document.addEventListener('click', () => {
                            if (!isPlaying) startMusic();
                        }, { once: true });
                    });
            }
        }

        // Audio toggle button
        audioToggle.addEventListener('click', () => {
            isMuted = !isMuted;
            
            if (isMuted) {
                musicTracks[currentTrackIndex].pause();
                audioToggle.innerHTML = '<i class="bx bx-volume-mute"></i>';
                isPlaying = false;
            } else {
                startMusic();
                audioToggle.innerHTML = '<i class="bx bx-volume-full"></i>';
            }
        });

        // Start music when page loads
        startMusic();
    });
    </script>
</body>
</html>