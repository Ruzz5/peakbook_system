<?php
// login Handler

require_once 'config.php';
session_status() === PHP_SESSION_NONE && session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

// Basic validation
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Email and password are required.']);
    exit;
}

$conn = getConnection();

// Fetch user by email
$stmt = $conn->prepare("SELECT user_id, firstname, lastname, password FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

// Verify password hash
if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
    $conn->close();
    exit;
}

// Set session variables
$_SESSION['user_id']   = $user['user_id'];
$_SESSION['firstname'] = $user['firstname'];
$_SESSION['lastname']  = $user['lastname'];

$conn->close();
echo json_encode(['success' => true]);