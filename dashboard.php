<?php
// dashboard.php — User Fan Dashboard
$pageTitle = "Dashboard | FormulaHub";
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/functions.php';

$user = get_logged_in_user();

// Counts & Stats
$favCountStmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
$favCountStmt->execute([$user['id']]);
$favCount = $favCountStmt->fetchColumn();

$predCountStmt = $pdo->prepare("SELECT COUNT(*) FROM predictions WHERE user_id = ?");
$predCountStmt->execute([$user['id']]);
$predCount = $predCountStmt->fetchColumn();

// Next Race
$nextRace = $pdo->query("SELECT * FROM races WHERE status = 'Upcoming' ORDER BY race_date ASC LIMIT 1")->fetch();

// User Favorite Drivers & Teams
$favDrivers = $pdo->prepare("SELECT d.*, t.name as team_name FROM favorites f JOIN drivers d ON f.item_id = d.id LEFT JOIN teams t ON d.team_id = t.id WHERE f.user_id = ? AND f.item_type = 'driver'");
$favDrivers->execute([$user['id']]);
$userFavDrivers = $favDrivers->fetchAll();

$favTeams = $pdo->prepare("SELECT t.* FROM favorites f JOIN teams t ON f.item_id = t.id WHERE f.user_id = ? AND f.item_type = 'team'");
$favTeams->execute([$user['id']]);
$userFavTeams = $favTeams->fetchAll();

// Notifications
$notifsStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifsStmt->execute([$user['id']]);
$notifications = $notifsStmt->fetchAll();

// User Predictions
$predsStmt = $pdo->prepare("SELECT p.*, r.grand_prix, d.name as driver_name FROM predictions p JOIN races r ON p.race_id = r.id JOIN drivers d ON p.driver_id = d.id WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 5");
$predsStmt->execute([$user['id']]);
$userPredictions = $predsStmt->fetchAll();

$badge = get_user_badge($user['points']);

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Custom Dashboard Theme & Layout Fixes */
:root {
  --dash-bg: #03070d;
  --dash-card-bg: rgba(10, 15, 26, 0.75);
  --dash-card-border: rgba(255, 255, 255, 0.08);
  --dash-red: #e10600;
  --dash-gold: #f5b942;
}

body {
  background-color: var(--dash-bg);
  color: #ffffff;
}

.dashboard-container {
  width: var(--container-width);
  max-width: var(--container-max);
  margin: 0 auto;
  padding: 24px var(--container-padding) 60px;
}

/* HERO BANNER */
.hero-dash {
  position: relative;
  background: linear-gradient(90deg, rgba(15,0,0,0.92) 0%, rgba(5,7,15,0.78) 50%, rgba(3,7,13,0.95) 100%),
              url('assets/images/f1-hero-bg.png') center/cover no-repeat;
  border-radius: 16px;
  padding: 35px 40px;
  margin-bottom: 30px;
  border: 1px solid rgba(225, 6, 0, 0.3);
  overflow: hidden;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.hero-dash::before {
  content: '';
  position: absolute;
  top: 0; left: 0; width: 4px; height: 100%;
  background: var(--dash-red);
}

.dash-badge {
  display: inline-block;
  background: rgba(225, 6, 0, 0.15);
  border: 1px solid var(--dash-red);
  color: var(--dash-red);
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 1px;
  padding: 4px 12px;
  border-radius: 20px;
  text-transform: uppercase;
  margin-bottom: 12px;
}

.dash-welcome-title {
  font-family: var(--font-heading, sans-serif);
  font-size: 2.2rem;
  font-weight: 900;
  line-height: 1.1;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.dash-welcome-title span {
  color: var(--dash-red);
}

.dash-welcome-sub {
  color: #8b9bb4;
  font-size: 0.88rem;
}

.btn-edit-profile {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #fff;
  padding: 8px 18px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-edit-profile:hover {
  background: #fff;
  color: #000;
}

/* TOP STATS CARDS */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 25px;
}

.stat-card-dash {
  background: var(--dash-card-bg);
  border: 1px solid var(--dash-card-border);
  border-left: 3px solid var(--dash-red);
  border-radius: 12px;
  padding: 22px;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(10px);
}

.stat-card-dash.gold {
  border-color: rgba(245, 185, 66, 0.4);
  border-left-color: var(--dash-gold);
  background: linear-gradient(135deg, rgba(20, 16, 5, 0.8) 0%, rgba(10, 15, 26, 0.8) 100%);
  box-shadow: 0 0 25px rgba(245, 185, 66, 0.08);
}

.stat-header {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 1px;
  color: #8b9bb4;
  text-transform: uppercase;
}

.stat-card-dash.gold .stat-header {
  color: var(--dash-gold);
}

.stat-val-dash {
  font-family: var(--font-heading, sans-serif);
  font-size: 2.2rem;
  font-weight: 900;
  margin: 10px 0 6px;
  color: #ffffff;
}

.stat-card-dash.gold .stat-val-dash {
  color: var(--dash-gold);
}

.stat-link-dash {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--dash-red);
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.stat-card-dash.gold .stat-link-dash {
  color: var(--dash-gold);
}

/* NEXT GRAND PRIX BANNER (VISIBLE BACKGROUND IMAGE FIX) */
.featured-race-card {
  position: relative;
  min-height: 280px;
  background: #080a0d;
  border: 1px solid rgba(255, 28, 28, 0.48);
  border-radius: 12px;
  box-shadow: 0 25px 60px rgba(0,0,0,.45), 0 0 35px rgba(255,20,20,.045);
  margin-bottom: 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  overflow: hidden;
  padding: 30px 40px;
}

.featured-race-card::before {
  content: "";
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 4px;
  background: linear-gradient(180deg, #ff3434, #ff0000 50%, #c50000);
  z-index: 10;
  box-shadow: 0 0 18px rgba(255,20,20,.35);
}

.featured-race-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0.65; /* Increased opacity so picture is clearly visible */
  z-index: 1;
}

.featured-race-shade {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, rgba(3,5,8,0.92) 20%, rgba(3,5,8,0.7) 50%, rgba(3,5,8,0.85) 100%);
  z-index: 2;
}

.featured-race-content {
  position: relative;
  z-index: 5;
  max-width: 55%;
}

.featured-race-top-tags {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.next-race-label {
  padding: 5px 10px;
  color: #fff;
  border: 1px solid rgba(255,255,255,.18);
  background: rgba(0,0,0,.6);
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 900;
  letter-spacing: 1px;
}

.race-status-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  background: rgba(0,0,0,.65);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 4px;
  color: #fff;
  font-size: 0.7rem;
  font-weight: 900;
}

.race-status-tag i {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--dash-red);
  box-shadow: 0 0 8px rgba(255,23,23,.8);
}

.featured-race-country {
  color: var(--dash-red);
  font-size: 0.8rem;
  font-weight: 900;
  letter-spacing: 1.5px;
  margin-bottom: 4px;
  text-transform: uppercase;
}

.featured-race-title {
  font-family: var(--font-heading, sans-serif);
  font-size: 2.1rem;
  font-weight: 900;
  text-transform: uppercase;
  line-height: 1.1;
  margin-bottom: 8px;
  letter-spacing: -0.5px;
}

.featured-race-circuit {
  color: #d0d4d9;
  font-size: 0.85rem;
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.featured-race-circuit span {
  color: var(--dash-red);
}

.btn-predict-now {
  background: var(--dash-red);
  color: #fff;
  padding: 10px 22px;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 900;
  letter-spacing: 0.5px;
  display: inline-block;
  transition: all 0.25s ease;
}

.btn-predict-now:hover {
  background: #ff3333;
  transform: translateY(-2px);
}

.featured-race-right {
  position: relative;
  z-index: 5;
  text-align: right;
  flex-shrink: 0;
}

.countdown-label {
  font-size: 0.75rem;
  font-weight: 800;
  color: var(--dash-red);
  letter-spacing: 1px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 6px;
}

.countdown-boxes {
  display: flex;
  gap: 10px;
}

.cd-box {
  background: rgba(0, 0, 0, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 10px 14px;
  text-align: center;
  min-width: 60px;
  backdrop-filter: blur(8px);
}

.cd-num {
  font-family: var(--font-heading, sans-serif);
  font-size: 1.6rem;
  font-weight: 900;
  line-height: 1;
  color: #fff;
}

.cd-txt {
  font-size: 0.6rem;
  font-weight: 700;
  color: #8b9bb4;
  margin-top: 4px;
}

/* MAIN 2-COLUMN LAYOUT */
.dash-grid-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 25px;
}

.dash-panel {
  background: var(--dash-card-bg);
  border: 1px solid var(--dash-card-border);
  border-radius: 14px;
  padding: 22px;
  margin-bottom: 25px;
  backdrop-filter: blur(10px);
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}

.panel-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: var(--font-heading, sans-serif);
  font-size: 1.05rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.panel-title span.icon {
  color: var(--dash-red);
}

.panel-view-all {
  font-size: 0.72rem;
  font-weight: 700;
  color: #8b9bb4;
  letter-spacing: 0.5px;
}

.panel-view-all:hover {
  color: #fff;
}

/* DRIVER & TEAM ITEMS */
.item-card-row {
  background: rgba(15, 22, 36, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  padding: 14px 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.item-card-row:last-child {
  margin-bottom: 0;
}

.driver-info-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.driver-avatar-mini {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  object-fit: cover;
  background: #141824;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.item-name {
  font-weight: 800;
  font-size: 0.95rem;
}

.item-sub {
  font-size: 0.75rem;
  color: #8b9bb4;
}

.btn-item-action {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #fff;
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 700;
}

.btn-item-action:hover {
  background: rgba(255, 255, 255, 0.15);
}

/* NOTIFICATIONS & PREDICTIONS */
.empty-box-dash {
  background: rgba(15, 22, 36, 0.4);
  border-radius: 10px;
  padding: 25px;
  text-align: center;
  color: #8b9bb4;
  font-size: 0.85rem;
}

.prediction-item {
  background: rgba(15, 22, 36, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  padding: 12px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.badge-pending {
  background: rgba(245, 185, 66, 0.15);
  color: var(--dash-gold);
  border: 1px solid rgba(245, 185, 66, 0.3);
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 800;
}

@media (max-width: 900px) {
  .stats-grid, .dash-grid-layout {
    grid-template-columns: 1fr;
  }
  .featured-race-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 20px;
  }
  .featured-race-content {
    max-width: 100%;
  }
  .featured-race-right {
    text-align: left;
  }
  .countdown-label {
    justify-content: flex-start;
  }
}
</style>

<div class="dashboard-container">

  <!-- HERO GREETING BANNER -->
  <section class="hero-dash">
    <div>
      <span class="dash-badge"><?php echo e($badge['label']); ?></span>
      <h1 class="dash-welcome-title">
        WELCOME BACK,<br>
        <span><?php echo e($user['full_name']); ?></span> 👋
      </h1>
      <p class="dash-welcome-sub">Track your predictions, favorite teams, and community standing.</p>
    </div>
    <div>
      <a href="profile.php" class="btn-edit-profile">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
        EDIT PROFILE
      </a>
    </div>
  </section>

  <!-- TOP STATS -->
  <section class="stats-grid">
    <div class="stat-card-dash">
      <div class="stat-header"><span>★</span> FAVORITES</div>
      <div class="stat-val-dash"><?php echo str_pad($favCount, 2, '0', STR_PAD_LEFT); ?></div>
      <a href="favorites.php" class="stat-link-dash">Manage Favorites →</a>
    </div>

    <div class="stat-card-dash">
      <div class="stat-header"><span>📊</span> PREDICTIONS MADE</div>
      <div class="stat-val-dash"><?php echo str_pad($predCount, 2, '0', STR_PAD_LEFT); ?></div>
      <a href="predictions.php" class="stat-link-dash">View Predictions →</a>
    </div>

    <div class="stat-card-dash gold">
      <div class="stat-header"><span>🏆</span> FAN POINTS</div>
      <div class="stat-val-dash"><?php echo e($user['points']); ?></div>
      <a href="leaderboard.php" class="stat-link-dash">View Leaderboard →</a>
    </div>
  </section>

  <!-- NEXT GRAND PRIX BANNER (VISIBLE BACKGROUND IMAGE) -->
  <?php if ($nextRace): ?>
    <?php
    $nextImage = trim($nextRace['circuit_image'] ?? '');
    if ($nextImage !== '' && strpos($nextImage, 'http://') !== 0 && strpos($nextImage, 'https://') !== 0 && strpos($nextImage, '/') !== 0) {
        $nextImage = 'assets/images/circuit_image/' . basename($nextImage);
    }
    $nextStatus = strtoupper($nextRace['status']);
    ?>
    <section class="featured-race-card">
      <?php if ($nextImage !== ''): ?>
        <img src="<?php echo e($nextImage); ?>" class="featured-race-bg" alt="<?php echo e($nextRace['grand_prix']); ?>" onerror="this.src='https://via.placeholder.com/1200x400/080a0d/FFFFFF?text=F1+Circuit'">
      <?php endif; ?>
      <div class="featured-race-shade"></div>

      <!-- LEFT CONTENT -->
      <div class="featured-race-content">
        <div class="featured-race-top-tags">
          <span class="next-race-label">NEXT RACE</span>
          <span class="race-status-tag">
            <i></i> <?php echo e($nextStatus); ?>
          </span>
        </div>
        <div class="featured-race-country"><?php echo e(strtoupper($nextRace['country'])); ?></div>
        <h2 class="featured-race-title"><?php echo e($nextRace['grand_prix']); ?> GP</h2>
        <div class="featured-race-circuit"><span>●</span> <?php echo e($nextRace['circuit']); ?></div>
        <a href="race-details.php?id=<?php echo (int)$nextRace['id']; ?>" class="btn-predict-now">MAKE PREDICTION NOW →</a>
      </div>

      <!-- RIGHT COUNTDOWN -->
      <div class="featured-race-right">
        <span class="countdown-label">🏁 COUNTDOWN</span>
        <div class="countdown-boxes" id="countdown">
          <div class="cd-box"><div class="cd-num" id="cd-days">00</div><div class="cd-txt">DAYS</div></div>
          <div class="cd-box"><div class="cd-num" id="cd-hours">00</div><div class="cd-txt">HOURS</div></div>
          <div class="cd-box"><div class="cd-num" id="cd-mins">00</div><div class="cd-txt">MINS</div></div>
        </div>
      </div>
    </section>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        initCountdown('<?php echo $nextRace['race_date'] . 'T' . $nextRace['race_time']; ?>');
      });
    </script>
  <?php endif; ?>

  <!-- 2-COLUMN CONTENT GRID -->
  <div class="dash-grid-layout">
    
    <!-- LEFT COLUMN: FAVORITES -->
    <div>
      <div class="dash-panel">
        <div class="panel-header">
          <div class="panel-title"><span class="icon">★</span> MY FAVORITE DRIVERS</div>
          <a href="drivers.php" class="panel-view-all">VIEW ALL →</a>
        </div>

        <?php if (empty($userFavDrivers)): ?>
          <div class="empty-box-dash">
            No favorite drivers added yet. <br>
            <a href="drivers.php" style="color:var(--dash-red); font-weight:700; margin-top:6px; display:inline-block;">Explore Drivers</a>
          </div>
        <?php else: ?>
          <?php foreach ($userFavDrivers as $fd): ?>
            <div class="item-card-row">
              <div class="driver-info-left">
                <img src="assets/images/drivers/<?php echo e($fd['image'] ?? 'placeholder.jpg'); ?>" class="driver-avatar-mini" alt="<?php echo e($fd['name']); ?>" onerror="this.src='https://via.placeholder.com/80/14161D/FFFFFF?text=F1'">
                <div>
                  <div class="item-name"><?php echo e($fd['name']); ?> <span style="color:#8b9bb4; font-size:0.8rem;">#<?php echo e($fd['number']); ?></span></div>
                  <div class="item-sub"><?php echo e($fd['team_name']); ?></div>
                </div>
              </div>
              <a href="driver-profile.php?id=<?php echo $fd['id']; ?>" class="btn-item-action">VIEW PROFILE →</a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="dash-panel">
        <div class="panel-header">
          <div class="panel-title"><span class="icon">🏎</span> MY FAVORITE TEAMS</div>
          <a href="teams.php" class="panel-view-all">VIEW ALL →</a>
        </div>

        <?php if (empty($userFavTeams)): ?>
          <div class="empty-box-dash">
            No favorite teams added yet. <br>
            <a href="teams.php" style="color:var(--dash-red); font-weight:700; margin-top:6px; display:inline-block;">Explore Teams</a>
          </div>
        <?php else: ?>
          <?php foreach ($userFavTeams as $ft): ?>
            <div class="item-card-row">
              <div>
                <div class="item-name"><?php echo e($ft['name']); ?></div>
                <div class="item-sub"><?php echo e($ft['points']); ?> Points</div>
              </div>
              <a href="team-profile.php?id=<?php echo $ft['id']; ?>" class="btn-item-action">VIEW TEAM →</a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT COLUMN: NOTIFICATIONS & PREDICTIONS -->
    <div>
      <div class="dash-panel">
        <div class="panel-header">
          <div class="panel-title"><span class="icon">🔔</span> RECENT NOTIFICATIONS</div>
          <a href="#" class="panel-view-all">VIEW ALL →</a>
        </div>

        <?php if (empty($notifications)): ?>
          <div class="empty-box-dash" style="display:flex; align-items:center; justify-content:center; gap:10px;">
            <span>🔔</span> You're all caught up! No new notifications.
          </div>
        <?php else: ?>
          <?php foreach ($notifications as $n): ?>
            <div class="item-card-row" style="<?php echo !$n['is_read'] ? 'border-left: 3px solid var(--dash-red);' : ''; ?>">
              <div>
                <div class="item-name" style="font-size:0.88rem;"><?php echo e($n['title']); ?></div>
                <div class="item-sub"><?php echo e($n['message']); ?></div>
              </div>
              <div class="item-sub" style="font-size:0.7rem;"><?php echo date('M d, H:i', strtotime($n['created_at'])); ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="dash-panel">
        <div class="panel-header">
          <div class="panel-title"><span class="icon">🎯</span> MY RECENT PREDICTIONS</div>
          <a href="predictions.php" class="panel-view-all">VIEW ALL →</a>
        </div>

        <?php if (empty($userPredictions)): ?>
          <div class="empty-box-dash">
            No predictions placed yet. <br>
            <a href="predictions.php" style="color:var(--dash-red); font-weight:700; margin-top:6px; display:inline-block;">Make a Prediction</a>
          </div>
        <?php else: ?>
          <?php foreach ($userPredictions as $p): ?>
            <div class="prediction-item">
              <div>
                <div class="item-name"><?php echo e($p['grand_prix']); ?></div>
                <div class="item-sub">Predicted: <strong style="color:#fff;"><?php echo e($p['driver_name']); ?></strong></div>
              </div>
              <span class="badge-pending">
                <?php echo e($p['prediction_status']); ?>
              </span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>