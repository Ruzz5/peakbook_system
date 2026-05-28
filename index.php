<?php
// Login Page
session_start();
// Redirect to dashboard if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeakBook – Log In</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- paths are relative to project root -->
</head>
<body>

<div class="auth-page">

    <div class="auth-mountain">
        <img src="images/mountain.jpg" alt="Mountain">
        <div class="auth-mountain-overlay">
            <p class="auth-tagline">Pages as endless<br>as the Peak</p>
        </div>
    </div>

<!-- login form -->
    <div class="auth-panel">
        <h1 class="auth-logo">Peak<span>Book</span></h1>
        <p class="auth-subtitle">Book Inventory Management System</p>

<!-- error alert -->
        <div class="alert alert-error" id="loginAlert"></div>

        <form class="auth-form" id="loginForm" novalidate method="POST">
            <div class="form-group">
                <input type="email" name="email" class="form-control"
                       placeholder="Email Address" required autocomplete="email">
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control"
                       placeholder="Password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-outline btn-full">Log In</button>
            <button type="button" class="btn btn-primary btn-full"
                    onclick="window.location.href='pages/register.php'">
                Create an Account
            </button>
        </form>
    </div>

</div>

<script src="js/main.js"></script>
</body>
</html>