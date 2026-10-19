<?php
// teams.php — FormulaHub 2026 Constructors
$pageTitle = "Constructors & Teams";
require_once __DIR__ . '/includes/header.php';

$teams = $pdo->query("
    SELECT *
    FROM teams
    ORDER BY points DESC, wins DESC, podiums DESC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);

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

<style>
/* Page Layout */
.constructors-page {
    --page-max: 1280px;
    position: relative;
    isolation: isolate;
    overflow: hidden;
    padding: 0 0 60px;
    color: #f4f6fa;
    background: #05090e;
    min-height: 80vh;
}

/* HERO SECTION BANNER WITH F1 IMAGE BACKGROUND */
.constructors-hero {
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
        url("assets/images/team_hero.png")
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

/* MAIN CONTENT CONTAINER */
.constructors-container {
    width: min(92%, var(--page-max));
    margin-inline: auto;
}

.constructors-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    align-items: stretch;
    gap: 18px;
}

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
    border-radius: 0;
}

.constructor-car-image {
    position: absolute; 
    inset: 0; 
    display: block; 
    width: 100% !important; 
    height: 100% !important; 
    max-width: none;
    object-fit: cover; 
    object-position: 120% center; 
    padding: 0 !important; 
    margin: 0;
    z-index: -2; 
    opacity: 1; 
    filter: brightness(1.12) contrast(1.08) saturate(1.08);
    transform: scale(1.015) translateX(15%); 
    transform-origin: right center; 
    transition: transform .4s ease; 
    pointer-events: none;
}

.constructor-card:hover .constructor-car-image { 
    transform: scale(1.07) translateX(10%); 
}

.constructor-visual-shade {
    position: absolute; 
    inset: 0; 
    z-index: -1; 
    pointer-events: none;
    background:
      linear-gradient(90deg, rgba(5,9,15,.92) 0%, rgba(5,9,15,.78) 28%, rgba(5,9,15,.35) 53%, rgba(5,9,15,.03) 82%),
      linear-gradient(0deg, rgba(4,8,14,.35), transparent 58%);
}

.constructor-visual::before {
    content: ""; 
    position: absolute; 
    inset: 0; 
    z-index: -1; 
    pointer-events: none;
    background: repeating-linear-gradient(130deg, transparent 0, transparent 42px, rgba(var(--team-rgb),.12) 43px, rgba(var(--team-rgb),.12) 59px, transparent 60px, transparent 82px);
}

.constructor-visual::after {
    content: ""; 
    position: absolute; 
    left: 0; 
    right: 0; 
    bottom: 0; 
    height: 1px;
    background: linear-gradient(90deg, var(--team-accent), rgba(var(--team-rgb),.22), transparent);
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

.constructor-rank { 
    color: var(--team-accent); 
    font-size: 24px; 
    line-height: 1; 
    font-weight: 850; 
    flex-shrink: 0; 
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

.constructor-nationality span { font-size: 14px; }

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
    object-position: left center; 
}

.constructor-brand h2 { 
    margin: 0; 
    max-width: 100%; 
    color: #f5f7fb; 
    font-size: clamp(13px, 1.05vw, 16px); 
    line-height: 1.35; 
    font-weight: 800; 
    text-transform: uppercase; 
    overflow-wrap: anywhere; 
    text-shadow: 0 2px 6px #05090e; 
}

.constructor-underline { 
    display: block; 
    width: 33px; 
    height: 3px; 
    margin-top: 7px; 
    background: var(--team-accent); 
}

.constructor-content { 
    display: flex; 
    flex-direction: column; 
    flex: 1; 
    padding: 12px 20px 13px; 
}

.constructor-stats { 
    display: grid; 
    grid-template-columns: repeat(3, minmax(0, 1fr)); 
    padding-bottom: 10px; 
    margin-bottom: 13px; 
    border-bottom: 1px solid rgba(150, 170, 194, .09); 
}

.constructor-stat { 
    display: flex; 
    flex-direction: column; 
    gap: 2px; 
    min-width: 0; 
    padding: 0 10px; 
    border-right: 1px solid rgba(150, 170, 194, .12); 
}

.constructor-stat:first-child { padding-left: 0; }
.constructor-stat:last-child { border-right: 0; }

.constructor-stat-value { 
    color: #f2f5fa; 
    font-size: 19px; 
    line-height: 1.2; 
    font-weight: 500; 
    font-variant-numeric: tabular-nums; 
}

.constructor-stat-label { 
    color: #a3b2c5; 
    font-size: 10px; 
    letter-spacing: .3px; 
}

.constructor-drivers { margin-bottom: 12px; }

.constructor-drivers-title { 
    margin-bottom: 6px; 
    color: #aab9cc; 
    font-size: 10px; 
    font-weight: 700; 
    letter-spacing: .4px; 
}

.constructor-driver-list { 
    display: flex; 
    flex-direction: column; 
    gap: 7px; 
}

.constructor-driver-pill { 
    display: flex; 
    align-items: center; 
    justify-content: space-between; 
    width: 100%; 
    padding: 6px 11px; 
    color: #e4eaf2; 
    background: rgba(7, 13, 21, .85); 
    border: 1px solid #4a5a6c; 
    border-radius: 7px; 
    text-decoration: none; 
    font-size: 11px; 
    line-height: 1.35; 
    transition: border-color .2s ease, background .2s ease; 
}

.constructor-driver-pill:hover { 
    color: #fff; 
    border-color: var(--team-accent); 
    background: rgba(var(--team-rgb), .12); 
}

.constructor-no-drivers { color: #8391a3; font-size: 11px; }

/* Button with Team Accent Shade */
.constructor-profile-btn {
    display: flex; 
    align-items: center; 
    justify-content: space-between; 
    gap: 10px;
    width: 100%; 
    min-height: 34px; 
    margin-top: auto; 
    padding: 8px 14px;
    color: #fff; 
    background: linear-gradient(180deg, rgba(var(--team-rgb), 0.25) 0%, rgba(var(--team-rgb), 0.08) 100%), rgba(7, 13, 21, 0.9);
    border: 1px solid rgba(var(--team-rgb), 0.5); 
    border-radius: 6px;
    font-size: 10px; 
    font-weight: 600; 
    letter-spacing: .3px; 
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(var(--team-rgb), 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(4px);
    transition: color .22s ease, background .22s ease, border-color .22s ease, box-shadow .22s ease;
}

.constructor-profile-btn:hover { 
    color: #fff; 
    border-color: var(--team-accent); 
    background: linear-gradient(180deg, rgba(var(--team-rgb), 0.4) 0%, rgba(var(--team-rgb), 0.15) 100%), rgba(7, 13, 21, 0.95);
    box-shadow: 0 4px 20px rgba(var(--team-rgb), 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.25);
}

.constructor-arrow { color: var(--team-accent); font-size: 15px; transition: transform .2s ease; }
.constructor-profile-btn:hover .constructor-arrow { transform: translateX(4px); }

.constructors-decoration { 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    justify-content: center; 
    min-height: 180px; 
    color: #34404e; 
}

.decoration-f1 { 
    font-size: 65px; 
    line-height: 1; 
    font-weight: 950; 
    font-style: italic; 
    letter-spacing: -8px; 
    opacity: .55; 
    transform: skew(-14deg); 
}

.decoration-tagline { 
    margin-top: 15px; 
    color: #8993a2; 
    font-size: 10px; 
    font-weight: 600; 
    font-style: italic; 
    letter-spacing: 4px; 
}

.decoration-tagline span { margin: 0 8px; color: #f0182b; font-weight: 900; }
.constructors-empty { grid-column: 1/-1; padding: 35px; color: #aeb8c7; text-align: center; border: 1px solid #303b49; border-radius: 8px; }

@media (max-width: 1050px) {
    .constructors-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .constructors-decoration { min-height: 150px; }
    .constructor-brand h2 { font-size: 15px; }
}

@media (max-width: 620px) {
    .constructors-page { padding: 0 0 18px; }
    .constructors-container { width: 92%; }
    .constructors-grid { grid-template-columns: minmax(0, 1fr); gap: 15px; }
    .constructor-visual { min-height: 132px; }
    .constructor-topline { padding: 15px 16px 0; }
    .constructor-brand { margin: 17px 10px 12px 16px; }
    .constructor-brand h2 { font-size: 16px; }
    .constructor-content { padding: 13px 16px 15px; }
    .constructors-decoration { min-height: 125px; }
}

@media (prefers-reduced-motion: reduce) {
    .constructor-card, .constructor-car-image, .constructor-profile-btn, .constructor-arrow { transition: none; }
}
</style>

<section class="constructors-page">

    <!-- HERO SECTION WITH F1 IMAGE BACKGROUND -->
    <header class="constructors-hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-tagline">OFFICIAL F1 TEAMS</div>
                <div class="hero-title-wrap">
                    <span class="hero-slash" aria-hidden="true"></span>
                    <h1><strong>2026</strong> CONSTRUCTORS</h1>
                </div>
                <p class="hero-subtitle">Explore official Formula 1 constructor standings and team profiles.</p>
            </div>
        </div>
    </header>

    <div class="constructors-container">
        <div class="constructors-grid">
            <?php foreach ($teams as $index => $team): ?>
                <?php
                    $rank = $index + 1;
                    $theme = constructorTheme($team['name'] ?? '');
                    $carImage = getTeamImage($team, ['car_image','car_image_url','banner_image','team_image','image','images']);
                    $logo = getTeamImage($team, ['logo','team_logo','logos']);
                    $nationality = $team['nationality'] ?? '';
                    $flag = constructorFlag($nationality);

                    $stmt = $pdo->prepare("
                        SELECT id, name, number
                        FROM drivers
                        WHERE team_id = ?
                        ORDER BY points DESC, name ASC
                        LIMIT 2
                    ");
                    $stmt->execute([$team['id']]);
                    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <article class="constructor-card" style="--team-accent:<?= e($theme['accent']) ?>;--team-rgb:<?= e($theme['rgb']) ?>;--team-banner:<?= e($theme['banner']) ?>;">
                    <div class="constructor-visual">
                        <?php if ($carImage !== ''): ?>
                            <img class="constructor-car-image" src="<?= e($carImage) ?>" alt="<?= e($team['name']) ?> racing car" onerror="this.style.display='none'">
                        <?php endif; ?>
                        <div class="constructor-visual-shade"></div>

                        <div class="constructor-topline">
                            <span class="constructor-rank">#<?= $rank ?></span>
                            <span class="constructor-nationality"><span><?= $flag ?></span><?= e($nationality) ?></span>
                        </div>

                        <div class="constructor-brand">
                            <?php if ($logo !== ''): ?>
                                <img class="constructor-logo" src="<?= e($logo) ?>" alt="<?= e($team['name']) ?> logo" onerror="this.style.display='none'">
                            <?php endif; ?>
                            <h2><?= e($team['name']) ?></h2>
                            <span class="constructor-underline"></span>
                        </div>
                    </div>

                    <div class="constructor-content">
                        <div class="constructor-stats">
                            <div class="constructor-stat"><span class="constructor-stat-value"><?= e($team['points'] ?? 0) ?></span><span class="constructor-stat-label">PTS</span></div>
                            <div class="constructor-stat"><span class="constructor-stat-value"><?= e($team['wins'] ?? 0) ?></span><span class="constructor-stat-label">WINS</span></div>
                            <div class="constructor-stat"><span class="constructor-stat-value"><?= e($team['podiums'] ?? 0) ?></span><span class="constructor-stat-label">PODIUMS</span></div>
                        </div>

                        <div class="constructor-drivers">
                            <div class="constructor-drivers-title">TEAM DRIVERS</div>
                            <div class="constructor-driver-list">
                                <?php if (count($drivers) > 0): ?>
                                    <?php foreach ($drivers as $driver): ?>
                                        <a href="driver-profile.php?id=<?= (int)$driver['id'] ?>" class="constructor-driver-pill">
                                            <span><?= e($driver['name']) ?></span>
                                            <span>(#<?= e($driver['number']) ?>)</span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="constructor-no-drivers">Drivers not added yet</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <a href="team-profile.php?id=<?= (int)$team['id'] ?>" class="constructor-profile-btn">
                            <span>VIEW TEAM PROFILE</span><span class="constructor-arrow" aria-hidden="true">→</span>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (count($teams) === 0): ?>
                <div class="constructors-empty">No teams found. Add teams to your database first.</div>
            <?php endif; ?>

            <div class="constructors-decoration" aria-hidden="true">
                <div class="decoration-f1">F1</div>
                <div class="decoration-tagline">FASTER <span>/</span> TOGETHER</div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>