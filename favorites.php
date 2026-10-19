<?php
// favorites.php — User Favorites Management & AJAX endpoint
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Handle AJAX POST toggle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_favorite') {
    header('Content-Type: application/json');
    if (!is_logged_in()) {
        echo json_encode(['status' => 'unauthorized']);
        exit();
    }

    $userId = $_SESSION['user_id'];
    $itemType = $_POST['item_type'] ?? '';
    $itemId = (int)($_POST['item_id'] ?? 0);

    if (!in_array($itemType, ['driver', 'team', 'race']) || $itemId <= 0) {
        echo json_encode(['status' => 'invalid']);
        exit();
    }

    if (is_favorited($userId, $itemType, $itemId)) {
        // Remove favorite
        $del = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND item_type = ? AND item_id = ?");
        $del->execute([$userId, $itemType, $itemId]);
        echo json_encode(['status' => 'removed']);
    } else {
        // Add favorite
        $ins = $pdo->prepare("INSERT INTO favorites (user_id, item_type, item_id) VALUES (?, ?, ?)");
        $ins->execute([$userId, $itemType, $itemId]);
        echo json_encode(['status' => 'added']);
    }
    exit();
}

require_login();
$pageTitle = "My Favorites";
$user = get_logged_in_user();

/* Image path helper matching drivers.php */
$resolveImage = function ($value, $folder) {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    if (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0) {
        return $value;
    }
    if (strpos($value, 'assets/') === 0 || strpos($value, '/assets/') === 0) {
        return ltrim($value, '/');
    }
    return 'assets/images/' . trim($folder, '/') . '/' . basename($value);
};

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

// Fetch favorited drivers
$favDrivers = $pdo->prepare("
    SELECT d.*, t.name AS team_name, t.logo AS team_logo
    FROM favorites f
    JOIN drivers d ON f.item_id = d.id
    LEFT JOIN teams t ON d.team_id = t.id
    WHERE f.user_id = ? AND f.item_type = 'driver'
    ORDER BY d.points DESC
");
$favDrivers->execute([$user['id']]);
$drivers = $favDrivers->fetchAll();

// Fetch favorited teams
$favTeams = $pdo->prepare("SELECT t.* FROM favorites f JOIN teams t ON f.item_id = t.id WHERE f.user_id = ? AND f.item_type = 'team'");
$favTeams->execute([$user['id']]);
$teams = $favTeams->fetchAll();

// Fetch favorited races
$favRaces = $pdo->prepare("SELECT r.* FROM favorites f JOIN races r ON f.item_id = r.id WHERE f.user_id = ? AND f.item_type = 'race' ORDER BY r.race_date ASC");
$favRaces->execute([$user['id']]);
$races = $favRaces->fetchAll();

// Calculate round mapping for races matching races.php logic[cite: 8]
$roundStmt = $pdo->query("SELECT id FROM races ORDER BY race_date ASC, race_time ASC, id ASC");
$allRaceIds = $roundStmt->fetchAll(PDO::FETCH_COLUMN);
$roundMap = [];
foreach ($allRaceIds as $index => $rId) {
    $roundMap[(string)$rId] = $index + 1;
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Base Page Styling */
.favorites-page {
    --page-max: 1280px;
    --race-red: #ff1717;
    --race-white: #ffffff;
    position: relative;
    isolation: isolate;
    overflow: hidden;
    padding: 0 0 70px;
    color: #f4f6fa;
    background: #05090e;
    min-height: 85vh;
}

/* HERO BANNER SECTION WITH F1 IMAGE BACKGROUND */
.favorites-hero {
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
        url("assets/images/fav_hero.jpg")
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
    text-transform: uppercase;
}

.hero-subtitle {
    color: #a3b2c5;
    font-size: 0.98rem;
    margin: 12px 0 0;
}

/* MAIN CONTAINER */
.favorites-container {
    width: min(92%, var(--page-max));
    margin-inline: auto;
}

/* SECTION HEADER */
.fav-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
    margin-top: 40px;
}

.fav-section-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.fav-title-border {
    width: 4px;
    height: 22px;
    background: #e10600;
    display: inline-block;
}

.fav-section-title h2 {
    font-size: 1.25rem;
    font-weight: 850;
    color: #f4f6fa;
    margin: 0;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-manage {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, rgba(225, 6, 0, 0.2) 0%, rgba(150, 4, 0, 0.3) 100%);
    border: 1px solid rgba(225, 6, 0, 0.5);
    border-radius: 20px;
    padding: 6px 18px;
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    text-decoration: none;
}

/* EMPTY STATE CARD DESIGN */
.fav-empty-card {
    position: relative;
    background: linear-gradient(145deg, rgba(13, 22, 35, 0.85) 0%, rgba(5, 10, 17, 0.95) 70%, rgba(3, 6, 11, 1) 100%);
    border: 1px solid rgba(50, 68, 92, 0.4);
    border-radius: 10px;
    padding: 45px 20px;
    text-align: center;
    margin-bottom: 45px;
}

.empty-title {
    font-size: 1.05rem;
    font-weight: 850;
    color: #f4f6fa;
    margin: 0 0 6px 0;
}

.empty-subtitle {
    font-size: 0.88rem;
    color: #7b8a9e;
    margin: 0;
}

/* GRID LAYOUT FOR CARDS */
.grid-3 {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

/* =========================================================
   DRIVER CARD STYLES
========================================================= */
.driver-card--visual {
    position: relative;
    isolation: isolate;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(27,32,39,.78), rgba(8,11,15,.90)), var(--driver-bg, linear-gradient(145deg, #20262d 0%, #171c22 48%, #101419 100%));
    background-position: center, center;
    background-size: cover, cover;
    background-repeat: no-repeat;
    border: 1px solid rgba(255,255,255,.11);
    border-radius: 5px;
    box-shadow: 0 14px 35px rgba(0,0,0,.38), inset 0 1px 0 rgba(255,255,255,.065);
    transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease;
}

.driver-card--visual:hover {
    transform: translateY(-5px);
    border-color: rgba(225,6,0,.38);
    box-shadow: 0 22px 48px rgba(0,0,0,.50), 0 0 25px rgba(225,6,0,.07);
}

.driver-card--visual .driver-img-box {
    position: relative;
    height: 235px;
    flex: 0 0 235px;
    overflow: hidden;
    background: radial-gradient(ellipse at 75% 28%, rgba(225,6,0,.18), transparent 42%),
                radial-gradient(ellipse at 50% 20%, rgba(255,255,255,.09), transparent 46%),
                linear-gradient(145deg, #252b33 0%, #1c2229 45%, #12171d 100%);
    border-bottom: 1px solid rgba(255,255,255,.09);
    z-index: 2;
}

.driver-card--visual .driver-img-wrap {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.driver-card--visual .driver-img {
    position: relative;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center top;
    display: block;
    z-index: 3;
}

.driver-card--visual .driver-number {
    position: absolute;
    top: 12px;
    left: 13px;
    padding: 5px 8px;
    background: rgba(12,16,21,.82);
    border: 1px solid rgba(255,255,255,.12);
    border-left: 2px solid #e10600;
    border-radius: 3px;
    color: #f3f5f7;
    font-size: 10px;
    font-weight: 900;
    z-index: 15;
}

.driver-card--visual .driver-details {
    position: relative;
    padding: 14px !important;
    background: linear-gradient(180deg, #1b2026 0%, #151a20 55%, #11151a 100%) !important;
    border-top: 1px solid rgba(255,255,255,.075);
    z-index: 10;
}

.driver-card--visual .driver-team-name {
    display: flex !important;
    align-items: center;
    gap: 7px;
    min-height: 20px;
    color: #c0c8d0 !important;
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
}

.driver-card--visual .driver-team-logo-wrap {
    width: 27px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, rgba(255,255,255,.08), rgba(255,255,255,.025));
    border: 1px solid rgba(255,255,255,.09);
    border-radius: 3px;
}

.driver-card--visual .driver-team-logo {
    width: 24px;
    height: 18px;
    object-fit: contain;
}

.driver-card--visual .driver-name {
    margin-top: 9px !important;
    margin-bottom: 9px !important;
    color: #ffffff !important;
    font-size: 21px !important;
    font-weight: 900;
    line-height: 1;
}

.driver-card--visual .driver-nationality {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 13px !important;
    color: #a2acb6 !important;
    font-size: 10px;
    font-weight: 600;
}

.driver-card--visual .driver-flag {
    width: 20px;
    height: 13px;
    object-fit: cover;
    border: 1px solid rgba(255,255,255,.12);
}

.driver-card--visual .driver-stats-row {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr);
    gap: 5px;
    padding: 6px;
    background: rgba(255,255,255,.035);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 4px;
}

.driver-card--visual .stat-item {
    padding: 7px 4px;
    background: linear-gradient(145deg, rgba(255,255,255,.065), rgba(255,255,255,.025));
    border: 1px solid rgba(255,255,255,.055);
    border-radius: 3px;
    text-align: center;
}

.driver-card--visual .stat-val {
    color: #ffffff !important;
    font-size: 16px;
    font-weight: 900;
}

.driver-card--visual .stat-lbl {
    margin-top: 4px;
    color: #89939e !important;
    font-size: 7px;
    font-weight: 900;
    letter-spacing: .12em;
}

.driver-card--visual .driver-profile-btn {
    display: block;
    width: 100%;
    margin-top: 12px !important;
    padding: 9px 0 !important;
    background: linear-gradient(100deg, #1a1f25, #20252c, rgba(225,6,0,.13)) !important;
    border: 1px solid rgba(225,6,0,.48) !important;
    border-radius: 3px;
    color: #ffffff !important;
    text-align: center;
    text-decoration: none;
    font-size: 9px !important;
    font-weight: 900;
    letter-spacing: .10em;
}

.btn-remove-fav-driver {
    display: block;
    width: 100%;
    margin-top: 6px;
    padding: 7px 0;
    background: rgba(225, 6, 0, 0.15);
    border: 1px solid rgba(225, 6, 0, 0.4);
    border-radius: 3px;
    color: #ff4d4d;
    text-align: center;
    font-size: 9px;
    font-weight: 900;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-remove-fav-driver:hover {
    background: #e10600;
    color: #fff;
}

/* =========================================================
   CONSTRUCTOR CARD STYLING
========================================================= */
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

.fav-card-actions {
    display: flex;
    gap: 10px;
    margin-top: auto;
}

.btn-action-profile {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(114, 129, 150, 0.3);
    color: #e2e8f0;
    font-size: 0.78rem;
    font-weight: 800;
    padding: 8px 12px;
    border-radius: 5px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-action-profile:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
}

.btn-action-remove {
    background: rgba(225, 6, 0, 0.18);
    border: 1px solid rgba(225, 6, 0, 0.5);
    color: #ff4d4d;
    font-size: 0.78rem;
    font-weight: 850;
    padding: 8px 14px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-action-remove:hover {
    background: rgba(225, 6, 0, 0.4);
    color: #fff;
}

/* =========================================================
   EXACT RACES.PHP CARD DESIGN FOR FAVORITES
========================================================= */
.race-card {
    position: relative;
    min-height: 315px;
    overflow: hidden;
    background: #080a0e;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 7px;
    isolation: isolate;
    transition: transform .35s ease, border-color .35s ease, box-shadow .35s ease;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.race-card:hover {
    transform: translateY(-7px);
    border-color: rgba(255,35,35,.62);
    box-shadow: 0 20px 45px rgba(0,0,0,.58), 0 0 28px rgba(255,20,20,.075);
}

.race-card__image {
    position: absolute;
    inset: 0;
    overflow: hidden;
    z-index: 0;
}

.race-card__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .7s ease, filter .6s ease;
}

.race-card:hover .race-card__image img {
    transform: scale(1.08);
    filter: saturate(1.12) contrast(1.03);
}

.race-card__shade {
    position: absolute;
    inset: 0;
    z-index: 1;
    background: linear-gradient(180deg, rgba(4,6,9,.26) 0%, rgba(4,6,9,.10) 28%, rgba(4,6,9,.52) 54%, rgba(4,6,9,.91) 78%, rgba(4,6,9,.99) 100%);
    pointer-events: none;
}

.race-card__vignette {
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    background: radial-gradient(ellipse at center, transparent 42%, rgba(0,0,0,.20) 75%, rgba(0,0,0,.55) 100%);
}

.race-card__side-accent {
    position: absolute;
    left: 0;
    top: 25px;
    bottom: 24px;
    width: 2px;
    z-index: 8;
    background: linear-gradient(180deg, transparent, rgba(255,23,23,.9) 30%, #ff1717 72%, transparent);
    box-shadow: 0 0 9px rgba(255,23,23,.35);
    opacity: .75;
    pointer-events: none;
}

.race-card__ghost-number {
    position: absolute;
    right: 3px;
    bottom: 43px;
    font-size: 105px;
    line-height: .8;
    font-weight: 900;
    letter-spacing: -8px;
    color: rgba(255,255,255,.09);
    z-index: 3;
    pointer-events: none;
}

.race-card__top {
    position: absolute;
    top: 13px;
    left: 13px;
    right: 13px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    z-index: 7;
}

.race-round {
    padding: 6px 9px;
    color: #fff;
    background: rgba(5,7,10,.72);
    border: 1px solid rgba(255,255,255,.17);
    border-radius: 3px;
    font-size: 8px;
    font-weight: 900;
    letter-spacing: .8px;
    backdrop-filter: blur(7px);
}

.race-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 10px;
    background: rgba(0,0,0,.65);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 4px;
    color: #fff;
    font-size: 8px;
    font-weight: 900;
    letter-spacing: .6px;
    backdrop-filter: blur(6px);
}

.race-status i {
    width: 6px;
    height: 6px;
    display: block;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--race-red);
    box-shadow: 0 0 8px rgba(255,23,23,.8);
}

.race-status--completed {
    border-color: rgba(0,220,120,.3);
}

.race-status--completed i {
    background: #00d878;
    box-shadow: 0 0 8px rgba(0,216,120,.7);
}

.race-card__content {
    position: relative;
    padding: 17px;
    z-index: 7;
    margin-top: auto;
}

.race-card__country {
    margin-bottom: 5px;
    color: var(--race-red);
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 1.1px;
}

.race-card__content h3 {
    max-width: 90%;
    margin: 0;
    color: #fff;
    font-size: 17px;
    line-height: 1.08;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: -.3px;
}

.race-card__circuit {
    display: flex;
    align-items: center;
    margin-top: 9px;
    color: #b5bbc3;
    font-size: 9px;
}

.race-card__circuit span {
    margin-right: 6px;
    color: var(--race-red);
    font-size: 7px;
}

.race-card__bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: 13px;
    padding-top: 11px;
    border-top: 1px solid rgba(255,255,255,.12);
}

.race-card__date {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
    color: #c2c7ce;
    font-size: 9px;
    font-weight: 700;
}

.race-card__actions-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
}

.race-card__link {
    flex-shrink: 0;
    min-height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 10px;
    color: #fff;
    text-decoration: none;
    background: rgba(5,7,10,.60);
    border: 1px solid rgba(255,255,255,.20);
    border-radius: 4px;
    font-size: 8px;
    font-weight: 900;
    letter-spacing: .7px;
    backdrop-filter: blur(5px);
    transition: all .25s ease;
}

.race-card__link:hover {
    border-color: rgba(255,35,35,.75);
    background: rgba(255,23,23,.12);
}

.race-card__link-arrow {
    color: var(--race-red);
    font-size: 13px;
}

.btn-remove-race-fav {
    min-height: 30px;
    padding: 0 10px;
    background: rgba(225, 6, 0, 0.18);
    border: 1px solid rgba(225, 6, 0, 0.5);
    color: #ff4d4d;
    border-radius: 4px;
    font-size: 8px;
    font-weight: 900;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-remove-race-fav:hover {
    background: #e10600;
    color: #fff;
}

.race-card__accent {
    position: absolute;
    left: 0;
    bottom: 0;
    width: 0;
    height: 3px;
    z-index: 10;
    background: linear-gradient(90deg, #ff1717, #ff3333 45%, transparent);
    transition: width .45s ease;
}

.race-card:hover .race-card__accent {
    width: 100%;
}

@media (max-width: 992px) {
    .grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 650px) {
    .grid-3 { grid-template-columns: 1fr; }
}
/* =========================================================
   DRIVER IMAGE FIX — FULL HEAD & SHOULDERS FIT
========================================================= */
.driver-card--visual .driver-img-box {
    position: relative;
    height: 235px;
    flex: 0 0 235px;
    overflow: hidden;
    background: radial-gradient(ellipse at 75% 28%, rgba(225,6,0,.18), transparent 42%),
                radial-gradient(ellipse at 50% 20%, rgba(255,255,255,.09), transparent 46%),
                linear-gradient(145deg, #252b33 0%, #1c2229 45%, #12171d 100%);
    border-bottom: 1px solid rgba(255,255,255,.09);
    box-shadow: inset 0 -35px 45px rgba(0,0,0,.20);
    z-index: 2;
}

.driver-card--visual .driver-img-wrap {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding-top: 10px; /* Top space for full head visibility */
    box-sizing: border-box;
}

.driver-card--visual .driver-img {
    position: relative;
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
    /* Key Fix: contain ensures no cropping on face or chin */
    object-fit: contain !important;
    object-position: center bottom !important;
    display: block;
    filter: drop-shadow(0 12px 18px rgba(0,0,0,.45));
    z-index: 3;
    transition: filter .3s ease;
}
</style>

<section class="favorites-page">

    <!-- HERO SECTION WITH F1 BACKGROUND IMAGE -->
    <header class="favorites-hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-title-wrap">
                    <span class="hero-slash" aria-hidden="true"></span>
                    <h1>MY <span>FAVORITES</span></h1>
                </div>
                <p class="hero-subtitle">Manage your bookmarked Formula 1 drivers, teams, and Grand Prix events.</p>
            </div>
        </div>
    </header>

    <div class="favorites-container">

        <!-- 1. FAVORITE DRIVERS SECTION -->
        <div class="fav-section-header">
            <div class="fav-section-title">
                <span class="fav-title-border"></span>
                <h2>👤 FAVORITE DRIVERS</h2>
            </div>
            <a href="drivers.php" class="btn-manage">MANAGE <span>→</span></a>
        </div>

        <?php if (empty($drivers)): ?>
            <div class="fav-empty-card">
                <h3 class="empty-title">Your favorite drivers list is empty.</h3>
                <p class="empty-subtitle">Explore drivers and click 'Add to Favorites'.</p>
            </div>
        <?php else: ?>
            <div class="grid-3">
                <?php foreach ($drivers as $d): ?>
                    <?php
                        $driverImage = !empty($d['image']) ? $resolveImage($d['image'], 'drivers') : '';
                        $teamLogo    = !empty($d['team_logo']) ? $resolveImage($d['team_logo'], 'teams') : '';
                        $flagImage   = !empty($d['flag']) ? $resolveImage($d['flag'], 'flags') : '';
                    ?>

                    <div class="driver-card driver-card--visual" id="fav-driver-<?php echo $d['id']; ?>" style="--driver-bg: url('assets/images/driver-bg/team-<?php echo (int)$d['team_id']; ?>.svg');">
                        
                        <div class="driver-img-box">
                            <div class="driver-img-wrap">
                                <span class="driver-number">#<?php echo e($d['number']); ?></span>
                                <?php if ($driverImage !== ''): ?>
                                    <img src="<?php echo e($driverImage); ?>" alt="<?php echo e($d['name']); ?>" class="driver-img" loading="lazy">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="driver-details">
                            <div class="driver-team-name">
                                <span class="driver-team-logo-wrap">
                                    <?php if ($teamLogo !== ''): ?>
                                        <img src="<?php echo e($teamLogo); ?>" alt="" class="driver-team-logo" loading="lazy">
                                    <?php endif; ?>
                                </span>
                                <span><?php echo e($d['team_name'] ?? 'Team'); ?></span>
                            </div>

                            <h3 class="driver-name"><?php echo e($d['name']); ?></h3>

                            <div class="driver-nationality">
                                <?php if ($flagImage !== ''): ?>
                                    <img src="<?php echo e($flagImage); ?>" alt="" class="driver-flag" loading="lazy">
                                <?php else: ?>
                                    <span class="driver-flag-placeholder">▪</span>
                                <?php endif; ?>
                                <span><?php echo e($d['nationality']); ?></span>
                            </div>

                            <div class="driver-stats-row">
                                <div class="stat-item">
                                    <div class="stat-val"><?php echo e($d['points']); ?></div>
                                    <div class="stat-lbl">PTS</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-val"><?php echo e($d['wins']); ?></div>
                                    <div class="stat-lbl">WINS</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-val"><?php echo e($d['podiums']); ?></div>
                                    <div class="stat-lbl">PODIUMS</div>
                                </div>
                            </div>

                            <a href="driver-profile.php?id=<?php echo (int)$d['id']; ?>" class="driver-profile-btn">VIEW PROFILE</a>
                            
                            <button onclick="toggleFavorite('driver', <?php echo $d['id']; ?>, this)" class="btn-remove-fav-driver">REMOVE</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


        <!-- 2. FAVORITE CONSTRUCTORS SECTION -->
        <div class="fav-section-header">
            <div class="fav-section-title">
                <span class="fav-title-border"></span>
                <h2>🏎 FAVORITE CONSTRUCTORS</h2>
            </div>
            <a href="teams.php" class="btn-manage">MANAGE <span>→</span></a>
        </div>

        <?php if (empty($teams)): ?>
            <div class="fav-empty-card">
                <h3 class="empty-title">Your favorite teams list is empty.</h3>
                <p class="empty-subtitle">Browse teams and add your favorites.</p>
            </div>
        <?php else: ?>
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

                            <div class="fav-card-actions">
                                <a href="team-profile.php?id=<?= (int)$t['id'] ?>" class="btn-action-profile">View Profile</a>
                                <button onclick="toggleFavorite('team', <?= (int)$t['id'] ?>, this)" class="btn-action-remove">Remove</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


        <!-- 3. BOOKMARKED RACES SECTION (EXACT RACES.PHP CARD DESIGN) -->
        <div class="fav-section-header">
            <div class="fav-section-title">
                <span class="fav-title-border"></span>
                <h2>🏁 BOOKMARKED RACES</h2>
            </div>
            <a href="races.php" class="btn-manage">MANAGE <span>→</span></a>
        </div>

        <?php if (empty($races)): ?>
            <div class="fav-empty-card">
                <h3 class="empty-title">No bookmarked races.</h3>
                <p class="empty-subtitle">Save your favorite races to access them quickly.</p>
            </div>
        <?php else: ?>
            <div class="grid-3">
                <?php foreach ($races as $r): ?>
                    <?php
                        $raceId = $r['id'];
                        $round = $roundMap[(string)$raceId] ?? 1;
                        $status = strtoupper($r['status'] ?? 'UPCOMING');

                        $image = trim($r['circuit_image'] ?? '');
                        if ($image !== '') {
                            if (strpos($image, 'http://') !== 0 && strpos($image, 'https://') !== 0 && strpos($image, '/') !== 0) {
                                $image = 'assets/images/circuit_image/' . basename($image);
                            }
                        }

                        $date = !empty($r['race_date']) ? format_date($r['race_date']) : '';
                        $time = !empty($r['race_time']) ? format_time($r['race_time']) : '';
                    ?>

                    <article class="race-card race-card--<?php echo strtolower($status); ?>">
                        
                        <!-- CARD BACKGROUND IMAGE -->
                        <div class="race-card__image">
                            <?php if ($image !== ''): ?>
                                <img src="<?php echo e($image); ?>" alt="<?php echo e($r['grand_prix']); ?>" loading="lazy">
                            <?php endif; ?>
                        </div>

                        <!-- SHADE & VIGNETTE -->
                        <div class="race-card__shade"></div>
                        <div class="race-card__vignette"></div>
                        <div class="race-card__side-accent"></div>

                        <!-- GHOST ROUND NUMBER -->
                        <div class="race-card__ghost-number">
                            <?php echo str_pad($round, 2, '0', STR_PAD_LEFT); ?>
                        </div>

                        <!-- CARD TOP -->
                        <div class="race-card__top">
                            <span class="race-round">ROUND <?php echo str_pad($round, 2, '0', STR_PAD_LEFT); ?></span>
                            <span class="race-status race-status--<?php echo strtolower($status); ?>">
                                <i></i>
                                <?php echo e($status); ?>
                            </span>
                        </div>

                        <!-- CARD CONTENT -->
                        <div class="race-card__content">
                            <div class="race-card__country"><?php echo e(strtoupper($r['country'])); ?></div>
                            <h3><?php echo e($r['grand_prix']); ?></h3>
                            
                            <div class="race-card__circuit">
                                <span>●</span>
                                <?php echo e($r['circuit']); ?>
                            </div>

                            <!-- CARD BOTTOM -->
                            <div class="race-card__bottom">
                                <div class="race-card__date">
                                    <?php if ($date !== ''): ?>
                                        <span>▣ <?php echo e($date); ?></span>
                                    <?php endif; ?>
                                    <?php if ($time !== ''): ?>
                                        <span>◷ <?php echo e($time); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="race-card__actions-wrap">
                                    <?php if ($status === 'COMPLETED'): ?>
                                        <a href="results.php?race_id=<?php echo (int)$raceId; ?>" class="race-card__link">
                                            <span class="race-card__link-text">RESULTS</span>
                                            <span class="race-card__link-arrow">→</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="race-details.php?id=<?php echo (int)$raceId; ?>" class="race-card__link">
                                            <span class="race-card__link-text">DETAILS</span>
                                            <span class="race-card__link-arrow">→</span>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="toggleFavorite('race', <?php echo (int)$raceId; ?>, this)" class="btn-remove-race-fav">REMOVE</button>
                                </div>
                            </div>
                        </div>

                        <div class="race-card__accent"></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
function toggleFavorite(itemType, itemId, btnElement) {
    fetch('favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=toggle_favorite&item_type=' + encodeURIComponent(itemType) + '&item_id=' + encodeURIComponent(itemId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'removed') {
            const cardEl = btnElement.closest('.driver-card, .constructor-card, .race-card');
            if (cardEl) {
                cardEl.style.transition = 'all 0.3s ease';
                cardEl.style.transform = 'scale(0.9)';
                cardEl.style.opacity = '0';
                setTimeout(() => cardEl.remove(), 300);
            }
        } else if (data.status === 'unauthorized') {
            window.location.href = 'login.php';
        }
    })
    .catch(err => console.error('Error:', err));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>