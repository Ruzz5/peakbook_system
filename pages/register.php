<?php
// Register Page
session_status() === PHP_SESSION_NONE && session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeakBook – Create Account</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="auth-page">

    <!-- Left: Mountain image -->
    <div class="auth-mountain">
        <img src="../images/mountain.jpg">
        <div class="auth-mountain-overlay">
            <p class="auth-tagline">Pages as endless<br>as the Peak</p>
        </div>
    </div>

    <!-- Right: Registration form -->
    <div class="auth-panel">
        <h1 class="auth-logo">Create an Account</h1>
        <p class="auth-subtitle">PeakBook</p>

        <!-- Status alert -->
        <div class="alert" id="registerAlert"></div>

        <form class="auth-form" id="registerForm" method="POST" novalidate>
            <div class="form-group">
                <input type="email" name="email" class="form-control"
                       placeholder="Email Address" required autocomplete="email">
            </div>
            <div class="auth-row">
                <input type="text" name="firstname" class="form-control"
                       placeholder="First name" required>
                <input type="text" name="lastname" class="form-control"
                       placeholder="Last name" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control"
                       placeholder="Password" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary btn-full">Sign Up</button>
            <p class="auth-link">
                Already have an account? <a href="../index.php">Log In</a>
            </p>
        </form>
    </div>

</div>

<script src="../js/main.js"></script>
</body>
</html>