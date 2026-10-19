<?php
/**
 * team-profile.php - Detailed Dynamic Team Profile Page
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$teamId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$teamId]);
$team = $stmt->fetch();

if (!$team) {
    require_once __DIR__ . '/includes/header.php';
    echo "<div class='container' style='padding:100px 0; text-align:center; color:#fff;'><h2>Team Not Found</h2><a href='teams.php' class='btn-primary-fl' style='margin-top:20px; display:inline-block;'>Back to Teams</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

$driversStmt = $pdo->prepare("SELECT * FROM drivers WHERE team_id = ? ORDER BY points DESC");
$driversStmt->execute([$team['id']]);
$teamDrivers = $driversStmt->fetchAll();

$isFav = is_logged_in() ? is_favorited($_SESSION['user_id'], 'team', $team['id']) : false;

/**
 * Convert Hex Color to RGBA for smooth broadcast glow
 */
function hexToRgba($hex, $alpha = 0.5) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    return "rgba($r, $g, $b, $alpha)";
}

/**
 * Dynamic F1 Team Color Palette Resolver (Exact Match with teams layout)
 */
function get_team_brand_palette($team) {
    if (!empty($team['color']) && trim($team['color']) !== '' && $team['color'] !== '#000000') {
        $c = $team['color'];
        return [
            'accent' => $c,
            'rgb'    => hexToRgba($c, 1),
            'banner' => $c
        ];
    }

    $name = strtolower($team['name'] ?? '');
    
    $palettes = [
        'red bull'     => ['accent' => '#153B72', 'rgb' => '21, 59, 114',  'banner' => '#0B1E3D'],
        'redbull'      => ['accent' => '#153B72', 'rgb' => '21, 59, 114',  'banner' => '#0B1E3D'],
        'ferrari'      => ['accent' => '#E80020', 'rgb' => '232, 0, 32',   'banner' => '#7B0611'],
        'mclaren'      => ['accent' => '#FF8000', 'rgb' => '255, 128, 0',  'banner' => '#753300'],
        'mercedes'     => ['accent' => '#00D7B9', 'rgb' => '0, 215, 185',  'banner' => '#064A49'],
        'aston'        => ['accent' => '#00A878', 'rgb' => '0, 168, 120',  'banner' => '#034A3B'],
        'alpine'       => ['accent' => '#FF87BC', 'rgb' => '255, 135, 188', 'banner' => '#59264B'],
        'williams'     => ['accent' => '#1868DB', 'rgb' => '24, 104, 219', 'banner' => '#102C59'],
        'racing bulls' => ['accent' => '#6692FF', 'rgb' => '102, 146, 255', 'banner' => '#132D62'],
        'rb'           => ['accent' => '#6692FF', 'rgb' => '102, 146, 255', 'banner' => '#132D62'],
        'haas'         => ['accent' => '#E6002B', 'rgb' => '230, 0, 43',   'banner' => '#4B111C'],
        'audi'         => ['accent' => '#F50537', 'rgb' => '245, 5, 55',   'banner' => '#32131B'],
        'cadillac'     => ['accent' => '#D6B779', 'rgb' => '214, 183, 121', 'banner' => '#3A3020']
    ];

    foreach ($palettes as $key => $palette) {
        if (strpos($name, $key) !== false) {
            return $palette;
        }
    }

    return ['accent' => '#E10600', 'rgb' => '225, 6, 0', 'banner' => '#500200'];
}

/**
 * Image resolution helper function
 */
$resolveImg = function ($val, $folder, $default = '') {
    $val = trim((string) $val);
    if ($val === '') {
        return $default !== '' ? 'assets/images/' . trim($folder, '/') . '/' . $default : '';
    }
    if (strpos($val, 'http://') === 0 || strpos($val, 'https://') === 0) {
        return $val;
    }
    if (strpos($val, 'assets/') === 0 || strpos($val, '/assets/') === 0) {
        return '/' . ltrim($val, '/');
    }
    return 'assets/images/' . trim($folder, '/') . '/' . basename($val);
};

/**
 * String sanitizer helper for UTF-8 names
 */
function clean_driver_name($name) {
    return htmlspecialchars(mb_convert_encoding($name, 'UTF-8', 'UTF-8'), ENT_QUOTES, 'UTF-8');
}

// Resolve Palette and Variables
$palette       = get_team_brand_palette($team);
$teamColor     = $palette['accent'];
$teamRgb       = $palette['rgb'];
$teamBannerHex = $palette['banner'];

$teamColorGlow = "rgba({$teamRgb}, 0.50)";
$teamColorDark = "rgba({$teamRgb}, 0.15)";
$teamBannerRgba = hexToRgba($teamBannerHex, 0.85);

$teamLogo  = $resolveImg($team['logo'] ?? '', 'teams', 'default.png');
$teamFlag  = $resolveImg($team['country_flag'] ?? $team['flag'] ?? '', 'flags', '');

// Resolving Team Car Image
$carImageVal = $team['car_image'] ?? $team['image'] ?? $team['hero_image'] ?? '';
$teamCarImg  = $resolveImg($carImageVal, 'teams', 'default-car.png');

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* DYNAMIC TEAM PROFILE DIRECT COLOR INJECTION */
.team-profile-page {
    --page-max: 1280px;
    background: #05090e;
    min-height: 85vh;
    padding-bottom: 80px;
    color: #f4f6fa;
}

/* HERO CARD CONTAINER */
.team-hero-card {
    position: relative;
    width: min(92%, var(--page-max));
    margin: 40px auto 50px;
    background: linear-gradient(135deg, <?php echo $teamBannerRgba; ?> 0%, rgba(5, 10, 17, 0.95) 80%);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 16px;
    padding: 40px 40px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.6), 0 0 35px <?php echo $teamColorDark; ?>;
    overflow: hidden;
    isolation: isolate;
    min-height: 380px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* LEFT ACCENT BAR */
.team-hero-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
    background: <?php echo e($teamColor); ?>;
    box-shadow: 0 0 20px <?php echo e($teamColor); ?>;
    z-index: 5;
}

/* LEFT HERO CONTENT */
.team-hero-content {
    position: relative;
    z-index: 4;
    max-width: 580px;
    flex: 1;
}

.team-hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.team-country-tag {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.78rem;
    font-weight: 900;
    color: <?php echo e($teamColor); ?>;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.team-country-tag img {
    width: 22px;
    height: 15px;
    object-fit: cover;
    border-radius: 2px;
}

.team-title {
    font-size: clamp(30px, 3.8vw, 52px);
    font-weight: 900;
    font-style: italic;
    color: #ffffff;
    text-transform: uppercase;
    margin: 8px 0 12px;
    line-height: 1.05;
    letter-spacing: -0.5px;
}

.team-desc {
    color: #a3b2c5;
    font-size: 0.95rem;
    line-height: 1.65;
    margin-bottom: 28px;
}

/* RIGHT CAR DISPLAY WRAPPER - CLEAN, HIGH-VISIBILITY & VIBRANT */
.team-car-display-wrap {
    position: relative;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48%;
    max-width: 550px;
    height: 100%;
}

.team-car-bg-img {
    width: 100%;
    height: auto;
    max-height: 280px;
    object-fit: contain;
    filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.7));
    transition: transform 0.3s ease;
}

.team-car-bg-img:hover {
    transform: scale(1.03);
}

/* STATS CARDS */
.team-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 160px);
    gap: 15px;
}

.team-stat-card {
    background: rgba(8, 14, 22, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-left: 3px solid <?php echo e($teamColor); ?>;
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
}

.team-stat-lbl {
    font-size: 0.65rem;
    font-weight: 900;
    color: #7b8a9e;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.team-stat-val {
    font-size: 1.4rem;
    font-weight: 900;
    color: #ffffff;
}

.team-stat-val.gold {
    color: #ffd700;
}

/* FAVORITE BUTTON */
.btn-fav-team {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(13, 22, 35, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 10px 22px;
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 850;
    text-transform: uppercase;
    cursor: pointer;
    backdrop-filter: blur(10px);
    transition: all 0.25s ease;
}

.btn-fav-team:hover, .btn-fav-team.active {
    background: <?php echo $teamColorDark; ?>;
    border-color: <?php echo e($teamColor); ?>;
    color: #ffffff;
    box-shadow: 0 0 20px <?php echo $teamColorGlow; ?>;
}

/* DRIVERS SECTION */
.team-drivers-container {
    width: min(92%, var(--page-max));
    margin-inline: auto;
}

.section-header-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.25rem;
    font-weight: 900;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0;
}

.title-border {
    width: 4px;
    height: 20px;
    background: <?php echo e($teamColor); ?>;
    box-shadow: 0 0 10px <?php echo e($teamColor); ?>;
}

.team-watermark-lbl {
    font-size: 0.85rem;
    font-weight: 900;
    color: rgba(255, 255, 255, 0.15);
    text-transform: uppercase;
    letter-spacing: 2px;
    font-style: italic;
}

/* SIDE-BY-SIDE DRIVER CARDS GRID */
.drivers-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}

.driver-profile-card {
    position: relative;
    background: linear-gradient(145deg, rgba(13, 22, 35, 0.9) 0%, rgba(5, 10, 17, 0.96) 100%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 14px;
    padding: 26px 24px;
    display: grid;
    grid-template-columns: 160px 1fr;
    gap: 22px;
    align-items: center;
    box-shadow: 0 15px 35px rgba(0,0,0,0.5);
    backdrop-filter: blur(12px);
    overflow: hidden;
}

/* LEFT SIDE: PORTRAIT WITH MATCHING TEAM COLOR GLOW */
.driver-portrait-wrap {
    position: relative;
    height: 210px;
    background: radial-gradient(circle at 50% 30%, <?php echo $teamColorGlow; ?> 0%, rgba(8, 14, 22, 0.95) 80%);
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.driver-num-bg {
    position: absolute;
    top: 6px;
    left: 10px;
    font-size: 3.4rem;
    font-weight: 900;
    font-style: italic;
    color: rgba(255, 255, 255, 0.08);
    line-height: 1;
}

.driver-team-logo {
    position: absolute;
    top: 12px;
    right: 12px;
    height: 20px;
    object-fit: contain;
}

.driver-portrait-img {
    height: 100%;
    width: auto;
    max-width: 100%;
    object-fit: contain;
    object-position: center bottom;
    position: relative;
    z-index: 2;
}

/* RIGHT SIDE: INFO & STATS */
.driver-info-wrap {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.driver-meta-sub {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    color: #7b8a9e;
    font-weight: 800;
    margin-bottom: 6px;
    text-transform: uppercase;
}

.driver-meta-sub img {
    width: 18px;
    height: 12px;
    object-fit: cover;
    border-radius: 2px;
}

.driver-fullname {
    font-size: 1.25rem;
    font-weight: 900;
    color: #ffffff;
    text-transform: uppercase;
    margin: 0 0 14px;
    letter-spacing: -0.2px;
    line-height: 1.2;
}

.driver-stats-bar {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 8px;
    padding: 10px 8px;
    text-align: center;
    margin-bottom: 14px;
}

.driver-stat-val {
    font-size: 1.15rem;
    font-weight: 900;
    color: #ffffff;
}

.driver-stat-lbl {
    font-size: 0.62rem;
    font-weight: 900;
    color: #7b8a9e;
    letter-spacing: 0.5px;
}

.btn-driver-profile {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 10px;
    color: #ffffff;
    font-size: 0.78rem;
    font-weight: 850;
    text-transform: uppercase;
    text-decoration: none;
    letter-spacing: 0.5px;
    transition: all 0.25s ease;
}

.btn-driver-profile:hover {
    background: <?php echo $teamColorDark; ?>;
    border-color: <?php echo e($teamColor); ?>;
    color: #ffffff;
}

@media (max-width: 992px) {
    .team-hero-card {
        flex-direction: column;
        text-align: center;
        padding: 30px 20px;
    }
    .team-hero-content {
        max-width: 100%;
        margin-bottom: 25px;
    }
    .team-car-display-wrap {
        width: 100%;
        max-width: 100%;
    }
    .drivers-grid {
        grid-template-columns: 1fr;
    }
    .team-stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 600px) {
    .team-stats-grid {
        grid-template-columns: 1fr;
    }
    .driver-profile-card {
        grid-template-columns: 1fr;
    }
    .driver-portrait-wrap {
        height: 220px;
    }
}
</style>

<section class="team-profile-page">
    <!-- DYNAMIC HERO SECTION -->
    <div class="team-hero-card">
        <!-- LEFT SIDE CONTENT -->
        <div class="team-hero-content">
            <div class="team-hero-top">
                <div class="team-country-tag">
                    <?php if ($teamFlag !== ''): ?>
                        <img src="<?php echo e($teamFlag); ?>" alt="" onerror="this.style.display='none';">
                    <?php endif; ?>
                    <span><?php echo e($team['nationality'] ?? 'INTERNATIONAL'); ?> CONSTRUCTOR</span>
                </div>
                <?php if (is_logged_in()): ?>
                    <button onclick="toggleFavorite('team', <?php echo $team['id']; ?>, this)" class="btn-fav-team <?php echo $isFav ? 'active' : ''; ?>">
                        <?php echo $isFav ? '★ FAVORITED' : '☆ ADD TO FAVORITES'; ?>
                    </button>
                <?php endif; ?>
            </div>

            <h1 class="team-title"><?php echo e($team['name']); ?></h1>
            <p class="team-desc"><?php echo e($team['description'] ?? 'Competing at the pinnacle of motorsport with cutting-edge engineering and passion.'); ?></p>

            <!-- STATS GRID -->
            <div class="team-stats-grid">
                <div class="team-stat-card">
                    <div>
                        <div class="team-stat-lbl">POINTS</div>
                        <div class="team-stat-val gold"><?php echo (int)($team['points'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="team-stat-card">
                    <div>
                        <div class="team-stat-lbl">TOTAL WINS</div>
                        <div class="team-stat-val"><?php echo (int)($team['wins'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="team-stat-card">
                    <div>
                        <div class="team-stat-lbl">PODIUMS</div>
                        <div class="team-stat-val"><?php echo (int)($team['podiums'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE CAR DISPLAY (PROPER & CRISP LIGHTING) -->
        <div class="team-car-display-wrap">
            <img src="<?php echo e($teamCarImg); ?>" alt="<?php echo e($team['name']); ?> Car" class="team-car-bg-img" onerror="this.style.display='none';">
        </div>
    </div>

    <!-- DRIVERS ROSTER SECTION -->
    <div class="team-drivers-container">
        <div class="section-header-wrap">
            <div class="section-title">
                <span class="title-border"></span>
                <span>OFFICIAL TEAM DRIVERS</span>
            </div>
            <span class="team-watermark-lbl"><?php echo e($team['name']); ?></span>
        </div>

        <div class="drivers-grid">
            <?php foreach ($teamDrivers as $d): 
                $dImg = $resolveImg($d['image'] ?? '', 'drivers', 'default.png');
                $dFlag = $resolveImg($d['flag'] ?? '', 'flags', '');
                $cleanDriverName = clean_driver_name($d['name']);
                $cleanHandle = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $d['name']));
            ?>
                <div class="driver-profile-card">
                    <!-- LEFT SIDE: PORTRAIT -->
                    <div class="driver-portrait-wrap">
                        <span class="driver-num-bg">#<?php echo e($d['number']); ?></span>
                        <?php if ($teamLogo !== ''): ?>
                            <img src="<?php echo e($teamLogo); ?>" alt="" class="driver-team-logo" onerror="this.style.display='none';">
                        <?php endif; ?>
                        <img src="<?php echo e($dImg); ?>" alt="<?php echo $cleanDriverName; ?>" class="driver-portrait-img" onerror="this.src='assets/images/drivers/default.png'">
                    </div>

                    <!-- RIGHT SIDE: INFO & STATS -->
                    <div class="driver-info-wrap">
                        <div class="driver-meta-sub">
                            <?php if ($dFlag !== ''): ?>
                                <img src="<?php echo e($dFlag); ?>" alt="" onerror="this.style.display='none';">
                            <?php endif; ?>
                            <span><?php echo e($d['nationality'] ?? 'N/A'); ?></span>
                            <span style="opacity:0.3;">|</span>
                            <span>@<?php echo $cleanHandle; ?></span>
                        </div>

                        <h3 class="driver-fullname"><?php echo $cleanDriverName; ?></h3>

                        <div class="driver-stats-bar">
                            <div>
                                <div class="driver-stat-val"><?php echo (int)($d['points'] ?? 0); ?></div>
                                <div class="driver-stat-lbl">PTS</div>
                            </div>
                            <div>
                                <div class="driver-stat-val"><?php echo (int)($d['wins'] ?? 0); ?></div>
                                <div class="driver-stat-lbl">WINS</div>
                            </div>
                            <div>
                                <div class="driver-stat-val"><?php echo (int)($d['podiums'] ?? 0); ?></div>
                                <div class="driver-stat-lbl">PODIUMS</div>
                            </div>
                        </div>

                        <a href="driver-profile.php?id=<?php echo $d['id']; ?>" class="btn-driver-profile">
                            <span>VIEW DRIVER PROFILE</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
function toggleFavorite(itemType, itemId, btnElement) {
    fetch('favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=toggle_favorite&item_type=' + encodeURIComponent(itemType) + '&item_id=' + encodeURIComponent(itemId)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'added') {
            btnElement.classList.add('active');
            btnElement.innerHTML = '★ FAVORITED';
        } else if (data.status === 'removed') {
            btnElement.classList.remove('active');
            btnElement.innerHTML = '☆ ADD TO FAVORITES';
        } else if (data.status === 'unauthorized') {
            window.location.href = 'login.php';
        }
    })
    .catch(err => console.error(err));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>