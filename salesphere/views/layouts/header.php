<?php
if (!isset($basePath)) $basePath = '../';
if (!isset($pageTitle)) $pageTitle = SITE_NAME;
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= escape($pageTitle) ?> | <?= SITE_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= $basePath ?>assets/css/app.css?v=20260720">
</head>
<body>
<?php if ($user): ?>
  <?php $avatarUrl = avatarUrl($user['Avatar'] ?? null, $user['UserID']); ?>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- sidebar with logo, navigation, and user role/branch -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="sidebar-logo-icon">S</div>
      <div>
        <h1 class="text-base font-bold" style="color:var(--text-primary)"><?= SITE_NAME ?></h1>
        <p class="text-xs" style="color:var(--text-secondary)">Management System</p>
      </div>
    </div>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="sidebar-footer">
      <div class="flex items-center gap-2">
        <div class="role-badge"><?= escape($user['RoleName'] ?? '') ?></div>
        <?php if (!empty($user['BranchName'])): ?>
          <span class="text-xs" style="color:var(--text-secondary)"><?= escape($user['BranchName']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </aside>

  <!-- top navbar with sidebar toggle, theme button, profile dropdown -->
  <header class="navbar">
    <div class="flex items-center gap-3">
      <button id="sidebarToggle" class="md:hidden p-2 rounded-lg" style="color:var(--text-secondary);background:transparent;border:1px solid var(--border);cursor:pointer">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h2 class="text-sm font-semibold hidden md:block" style="color:var(--text-secondary)"><?= escape($pageTitle) ?></h2>
    </div>

    <div class="flex items-center gap-2">
      <button id="themeToggle" class="p-2 rounded-lg transition-colors" style="color:var(--text-secondary);background:transparent;border:1px solid var(--border);cursor:pointer">
      </button>

      <div class="relative">
        <button id="profileBtn" class="flex items-center gap-2 px-3 py-1.5 rounded-lg transition-colors" style="background:transparent;border:1px solid var(--border);cursor:pointer">
          <?php if ($avatarUrl): ?>
            <img id="navAvatar" src="<?= $avatarUrl ?>" alt="" class="avatar-sm">
            <span id="navInitials" style="display:none"></span>
          <?php else: ?>
            <div id="navInitials" class="avatar-initials"><?= getInitials($user['Username'] ?? '') ?></div>
            <img id="navAvatar" src="" alt="" style="display:none">
          <?php endif; ?>
          <span class="text-sm font-medium hidden sm:inline" style="color:var(--text-primary)"><?= escape($user['Username'] ?? '') ?></span>
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--text-secondary)"><polyline points="6 9 12 15 18 9"/></svg>
        </button>

        <div class="profile-dropdown" id="profileDropdown">
          <a href="<?= $basePath ?>profile/" class="dropdown-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            View Profile
          </a>
          <div class="dropdown-divider"></div>
          <a href="<?= $basePath ?>logout.php" class="dropdown-item" style="color:var(--danger)">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign Out
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="main-content">
<?php else: ?>
  <main>
<?php endif; ?>
