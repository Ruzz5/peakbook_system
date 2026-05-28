<?php
session_status() === PHP_SESSION_NONE && session_start();

/**
 * Redirect to login if user is not authenticated.
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ../index.php'); // kani ang issue.
        exit;
    }
}

/**
 * Returns the currently logged-in user's display name.
 */
function currentUser(): string {
    return $_SESSION['firstname'] ?? 'User';
}