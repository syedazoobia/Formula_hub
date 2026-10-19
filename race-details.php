<?php
// race-details.php — Detailed Grand Prix Info, Countdown, and Results
$pageTitle = "Race Details";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$raceId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM races WHERE id = ?");
$stmt->execute([$raceId]);
$race = $stmt->fetch();

if (!$race) {
    echo "<div class='details-error-wrap'><h2>Race Not Found</h2><p>The requested Grand Prix could not be retrieved.</p><a href='races.php' class='btn-primary-f1'>Back to Races</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

$isFav = is_logged_in() ? is_favorited($_SESSION['user_id'], 'race', $race['id']) : false;

// Image path resolution logic
$circuitImage = trim($race['circuit_image'] ?? $race['image'] ?? '');
if ($circuitImage !== '' && strpos($circuitImage, 'http://') !== 0 && strpos($circuitImage, 'https://') !== 0 && strpos($circuitImage, '/') !== 0) {
    $circuitImage = 'assets/images/circuit_image/' . basename($circuitImage);
}
if ($circuitImage === '') {
    $circuitImage = 'assets/images/races/default.jpg';
}

// Fetch Results if completed
$results = [];
if (strtolower($race['status'] ?? '') === 'completed') {
    $rStmt = $pdo->prepare("SELECT r.*, d.name as driver_name, d.number, t.name as team_name
                            FROM race_results r
                            JOIN drivers d ON r.driver_id = d.id
                            LEFT JOIN teams t ON d.team_id = t.id
                            WHERE r.race_id = ? ORDER BY r.position ASC");
    $rStmt->execute([$race['id']]);
    $results = $rStmt->fetchAll();
}
?>

<style>
/* =========================================================
   ULTRA-PREMIUM F1 RACE DETAILS PAGE STYLING
========================================================= */
.race-details-page {
    --page-max: 1280px;
    --f1-red: #e10600;
    --f1-red-glow: rgba(225, 6, 0, 0.4);
    position: relative;
    isolation: isolate;
    padding-bottom: 80px;
    color: #f4f6fa;
    background: #05090e;
    min-height: 85vh;
}

/* HERO BANNER SECTION WITH DYNAMIC CIRCUIT IMAGE & LIGHTING */
.details-hero {
    position: relative;
    width: 100%;
    padding: 60px 0 52px;
    margin-bottom: 35px;
    background: linear-gradient(180deg, rgba(3, 6, 10, 0.7) 0%, rgba(3, 6, 10, 0.95) 100%),
                radial-gradient(circle at 85% 25%, rgba(225, 6, 0, 0.28) 0%, transparent 60%),
                url('<?php echo e($circuitImage); ?>') center center / cover no-repeat;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.details-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0.12;
    background: repeating-linear-gradient(135deg, transparent 0, transparent 9px, rgba(255,255,255,.04) 10px, rgba(255,255,255,.04) 12px);
}

.details-hero-container {
    width: min(92%, var(--page-max));
    margin-inline: auto;
    position: relative;
    z-index: 2;
}

.details-hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 30px;
}

.details-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: rgba(225, 6, 0, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    color: #ffffff;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    box-shadow: 0 0 18px var(--f1-red-glow);
}

.details-title {
    font-size: clamp(32px, 4vw, 54px);
    font-weight: 900;
    font-style: italic;
    letter-spacing: -0.5px;
    color: #ffffff;
    margin: 12px 0 8px;
    text-transform: uppercase;
    line-height: 1.05;
    text-shadow: 0 4px 12px rgba(0,0,0,0.6);
}

.details-location {
    font-size: 1.05rem;
    color: #a3b2c5;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.details-location span {
    color: var(--f1-red);
}

/* LUXURY FAVORITE BUTTON */
.btn-fav-details {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, rgba(13, 22, 35, 0.9) 0%, rgba(5, 10, 17, 0.95) 100%);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 10px 22px;
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 800;
    text-transform: uppercase;
    cursor: pointer;
    backdrop-filter: blur(10px);
    transition: all 0.25s ease;
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
}

.btn-fav-details:hover, .btn-fav-details.active {
    background: rgba(225, 6, 0, 0.25);
    border-color: rgba(225, 6, 0, 0.7);
    color: #ff4d4d;
    box-shadow: 0 0 25px rgba(225, 6, 0, 0.35);
    transform: translateY(-2px);
}

/* SCHEDULE & COUNTDOWN BAR */
.schedule-countdown-bar {
    background: linear-gradient(145deg, rgba(13, 22, 35, 0.92) 0%, rgba(5, 10, 17, 0.98) 100%);
    border: 1px solid rgba(225, 6, 0, 0.4);
    border-left: 4px solid var(--f1-red);
    border-radius: 12px;
    padding: 24px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    backdrop-filter: blur(14px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.schedule-info-title {
    font-size: 0.75rem;
    color: var(--f1-red);
    font-weight: 900;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.schedule-info-val {
    font-size: 1.25rem;
    font-weight: 850;
    color: #ffffff;
}

.countdown-box {
    display: flex;
    align-items: center;
    gap: 12px;
}

.cd-unit {
    background: rgba(0, 0, 0, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 10px 16px;
    min-width: 68px;
    text-align: center;
    box-shadow: inset 0 2px 5px rgba(0,0,0,0.5);
}

.cd-number {
    font-size: 1.55rem;
    font-weight: 900;
    color: #ffffff;
    line-height: 1;
    font-family: monospace, monospace;
}

.cd-label {
    font-size: 0.6rem;
    color: #7b8a9e;
    font-weight: 900;
    letter-spacing: 1px;
    margin-top: 4px;
}

.cd-sep {
    font-size: 1.4rem;
    font-weight: 900;
    color: var(--f1-red);
}

/* MAIN CONTENT GRID */
.details-container {
    width: min(92%, var(--page-max));
    margin-inline: auto;
}

.details-grid {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 30px;
}

/* LUXURY CARDS */
.details-card {
    background: linear-gradient(145deg, rgba(13, 22, 35, 0.88) 0%, rgba(5, 10, 17, 0.96) 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(16px);
}

.details-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.details-header-border {
    width: 4px;
    height: 20px;
    background: var(--f1-red);
    display: inline-block;
    box-shadow: 0 0 10px var(--f1-red-glow);
}

.details-section-header h3 {
    font-size: 1.15rem;
    font-weight: 850;
    color: #ffffff;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.overview-desc {
    color: #a3b2c5;
    font-size: 0.94rem;
    line-height: 1.7;
    margin-bottom: 22px;
}

/* TRACK TECHNICAL SPECS GRID */
.circuit-specs-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}

.spec-card {
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 12px 8px;
    text-align: center;
}

.spec-lbl {
    display: block;
    font-size: 0.65rem;
    font-weight: 900;
    color: #7b8a9e;
    letter-spacing: 1px;
    margin-bottom: 4px;
}

.spec-val {
    font-size: 1.05rem;
    font-weight: 900;
    color: #ffffff;
}

.overview-meta-box {
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    font-size: 0.88rem;
}

.overview-meta-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #bdc8d8;
}

.overview-meta-item strong {
    color: #ffffff;
}

.circuit-map-wrap {
    margin-top: 20px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.12);
    position: relative;
    max-height: 200px;
}

.circuit-map-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
}

.circuit-map-wrap:hover img {
    transform: scale(1.05);
}

/* PREDICTION CARD */
.prediction-box-card {
    background: linear-gradient(145deg, rgba(13, 22, 35, 0.88) 0%, rgba(5, 10, 17, 0.96) 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 50px 30px;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
}

.prediction-bg-accent {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at center, rgba(225, 6, 0, 0.15) 0%, transparent 70%);
    pointer-events: none;
}

.prediction-box-card p {
    color: #a3b2c5;
    font-size: 1.05rem;
    margin-bottom: 28px;
    line-height: 1.6;
}

.btn-primary-f1 {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #e10600 0%, #b30000 100%);
    border: none;
    border-radius: 6px;
    padding: 15px 34px;
    color: #ffffff;
    font-size: 0.92rem;
    font-weight: 900;
    text-transform: uppercase;
    text-decoration: none;
    letter-spacing: 1px;
    box-shadow: 0 8px 25px var(--f1-red-glow), inset 0 1px 0 rgba(255,255,255,0.3);
    transition: all 0.25s ease;
}

.btn-primary-f1:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(225, 6, 0, 0.6), inset 0 1px 0 rgba(255,255,255,0.4);
    color: #ffffff;
}

/* PODIUM RESULTS TABLE STYLING */
.f1-table-wrap {
    background: linear-gradient(145deg, rgba(13, 22, 35, 0.88) 0%, rgba(5, 10, 17, 0.96) 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
}

.f1-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.f1-table th {
    background: rgba(0, 0, 0, 0.6);
    padding: 15px 18px;
    font-size: 0.75rem;
    font-weight: 900;
    letter-spacing: 1px;
    color: #7b8a9e;
    text-transform: uppercase;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.f1-table td {
    padding: 15px 18px;
    font-size: 0.9rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: #e2e8f0;
}

.f1-table tbody tr {
    transition: background 0.2s ease;
}

.f1-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.04);
}

/* PODIUM HIGHLIGHT ROWS */
.f1-table tr.row-p1 {
    background: linear-gradient(90deg, rgba(255, 215, 0, 0.09), transparent 70%);
    border-left: 4px solid #ffd700;
}

.f1-table tr.row-p2 {
    background: linear-gradient(90deg, rgba(192, 192, 192, 0.08), transparent 70%);
    border-left: 4px solid #c0c0c0;
}

.f1-table tr.row-p3 {
    background: linear-gradient(90deg, rgba(205, 127, 50, 0.08), transparent 70%);
    border-left: 4px solid #cd7f32;
}

.pos-pill {
    display: inline-block;
    font-weight: 900;
    padding: 4px 9px;
    border-radius: 4px;
    font-size: 0.85rem;
}

.pos-1 { color: #ffd700; background: rgba(255, 215, 0, 0.18); border: 1px solid rgba(255, 215, 0, 0.4); box-shadow: 0 0 10px rgba(255,215,0,0.2); }
.pos-2 { color: #c0c0c0; background: rgba(192, 192, 192, 0.18); border: 1px solid rgba(192, 192, 192, 0.4); }
.pos-3 { color: #cd7f32; background: rgba(205, 127, 50, 0.18); border: 1px solid rgba(205, 127, 50, 0.4); }

.fastest-lap-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(168, 85, 247, 0.2);
    border: 1px solid rgba(168, 85, 247, 0.4);
    color: #d8b4fe;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 800;
}

.details-error-wrap {
    padding: 100px 20px;
    text-align: center;
}

.details-error-wrap h2 {
    font-size: 2rem;
    margin-bottom: 10px;
}

.details-error-wrap p {
    color: #7b8a9e;
    margin-bottom: 25px;
}

@media (max-width: 992px) {
    .details-grid { grid-template-columns: 1fr; }
    .schedule-countdown-bar { flex-direction: column; text-align: center; }
    .details-hero-top { flex-direction: column; }
}
</style>

<section class="race-details-page">

    <!-- HERO SECTION -->
    <header class="details-hero">
        <div class="details-hero-container">
            <div class="details-hero-top">
                <div>
                    <span class="details-badge">ROUND <?php echo e($race['round']); ?> — <?php echo e(strtoupper($race['status'])); ?></span>
                    <h1 class="details-title"><?php echo e($race['grand_prix']); ?></h1>
                    <div class="details-location">
                        <span>📍</span> <?php echo e($race['circuit']); ?>, <?php echo e($race['country']); ?>
                    </div>
                </div>

                <?php if (is_logged_in()): ?>
                    <button onclick="toggleFavorite('race', <?php echo $race['id']; ?>, this)" class="btn-fav-details <?php echo $isFav ? 'active' : ''; ?>">
                        <?php echo $isFav ? '❤️ FAVORITED' : '🤍 ADD TO FAVORITES'; ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- COUNTDOWN / SCHEDULE BANNER -->
            <?php if (strtolower($race['status'] ?? '') === 'upcoming'): ?>
                <div class="schedule-countdown-bar">
                    <div>
                        <div class="schedule-info-title">RACE DAY SCHEDULE</div>
                        <div class="schedule-info-val">
                            📅 <?php echo format_date($race['race_date']); ?> &nbsp;•&nbsp; ⏰ <?php echo format_time($race['race_time']); ?>
                        </div>
                    </div>
                    <div class="countdown-box" id="countdown">
                        <div class="cd-unit"><div class="cd-number" id="cd-days">00</div><div class="cd-label">DAYS</div></div>
                        <div class="cd-sep">:</div>
                        <div class="cd-unit"><div class="cd-number" id="cd-hours">00</div><div class="cd-label">HOURS</div></div>
                        <div class="cd-sep">:</div>
                        <div class="cd-unit"><div class="cd-number" id="cd-mins">00</div><div class="cd-label">MINS</div></div>
                        <div class="cd-sep">:</div>
                        <div class="cd-unit"><div class="cd-number" id="cd-secs">00</div><div class="cd-label">SECS</div></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- CONTENT GRID -->
    <div class="details-container">
        <div class="details-grid">

            <!-- LEFT: CIRCUIT OVERVIEW & TECHNICAL SPECS -->
            <div class="details-card">
                <div class="details-section-header">
                    <span class="details-header-border"></span>
                    <h3>CIRCUIT OVERVIEW</h3>
                </div>

                <p class="overview-desc"><?php echo e($race['description'] ?? 'Experience the intensity of Formula 1 racing at this iconic Grand Prix venue.'); ?></p>

                <!-- TRACK TECHNICAL SPECS -->
                <div class="circuit-specs-grid">
                    <div class="spec-card">
                        <span class="spec-lbl">LAPS</span>
                        <span class="spec-val"><?php echo e($race['laps'] ?? '58'); ?></span>
                    </div>
                    <div class="spec-card">
                        <span class="spec-lbl">LENGTH</span>
                        <span class="spec-val"><?php echo e($race['track_length'] ?? '5.4 km'); ?></span>
                    </div>
                    <div class="spec-card">
                        <span class="spec-lbl">DISTANCE</span>
                        <span class="spec-val"><?php echo e($race['race_distance'] ?? '308 km'); ?></span>
                    </div>
                </div>

                <div class="overview-meta-box">
                    <div class="overview-meta-item">
                        <span>Country</span>
                        <strong><?php echo e($race['country']); ?></strong>
                    </div>
                    <div class="overview-meta-item">
                        <span>Circuit</span>
                        <strong><?php echo e($race['circuit']); ?></strong>
                    </div>
                    <div class="overview-meta-item">
                        <span>Status</span>
                        <strong style="color:var(--f1-red);"><?php echo e(strtoupper($race['status'])); ?></strong>
                    </div>
                </div>

              
            </div>

            <!-- RIGHT: RESULTS OR PREDICTIONS -->
            <div>
                <div class="details-section-header" style="margin-bottom: 20px;">
                    <span class="details-header-border"></span>
                    <h3><?php echo strtolower($race['status'] ?? '') === 'completed' ? 'OFFICIAL RACE RESULTS' : 'RACE SCHEDULE & PREDICTIONS'; ?></h3>
                </div>

                <?php if (strtolower($race['status'] ?? '') === 'completed'): ?>
                    <div class="f1-table-wrap">
                        <table class="f1-table">
                            <thead>
                                <tr>
                                    <th>POS</th>
                                    <th>DRIVER</th>
                                    <th>TEAM</th>
                                    <th>POINTS</th>
                                    <th>FASTEST LAP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $res): ?>
                                    <?php 
                                        $posClass = $res['position'] == 1 ? 'pos-1' : ($res['position'] == 2 ? 'pos-2' : ($res['position'] == 3 ? 'pos-3' : '')); 
                                        $rowClass = $res['position'] == 1 ? 'row-p1' : ($res['position'] == 2 ? 'row-p2' : ($res['position'] == 3 ? 'row-p3' : ''));
                                    ?>
                                    <tr class="<?php echo $rowClass; ?>">
                                        <td><span class="pos-pill <?php echo $posClass; ?>">P<?php echo $res['position']; ?></span></td>
                                        <td><strong><?php echo e($res['driver_name']); ?></strong> <span style="color:#7b8a9e; font-size:0.8rem;">(#<?php echo e($res['number']); ?>)</span></td>
                                        <td><?php echo e($res['team_name']); ?></td>
                                        <td><strong style="color:#ffd700;"><?php echo $res['points']; ?> PTS</strong></td>
                                        <td>
                                            <?php if (!empty($res['fastest_lap']) && $res['fastest_lap'] == 1): ?>
                                                <span class="fastest-lap-badge">⚡ FASTEST LAP</span>
                                            <?php else: ?>
                                                <span style="color:#63738a;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="prediction-box-card">
                        <div class="prediction-bg-accent"></div>
                        <p>This Grand Prix is upcoming! Predict the winner now to earn fan points and climb the global fan leaderboard.</p>
                        <a href="predictions.php?race_id=<?php echo $race['id']; ?>" class="btn-primary-f1">
                            🏆 SUBMIT WINNER PREDICTION <span>→</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<?php if (strtolower($race['status'] ?? '') === 'upcoming'): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const targetDateStr = '<?php echo $race['race_date'] . 'T' . $race['race_time']; ?>';
    const targetDate = new Date(targetDateStr).getTime();

    function updateCd() {
        const now = new Date().getTime();
        const diff = targetDate - now;

        if (diff <= 0) {
            document.getElementById('countdown').innerHTML = "<div style='color:#00d878; font-weight:900;'>RACE WEEKEND IS LIVE!</div>";
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const secs = Math.floor((diff % (1000 * 60)) / 1000);

        document.getElementById('cd-days').innerText = String(days).padStart(2, '0');
        document.getElementById('cd-hours').innerText = String(hours).padStart(2, '0');
        document.getElementById('cd-mins').innerText = String(mins).padStart(2, '0');
        document.getElementById('cd-secs').innerText = String(secs).padStart(2, '0');
    }

    updateCd();
    setInterval(updateCd, 1000);
});
</script>
<?php endif; ?>

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