<?php
// driver-profile.php — Broadcast-Grade Driver Profile with Dynamic Team Colors
$pageTitle = "Driver Profile";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$driverId = (int)($_GET['id'] ?? 0);

// Fixed Query: Left Join to fetch team color or name for palette mapping
$stmt = $pdo->prepare("SELECT d.*, t.name as team_name, t.logo as team_logo, t.nationality as team_country FROM drivers d LEFT JOIN teams t ON d.team_id = t.id WHERE d.id = ?");
$stmt->execute([$driverId]);
$driver = $stmt->fetch();

if (!$driver) {
    require_once __DIR__ . '/includes/header.php';
    echo "<div class='container' style='padding:100px 0; text-align:center; color:#fff;'><h2>Driver Not Found</h2><a href='drivers.php' class='btn-primary-f1' style='margin-top:20px; display:inline-block;'>Back to Drivers</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

$isFav = is_logged_in() ? is_favorited($_SESSION['user_id'], 'driver', $driver['id']) : false;

/* Convert Hex Color to RGBA for smooth broadcast glow */
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

/* Dynamic F1 Team Color Palette Resolver (Exact Match with teams layout) */
function get_team_brand_palette($driver) {
    if (!empty($driver['team_color']) && trim($driver['team_color']) !== '' && $driver['team_color'] !== '#000000') {
        $c = $driver['team_color'];
        return [
            'accent' => $c,
            'rgb'    => hexToRgba($c, 1),
            'banner' => $c
        ];
    }

    $name = strtolower($driver['team_name'] ?? '');
    
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

/* Helper to resolve image paths safely */
$resolveImg = function($val, $folder, $default = '') {
    $val = trim((string)$val);
    if ($val === '') {
        return $default !== '' ? 'assets/images/' . trim($folder, '/') . '/' . $default : '';
    }
    if (strpos($val, 'http://') === 0 || strpos($val, 'https://') === 0) {
        return $val;
    }
    if (strpos($val, 'assets/') === 0 || strpos($val, '/assets/') === 0) {
        return ltrim($val, '/');
    }
    return 'assets/images/' . trim($folder, '/') . '/' . basename($val);
};

$driverImg  = $resolveImg($driver['image'] ?? '', 'drivers', 'default.png');
$driverFlag = $resolveImg($driver['flag'] ?? '', 'flags', '');
$teamLogo   = $resolveImg($driver['team_logo'] ?? '', 'teams', '');

/* Resolve Palette & Variables */
$palette       = get_team_brand_palette($driver);
$teamColor     = $palette['accent'];
$teamRgb       = $palette['rgb'];
$teamBannerHex = $palette['banner'];

$teamColorGlow = "rgba({$teamRgb}, 0.50)";
$teamColorDark = "rgba({$teamRgb}, 0.20)";

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* =========================================================
   ULTRA-PREMIUM F1 DRIVER PROFILE — DYNAMIC TEAM THEME
========================================================= */
.driver-profile-page {
    --page-max: 1280px;
    background: #05090e;
    min-height: 85vh;
    padding: 50px 0 80px;
    color: #f4f6fa;
    position: relative;
    isolation: isolate;
}

/* MAIN ULTRA-GLASS CONTAINER CARD */
.profile-main-card {
    position: relative;
    width: min(92%, var(--page-max));
    margin-inline: auto;
    background: linear-gradient(145deg, rgba(13, 22, 35, 0.88) 0%, rgba(5, 10, 17, 0.96) 100%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    padding: 35px;
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 40px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(16px);
    overflow: hidden;
}

.profile-main-card::before {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent 0%, <?php echo e($teamColor); ?> 50%, transparent 100%);
    box-shadow: 0 0 15px <?php echo $teamColorGlow; ?>;
}

/* LEFT COLUMN: PORTRAIT & FAV BUTTON */
.profile-left-col {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.portrait-card {
    position: relative;
    background: radial-gradient(circle at 50% 30%, <?php echo $teamColorGlow; ?> 0%, rgba(10, 16, 26, 0.95) 80%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    height: 440px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: center;
    overflow: hidden;
    box-shadow: inset 0 0 40px rgba(0,0,0,0.8);
}

/* DRIVER NUMBER BACKGROUND WATERMARK */
.portrait-num-bg {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 5.5rem;
    font-weight: 900;
    font-style: italic;
    color: rgba(255, 255, 255, 0.08);
    line-height: 1;
    pointer-events: none;
    font-family: sans-serif;
}

.portrait-driver-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center top;
    position: relative;
    z-index: 2;
    filter: drop-shadow(0 10px 20px rgba(0,0,0,0.6));
}

.portrait-team-badge {
    position: absolute;
    bottom: 15px;
    left: 15px;
    z-index: 3;
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(8, 14, 22, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 8px 14px;
    backdrop-filter: blur(8px);
}

.portrait-team-badge img {
    height: 22px;
    width: auto;
    object-fit: contain;
}

.portrait-team-text {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}

.portrait-team-text .lbl {
    font-size: 0.6rem;
    font-weight: 900;
    color: <?php echo e($teamColor); ?>;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.portrait-team-text .val {
    font-size: 0.8rem;
    font-weight: 850;
    color: #ffffff;
    text-transform: uppercase;
}

.btn-fav-profile {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: linear-gradient(135deg, rgba(15, 24, 38, 0.9) 0%, rgba(8, 14, 22, 0.95) 100%);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 14px 20px;
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
    text-decoration: none;
}

.btn-fav-profile:hover, .btn-fav-profile.active {
    background: <?php echo $teamColorDark; ?>;
    border-color: <?php echo e($teamColor); ?>;
    color: #ffffff;
    box-shadow: 0 0 25px <?php echo $teamColorGlow; ?>;
}

/* RIGHT COLUMN: HEADER & STATS & BIO */
.profile-right-col {
    display: flex;
    flex-direction: column;
    position: relative;
}

/* TOP RIGHT SLOGAN BRANDING */
.branding-slogan {
    position: absolute;
    top: 0;
    right: 0;
    text-align: right;
    color: rgba(255, 255, 255, 0.2);
    font-size: 0.75rem;
    font-weight: 900;
    letter-spacing: 2px;
    text-transform: uppercase;
    line-height: 1.3;
}

.driver-team-title {
    font-size: 0.85rem;
    font-weight: 900;
    color: <?php echo e($teamColor); ?>;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 6px;
}

.driver-fullname {
    font-size: clamp(32px, 3.5vw, 52px);
    font-weight: 900;
    font-style: italic;
    color: #ffffff;
    text-transform: uppercase;
    margin: 0 0 12px;
    line-height: 1;
    letter-spacing: -0.5px;
    text-shadow: 0 4px 15px rgba(0,0,0,0.5);
}

.driver-sub-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.95rem;
    color: #a3b2c5;
    font-weight: 600;
    margin-bottom: 35px;
}

.driver-sub-meta img {
    width: 22px;
    height: 15px;
    object-fit: cover;
    border-radius: 2px;
    border: 1px solid rgba(255,255,255,0.2);
}

.driver-sub-meta strong {
    color: #ffffff;
}

/* STATS CARDS GRID */
.profile-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

.stat-box-card {
    position: relative;
    background: rgba(8, 14, 22, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-left: 3px solid <?php echo e($teamColor); ?>;
    border-radius: 10px;
    padding: 22px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    transition: transform 0.25s ease;
}

.stat-box-card:hover {
    transform: translateY(-3px);
    background: rgba(13, 22, 35, 0.8);
    border-color: rgba(255,255,255,0.18);
}

.stat-icon {
    font-size: 1.8rem;
    color: <?php echo e($teamColor); ?>;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.9;
}

.stat-info-wrap {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.68rem;
    font-weight: 900;
    color: #7b8a9e;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 900;
    color: #ffffff;
    line-height: 1;
}

.stat-value.pts-gold {
    color: #ffd700;
}

/* BIOGRAPHY SECTION */
.bio-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.05rem;
    font-weight: 900;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 15px;
}

.bio-title-bar {
    width: 4px;
    height: 18px;
    background: <?php echo e($teamColor); ?>;
    display: inline-block;
    box-shadow: 0 0 10px <?php echo $teamColorGlow; ?>;
}

.bio-text-content {
    color: #a3b2c5;
    font-size: 0.95rem;
    line-height: 1.75;
    margin: 0;
}

@media (max-width: 992px) {
    .profile-main-card {
        grid-template-columns: 1fr;
    }
    .portrait-card {
        height: 380px;
    }
    .branding-slogan {
        display: none;
    }
}

@media (max-width: 600px) {
    .profile-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="driver-profile-page">
    <div class="profile-main-card">
        
        <!-- LEFT COLUMN: PORTRAIT & FAVORITE BUTTON -->
        <div class="profile-left-col">
            <div class="portrait-card">
                <span class="portrait-num-bg"><?php echo e($driver['number']); ?></span>
                
                <img src="<?php echo e($driverImg); ?>" alt="<?php echo e($driver['name']); ?>" class="portrait-driver-img" onerror="this.src='assets/images/drivers/default.png'">

                <?php if (!empty($driver['team_name'])): ?>
                    <div class="portrait-team-badge">
                        <?php if ($teamLogo !== ''): ?>
                            <img src="<?php echo e($teamLogo); ?>" alt="" onerror="this.style.display='none';">
                        <?php endif; ?>
                        <div class="portrait-team-text">
                            <span class="lbl">CONSTRUCTOR</span>
                            <span class="val"><?php echo e($driver['team_name']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (is_logged_in()): ?>
                <button onclick="toggleFavorite('driver', <?php echo $driver['id']; ?>, this)" class="btn-fav-profile <?php echo $isFav ? 'active' : ''; ?>">
                    <?php echo $isFav ? '❤️ FAVORITED' : '🤍 ADD TO FAVORITES'; ?>
                </button>
            <?php else: ?>
                <a href="login.php" class="btn-fav-profile">🔒 LOGIN TO FAVORITE</a>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: DETAILS, STATS & BIO -->
        <div class="profile-right-col">
            <div class="branding-slogan">
                "DREAMS<br>DRIVE<br>US"
            </div>

            <div class="driver-team-title"><?php echo e($driver['team_name'] ?? 'FORMULA 1 DRIVER'); ?></div>
            <h1 class="driver-fullname"><?php echo e($driver['name']); ?></h1>

            <div class="driver-sub-meta">
                <?php if ($driverFlag !== ''): ?>
                    <img src="<?php echo e($driverFlag); ?>" alt="" onerror="this.style.display='none';">
                <?php else: ?>
                    <span>🚩</span>
                <?php endif; ?>
                <span>Nationality: <strong><?php echo e($driver['nationality'] ?? 'N/A'); ?></strong></span>
                <span style="opacity:0.4;">|</span>
                <span>Car Number: <strong>#<?php echo e($driver['number']); ?></strong></span>
            </div>

            <!-- STATS CARDS GRID -->
            <div class="profile-stats-grid">
                <!-- POINTS -->
                <div class="stat-box-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-info-wrap">
                        <span class="stat-label">CHAMPIONSHIP POINTS</span>
                        <span class="stat-value pts-gold"><?php echo (int)($driver['points'] ?? 0); ?></span>
                    </div>
                </div>

                <!-- WINS -->
                <div class="stat-box-card">
                    <div class="stat-icon">🏁</div>
                    <div class="stat-info-wrap">
                        <span class="stat-label">GRAND PRIX WINS</span>
                        <span class="stat-value"><?php echo (int)($driver['wins'] ?? 0); ?></span>
                    </div>
                </div>

                <!-- PODIUMS -->
                <div class="stat-box-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info-wrap">
                        <span class="stat-label">CAREER PODIUMS</span>
                        <span class="stat-value"><?php echo (int)($driver['podiums'] ?? 0); ?></span>
                    </div>
                </div>
            </div>

            <!-- BIOGRAPHY SECTION -->
            <div class="bio-section-title">
                <span class="bio-title-bar"></span>
                <span>DRIVER BIOGRAPHY</span>
            </div>
            <p class="bio-text-content">
                <?php echo e($driver['bio'] ?? 'No biography details available for this driver yet.'); ?>
            </p>
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
            btnElement.innerHTML = '❤️ FAVORITED';
        } else if (data.status === 'removed') {
            btnElement.classList.remove('active');
            btnElement.innerHTML = '🤍 ADD TO FAVORITES';
        } else if (data.status === 'unauthorized') {
            window.location.href = 'login.php';
        }
    })
    .catch(err => console.error(err));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>