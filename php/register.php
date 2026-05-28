<?php
require_once 'config.php';

header('Content-Type: application/json');
session_status() === PHP_SESSION_NONE && session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$email     = trim($_POST['email'] ?? '');
$firstname = trim($_POST['firstname'] ?? '');
$lastname  = trim($_POST['lastname'] ?? '');
$password  = trim($_POST['password'] ?? '');

if (empty($email) || empty($firstname) || empty($lastname) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

$conn = getConnection();

$stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Email address is already registered.']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare('INSERT INTO users (email, firstname, lastname, password) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $email, $firstname, $lastname, $hash);
$success = $stmt->execute();

if (!$success) {
    echo json_encode(['success' => false, 'error' => 'Unable to create account.']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true]);