// Bubble Creation Function
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

// User Dropdown Toggle
function toggleUserDropdown() {
    const dropdown = document.getElementById('user-dropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
function setupDropdownClose() {
    window.addEventListener('click', function(e) {
        const dropdown = document.getElementById('user-dropdown');
        const userIcon = document.querySelector('.user-icon');
        
        if (dropdown.classList.contains('show') && 
            !dropdown.contains(e.target) && 
            e.target !== userIcon) {
            dropdown.classList.remove('show');
        }
    });
}

// Music Player Setup
function setupMusicPlayer() {
    const audioToggle = document.createElement('button');
    audioToggle.id = 'audio-toggle';
    audioToggle.className = 'audio-toggle';
    audioToggle.innerHTML = '<i class="bx bx-volume-full"></i>';
    
    const audioControls = document.createElement('div');
    audioControls.className = 'audio-controls';
    audioControls.style.position = 'fixed';
    audioControls.style.bottom = '20px';
    audioControls.style.right = '20px';
    audioControls.style.zIndex = '1000';
    audioControls.appendChild(audioToggle);
    
    document.body.appendChild(audioControls);

    const audioTracks = [
        '../assests/songs/song1.mp3',
        '../assests/songs/song2.mp3',
        '../assests/songs/song3.mp3',
        '../assests/songs/song4.mp3'
    ];

    const musicTracks = audioTracks.map(src => {
        const audio = document.createElement('audio');
        audio.src = src;
        audio.classList.add('music-track');
        document.body.appendChild(audio);
        return audio;
    });

    let currentTrackIndex = 0;
    let isMuted = false;
    let isPlaying = true;

    function playNextTrack() {
        musicTracks[currentTrackIndex].pause();
        musicTracks[currentTrackIndex].currentTime = 0;
        currentTrackIndex = (currentTrackIndex + 1) % musicTracks.length;

        if (!isMuted && isPlaying) {
            musicTracks[currentTrackIndex].play()
                .catch(error => console.log("Error playing track:", error));
        }
    }

    musicTracks.forEach(track => {
        track.addEventListener('ended', playNextTrack);
        track.volume = 0.5;
    });

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

    startMusic();
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    createBubbles();
    setupDropdownClose();
    setupMusicPlayer();
});