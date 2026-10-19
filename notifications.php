<?php
// notifications.php — Broadcast-Grade Notifications Center
$pageTitle = "Notifications";
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/functions.php';

$userId = $_SESSION['user_id'];

// Handle Mark All as Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $up = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $up->execute([$userId]);
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'All notifications marked as read.'];
    header("Location: notifications.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$unreadCount = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unreadCount++;
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* =========================================================
   FORMULAHUB — BROADCAST NOTIFICATIONS CENTER DESIGN
========================================================= */
.notif-hero {
    background:
        linear-gradient(
            90deg,
            rgba(3, 5, 8, 0.97) 0%,
            rgba(3, 5, 8, 0.85) 38%,
            rgba(3, 5, 8, 0.45) 75%,
            rgba(3, 5, 8, 0.82) 100%
        ),
        url("assets/images/notification.jpg") center 30% / cover no-repeat;
}

.notifications-page-body {
    min-height: 60vh;
    padding: 10px 0 80px;
    background: var(--bg-primary);
}

.notif-feed-container {
    max-width: 860px;
    margin: 0 auto;
}

/* ACTION BUTTON */
.btn-mark-read {
    background: linear-gradient(135deg, rgba(20, 28, 40, 0.9) 0%, rgba(10, 16, 26, 0.95) 100%);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 8px;
    padding: 11px 22px;
    color: #ffffff;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
}

.btn-mark-read:hover {
    background: rgba(225, 6, 0, 0.2);
    border-color: var(--accent-red);
    box-shadow: 0 0 20px rgba(225, 6, 0, 0.35);
    transform: translateY(-1px);
}

/* EMPTY STATE CARD */
.empty-notif-card {
    position: relative;
    background: linear-gradient(145deg, rgba(13, 20, 31, 0.88) 0%, rgba(6, 11, 18, 0.96) 100%);
    border: 1px solid rgba(225, 6, 0, 0.35);
    border-radius: 16px;
    padding: 70px 30px;
    text-align: center;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.75), inset 0 0 30px rgba(225, 6, 0, 0.08);
    backdrop-filter: blur(16px);
    overflow: hidden;
    margin-top: 20px;
}

.empty-bell-wrap {
    width: 88px;
    height: 88px;
    margin: 0 auto 22px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-bell-circle {
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(225, 6, 0, 0.35) 0%, rgba(15, 22, 32, 0.9) 70%);
    border: 1.5px solid rgba(225, 6, 0, 0.7);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 35px rgba(225, 6, 0, 0.5), inset 0 0 15px rgba(225, 6, 0, 0.4);
}

.empty-bell-svg {
    width: 36px;
    height: 36px;
    fill: #d1d8e0;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.6));
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 900;
    font-style: italic;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin: 0 0 10px 0;
}

.empty-desc {
    color: var(--text-muted);
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0;
}

/* LIST STATE NOTIFICATIONS */
.notif-list-grid {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-top: 15px;
}

.notif-item-card {
    position: relative;
    background: linear-gradient(145deg, rgba(13, 20, 31, 0.85) 0%, rgba(6, 11, 18, 0.95) 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 22px 24px;
    display: flex;
    gap: 20px;
    align-items: flex-start;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(12px);
    transition: all 0.25s ease;
}

.notif-item-card:hover {
    border-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 14px 35px rgba(0, 0, 0, 0.5);
}

.notif-item-card.unread {
    border-left: 4px solid var(--accent-red);
    background: linear-gradient(145deg, rgba(22, 32, 48, 0.92) 0%, rgba(8, 14, 22, 0.98) 100%);
    box-shadow: 0 10px 30px rgba(0,0,0,0.5), 0 0 20px rgba(225, 6, 0, 0.15);
}

.notif-type-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(225, 6, 0, 0.12);
    border: 1px solid rgba(225, 6, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.notif-content {
    flex: 1;
    min-width: 0;
}

.notif-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.notif-item-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    letter-spacing: 0.2px;
}

.notif-time-stamp {
    font-size: 0.75rem;
    font-weight: 700;
    color: #7b8a9e;
    text-transform: uppercase;
    white-space: nowrap;
}

.notif-item-msg {
    color: #a3b2c5;
    font-size: 0.92rem;
    line-height: 1.65;
    margin: 0;
}

@media (max-width: 768px) {
    .notif-hero .hero-container {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
    .notif-item-card {
        padding: 18px 16px;
        gap: 14px;
    }
    .notif-type-icon {
        width: 38px;
        height: 38px;
        font-size: 1.1rem;
    }
}
</style>

<!-- STANDARDIZED HERO BANNER -->
<section class="page-hero notif-hero">
    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-tagline">REAL-TIME BROADCAST • PADDOCK DISPATCH</div>
            <div class="hero-title-wrap">
                <span class="hero-slash" aria-hidden="true"></span>
                <h1 class="hero-title">NOTIFICATIONS</h1>
            </div>
            <p class="hero-subtitle">Stay updated with Grand Prix alerts, prediction outcomes, and community updates.</p>
        </div>

        <?php if (!empty($notifications) && $unreadCount > 0): ?>
            <div class="hero-actions">
                <form method="POST" action="notifications.php" style="margin: 0;">
                    <button type="submit" name="mark_all_read" class="btn-mark-read">
                        <span>✓</span> MARK ALL AS READ
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- MAIN CONTENT FEED -->
<main class="notifications-page-body">
    <div class="container notif-feed-container">
        
        <?php if (empty($notifications)): ?>
            <!-- EMPTY STATE CARD -->
            <div class="empty-notif-card">
                <div class="empty-bell-wrap">
                    <div class="empty-bell-circle">
                        <svg class="empty-bell-svg" viewBox="0 0 24 24">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                        </svg>
                    </div>
                </div>
                <h2 class="empty-title">YOU'RE ALL CAUGHT UP!</h2>
                <p class="empty-desc">No notifications at this time. Paddock alerts and race results will appear here as the season unfolds.</p>
            </div>

        <?php else: ?>

            <!-- LIST OF NOTIFICATIONS -->
            <div class="notif-list-grid">
                <?php foreach ($notifications as $n): ?>
                    <div class="notif-item-card <?php echo !$n['is_read'] ? 'unread' : ''; ?>">
                        <div class="notif-type-icon">
                            <?php 
                            switch ($n['type']) {
                                case 'race': echo '🏎️'; break;
                                case 'prediction': echo '🎯'; break;
                                case 'poll': echo '📊'; break;
                                default: echo '📢'; break;
                            }
                            ?>
                        </div>

                        <div class="notif-content">
                            <div class="notif-top-bar">
                                <h3 class="notif-item-title"><?php echo e($n['title']); ?></h3>
                                <span class="notif-time-stamp"><?php echo date('M d, H:i', strtotime($n['created_at'])); ?></span>
                            </div>
                            <p class="notif-item-msg"><?php echo e($n['message']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>