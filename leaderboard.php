<?php
// leaderboard.php — Fan Leaderboard & Community Standings
$pageTitle = "Fan Leaderboard";
require_once __DIR__ . '/includes/header.php';

// Fetch users ordered by points
$stmt = $pdo->query("SELECT u.*, 
                    (SELECT COUNT(*) FROM predictions p WHERE p.user_id = u.id) as total_predictions,
                    (SELECT COUNT(*) FROM predictions p WHERE p.user_id = u.id AND p.prediction_status = 'Correct') as correct_predictions
                    FROM users u WHERE u.role = 'user' ORDER BY u.points DESC");
$leaderboard = $stmt->fetchAll();

// Top 3 users for Podium Showcase
$p1 = $leaderboard[0] ?? null;
$p2 = $leaderboard[1] ?? null;
$p3 = $leaderboard[2] ?? null;
?>

<style>
/* =========================================================
   GLOBAL LEADERBOARD PAGE STYLING
   ========================================================= */
.leaderboard-page {
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
.leaderboard-hero {
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
        url("assets/images/leaderboard_hero.png")
        right center / cover no-repeat;
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

.leaderboard-container {
    width: min(92%, var(--page-max));
    margin-inline: auto;
}

/* =========================================================
   PODIUM SHOWCASE (TOP 3 STAGES)
   ========================================================= */
.podium-showcase {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 20px;
    margin: 20px 0 50px;
    padding: 10px 0;
}

.podium-stage {
    flex: 1;
    max-width: 320px;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    background: linear-gradient(180deg, rgba(20, 22, 30, 0.9) 0%, rgba(12, 14, 20, 0.95) 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 24px 20px 0;
    backdrop-filter: blur(12px);
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

.podium-stage:hover {
    transform: translateY(-8px);
}

/* P1 Gold Stage */
.p1-stage {
    max-width: 350px;
    border-color: rgba(245, 185, 66, 0.4);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5), 0 0 30px rgba(245, 185, 66, 0.15);
    background: linear-gradient(180deg, rgba(30, 26, 16, 0.95) 0%, rgba(14, 13, 10, 0.98) 100%);
}

.p1-stage:hover {
    border-color: var(--gold, #f5b942);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 40px rgba(245, 185, 66, 0.25);
}

/* P2 Silver Stage */
.p2-stage {
    border-color: rgba(203, 213, 225, 0.3);
}

.p2-stage:hover {
    border-color: var(--silver, #cbd5e1);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 0 25px rgba(203, 213, 225, 0.15);
}

/* P3 Bronze Stage */
.p3-stage {
    border-color: rgba(217, 119, 6, 0.3);
}

.p3-stage:hover {
    border-color: var(--bronze, #d97706);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 0 25px rgba(217, 119, 6, 0.15);
}

.podium-crown {
    font-size: 2.2rem;
    margin-bottom: -10px;
    filter: drop-shadow(0 0 10px rgba(245, 185, 66, 0.6));
}

.podium-badge-tag {
    font-family: var(--font-heading, sans-serif);
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 1px;
    padding: 4px 12px;
    border-radius: 20px;
    margin-bottom: 14px;
}

.p1-tag { background: rgba(245, 185, 66, 0.2); color: var(--gold, #f5b942); border: 1px solid rgba(245, 185, 66, 0.4); }
.p2-tag { background: rgba(203, 213, 225, 0.15); color: var(--silver, #cbd5e1); border: 1px solid rgba(203, 213, 225, 0.3); }
.p3-tag { background: rgba(217, 119, 6, 0.15); color: var(--bronze, #d97706); border: 1px solid rgba(217, 119, 6, 0.3); }

.podium-avatar {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: var(--bg-card, #0a111b);
    border: 2px solid var(--border-color, #303b49);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-heading, sans-serif);
    font-size: 1.6rem;
    font-weight: 900;
    color: #fff;
    margin-bottom: 12px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
}

.podium-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.p1-avatar {
    width: 82px;
    height: 82px;
    font-size: 2rem;
    border-color: var(--gold, #f5b942);
    background: radial-gradient(circle, rgba(245, 185, 66, 0.2), var(--bg-card, #0a111b));
    box-shadow: 0 0 25px rgba(245, 185, 66, 0.3);
}

.podium-user-name {
    font-size: 1.35rem;
    margin-bottom: 4px;
    color: #fff;
    font-weight: 850;
}

.podium-title-badge {
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #8b99ac;
    background: rgba(255, 255, 255, 0.05);
    padding: 3px 10px;
    border-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 14px;
}

.p1-badge {
    color: var(--gold, #f5b942);
    background: rgba(245, 185, 66, 0.12);
    border-color: rgba(245, 185, 66, 0.25);
}

.podium-points {
    font-family: var(--font-heading, sans-serif);
    font-size: 1.6rem;
    font-weight: 900;
    color: #fff;
    margin-bottom: 18px;
}

.podium-points small {
    font-size: 0.75rem;
    color: #8b99ac;
}

.p1-points {
    font-size: 2.1rem;
    color: var(--gold, #f5b942);
}

.podium-pedestal {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0 0 16px 16px;
}

.p1-pedestal { height: 75px; background: linear-gradient(180deg, rgba(245, 185, 66, 0.15) 0%, rgba(245, 185, 66, 0.05) 100%); }
.p2-pedestal { height: 58px; background: linear-gradient(180deg, rgba(203, 213, 225, 0.12) 0%, rgba(203, 213, 225, 0.03) 100%); }
.p3-pedestal { height: 46px; background: linear-gradient(180deg, rgba(217, 119, 6, 0.12) 0%, rgba(217, 119, 6, 0.03) 100%); }

.pedestal-rank {
    font-family: var(--font-heading, sans-serif);
    font-size: 2.2rem;
    font-weight: 900;
    line-height: 1;
    opacity: 0.7;
}

.p1-pedestal .pedestal-rank { font-size: 2.8rem; color: var(--gold, #f5b942); opacity: 0.9; }
.p2-pedestal .pedestal-rank { color: var(--silver, #cbd5e1); }
.p3-pedestal .pedestal-rank { color: var(--bronze, #d97706); }

/* =========================================================
   PREMIUM TABLE STYLING
   ========================================================= */
.table-card-wrapper {
    background: linear-gradient(145deg, rgba(13, 22, 35, 0.95) 0%, rgba(5, 10, 17, 0.98) 70%, rgba(3, 6, 11, 1) 100%);
    border: 1px solid rgba(50, 68, 92, 0.45);
    border-radius: 12px;
    padding: 28px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
    margin-bottom: 20px;
}

.section-title-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 22px;
}

.section-title-wrap .title-slash {
    width: 6px;
    height: 24px;
    background: #e10600;
    transform: skew(-20deg);
}

.section-title-wrap h2 {
    font-size: 1.35rem;
    font-weight: 850;
    color: #ffffff;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.custom-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.custom-table th {
    padding: 12px 16px;
    font-size: 0.72rem;
    font-weight: 850;
    color: #8b99ac;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    text-align: left;
}

.custom-table tbody tr {
    background: rgba(3, 7, 13, 0.65);
    border: 1px solid rgba(50, 68, 92, 0.4);
    transition: all 0.2s ease;
}

.custom-table tbody tr:hover {
    background: rgba(10, 18, 30, 0.85);
    transform: translateX(4px);
}

.custom-table td {
    padding: 14px 16px;
    font-size: 0.9rem;
    color: #e2e8f0;
    border-top: 1px solid rgba(50, 68, 92, 0.3);
    border-bottom: 1px solid rgba(50, 68, 92, 0.3);
}

.custom-table td:first-child {
    border-left: 1px solid rgba(50, 68, 92, 0.3);
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
}

.custom-table td:last-child {
    border-right: 1px solid rgba(50, 68, 92, 0.3);
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.small-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #e10600 0%, #8b0000 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 850;
    font-size: 0.85rem;
    color: #fff;
    flex-shrink: 0;
}

.small-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.you-tag {
    font-size: 0.6rem;
    background: #e10600;
    color: #fff;
    padding: 2px 6px;
    border-radius: 4px;
    margin-left: 6px;
    font-weight: 850;
    letter-spacing: 0.5px;
}

.rank-text {
    font-weight: 900;
    font-size: 1.05rem;
}

@media (max-width: 820px) {
    .podium-showcase {
        flex-direction: column;
        align-items: center;
    }
    .podium-stage {
        width: 100%;
        max-width: 100%;
    }
    .custom-table {
        display: block;
        overflow-x: auto;
    }
}
</style>

<div class="leaderboard-page">

    <!-- HERO SECTION WITH F1 BACKGROUND IMAGE -->
    <header class="leaderboard-hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-tagline">GLOBAL FAN STANDINGS</div>
                <div class="hero-title-wrap">
                    <span class="hero-slash" aria-hidden="true"></span>
                    <h1>FAN <span>LEADERBOARD</span></h1>
                </div>
                <p class="hero-subtitle">Compete in race predictions, polls, and discussions to claim the top step on the paddock podium.</p>
            </div>
        </div>
    </header>

    <div class="leaderboard-container">

        <!-- PODIUM SHOWCASE FOR TOP 3 -->
        <div class="podium-showcase">

            <!-- P2 - SECOND PLACE -->
            <?php if ($p2): ?>
                <?php $badge2 = get_user_badge($p2['points']); ?>
                <div class="podium-stage p2-stage">
                    <div class="podium-badge-tag p2-tag">P2</div>
                    <div class="podium-avatar">
                        <?php if (!empty($p2['profile_image'])): ?>
                            <img src="assets/images/avatar/<?php echo e($p2['profile_image']); ?>" alt="<?php echo e($p2['username']); ?>" onerror="this.style.display='none'; this.parentElement.innerText='<?php echo strtoupper(substr($p2['username'], 0, 1)); ?>';">
                        <?php else: ?>
                            <span><?php echo strtoupper(substr($p2['username'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="podium-user-name"><?php echo e($p2['username']); ?></h3>
                    <span class="podium-title-badge"><?php echo e($badge2['label']); ?></span>
                    <div class="podium-points"><?php echo e($p2['points']); ?> <small>PTS</small></div>
                    <div class="podium-pedestal p2-pedestal"><span class="pedestal-rank">2</span></div>
                </div>
            <?php endif; ?>

            <!-- P1 - FIRST PLACE -->
            <?php if ($p1): ?>
                <?php $badge1 = get_user_badge($p1['points']); ?>
                <div class="podium-stage p1-stage">
                    <div class="podium-crown">👑</div>
                    <div class="podium-badge-tag p1-tag">CHAMPION</div>
                    <div class="podium-avatar p1-avatar">
                        <?php if (!empty($p1['profile_image'])): ?>
                            <img src="assets/images/avatar/<?php echo e($p1['profile_image']); ?>" alt="<?php echo e($p1['username']); ?>" onerror="this.style.display='none'; this.parentElement.innerText='<?php echo strtoupper(substr($p1['username'], 0, 1)); ?>';">
                        <?php else: ?>
                            <span><?php echo strtoupper(substr($p1['username'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="podium-user-name"><?php echo e($p1['username']); ?></h3>
                    <span class="podium-title-badge p1-badge"><?php echo e($badge1['label']); ?></span>
                    <div class="podium-points p1-points"><?php echo e($p1['points']); ?> <small>PTS</small></div>
                    <div class="podium-pedestal p1-pedestal"><span class="pedestal-rank">1</span></div>
                </div>
            <?php endif; ?>

            <!-- P3 - THIRD PLACE -->
            <?php if ($p3): ?>
                <?php $badge3 = get_user_badge($p3['points']); ?>
                <div class="podium-stage p3-stage">
                    <div class="podium-badge-tag p3-tag">P3</div>
                    <div class="podium-avatar">
                        <?php if (!empty($p3['profile_image'])): ?>
                            <img src="assets/images/avatar/<?php echo e($p3['profile_image']); ?>" alt="<?php echo e($p3['username']); ?>" onerror="this.style.display='none'; this.parentElement.innerText='<?php echo strtoupper(substr($p3['username'], 0, 1)); ?>';">
                        <?php else: ?>
                            <span><?php echo strtoupper(substr($p3['username'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="podium-user-name"><?php echo e($p3['username']); ?></h3>
                    <span class="podium-title-badge"><?php echo e($badge3['label']); ?></span>
                    <div class="podium-points"><?php echo e($p3['points']); ?> <small>PTS</small></div>
                    <div class="podium-pedestal p3-pedestal"><span class="pedestal-rank">3</span></div>
                </div>
            <?php endif; ?>

        </div>

        <!-- FULL LEADERBOARD STANDINGS TABLE -->
        <div class="table-card-wrapper">
            <div class="section-title-wrap">
                <span class="title-slash"></span>
                <h2>COMPLETE STANDINGS</h2>
            </div>

            <table class="custom-table">
                <thead>
                    <tr>
                        <th>RANK</th>
                        <th>FAN USERNAME</th>
                        <th>BADGE TITLE</th>
                        <th>PREDICTIONS</th>
                        <th>ACCURACY</th>
                        <th>FAN POINTS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaderboard as $index => $u): ?>
                        <?php 
                            $b = get_user_badge($u['points']); 
                            $isCurrentUser = (is_logged_in() && $u['id'] == $_SESSION['user_id']);
                            $rankColor = '#ffffff';
                            if ($index == 0) $rankColor = '#f5b942';
                            else if ($index == 1) $rankColor = '#cbd5e1';
                            else if ($index == 2) $rankColor = '#d97706';
                        ?>
                        <tr <?php echo $isCurrentUser ? 'style="background:rgba(225,6,0,0.15); border-left:3px solid #e10600;"' : ''; ?>>
                            <td><strong class="rank-text" style="color:<?php echo $rankColor; ?>;">#<?php echo $index + 1; ?></strong></td>
                            <td>
                                <div class="user-cell">
                                    <div class="small-avatar">
                                        <?php if (!empty($u['profile_image'])): ?>
                                            <img src="assets/images/avatar/<?php echo e($u['profile_image']); ?>" alt="<?php echo e($u['username']); ?>" onerror="this.style.display='none'; this.parentElement.innerText='<?php echo strtoupper(substr($u['username'], 0, 1)); ?>';">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong><?php echo e($u['username']); ?></strong>
                                        <?php if ($isCurrentUser): ?>
                                            <span class="you-tag">YOU</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><span style="font-size:0.7rem; padding:3px 8px; border-radius:4px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); color:#e2e8f0; font-weight:700;"><?php echo e($b['label']); ?></span></td>
                            <td><?php echo $u['total_predictions']; ?></td>
                            <td><?php echo $u['total_predictions'] > 0 ? round(($u['correct_predictions'] / $u['total_predictions']) * 100) . '%' : '0%'; ?></td>
                            <td><strong style="color:#f5b942; font-family:var(--font-heading, sans-serif); font-size:1.1rem;"><?php echo e($u['points']); ?> PTS</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>