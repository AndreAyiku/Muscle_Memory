<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up Page</title>
    <link rel="icon" type="image/x-icon" href="../assests/images/dumbell.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            margin: 0 auto;
            padding: 0;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url('../assests/images/image6.jpg') no-repeat;
            background-size: cover;
        }
        .wrapper {
        position: relative;
        width: 500px;
        background: rgba(30, 0, 50, 0.9);
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 0 20px rgba(128, 0, 128, 0.3);
        color: #fff;
    }
        .wrapper h1 {
            text-align: center;
            color: white;
        }
        .input-box {
            position: relative;
            margin-bottom: 10px;
        }
        .input-box input {
        width: 100%;
        padding: 10px 35px 10px 10px;
        border-radius: 90px;
        border: 1px solid rgba(128, 0, 128, 0.2);
        box-sizing: border-box;
        background: #000;
        color: #fff;
    }
        .input-box i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #800080;

        }
        .wrapper .button {
            width: 100%;
            border-radius: 90px;
            font-size: 19px;
            padding: 10px;
            border: none;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            margin-top: 10px;
        }
        .user-type-container input[type="radio"]:checked + label {
        background: linear-gradient(45deg, #800080, #4B0082);
    }
    
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: white;
        }
        .error {
            color: #ff4500;
            font-size: 12px;
            margin-top: 5px;
            text-align: center;
        }
        .profile-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 15px;
        }
        .profile-upload input[type="file"] {
            display: none;
        }
        .profile-upload label {
            background-color: #2196F3;
            color: white;
            padding: 10px 15px;
            border-radius: 90px;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .profile-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            display: none;
            margin-bottom: 10px;
        }
        a {
            color: #87CEFA;
            text-decoration: none;
        }
        .user-type-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .user-type-container label {
            flex: 1;
            text-align: center;
            padding: 10px;
            margin: 0 5px;
            background-color: rgba(255,255,255,0.2);
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .user-type-container input[type="radio"] {
            display: none;
        }
        .user-type-container input[type="radio"]:checked + label {
            background-color: #4CAF50;
        }
        button[type="submit"] {
        background: linear-gradient(45deg, #800080, #4B0082);
        color: white;
        transition: all 0.3s ease;
    }
    button[type="submit"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(128, 0, 128, 0.4);
    }
    </style>
</head>
<body>
    <div class="wrapper">
        <h1>Sign Up</h1>
        <form id="signupForm" action="../actions/sign-up_user.php" method="POST" enctype="multipart/form-data">
            <div class="user-type-container">
                <input type="radio" id="regular" name="user_type" value="regular" required>
                <label for="regular">Regular User</label>
                
                <input type="radio" id="trainer" name="user_type" value="trainer">
                <label for="trainer">Trainer</label>
                
                <input type="radio" id="nutritionist" name="user_type" value="nutritionist">
                <label for="nutritionist">Nutritionist</label>
            </div>
            <div id="userTypeError" class="error"></div>

            <div class="profile-upload">
                <img id="profilePreview" class="profile-preview" src="" alt="Profile Preview">
                <input type="file" id="profilePicture" name="profile_picture" accept="image/*">                
                <label for="profilePicture">
                    <i class='bx bx-upload'></i> Upload Profile Picture
                </label>
            </div>

            <div class="input-box">
                <input type="text" id="first_name" placeholder="First Name" name="first_name" required>
                <i class='bx bx-user-circle'></i>
                <div id="fnameError" class="error"></div>
            </div>
            <div class="input-box">
                <input type="text" id="last_name" placeholder="Last Name" name="last_name" required>
                <i class='bx bx-user-circle'></i>
                <div id="lnameError" class="error"></div>
            </div>
            <div class="input-box">
                <input type="text" id="username" placeholder="Username" name="username" required>
                <i class='bx bx-user-circle'></i>
                <div id="usernameError" class="error"></div>
            </div>

            <div class="input-box">
                <input type="email" id="email" placeholder="Email" name="email" required>
                <i class='bx bxs-envelope'></i>
                <div id="emailError" class="error"></div>
            </div>

            <div class="input-box">
                <input type="password" id="password" placeholder="Password" name="password" required>
                <i class='bx bx-lock'></i>
                <div id="passwordError" class="error"></div>
            </div>

            <div class="input-box">
                <input type="password" id="confirm_password" placeholder="Confirm Password" name="confirm_password" required>
                <i class='bx bx-lock-open'></i>
                <div id="confirmPasswordError" class="error"></div>
            </div>

            <button type="submit" class="button">Sign Up</button>

            <div class="login-link">
                Already have an account? <a href="../view/login.php">Login</a>
            </div>
        </form>
    </div>

    <script>
        // Profile Picture Preview
        document.getElementById('profilePicture').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('profilePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Form Validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const fname = document.getElementById('first_name').value;
            const lname = document.getElementById('last_name').value;
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password').value;
            const userType = document.querySelector('input[name="user_type"]:checked');
            let isValid = true;
            
            // User Type Validation
            if (!userType) {
                document.getElementById('userTypeError').textContent = 'Please select a user type.';
                isValid = false;
            } else {
                document.getElementById('userTypeError').textContent = '';
            }
            
            // First Name Validation
            if (fname.length < 2) {
                document.getElementById('fnameError').textContent = 'First name must be at least 2 characters long.';
                isValid = false;
            } else {
                document.getElementById('fnameError').textContent = '';
            }
            
            // Last Name Validation
            if (lname.length < 2) {
                document.getElementById('lnameError').textContent = 'Last name must be at least 2 characters long.';
                isValid = false;
            } else {
                document.getElementById('lnameError').textContent = '';
            }
            
            // Username Validation
            if (username.length < 3) {
                document.getElementById('usernameError').textContent = 'Username must be at least 3 characters long.';
                isValid = false;
            } else {
                document.getElementById('usernameError').textContent = '';
            }
            
            // Email Validation
            if (!validateEmail(email)) {
                document.getElementById('emailError').textContent = 'Please enter a valid email address.';
                isValid = false;
            } else {
                document.getElementById('emailError').textContent = '';
            }
            
            // Password Validation
            if (!CheckPassword(password)) {
                document.getElementById('passwordError').textContent = 'Password must be 6-20 characters long, contain at least one digit, one uppercase and one lowercase letter.';
                isValid = false;
            } else {
                document.getElementById('passwordError').textContent = '';
            }
            
            // Confirm Password Validation
            if (password.value !== confirmPassword) {
                document.getElementById('confirmPasswordError').textContent = 'Passwords do not match.';
                isValid = false;
            } else {
                document.getElementById('confirmPasswordError').textContent = '';
            }
            
            if (isValid) {
                this.submit();
            }
        });

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function CheckPassword(inputtxt) { 
            var passw = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/; 
            return inputtxt.value.match(passw) !== null;
        }
    </script>
</body>
</html>