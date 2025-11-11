<!-- Left vertical nav partial -->
<?php
// Fetch current user for avatar
$nav_user = null;
if (function_exists('current_user')) {
    $nav_user = current_user();
} elseif (isset($_SESSION['user_id'])) {
    $nav_db = db_connect();
    $nav_stmt = $nav_db->prepare('SELECT id, username, profile_pic FROM users WHERE id = ?');
    $nav_stmt->bind_param('i', $_SESSION['user_id']);
    $nav_stmt->execute();
    $nav_res = $nav_stmt->get_result();
    $nav_user = $nav_res->fetch_assoc();
    $nav_stmt->close();
    $nav_db->close();
}

// Determine active page
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="left-nav" aria-label="Main navigation">
    <ul>
        <li class="nav-item <?php echo ($current_page === 'index') ? 'active' : ''; ?>" data-key="home">
            <a href="/Nathan/wd-project/" style="display:flex;align-items:center;gap:10px;width:100%;background:none;border:0;padding:8px;border-radius:8px;cursor:pointer;color:#222;text-decoration:none" aria-label="Home">
                <svg viewBox="0 0 24 24" class="icon"><path d="M3 11.5L12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V11.5z" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
                <span class="label">Home</span>
            </a>
        </li>
        <li class="nav-item <?php echo ($current_page === 'codeas') ? 'active' : ''; ?>" data-key="codeas">
            <a href="codeas" style="display:flex;align-items:center;gap:10px;width:100%;background:none;border:0;padding:8px;border-radius:8px;cursor:pointer;color:#222;text-decoration:none" aria-label="Codeas">
                <svg viewBox="0 0 24 24" class="icon"><rect x="3" y="5" width="18" height="14" rx="2" ry="2" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M10 9l5 3-5 3V9z" fill="currentColor"/></svg>
                <span class="label">Codeas</span>
            </a>
        </li>
        <li class="nav-item <?php echo ($current_page === 'messages') ? 'active' : ''; ?>" data-key="messages">
            <a href="messages" style="display:flex;align-items:center;gap:10px;width:100%;background:none;border:0;padding:8px;border-radius:8px;cursor:pointer;color:#222;text-decoration:none" aria-label="Messages">
                <svg viewBox="0 0 24 24" class="icon" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <span class="label">Messages</span>
            </a>
        </li>
        <li class="nav-item <?php echo ($current_page === 'notifications') ? 'active' : ''; ?>" data-key="notifications">
            <a href="notifications" style="display:flex;align-items:center;gap:10px;width:100%;background:none;border:0;padding:8px;border-radius:8px;cursor:pointer;color:#222;text-decoration:none" aria-label="Notifications">
                <svg viewBox="0 0 24 24" class="icon"><path d="M15 17H9a3 3 0 0 0 6 0z" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                <span class="label">Notifications</span>
            </a>
        </li>
        <li class="nav-item <?php echo ($current_page === 'create' || $current_page === 'upload_post') ? 'active' : ''; ?>" data-key="create">
            <a href="create" style="display:flex;align-items:center;gap:10px;width:100%;background:none;border:0;padding:8px;border-radius:8px;cursor:pointer;color:#222;text-decoration:none" aria-label="Create">
                <svg viewBox="0 0 24 24" class="icon"><rect x="3" y="3" width="18" height="18" rx="2" ry="2" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M12 7v10M7 12h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                <span class="label">Create</span>
            </a>
        </li>
        <li class="nav-item <?php echo ($current_page === 'profile') ? 'active' : ''; ?>" data-key="profile">
            <a href="profile" style="display:flex;align-items:center;gap:10px;width:100%;background:none;border:0;padding:8px;border-radius:8px;cursor:pointer;color:#222;text-decoration:none" aria-label="Profile">
                <?php if ($nav_user && $nav_user['profile_pic']): ?>
                    <img src="<?php echo htmlspecialchars($nav_user['profile_pic']); ?>" alt="Profile" class="nav-avatar" style="width:28px;height:28px;border-radius:50%;object-fit:cover;background:#ccc">
                <?php else: ?>
                    <div class="avatar nav-avatar" style="width:28px;height:28px;border-radius:50%;background:#ccc"></div>
                <?php endif; ?>
                <span class="label">Profile</span>
            </a>
        </li>
        <li class="nav-item nav-logout" data-key="logout">
            <a href="logout" style="display:flex;align-items:center;gap:10px;width:100%;background:none;border:0;padding:8px;border-radius:8px;cursor:pointer;color:#222;text-decoration:none" aria-label="Logout">
                <svg viewBox="0 0 24 24" class="icon" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span class="label">Logout</span>
            </a>
        </li>
    </ul>
</nav>
