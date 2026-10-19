<?php
// results.php — Race Results Center
$pageTitle = "Race Results";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$raceId = (int)($_GET['race_id'] ?? 0);

// Get list of all completed races
$completedRaces = $pdo->query("SELECT * FROM races WHERE status = 'Completed' ORDER BY round ASC")->fetchAll();

if ($raceId == 0) {
    if (!empty($completedRaces)) {
        $raceId = $completedRaces[0]['id'];
    }
}

$currentRace = null;
$results = [];

if ($raceId > 0) {
    $rStmt = $pdo->prepare("SELECT * FROM races WHERE id = ?");
    $rStmt->execute([$raceId]);
    $currentRace = $rStmt->fetch();

    if ($currentRace) {
        $resStmt = $pdo->prepare("SELECT r.*, 
                                         d.name as driver_name, d.number, d.image as driver_image, d.flag as driver_flag, 
                                         t.name as team_name, t.logo as team_logo
                                  FROM race_results r
                                  JOIN drivers d ON r.driver_id = d.id
                                  LEFT JOIN teams t ON d.team_id = t.id
                                  WHERE r.race_id = ? ORDER BY r.position ASC");
        $resStmt->execute([$raceId]);
        $results = $resStmt->fetchAll();
    }
}
?>

<style>
/* =========================================================
   FORMULAHUB — RACE RESULTS STYLING
========================================================= */
.results-hero {
    background:
        linear-gradient(
            90deg,
            rgba(3, 5, 8, 0.97) 0%,
            rgba(3, 5, 8, 0.85) 38%,
            rgba(3, 5, 8, 0.35) 75%,
            rgba(3, 5, 8, 0.75) 100%
        ),
        url("assets/images/race_hero.png") center center / cover no-repeat;
}

.results-page-body {
    min-height: 65vh;
    padding: 10px 0 80px;
    background: var(--bg-primary);
    color: #f4f6fa;
}

/* RACE SELECTOR IN HERO */
.results-selector-wrap {
    min-width: 280px;
}

.results-select-control {
    width: 100%;
    min-width: 280px;
    background: rgba(13, 22, 35, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #ffffff;
    padding: 12px 18px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
    transition: all 0.2s ease;
}

.results-select-control:focus {
    outline: none;
    border-color: var(--accent-red);
    box-shadow: 0 0 15px rgba(225, 6, 0, 0.3);
}

.results-select-control option {
    background: #090e15;
    color: #fff;
    padding: 8px;
}

/* GP BANNER CARD */
.gp-banner-card {
    position: relative;
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 28px 32px;
    border-radius: 14px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.5);
    overflow: hidden;
}

.gp-banner-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--accent-red);
}

.gp-meta-row {
    color: var(--text-muted);
    font-size: 0.9rem;
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    font-weight: 600;
    margin-top: 6px;
}

/* TABLE WRAPPER */
.results-table-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.results-table {
    width: 100%;
    border-collapse: collapse;
}

.results-table th {
    padding: 16px 20px;
    text-align: left;
    background: rgba(255, 255, 255, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--text-muted);
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.results-table td {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    font-size: 0.92rem;
    vertical-align: middle;
}

.results-table tr:hover td {
    background: rgba(255, 255, 255, 0.02);
}

.driver-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.driver-avatar-thumb {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    overflow: hidden;
    background: #121822;
    border: 1px solid rgba(255, 255, 255, 0.15);
    flex-shrink: 0;
}

.driver-avatar-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center top;
}

@media (max-width: 768px) {
    .results-hero .hero-container {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
    .results-selector-wrap {
        width: 100%;
    }
    .results-select-control {
        min-width: 100%;
    }
}
</style>

<!-- STANDARDIZED RESULTS HERO -->
<section class="page-hero results-hero">
    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-tagline">OFFICIAL CLASSIFICATION • 2026 SEASON</div>
            <div class="hero-title-wrap">
                <span class="hero-slash" aria-hidden="true"></span>
                <h1 class="hero-title">RACE RESULTS</h1>
            </div>
            <p class="hero-subtitle">Select a Grand Prix to view full race classification, fastest laps, and championship points awarded.</p>
        </div>

        <div class="results-selector-wrap">
            <form method="GET" action="results.php" style="margin: 0;">
                <select name="race_id" onchange="this.form.submit()" class="results-select-control" aria-label="Select Grand Prix">
                    <?php foreach ($completedRaces as $cr): ?>
                        <option value="<?php echo $cr['id']; ?>" <?php echo $cr['id'] == $raceId ? 'selected' : ''; ?>>
                            Round <?php echo $cr['round']; ?> — <?php echo e($cr['grand_prix']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
</section>

<!-- MAIN CONTENT -->
<main class="results-page-body">
    <div class="container">

        <?php if ($currentRace && !empty($results)): ?>
            <?php
                // Resolve Flag Image & Circuit/Background Image from database
                $dbFlag = trim($currentRace['country_flag'] ?? '');
                $gpFlagImage = '';
                if ($dbFlag !== '') {
                    $gpFlagImage = 'assets/images/races/' . basename($dbFlag);
                }

                $circuitBg = trim($currentRace['circuit_image'] ?? $currentRace['image'] ?? '');
                $cardBgUrl = '';
                if ($circuitBg !== '') {
                    $cardBgUrl = (strpos($circuitBg, 'http://') === 0 || strpos($circuitBg, 'https://') === 0 || strpos($circuitBg, 'assets/') === 0) ? $circuitBg : 'assets/images/circuit_image/' . basename($circuitBg);
                }
            ?>

            <!-- GRAND PRIX BANNER CARD -->
            <div class="gp-banner-card" style="background: linear-gradient(90deg, rgba(5, 10, 17, 0.95) 0%, rgba(5, 10, 17, 0.85) 55%, rgba(5, 10, 17, 0.6) 100%), url('<?php echo e($cardBgUrl); ?>') center right / cover no-repeat;">
                <div style="display: flex; align-items: center; gap: 20px; z-index: 2;">
                    <div style="width: 58px; height: 40px; border-radius: 6px; overflow: hidden; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <?php if ($gpFlagImage !== ''): ?>
                            <img src="<?php echo e($gpFlagImage); ?>" alt="Flag" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <span style="display: none; font-size: 1.2rem; color: #e10600;">📍</span>
                        <?php else: ?>
                            <span style="font-size: 1.2rem; color: #e10600;">📍</span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div style="color: var(--accent-red); font-size: 0.75rem; font-weight: 900; letter-spacing: 1.2px; text-transform: uppercase;">
                            ROUND <?php echo e($currentRace['round']); ?>
                        </div>
                        <h2 style="font-size: 1.8rem; margin: 3px 0 6px 0; text-transform: uppercase; font-weight: 900;">
                            <?php echo e($currentRace['grand_prix']); ?>
                        </h2>
                        <div class="gp-meta-row">
                            <span>📍 <?php echo e($currentRace['circuit']); ?>, <?php echo e($currentRace['country']); ?></span>
                            <span>📅 Date: <?php echo format_date($currentRace['race_date']); ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($currentRace['track_map'])): ?>
                    <div style="z-index: 2; display: none; @media(min-width: 900px){display:block;}">
                        <img src="assets/images/circuit_image/<?php echo e(basename($currentRace['track_map'])); ?>" alt="Circuit Map" style="height: 70px; object-fit: contain; filter: drop-shadow(0 0 10px rgba(255,255,255,0.3)); opacity: 0.85;" onerror="this.style.display='none';">
                    </div>
                <?php endif; ?>
            </div>

            <!-- RESULTS TABLE CARD -->
            <div class="results-table-card">
                <div class="table-responsive">
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">POS</th>
                                <th>DRIVER</th>
                                <th>CONSTRUCTOR</th>
                                <th>POINTS</th>
                                <th>FASTEST LAP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $res): ?>
                                <?php
                                    $pos = (int)$res['position'];
                                    $posColor = '#fff';
                                    if ($pos == 1) {
                                        $posColor = 'var(--gold)';
                                    } else if ($pos == 2) {
                                        $posColor = 'var(--silver)';
                                    } else if ($pos == 3) {
                                        $posColor = 'var(--bronze)';
                                    }

                                    $resolveImg = function($val, $folder) {
                                        $val = trim((string)$val);
                                        if ($val === '') return '';
                                        return 'assets/images/' . trim($folder, '/') . '/' . basename($val);
                                    };

                                    $driverImg = !empty($res['driver_image']) ? $resolveImg($res['driver_image'], 'drivers') : '';
                                    $driverFlag = !empty($res['driver_flag']) ? $resolveImg($res['driver_flag'], 'flags') : '';
                                    $teamLogo = !empty($res['team_logo']) ? $resolveImg($res['team_logo'], 'teams') : '';
                                ?>
                                <tr>
                                    <td>
                                        <strong style="font-family: var(--font-heading); font-size: 1.15rem; color: <?php echo $posColor; ?>;">
                                            P<?php echo $pos; ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <div class="driver-cell">
                                            <?php if ($driverFlag !== ''): ?>
                                                <img src="<?php echo e($driverFlag); ?>" alt="" style="width: 20px; height: 14px; object-fit: cover; border-radius: 2px;" loading="lazy">
                                            <?php endif; ?>

                                            <?php if ($driverImg !== ''): ?>
                                                <div class="driver-avatar-thumb">
                                                    <img src="<?php echo e($driverImg); ?>" alt="" loading="lazy">
                                                </div>
                                            <?php endif; ?>

                                            <div>
                                                <strong style="font-size: 0.98rem; color: #fff;"><?php echo e($res['driver_name']); ?></strong> 
                                                <span style="color: var(--text-muted); font-size: 0.82rem;">(#<?php echo e($res['number']); ?>)</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <?php if ($teamLogo !== ''): ?>
                                                <img src="<?php echo e($teamLogo); ?>" alt="" style="width: 24px; height: 18px; object-fit: contain;" loading="lazy">
                                            <?php endif; ?>
                                            <span><?php echo e($res['team_name'] ?? 'Independent'); ?></span>
                                        </div>
                                    </td>

                                    <td>
                                        <strong style="color: var(--gold); font-family: var(--font-heading); font-size: 1.05rem;">
                                            <?php echo (int)$res['points']; ?> PTS
                                        </strong>
                                    </td>

                                    <td>
                                        <?php if (!empty($res['fastest_lap'])): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 6px; color: #d8b4fe; font-weight: 800; font-size: 0.85rem;">
                                                <span style="background: rgba(168, 85, 247, 0.2); border: 1px solid rgba(168, 85, 247, 0.4); border-radius: 50%; width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem;">⏱</span>
                                                <?php echo e($res['fastest_lap']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); padding: 50px 30px; text-align: center; border-radius: 14px; color: var(--text-muted); margin-top: 20px;">
                <h3 style="margin: 0 0 8px 0; color: #fff; font-size: 1.3rem;">No race results recorded yet.</h3>
                <p style="margin: 0; font-size: 0.95rem;">Check back after the race weekend concludes!</p>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>