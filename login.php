<?php
session_start();
include 'conf.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $error = "";
    
    // Validate and sanitize input
    $service_number = filter_input(INPUT_POST, 'service_number', FILTER_SANITIZE_STRING);
    $password = $_POST['password']; // Don't sanitize passwords
    
    if (empty($service_number) || empty($password)) {
        $error = "Service number and password are required.";
    } else {
        $sql = "SELECT * FROM users WHERE service_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $service_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                $_SESSION['logged'] = "true";
                $_SESSION['user'] = $user['service_number'];
                $_SESSION['last_activity'] = time();
                
                header("Location: profile.php");
                exit;
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Service number not found.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=900">
    <title>Login profile</title>
<link rel="apple-touch-icon" sizes="76x76" href="favicon.png">
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Arial', sans-serif;
    }
    
    body {
      height: 93vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background-image: url(mig29bg.jpg);
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      position: relative;
    }
    .forgot-password {
      text-align: right;
      margin-top: 10px;
    }
    
    .forgot-password a {
      color: #4fc3f7;
      text-decoration: none;
      font-size: 15px;
      transition: color 0.3s;
    }
    
    .forgot-password a:hover {
      color: #32a12e;
      text-decoration: underline;
    }
/*------------logo css------------*/

    .logo {
    	z-index: 10;
    	position: relative;
    	margin: 10px 0px;
    }
    
    .logo img {
              height: 120px;
              
            }
    
    /*------------login card css------------*/
    
    .card {
        width: 82% !important;
        margin-left: 3rem;
        margin-top: 1.1rem;
    	border-radius: 10px;
    	overflow: hidden;
    	border: none;
    	box-shadow: 0px 0px 8px 0px rgba(206, 89, 35, 0.2);
    }
    
    /*------------login card left css------------*/
    
    .login-form {
    	padding: 20px 20px;
    }
    h1 {
    	color: #000;
    	font-size: 1.2rem;
    	margin-bottom: 25px;
    }
    h2 {
    	font-family: 'Kalpurush', sans-serif;
    	font-size: 1.87rem;
    	color: #fff;
    	position: relative;
    	z-index: 10;
    }
    h4{
    	font-size: 1rem;
    	color: #fff;
    	position: relative;
    	z-index: 10;
    }
    label {
    	color: #637280;
    	font-size: 0.87rem;
    }
    .form-control {
    	border-radius: 0;
    	border: 1px solid transparent;
    	border-bottom-color: #ddd;
    	padding: 0;
    	font-size: 1rem;
    }
    .form-control:focus {
    	color: #000;
    	background-color: #fff;
    	border-color: transparent;
    	border-bottom-color: #000;
    	outline: 0;
    	box-shadow: 0 0 0 0.2rem rgba(0,123,255,.0);
    }
    .btn {
    	background-color: #3696ed;
    }
    .card-right {
    	background-image: url(conbg.jpg);
    	background-repeat: no-repeat;
    	background-size: cover;
    }
    .card-right::before {
    	content: "";
    	display: block;
    	width: 100%;
    	height: 100%;
    	background-color: #105ea5;
    	opacity: 0.75;
    	position: absolute;
    	top: 0;
    	left: 0;
    	z-index: 0;
    }
    
    /*animation text*/
    
    .at-item {
        color: #3079ed;
        animation-name: focus-in-expand-top;
        animation-duration: 1s;
        animation-timing-function: ease;
        animation-delay: 0s;
        animation-iteration-count: 1;
        animation-direction: normal;
        animation-fill-mode: none;
    }
    @keyframes focus-in-expand-top {
        0%{
            letter-spacing: -.5em;
            -webkit-transform: translateY(-300px);
            transform: translateY(-300px);
            filter: blur(12px);
            opacity: 0;
        }
        100%{
            -webkit-transform: translateY(0);
            transform: translateY(0);
            filter: blur(0);
            opacity: 1;
        }
  </style>
</head>
<body>
  
<div class="container">
    <div class="logo text-center">
        <a href="index.php">
            <img src="https://upload.wikimedia.org/wikipedia/en/thumb/e/e5/Seal_of_the_Bangladesh_Air_Force_%28BAF%29.svg/1200px-Seal_of_the_Bangladesh_Air_Force_%28BAF%29.svg.png" alt="BAF Logo">
        </a>
        
        
    </div>
    <h4 class="text-center text-white">Item Issue Management System (IIMS)</h4>

    <div class="row">
        <div class="col-xl-6 col-lg-8 mx-auto">
            <div class="card">
                <div class="row no-gutturs">
                    <div class="col-sm-7 order-2 order-sm-2">
                        <div class="login-form">
                            <h1>SIGN IN</h1>
                                                        <form method="post">
                                <input type="hidden" name="_token" value="B2L2ezIjfqWujy0tfwPxEzXitQRuDf5SqrEUg8H2">                                <div class="form-group">
                                    <label for="service_number">Service Number</label>
                                    <input id="service_number" type="text" class="form-control" name="service_number" value="" autofocus="" required="">
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input id="password" type="password" class="form-control" name="password" required="">                                              </div>
                                <div class="d-flex justify-content-around align-items-center">
                                    <button type="submit" class="btn btn-info text-uppercase">Sign In</button>
</div>
</form>
  <div class="forgot-password">       
<p style="margin-top: 2px; margin-bottom: 0px;"><a href="register.php">No Account? Register</a>
                                </div>                          
                        </div>
                    </div>
                    <div class="col-sm-5 card-right order-1 order-sm-2">
                        <div class="py-5 pr-md-3 d-flex d-sm-block justify-content-center">
                            <h2 style="font-size: 20px;" class="text-left">বাংলার আকাশ </h2>
                            <h2 style="font-size: 20px;" class="text-right ml-2"> রাখিব মুক্ত</h2>
                            <div id="userInfo" class="d-none bg-success p-1 mt-3">
                                <h4 style="font-size: 12px;"><strong>Name : </strong><span id="name"></span></h4>
                                <h4 style="font-size: 12px;"><strong>Personnel Type : </strong><span id="personnel_type"></span></h4>
                                <h4 style="font-size: 12px;"><strong>Rank : </strong><span id="rank"></span></h4>
                            </div>
                            <h4 id="invalidUser" class="d-none bg-danger mt-5 p-3">
                                Invalid User
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- row ends -->
</div> <!-- container ends -->

<!-- JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Basic form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const serviceNumber = document.getElementById('service_number').value.trim();
        const password = document.getElementById('password').value.trim();
        
        if (!serviceNumber || !password) {
            e.preventDefault();
            alert('Please fill in all fields');
        }
    });
</script>
</body>
</html>
