<?php
// index.php — FormulaHub Main Homepage
$pageTitle = "Home | The World of Formula 1";
require_once __DIR__ . '/includes/header.php';

// Fetch Next Upcoming Race
$nextRaceStmt = $pdo->query("SELECT * FROM races WHERE status = 'Upcoming' ORDER BY race_date ASC LIMIT 1");
$nextRace = $nextRaceStmt->fetch();

// Fetch Latest Completed Race Results (Top 3 Podium) with Driver images and Team logos
$latestResults = [];
$completedRace = $pdo->query("SELECT * FROM races WHERE status = 'Completed' ORDER BY race_date DESC LIMIT 1")->fetch();
if ($completedRace) {
    $resultsStmt = $pdo->prepare("SELECT r.*, d.name as driver_name, d.number, d.image as driver_image, t.name as team_name, t.logo as team_logo
                                FROM race_results r
                                JOIN drivers d ON r.driver_id = d.id
                                LEFT JOIN teams t ON d.team_id = t.id
                                WHERE r.race_id = ? ORDER BY r.position ASC LIMIT 3");
    $resultsStmt->execute([$completedRace['id']]);
    $latestResults = $resultsStmt->fetchAll();
}

// Fetch Featured Drivers
$drivers = $pdo->query("SELECT d.*, t.name as team_name FROM drivers d LEFT JOIN teams t ON d.team_id = t.id ORDER BY d.points DESC LIMIT 3")->fetchAll();

// Fetch Top Teams
$teams = $pdo->query("SELECT * FROM teams ORDER BY points DESC LIMIT 3")->fetchAll();

// Fetch Top 3 Fan Leaderboard
$leaderboard = $pdo->query("SELECT username, points, profile_image FROM users WHERE role = 'user' ORDER BY points DESC LIMIT 3")->fetchAll();

// Helper Functions for Constructor Cards styling
if (!function_exists('constructorTheme')) {
    function constructorTheme($name) {
        $name = strtolower((string)$name);
        $themes = [
            'red bull' => ['accent' => '#153B72', 'rgb' => '21, 59, 114', 'banner' => '#0B1E3D'],
            'redbull'  => ['accent' => '#153B72', 'rgb' => '21, 59, 114', 'banner' => '#0B1E3D'],
            'ferrari'  => ['accent' => '#E80020', 'rgb' => '232, 0, 32', 'banner' => '#7B0611'],
            'mclaren'  => ['accent' => '#FF8000', 'rgb' => '255, 128, 0', 'banner' => '#753300'],
            'mercedes' => ['accent' => '#00D7B9', 'rgb' => '0, 215, 185', 'banner' => '#064A49'],
            'aston'    => ['accent' => '#00A878', 'rgb' => '0, 168, 120', 'banner' => '#034A3B'],
            'alpine'   => ['accent' => '#FF87BC', 'rgb' => '255, 135, 188', 'banner' => '#59264B'],
            'williams' => ['accent' => '#1868DB', 'rgb' => '24, 104, 219', 'banner' => '#102C59'],
            'racing bulls' => ['accent' => '#6692FF', 'rgb' => '102, 146, 255', 'banner' => '#132D62'],
            'rb'       => ['accent' => '#6692FF', 'rgb' => '102, 146, 255', 'banner' => '#132D62'],
            'haas'     => ['accent' => '#E6002B', 'rgb' => '230, 0, 43', 'banner' => '#4B111C'],
            'audi'     => ['accent' => '#F50537', 'rgb' => '245, 5, 55', 'banner' => '#32131B'],
            'cadillac' => ['accent' => '#D6B779', 'rgb' => '214, 183, 121', 'banner' => '#3A3020']
        ];
        foreach ($themes as $keyword => $theme) {
            if (strpos($name, $keyword) !== false) return $theme;
        }
        return ['accent' => '#E10600', 'rgb' => '225, 6, 0', 'banner' => '#182333'];
    }
}

if (!function_exists('getTeamImage')) {
    function getTeamImage($team, $columns) {
        foreach ($columns as $column) {
            if (!isset($team[$column]) || trim((string)$team[$column]) === '') continue;
            $image = trim((string)$team[$column]);
            if (preg_match('~^https?://~i', $image) || strpos($image, '/') === 0) return $image;
            if (strpos($image, 'assets/') === 0) return $image;
            return 'assets/images/teams/' . basename($image);
        }
        return '';
    }
}

if (!function_exists('constructorFlag')) {
    function constructorFlag($nationality) {
        $flags = [
            'austrian'=>'🇦🇹','italian'=>'🇮🇹','british'=>'🇬🇧','german'=>'🇩🇪',
            'french'=>'🇫🇷','american'=>'🇺🇸','japanese'=>'🇯🇵','canadian'=>'🇨🇦',
            'spanish'=>'🇪🇸','mexican'=>'🇲🇽','swiss'=>'🇨🇭','australian'=>'🇦🇺',
            'dutch'=>'🇳🇱','new zealand'=>'🇳🇿'
        ];
        return $flags[strtolower(trim((string)$nationality))] ?? '🏁';
    }
}
?>

<!-- HERO SECTION -->
<section class="hero-home">
  <div class="hero-home__shade"></div>
  <div class="container hero-home__inner">
    <div class="hero-home__content">
      <div class="hero-live-pill"><span class="live-dot"></span> LIVE <span>SEASON 2026</span></div>
      <h1 class="hero-home__title">THE WORLD OF<br><strong>FORMULA 1</strong><br>AT YOUR FINGERTIPS.</h1>
      <p class="hero-home__subtitle">Follow races, drivers, teams and compete with fellow F1 fans around the globe in real-time predictions and leaderboards.</p>
      <div class="hero-home__actions">
        <a href="races.php" class="btn-primary hero-action-primary">EXPLORE RACES <span aria-hidden="true">→</span></a>
        <?php if(!is_logged_in()): ?>
          <a href="register.php" class="btn-secondary hero-action-secondary">JOIN FORMULAHUB</a>
        <?php else: ?>
          <a href="<?php echo is_admin() ? 'admin/dashboard.php' : 'dashboard.php'; ?>" class="btn-secondary hero-action-secondary"><?php echo is_admin() ? 'ADMIN DASHBOARD' : 'FAN DASHBOARD'; ?></a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($nextRace): ?>
    <aside class="hero-race-card" aria-label="Next race">
      <div class="hero-race-card__top">
        <span class="hero-race-card__label">🏁 NEXT RACE</span>
        <span class="hero-race-card__round">ROUND <?php echo e($nextRace['round']); ?> / 24</span>
      </div>
      <div class="hero-race-card__title-row">
        <h2><?php echo e($nextRace['grand_prix']); ?> GP</h2>
        <span class="hero-race-card__flag"><?php echo e($nextRace['country']); ?></span>
      </div>
      <div class="hero-race-card__meta">
        <span>▣ <?php echo date('d M Y', strtotime($nextRace['race_date'])); ?></span>
        <span>⌖ <?php echo e($nextRace['circuit']); ?></span>
      </div>
      <div class="hero-countdown" id="countdown">
        <div class="hero-countdown__unit"><strong id="cd-days">00</strong><span>DAYS</span></div>
        <div class="hero-countdown__unit"><strong id="cd-hours">00</strong><span>HRS</span></div>
        <div class="hero-countdown__unit"><strong id="cd-mins">00</strong><span>MIN</span></div>
        <div class="hero-countdown__unit"><strong id="cd-secs">00</strong><span>SEC</span></div>
      </div>
      <div class="hero-race-card__footer">
        <a href="race-details.php?id=<?php echo $nextRace['id']; ?>" class="hero-race-card__circuit">VIEW RACE DETAILS <span>→</span></a>
        <span class="hero-race-card__status">LIGHTS OUT</span>
      </div>
    </aside>
    <?php endif; ?>
  </div>
</section>

<?php if ($nextRace): ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    initCountdown('<?php echo $nextRace['race_date'] . 'T' . $nextRace['race_time']; ?>');
  });
</script>
<?php endif; ?>

<!-- SEASON STATS STRIP -->
<section class="home-stats-strip" aria-label="FormulaHub season overview">
  <div class="container home-stats-grid">
    <div class="home-stat">
      <span class="home-stat__icon" aria-hidden="true">
        <svg viewBox="0 0 32 32">
          <path d="M7 5v23"/>
          <path d="M8 6h17l-3 5 3 5H8z"/>
          <path d="M8 6h6v5H8z"/>
          <path d="M20 11h5"/>
          <path d="M14 11h6v5h-6z"/>
        </svg>
      </span>
      <div>
        <strong>24</strong>
        <span>RACES THIS SEASON</span>
      </div>
    </div>

    <div class="home-stat">
      <span class="home-stat__icon" aria-hidden="true">
        <svg viewBox="0 0 32 32">
          <path d="M7 21c0-7 4-13 10-13s10 5 10 12v3H11c-2 0-4-1-4-2z"/>
          <path d="M8 20h14l5 3"/>
          <path d="M12 14h13"/>
          <path d="M17 8v6"/>
          <path d="M11 24v4h11"/>
        </svg>
      </span>
      <div>
        <strong>20</strong>
        <span>DRIVERS</span>
      </div>
    </div>

    <div class="home-stat">
      <span class="home-stat__icon" aria-hidden="true">
        <svg viewBox="0 0 32 32">
          <path d="M5 20h3l3-8h10l4 8h2v6H5z"/>
          <path d="M11 12l3-4h5l3 4"/>
          <path d="M8 20h17"/>
          <circle cx="10" cy="26" r="2"/>
          <circle cx="23" cy="26" r="2"/>
        </svg>
      </span>
      <div>
        <strong>10</strong>
        <span>TEAMS</span>
      </div>
    </div>

    <div class="home-stat home-stat--live">
      <span class="home-stat__icon" aria-hidden="true">
        <svg viewBox="0 0 32 32">
          <circle cx="16" cy="16" r="3"/>
          <path d="M10 10a8 8 0 0 0 0 12M22 10a8 8 0 0 1 0 12M6 6a14 14 0 0 0 0 20M26 6a14 14 0 0 1 0 20"/>
        </svg>
      </span>
      <div>
        <strong>LIVE</strong>
        <span>RACE RESULTS</span>
      </div>
    </div>

    <div class="home-community-line">
      <strong>RACE. PREDICT. COMPETE.</strong>
      <span>YOUR F1 COMMUNITY.</span>
    </div>
  </div>
</section>

<!-- LATEST RESULTS SECTION (HIGH-END BROADCAST PODIUM CARDS) -->
<?php if ($completedRace && !empty($latestResults)): ?>
<section class="section" style="background: var(--bg-secondary);">
  <div class="container">
    <div class="section-header">
      <div>
        <h2 class="section-title">LATEST RACE PODIUM</h2>
        <p class="section-subtitle">Official Results — <?php echo e($completedRace['grand_prix']); ?></p>
      </div>
      <a href="results.php?race_id=<?php echo $completedRace['id']; ?>" class="btn-secondary" style="font-size:0.8rem;">FULL RESULTS →</a>
    </div>

    <div class="grid-3">
      <?php foreach ($latestResults as $res): ?>
        <?php 
          $theme = constructorTheme($res['team_name'] ?? '');
          $posClass = $res['position'] == 1 ? 'pos-p1' : ($res['position'] == 2 ? 'pos-p2' : 'pos-p3');
        ?>
        <article class="pro-podium-card <?php echo $posClass; ?>" style="--team-accent:<?= e($theme['accent']) ?>; --team-rgb:<?= e($theme['rgb']) ?>; --team-banner:<?= e($theme['banner']) ?>;">
          
          <div class="pro-card-top">
            <span class="pro-pos-badge">P<?php echo $res['position']; ?></span>
            <?php if (!empty($res['team_logo'])): ?>
              <img src="assets/images/teams/<?php echo e(basename($res['team_logo'])); ?>" alt="<?php echo e($res['team_name']); ?>" class="pro-team-logo" onerror="this.style.display='none'">
            <?php endif; ?>
          </div>

          <div class="pro-visual-area">
            <div class="pro-driver-glow"></div>
            <?php if (!empty($res['driver_image'])): ?>
              <img src="assets/images/drivers/<?php echo e($res['driver_image']); ?>" alt="<?php echo e($res['driver_name']); ?>" class="pro-driver-photo" onerror="this.src='https://via.placeholder.com/300x350/14161D/FFFFFF?text=DRIVER'">
            <?php else: ?>
              <img src="https://via.placeholder.com/300x350/14161D/FFFFFF?text=DRIVER" alt="Driver" class="pro-driver-photo">
            <?php endif; ?>
          </div>

          <div class="pro-info-area">
            <h3 class="pro-driver-name"><?php echo e($res['driver_name']); ?></h3>
            <div class="pro-team-name"><?php echo e($res['team_name']); ?></div>
            
            <div class="pro-stats-strip">
              <div class="pro-stat-item">
                <span class="pro-stat-icon">⭐</span>
                <div>
                  <strong><?php echo $res['points']; ?></strong>
                  <small>PTS</small>
                </div>
              </div>
              <div class="pro-stat-divider"></div>
              <div class="pro-stat-item">
                <span class="pro-stat-icon">⏱</span>
                <div>
                  <strong><?php echo e($res['time'] ?? '1:43.372'); ?></strong>
                  <small>TIME</small>
                </div>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- FEATURED DRIVERS -->
<section class="section featured-drivers-section">
  <div class="container">
    <div class="section-header">
      <div>
       <h2 class="section-title">FEATURED DRIVERS</h2>
        <p class="section-subtitle">Championship Standings Leaders</p>
      </div>
      <a href="drivers.php" class="btn-secondary" style="font-size:0.8rem;">VIEW ALL DRIVERS <span>→</span> </a>
    </div>

    <div class="featured-drivers-grid">
      <?php foreach ($drivers as $index => $d): ?>
        <article class="premium-driver-card">
          <div class="premium-driver-visual">
            <div class="driver-number-watermark">
              <?php echo e($d['number']); ?>
            </div>
            <span class="premium-driver-number">
              #<?php echo e($d['number']); ?>
            </span>
            <div class="driver-image-glow"></div>
            <img
              src="assets/images/drivers/<?php echo e($d['image']); ?>"
              alt="<?php echo e($d['name']); ?>"
              class="premium-driver-img"
              onerror="this.src='https://via.placeholder.com/500x600/14161D/FFFFFF?text=DRIVER'"
            >
            <div class="driver-bottom-gradient"></div>
            <div class="driver-position">
              <span>POS</span>
              <strong><?php echo $index + 1; ?></strong>
            </div>
          </div>

          <div class="premium-driver-content">
            <div class="premium-driver-team">
              <span class="team-line"></span>
              <?php echo e($d['team_name']); ?>
            </div>
            <h3 class="premium-driver-name">
              <?php echo e($d['name']); ?>
            </h3>
            <div class="premium-driver-stats">
              <div class="premium-stat">
                <strong><?php echo e($d['points']); ?></strong>
                <span>POINTS</span>
              </div>
              <div class="premium-stat">
                <strong><?php echo e($d['wins']); ?></strong>
                <span>WINS</span>
              </div>
              <div class="premium-stat">
                <strong><?php echo e($d['podiums']); ?></strong>
                <span>PODIUMS</span>
              </div>
            </div>
            <a
              href="driver-profile.php?id=<?php echo $d['id']; ?>"
              class="premium-profile-btn"
            >
              <span>VIEW PROFILE</span>
              <span class="profile-arrow">↗</span>
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TOP TEAMS / CONSTRUCTORS STANDINGS -->
<section class="section" style="background: var(--bg-secondary);">
  <div class="container">
    <div class="section-header">
      <div>
        <h2 class="section-title">CONSTRUCTORS STANDINGS</h2>
        <p class="section-subtitle">Top Formula 1 Teams</p>
      </div>
      <a href="teams.php" class="btn-secondary" style="font-size:0.8rem;">
        ALL CONSTRUCTORS <span>→</span>
      </a>
    </div>

    <div class="grid-3">
      <?php foreach ($teams as $t): ?>
        <?php
            $theme = constructorTheme($t['name'] ?? '');
            $carImage = getTeamImage($t, ['car_image','car_image_url','banner_image','team_image','image']);
            $logo = getTeamImage($t, ['logo','team_logo','logos']);
            $nationality = $t['nationality'] ?? '';
            $flag = constructorFlag($nationality);
        ?>
        <article class="constructor-card" style="--team-accent:<?= e($theme['accent']) ?>;--team-rgb:<?= e($theme['rgb']) ?>;--team-banner:<?= e($theme['banner']) ?>;">
            <div class="constructor-visual">
                <?php if ($carImage !== ''): ?>
                    <img class="constructor-car-image" src="<?= e($carImage) ?>" alt="<?= e($t['name']) ?> car" onerror="this.style.display='none'">
                <?php endif; ?>
                <div class="constructor-visual-shade"></div>
                <div class="constructor-topline">
                    <span class="constructor-nationality"><span><?= $flag ?></span><?= e($nationality) ?></span>
                </div>
                <div class="constructor-brand">
                    <?php if ($logo !== ''): ?>
                        <img class="constructor-logo" src="<?= e($logo) ?>" alt="<?= e($t['name']) ?> logo" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <h2><?= e($t['name']) ?></h2>
                </div>
            </div>
            <div class="constructor-content">
                <div class="constructor-stats">
                    <div class="constructor-stat"><span class="constructor-stat-value"><?= e($t['points'] ?? 0) ?></span><span class="constructor-stat-label">PTS</span></div>
                    <div class="constructor-stat"><span class="constructor-stat-value"><?= e($t['wins'] ?? 0) ?></span><span class="constructor-stat-label">WINS</span></div>
                </div>
                <div class="fav-card-actions" style="margin-top: auto;">
                    <a href="team-profile.php?id=<?= (int)$t['id'] ?>" class="btn-action-profile" style="width: 100%; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(114, 129, 150, 0.3); color: #e2e8f0; font-size: 0.78rem; font-weight: 800; padding: 8px 12px; border-radius: 5px; text-decoration: none;">TEAM PROFILE →</a>
                </div>
            </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FAN LEADERBOARD PREVIEW -->
<section class="section home-leaderboard-section">
    <div class="container">
        <div class="section-header leaderboard-header-flex">
            <div>
                <h2 class="section-title">FAN LEADERBOARD</h2>
                <p class="section-subtitle">Top Predictors & Community Members</p>
            </div>
            <a href="leaderboard.php" class="btn-primary leaderboard-view-btn">VIEW FULL LEADERBOARD</a>
        </div>

        <?php
        $p1 = $leaderboard[0] ?? null;
        $p2 = $leaderboard[1] ?? null;
        $p3 = $leaderboard[2] ?? null;
        ?>

        <div class="podium-showcase">
            <?php if ($p2): ?>
                <?php $badge2 = get_user_badge($p2['points']); ?>
                <div class="podium-stage p2-stage">
                    <div class="podium-badge-tag p2-tag">P2</div>
                    <div class="podium-avatar">
                        <?php if (!empty($p2['profile_image'])): ?>
                            <img src="assets/images/avatar/<?php echo e($p2['profile_image']); ?>" alt="<?php echo e($p2['username']); ?>">
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

            <?php if ($p1): ?>
                <?php $badge1 = get_user_badge($p1['points']); ?>
                <div class="podium-stage p1-stage">
                    <div class="podium-crown">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
                            <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/>
                        </svg>
                    </div>
                    <div class="podium-badge-tag p1-tag">CHAMPION</div>
                    <div class="podium-avatar p1-avatar">
                        <?php if (!empty($p1['profile_image'])): ?>
                            <img src="assets/images/avatar/<?php echo e($p1['profile_image']); ?>" alt="<?php echo e($p1['username']); ?>">
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

            <?php if ($p3): ?>
                <?php $badge3 = get_user_badge($p3['points']); ?>
                <div class="podium-stage p3-stage">
                    <div class="podium-badge-tag p3-tag">P3</div>
                    <div class="podium-avatar">
                        <?php if (!empty($p3['profile_image'])): ?>
                            <img src="assets/images/avatar/<?php echo e($p3['profile_image']); ?>" alt="<?php echo e($p3['username']); ?>">
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
    </div>
</section>

<style>
/* =========================================================
   NEW PRO BROADCAST PODIUM CARDS (PREMIUM GLOW LOOK)
   ========================================================= */
.pro-podium-card {
  position: relative;
  background: linear-gradient(180deg, var(--team-banner, #0f121d) 0%, #07090e 100%);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-top: 3px solid var(--team-accent, #E10600);
  border-radius: 16px;
  padding: 20px 20px 18px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  overflow: hidden;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
  transition: transform 0.35s ease, box-shadow 0.35s ease, border-color 0.35s ease;
}

.pro-podium-card:hover {
  transform: translateY(-8px);
  border-color: rgba(var(--team-rgb, 225,6,0), 0.8);
  box-shadow: 0 20px 45px rgba(0,0,0,0.8), 0 0 30px rgba(var(--team-rgb, 225,6,0), 0.3);
}

.pro-card-top {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  z-index: 2;
}

.pro-pos-badge {
  font-family: var(--font-heading);
  font-size: 0.9rem;
  font-weight: 900;
  padding: 4px 12px;
  border-radius: 20px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.pos-p1 .pro-pos-badge { background: rgba(245, 185, 66, 0.2); color: #F5B942; border: 1px solid rgba(245, 185, 66, 0.5); }
.pos-p2 .pro-pos-badge { background: rgba(203, 213, 225, 0.2); color: #CBD5E1; border: 1px solid rgba(203, 213, 225, 0.5); }
.pos-p3 .pro-pos-badge { background: rgba(217, 119, 6, 0.2); color: #D97706; border: 1px solid rgba(217, 119, 6, 0.5); }

.pro-team-logo {
  height: 26px;
  max-width: 65px;
  object-fit: contain;
  filter: drop-shadow(0 2px 5px rgba(0,0,0,0.5));
}

.pro-visual-area {
  position: relative;
  width: 100%;
  height: 220px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
}

.pro-driver-glow {
  position: absolute;
  width: 140px;
  height: 140px;
  background: var(--team-accent, #E10600);
  opacity: 0.25;
  filter: blur(40px);
  border-radius: 50%;
  z-index: 0;
  transition: opacity 0.35s ease;
}

.pro-podium-card:hover .pro-driver-glow {
  opacity: 0.45;
}

.pro-driver-photo {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  border-radius: 10px;
  transition: transform 0.4s ease;
}

.pro-podium-card:hover .pro-driver-photo {
  transform: scale(1.05);
}

.pro-info-area {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  z-index: 2;
}

.pro-driver-name {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  font-weight: 900;
  color: #fff;
  text-transform: uppercase;
  margin: 0 0 3px 0;
  line-height: 1.1;
  letter-spacing: 0.3px;
}

.pro-team-name {
  font-size: 0.82rem;
  color: #94a3b8;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 16px;
  letter-spacing: 0.5px;
}

.pro-stats-strip {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-around;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 10px 8px;
}

.pro-stat-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pro-stat-icon {
  font-size: 1.1rem;
}

.pro-stat-item strong {
  display: block;
  font-size: 0.95rem;
  color: #fff;
  font-weight: 800;
  line-height: 1;
}

.pro-stat-item small {
  font-size: 0.65rem;
  color: #64748b;
  font-weight: 800;
  letter-spacing: 0.5px;
}

.pro-stat-divider {
  width: 1px;
  height: 22px;
  background: rgba(255, 255, 255, 0.1);
}

.leaderboard-view-btn {
  font-size: 0.8rem;
}

/* Responsive Styles */
@media screen and (max-width: 991px) {
  .grid-3 {
    grid-template-columns: 1fr !important;
    gap: 20px;
  }
}

@media screen and (max-width: 768px) {
  .leaderboard-header-flex {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 15px;
  }
  .leaderboard-view-btn {
    width: 100%;
    text-align: center;
  }
  .podium-showcase {
    flex-direction: column !important;
    align-items: center !important;
    gap: 20px !important;
  }
  .podium-stage {
    width: 100% !important;
    max-width: 320px;
  }
  .p1-stage {
    order: -1;
  }
}
</style>

<style>
/* PODIUM SHOWCASE & CONSTRUCTORS CARDS STYLING */
.home-leaderboard-section {
  position: relative;
  background: radial-gradient(circle at 50% 0%, rgba(245, 185, 66, 0.05), transparent 70%), var(--bg-primary);
}

.podium-showcase {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  gap: 24px;
  margin-top: 40px;
  padding: 20px 0;
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

.p1-stage {
  max-width: 350px;
  border-color: rgba(245, 185, 66, 0.4);
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5), 0 0 30px rgba(245, 185, 66, 0.15);
  background: linear-gradient(180deg, rgba(30, 26, 16, 0.95) 0%, rgba(14, 13, 10, 0.98) 100%);
}

.p2-stage { border-color: rgba(203, 213, 225, 0.3); }
.p3-stage { border-color: rgba(217, 119, 6, 0.3); }

.podium-crown {
  font-size: 2.2rem;
  margin-bottom: -10px;
  filter: drop-shadow(0 0 10px rgba(245, 185, 66, 0.6));
}

.podium-badge-tag {
  font-family: var(--font-heading);
  font-size: 0.68rem;
  font-weight: 900;
  letter-spacing: 1px;
  padding: 4px 12px;
  border-radius: 20px;
  margin-bottom: 14px;
}

.p1-tag { background: rgba(245, 185, 66, 0.2); color: var(--gold); border: 1px solid rgba(245, 185, 66, 0.4); }
.p2-tag { background: rgba(203, 213, 225, 0.15); color: var(--silver); border: 1px solid rgba(203, 213, 225, 0.3); }
.p3-tag { background: rgba(217, 119, 6, 0.15); color: var(--bronze); border: 1px solid rgba(217, 119, 6, 0.3); }

.podium-avatar {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: var(--bg-card);
  border: 2px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-heading);
  font-size: 1.6rem;
  font-weight: 900;
  color: #fff;
  margin-bottom: 12px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
}

.p1-avatar {
  width: 82px;
  height: 82px;
  font-size: 2rem;
  border-color: var(--gold);
  background: radial-gradient(circle, rgba(245, 185, 66, 0.2), var(--bg-card));
  box-shadow: 0 0 25px rgba(245, 185, 66, 0.3);
}

.podium-user-name {
  font-size: 1.35rem;
  margin-bottom: 4px;
  color: #fff;
}

.podium-title-badge {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--text-muted);
  background: rgba(255, 255, 255, 0.05);
  padding: 3px 10px;
  border-radius: 4px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 14px;
}

.p1-badge {
  color: var(--gold);
  background: rgba(245, 185, 66, 0.12);
  border-color: rgba(245, 185, 66, 0.25);
}

.podium-points {
  font-family: var(--font-heading);
  font-size: 1.6rem;
  font-weight: 900;
  color: #fff;
  margin-bottom: 18px;
}

.podium-points small {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.p1-points {
  font-size: 2.1rem;
  color: var(--gold);
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
  font-family: var(--font-heading);
  font-size: 2.2rem;
  font-weight: 900;
  line-height: 1;
  opacity: 0.7;
}

.p1-pedestal .pedestal-rank { font-size: 2.8rem; color: var(--gold); opacity: 0.9; }
.p2-pedestal .pedestal-rank { color: var(--silver); }
.p3-pedestal .pedestal-rank { color: var(--bronze); }

.constructor-card {
    --team-accent: #e10600;
    --team-rgb: 225,6,0;
    --team-banner: #182333;
    display: flex;
    flex-direction: column;
    min-width: 0;
    overflow: hidden;
    position: relative;
    background: linear-gradient(145deg, #0a111b, #04080d 80%);
    border: 1px solid #303b49;
    border-left: 4px solid var(--team-accent);
    border-radius: 7px;
    box-shadow: 0 7px 20px rgba(0,0,0,.2);
    transition: transform .22s ease, border-color .22s ease, box-shadow .22s ease;
    padding: 0 !important;
}

.constructor-card:hover {
    transform: translateY(-3px);
    border-color: rgba(var(--team-rgb), .75);
    box-shadow: 0 12px 28px rgba(0,0,0,.36);
}

.constructor-visual {
    position: relative;
    isolation: isolate;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 126px;
    padding: 0;
    margin: 0;
    overflow: hidden;
    background: var(--team-banner);
}

.constructor-car-image {
    position: absolute;
    inset: 0;
    display: block;
    width: 100% !important;
    height: 100% !important;
    object-fit: cover;
    object-position: 120% center;
    z-index: -2;
    opacity: 1;
    filter: brightness(1.12) contrast(1.08) saturate(1.08);
    transform: scale(1.015) translateX(15%);
    transition: transform .4s ease;
}

.constructor-card:hover .constructor-car-image {
    transform: scale(1.07) translateX(10%);
}

.constructor-visual-shade {
    position: absolute;
    inset: 0;
    z-index: -1;
    background: linear-gradient(90deg, rgba(5,9,15,.92) 0%, rgba(5,9,15,.78) 28%, rgba(5,9,15,.35) 53%, rgba(5,9,15,.03) 82%);
}

.constructor-topline {
    display: flex;
    align-items: center;
    gap: 18px;
    position: relative;
    z-index: 2;
    margin: 0;
    padding: 15px 20px 0;
}

.constructor-nationality {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #bdc8d8;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .35px;
}

.constructor-brand {
    position: relative;
    z-index: 2;
    margin: 17px 12px 12px 20px;
}

.constructor-logo {
    display: block;
    width: auto;
    max-width: 105px;
    height: 27px;
    margin: 0 0 7px;
    object-fit: contain;
}

.constructor-brand h2 {
    margin: 0;
    color: #f5f7fb;
    font-size: 1.1rem;
    font-weight: 800;
    text-transform: uppercase;
}

.constructor-content {
    display: flex;
    flex-direction: column;
    flex: 1;
    padding: 14px 20px;
}

.constructor-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    padding-bottom: 10px;
    margin-bottom: 13px;
    border-bottom: 1px solid rgba(150, 170, 194, .09);
}

.constructor-stat {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.constructor-stat-value {
    color: #f2f5fa;
    font-size: 18px;
    font-weight: 800;
}

.constructor-stat-label {
    color: #a3b2c5;
    font-size: 10px;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>