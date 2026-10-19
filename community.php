<?php
// community.php — Fan Discussions & Community Feed with Pagination
$pageTitle = "Community Hub";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Handle Comment Posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_comment'])) {
    require_login();
    $content = trim($_POST['content'] ?? '');
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, content) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $content]);
        
        // Add 10 fan points for community engagement!
        add_user_points($_SESSION['user_id'], 10);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Comment posted! +10 Fan Points awarded!'];
        header("Location: community.php");
        exit();
    }
}

// Pagination Configuration
$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Total comments count for pagination
$totalStmt = $pdo->query("SELECT COUNT(*) FROM comments");
$totalComments = $totalStmt->fetchColumn();
$totalPages = ceil($totalComments / $limit);

// Fetch comments with user details and limit for current page
$stmt = $pdo->prepare("SELECT c.*, u.username, u.full_name, u.points, u.profile_image FROM comments c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Page Container & Background */
.community-page {
    --page-max: 1280px;
    position: relative;
    isolation: isolate;
    overflow: hidden;
    padding: 0 0 70px;
    color: #f4f6fa;
    background: #05090e;
    min-height: 85vh;
}

/* HERO BANNER SECTION WITH F1 IMAGE BACKGROUND */
.community-hero {
    position: relative;
    width: 100%;
    padding: 56px 0 50px;
    margin-bottom: 35px;
     background:
        linear-gradient(
            90deg,
            rgba(3,5,8,.97) 0%,
            rgba(3,5,8,.84) 38%,
            rgba(3,5,8,.28) 72%,
            rgba(3,5,8,.70) 100%
        ),
        url("assets/images/community_hero.png")
        center right / cover no-repeat;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.hero-container {
    width: min(92%, var(--page-max));
    margin-inline: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    z-index: 2;
}

.hero-content {
    max-width: 650px;
}

.hero-tagline {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    font-weight: 800;
    color: #e10600;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin-bottom: 8px;
}

.hero-tagline::before {
    content: "";
    width: 14px;
    height: 3px;
    background: #e10600;
}

.hero-title-wrap {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 12px;
}

.hero-slash {
    width: 10px;
    height: 38px;
    background: #e10600;
    transform: skew(-24deg);
    display: inline-block;
}

.hero-title-wrap h1 {
    font-size: clamp(28px, 3.2vw, 44px);
    font-weight: 900;
    font-style: italic;
    letter-spacing: 1.5px;
    color: #ffffff;
    margin: 0;
    line-height: 1.1;
    text-transform: uppercase;
}

.hero-title-wrap h1 span {
    color: #e10600;
}

.hero-subtitle {
    color: #a3b2c5;
    font-size: 0.98rem;
    margin: 12px 0 0;
    line-height: 1.5;
}

/* MAIN CONTENT CONTAINER */
.community-container {
    width: min(92%, var(--page-max));
    margin-inline: auto;
}

/* MAIN GLASS CARD WRAPPER */
.community-card-wrapper {
    position: relative;
    background: linear-gradient(145deg, rgba(13, 22, 35, 0.95) 0%, rgba(5, 10, 17, 0.98) 70%, rgba(3, 6, 11, 1) 100%);
    border: 1px solid rgba(50, 68, 92, 0.45);
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(8px);
}

/* POST COMMENT BOX CARD */
.join-conversation-card {
    background: linear-gradient(135deg, rgba(25, 12, 16, 0.8) 0%, rgba(10, 15, 24, 0.9) 100%);
    border: 1px solid rgba(225, 6, 0, 0.4);
    border-left: 4px solid #e10600;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 35px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
}

.post-form-box {
    display: flex;
    flex-direction: column;
    gap: 14px;
    width: 100%;
}

.post-textarea {
    width: 100%;
    background: rgba(3, 7, 13, 0.85);
    border: 1px solid rgba(48, 65, 88, 0.7);
    border-radius: 6px;
    color: #e2e8f0;
    padding: 14px 16px;
    font-size: 0.9rem;
    font-family: inherit;
    resize: vertical;
    min-height: 90px;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.post-textarea:focus {
    border-color: #e10600;
    box-shadow: 0 0 10px rgba(225, 6, 0, 0.25);
}

.form-footer-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.btn-post-submit {
    background: linear-gradient(135deg, #e10600 0%, #b30400 100%);
    color: #ffffff;
    border: none;
    padding: 10px 24px;
    border-radius: 6px;
    font-weight: 850;
    font-size: 0.82rem;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(225, 6, 0, 0.4);
    transition: all 0.2s ease;
}

.btn-post-submit:hover {
    background: linear-gradient(135deg, #ff0800 0%, #d10500 100%);
    box-shadow: 0 6px 20px rgba(225, 6, 0, 0.6);
    transform: translateY(-2px);
}

/* COMMENTS FEED */
.posts-list {
    display: flex;
    flex-direction: column;
    gap: 18px;
    margin-bottom: 30px;
}

.post-item-card {
    background: rgba(3, 7, 13, 0.65);
    border: 1px solid rgba(50, 68, 92, 0.4);
    border-radius: 8px;
    padding: 20px 24px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    transition: border-color 0.2s ease, background 0.2s ease;
}

.post-item-card:hover {
    border-color: rgba(225, 6, 0, 0.35);
    background: rgba(4, 9, 16, 0.85);
}

/* User Avatar / Profile Image Styles */
.user-avatar-box {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #e10600 0%, #8b0000 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 1.15rem;
    font-weight: 900;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(225, 6, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.user-avatar-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.post-content-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.post-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.author-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.author-name {
    font-size: 0.98rem;
    font-weight: 850;
    color: #ffffff;
}

.badge-tier {
    font-size: 0.65rem;
    font-weight: 850;
    padding: 3px 8px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: rgba(225, 6, 0, 0.18);
    color: #e10600;
    border: 1px solid rgba(225, 6, 0, 0.35);
}

.post-time {
    font-size: 0.78rem;
    color: #7b8a9e;
}

.post-text {
    font-size: 0.93rem;
    color: #cad5e2;
    line-height: 1.6;
    margin: 4px 0 0 0;
}

/* PAGINATION STYLING */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 30px;
}

.pagination a, .pagination span {
    padding: 8px 14px;
    background: rgba(3, 7, 13, 0.8);
    border: 1px solid rgba(50, 68, 92, 0.5);
    color: #cbd5e1;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
}

.pagination a:hover {
    background: rgba(225, 6, 0, 0.2);
    border-color: #e10600;
    color: #ffffff;
}

.pagination .active {
    background: #e10600;
    border-color: #e10600;
    color: #ffffff;
    box-shadow: 0 0 10px rgba(225, 6, 0, 0.4);
}

.login-notice {
    text-align: center;
    padding: 20px;
    background: rgba(3, 7, 13, 0.8);
    border: 1px dashed rgba(48, 65, 88, 0.7);
    border-radius: 6px;
    color: #8b99ac;
}

.login-notice a {
    color: #e10600;
    font-weight: 700;
    text-decoration: none;
}

.login-notice a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .form-footer-row {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    .btn-post-submit {
        width: 100%;
        text-align: center;
    }
    .post-header-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}
</style>

<div class="community-page">
    
    <!-- HERO SECTION WITH F1 BACKGROUND IMAGE -->
    <header class="community-hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-tagline">COMMUNITY & DISCUSSIONS</div>
                <div class="hero-title-wrap">
                    <span class="hero-slash" aria-hidden="true"></span>
                    <h1>FAN PADDOCK <span>COMMUNITY</span></h1>
                </div>
                <p class="hero-subtitle">Discuss race strategy, driver pace, and Grand Prix predictions with fellow F1 fans.</p>
            </div>
        </div>
    </header>

    <div class="community-container">
        <div class="community-card-wrapper">
            
            <!-- POST COMMENT BOX -->
            <div class="join-conversation-card">
                <h3 style="font-size:1.15rem; margin-bottom:15px; border-left:3px solid #e10600; padding-left:10px; color:#ffffff; text-transform:uppercase; letter-spacing:0.6px;">JOIN THE CONVERSATION</h3>

                <?php if (is_logged_in()): ?>
                    <form method="POST" action="community.php" class="post-form-box">
                        <textarea name="content" rows="3" class="post-textarea" placeholder="Share your thoughts on the upcoming Grand Prix..." required></textarea>
                        <div class="form-footer-row">
                            <span style="font-size:0.78rem; color:#f5b942;">⚡ Earn +10 Fan Points per post!</span>
                            <button type="submit" name="post_comment" class="btn-post-submit">POST DISCUSSION →</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="login-notice">
                        Please <a href="login.php">Log In</a> to join the community discussion.
                    </div>
                <?php endif; ?>
            </div>

            <!-- COMMENTS FEED -->
            <div class="posts-list">
                <?php if (empty($comments)): ?>
                    <div style="text-align:center; padding:30px; color:#7b8a9e;">
                        No community discussions yet. Be the first to start the conversation!
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $c): ?>
                        <?php 
                            $badge = get_user_badge($c['points']); 
                        ?>
                        <div class="post-item-card">
                            <div class="user-avatar-box">
                                <?php if (!empty($c['profile_image'])): ?>
                                    <img src="assets/images/avatar/<?php echo e($c['profile_image']); ?>" alt="<?php echo e($c['username']); ?>" onerror="this.style.display='none'; this.parentElement.innerText='<?php echo strtoupper(substr($c['username'], 0, 1)); ?>';">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($c['username'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="post-content-area">
                                <div class="post-header-row">
                                    <div class="author-info">
                                        <strong class="author-name"><?php echo e($c['username']); ?></strong>
                                        <span class="badge-tier"><?php echo e($badge['label']); ?></span>
                                    </div>
                                    <span class="post-time"><?php echo date('M d, Y H:i', strtotime($c['created_at'])); ?></span>
                                </div>
                                <p class="post-text"><?php echo e($c['content']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PAGINATION LINKS -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="community.php?page=<?php echo ($page - 1); ?>">← Prev</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="community.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="community.php?page=<?php echo ($page + 1); ?>">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>