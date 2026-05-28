<?php
// ============================================================
// PeakBook - Logout Handler
// ============================================================

session_status() === PHP_SESSION_NONE && session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging out...</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #0d0d0f;
            color: #e8e8ef;
            font-family: 'DM Sans', sans-serif;
        }
        .logout-message {
            text-align: center;
        }
    </style>
</head>
<body>
<div class="logout-message">
    <p>Logging out...</p>
</div>

<script>
    // Signal logout to other tabs IMMEDIATELY
    try {
        if (typeof BroadcastChannel !== 'undefined') {
            const logoutChannel = new BroadcastChannel('peakbook_logout');
            logoutChannel.postMessage('logout');
            setTimeout(() => logoutChannel.close(), 100);
        }
    } catch (e) {
        console.error('BroadcastChannel error:', e);
    }
    
    // Always set localStorage as fallback
    try {
        localStorage.setItem('peakbook_logout_signal', 'true');
    } catch (e) {
        console.error('localStorage error:', e);
    }
    
    // Give other tabs time to process the signal before redirecting
    setTimeout(() => {
        window.location.href = '../index.php';
    }, 500);
</script>
</body>
</html>