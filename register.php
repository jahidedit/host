<?php
include 'conf.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_number = $_POST['service_number'];
    $name = $_POST['name'];
    $rank = $_POST['rank'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (service_number, name, rank, phone, email, password) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $service_number, $name, $rank, $phone, $email, $password);

    if ($stmt->execute()) {
        $success = "Registration successful. You can now log in.";
    } else {
        $error = "Registration failed. Service number or email may already exist.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register to IIMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #0d47a1;
            --secondary-color: #1976d2;
            --accent-color: #4fc3f7;
            --dark-blue: #0a2e5a;
            --light-gray: #f5f5f5;
            --white: #ffffff;
            --error-color: #e53935;
            --success-color: #43a047;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: linear-gradient(rgba(0, 20, 40, 0.2), rgba(0, 20, 40, 0.3)), url('mig29bg.jpg');
            background-size: cover;
            background-position: left;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: var(--white);
        }
        
        .register-container {
            width: 90%;
            max-width: 800px;
            padding: 40px;
            background-color: rgba(13, 71, 161, 0.2);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .header h2 {
            color: var(--white);
            font-size: 2.2rem;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
        }
        
        .logo {
            height: 60px;
            margin-bottom: 15px;
            filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.7));
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-group label {
            position: absolute;
            top: -20px;
            left: 15px;
            background-color: #0c264200;
            padding: 0 8px;
            font-size: 0.85rem;
            color: var(--accent-color);
            z-index: 1;
            letter-spacing: 0.5px;
        }
        
        .input-group input, .input-group select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            /* Your existing styles */
            width: 100%;
            padding: 15px;
            font-size: 1rem;
            color: var(--white);
            background-color: rgb(30, 48, 70);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            outline: none;
            transition: all 0.3s ease;
            /* Add padding to make room for your custom icon */
            padding-right: 40px;
        }
        
        .input-group select::-ms-expand {
            display: none;
        }
        
        .input-group input:focus, .input-group select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(79, 195, 247, 0.3);
        }
        
        .input-group i {
            position: absolute;
            right: 15px;
            top: 15px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .full-width {
            grid-column: span 2;
        }
        
        button.register-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            color: var(--white);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(13, 71, 161, 0.4);
        }
        
        button.register-btn:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 71, 161, 0.6);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .login-link a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            color: var(--white);
            text-decoration: underline;
        }
        
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        
        .success {
            background-color: rgba(67, 160, 71, 0.2);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }
        
        .error {
            background-color: rgba(229, 57, 53, 0.2);
            border: 1px solid var(--error-color);
            color: var(--error-color);
        }
        
        .password-strength {
            height: 4px;
            background-color: #ddd;
            margin-top: 5px;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .full-width {
                grid-column: span 1;
            }
            
            .register-container {
                padding: 30px;
            }
            
            .header h2 {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .register-container {
                padding: 25px 20px;
                width: 95%;
            }
            
            .header h2 {
                font-size: 1.5rem;
            }
            
            .input-group input, .input-group select {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="header">
            <img src="https://upload.wikimedia.org/wikipedia/en/thumb/e/e5/Seal_of_the_Bangladesh_Air_Force_%28BAF%29.svg/1200px-Seal_of_the_Bangladesh_Air_Force_%28BAF%29.svg.png" alt="BAF Logo" class="logo">
            <h2>IIMS Registration</h2>
            <p>Integrated Inventory Management System</p>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" class="register-form">
            <div class="form-grid">
                <div class="input-group">
                    <label for="service_number">Service Number</label>
                    <input type="text" id="service_number" name="service_number" required>
                    <i class="fas fa-id-card"></i>
                </div>
                
                <div class="input-group">
                    <label for="rank">Rank</label>
                    <select id="rank" name="rank" required>
                        <option value="" disabled selected>Select Rank</option>
                        
                        <option value="AC-2">AC-2</option>
                        <option value="AC-1">AC-1</option>
                        <option value="LAC">LAC</option>
                        <option value="Corporal">Corporal</option>
                        <option value="SGT">SGT</option>
                        <option value="WO">WO</option>
                        <option value="SWO">SWO</option>
                        <option value="MWO">MWO</option>
                        
                    </select>
                    <i class="fas fa-chevron-down"></i>
                </div>
                
                <div class="input-group full-width">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                    <i class="fas fa-user"></i>
                </div>
                
                <div class="input-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                    <i class="fas fa-phone"></i>
                </div>
                
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                    <i class="fas fa-envelope"></i>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <i class="fas fa-lock"></i>
                    <div class="password-strength">
                        <div class="strength-bar" id="strength-bar"></div>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <button type="submit" class="register-btn">Register Account</button>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </form>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strength-bar');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check for length
            if (password.length >= 8) strength += 1;
            
            // Check for uppercase letters
            if (/[A-Z]/.test(password)) strength += 1;
            
            // Check for numbers
            if (/[0-9]/.test(password)) strength += 1;
            
            // Check for special characters
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Update strength bar
            const width = strength * 25;
            let color;
            
            if (strength <= 1) {
                color = '#e53935'; // Red
            } else if (strength <= 2) {
                color = '#fb8c00'; // Orange
            } else if (strength <= 3) {
                color = '#fdd835'; // Yellow
            } else {
                color = '#43a047'; // Green
            }
            
            strengthBar.style.width = width + '%';
            strengthBar.style.backgroundColor = color;
        });
        
        // Confirm password validation
        const confirmPassword = document.getElementById('confirm_password');
        
        confirmPassword.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity("Passwords don't match");
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>