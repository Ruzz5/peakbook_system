<?php
$page = $activePage ?? 'dashboard';
$user = $_SESSION['firstname'] ?? 'User';
$initials = strtoupper(substr($user, 0, 1));

$nav = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
    'inventory' => ['label' => 'Inventory',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>'],
    'analysis'  => ['label' => 'Analysis',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
    'reports'   => ['label' => 'Reports',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
];
?>
<aside class="sidebar">
    <!-- Faint mountain texture in background -->
    <div class="sidebar-mountain">
        <img src="../images/mountain.jpg" alt="">
    </div>

    <div class="sidebar-inner">
        <div class="sidebar-logo">Peak<span>Book</span></div>

        <ul class="sidebar-nav">
            <?php foreach ($nav as $key => $item): ?>
            <li>
                <a href="<?= $key ?>.php" class="<?= $page === $key ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <?= $item['icon'] ?>
                    </svg>
                    <span><?= $item['label'] ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar"><?= $initials ?></div>
                <div>
                    <div class="sidebar-username"><?= htmlspecialchars($user) ?></div>
                    <div class="sidebar-role">Admin</div>
                </div>
            </div>
            <a href="../php/logout.php" class="logout-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Log Out</span>
            </a>
        </div>
    </div>
</aside>