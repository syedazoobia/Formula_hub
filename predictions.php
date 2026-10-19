<?php
// predictions.php — Race Winner Predictions Game
$pageTitle = "Race Predictions";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Handle Prediction Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_prediction'])) {
    require_login();
    $userId   = $_SESSION['user_id'];
    $raceId   = (int)($_POST['race_id'] ?? 0);
    $driverId = (int)($_POST['driver_id'] ?? 0);

    if ($raceId > 0 && $driverId > 0) {
        // Check if user already predicted for this race
        $check = $pdo->prepare("SELECT id FROM predictions WHERE user_id = ? AND race_id = ?");
        $check->execute([$userId, $raceId]);
        if ($check->fetch()) {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'You have already submitted a prediction for this Grand Prix!'];
        } else {
            $ins = $pdo->prepare("INSERT INTO predictions (user_id, race_id, driver_id, prediction_status, points_earned) VALUES (?, ?, ?, 'Pending', 0)");
            $ins->execute([$userId, $raceId, $driverId]);

            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Prediction locked in! Good luck!'];
        }
        header("Location: predictions.php");
        exit();
    }
}

// Fetch Upcoming Races for prediction selector
$upcomingRaces = $pdo->query("SELECT * FROM races WHERE status = 'Upcoming' ORDER BY race_date ASC")->fetchAll();
$allDrivers    = $pdo->query("SELECT d.*, t.name as team_name FROM drivers d LEFT JOIN teams t ON d.team_id = t.id ORDER BY d.name ASC")->fetchAll();

// Fetch User Predictions if logged in
$userPredictions = [];
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT p.*, r.grand_prix, r.race_date, d.name as driver_name, t.name as team_name 
                           FROM predictions p 
                           JOIN races r ON p.race_id = r.id 
                           JOIN drivers d ON p.driver_id = d.id 
                           LEFT JOIN teams t ON d.team_id = t.id 
                           WHERE p.user_id = ? ORDER BY p.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $userPredictions = $stmt->fetchAll();
}

$preSelectedRaceId = (int)($_GET['race_id'] ?? 0);

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Page Layout */
.predictions-page {
    --page-max: 1280px;
    position: relative;
    isolation: isolate;
    overflow: hidden;
    padding: 0 0 60px;
    color: #f4f6fa;
    background: #05090e;
    min-height: 80vh;
}

/* HERO SECTION WITH F1 BACKGROUND IMAGE */
.predictions-hero {
    position: relative;
    width: 100%;
    padding: 56px 0 50px;
    margin-bottom: 35px;
     background:
        linear-gradient(
            90deg,
            rgba(3,5,8,.96) 0%,
            rgba(3,5,8,.82) 38%,
            rgba(3,5,8,.22) 72%,
            rgba(3,5,8,.65) 100%
        ),
        url("assets/images/predication.png")
        70% center / cover no-repeat;
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
    letter-spacing: 1px;
    color: #ffffff;
    margin: 0;
    line-height: 1.1;
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

/* MAIN CARDS CONTAINER */
.predictions-container {
    width: min(92%, var(--page-max));
    margin-inline: auto;
}

/* EXACT IMAGE 2 CARD SHADE & GLOW */
.prediction-card {
    position: relative;
    background: linear-gradient(145deg, rgba(13, 22, 35, 0.95) 0%, rgba(5, 10, 17, 0.98) 70%, rgba(3, 6, 11, 1) 100%);
    border: 1px solid rgba(50, 68, 92, 0.45);
    border-left: 4px solid #e10600;
    border-radius: 10px;
    padding: 30px 36px;
    margin-bottom: 35px;
    box-shadow: 
        0 15px 35px rgba(0, 0, 0, 0.6),
        inset 0 1px 0 rgba(255, 255, 255, 0.08),
        inset 0 0 20px rgba(0, 0, 0, 0.4);
    overflow: hidden;
    backdrop-filter: blur(8px);
}

/* Internal Subtle Pattern Overlay */
.prediction-card::after {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    background: repeating-linear-gradient(130deg, transparent 0, transparent 40px, rgba(225, 6, 0, 0.02) 41px, rgba(225, 6, 0, 0.02) 55px);
    opacity: 0.7;
}

.prediction-card > * {
    position: relative;
    z-index: 1;
}

.card-heading {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.2rem;
    font-weight: 850;
    color: #f4f6fa;
    margin-bottom: 25px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.card-heading-icon {
    color: #e10600;
    font-size: 1.3rem;
    filter: drop-shadow(0 0 6px rgba(225, 6, 0, 0.6));
}

/* Form Styling */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 25px;
}

.form-group-custom {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group-custom label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #8b99ac;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.select-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.select-wrapper select {
    width: 100%;
    background: rgba(3, 7, 13, 0.85);
    border: 1px solid rgba(48, 65, 88, 0.7);
    border-radius: 6px;
    color: #e2e8f0;
    padding: 12px 16px 12px 42px;
    font-size: 0.9rem;
    font-weight: 600;
    outline: none;
    appearance: none;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.5);
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.select-wrapper select:focus {
    border-color: #e10600;
    background: rgba(4, 9, 16, 0.95);
    box-shadow: 0 0 12px rgba(225, 6, 0, 0.3), inset 0 2px 4px rgba(0, 0, 0, 0.5);
}

.select-icon-left {
    position: absolute;
    left: 14px;
    color: #63738a;
    font-size: 1.05rem;
    pointer-events: none;
}

.select-icon-right {
    position: absolute;
    right: 14px;
    color: #63738a;
    font-size: 0.8rem;
    pointer-events: none;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 10px;
}

.reward-tag {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #cbd5e1;
}

.reward-tag strong {
    color: #f5b942;
    text-shadow: 0 0 8px rgba(245, 185, 66, 0.3);
}

.btn-submit-prediction {
    background: linear-gradient(135deg, #e10600 0%, #b30400 100%);
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 12px 28px;
    font-size: 0.85rem;
    font-weight: 850;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
    box-shadow: 0 4px 15px rgba(225, 6, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.btn-submit-prediction:hover {
    background: linear-gradient(135deg, #ff0800 0%, #d10500 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 22px rgba(225, 6, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

/* History Table Styling */
.table-wrapper {
    overflow-x: auto;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.history-table th {
    background: rgba(255, 255, 255, 0.025);
    padding: 14px 18px;
    font-size: 0.75rem;
    font-weight: 800;
    color: #7b8a9e;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    border-bottom: 1px solid rgba(50, 68, 92, 0.4);
}

.history-table td {
    padding: 16px 18px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    font-size: 0.9rem;
    color: #e2e8f0;
}

.history-table tr:last-child td {
    border-bottom: none;
}

.badge-status {
    display: inline-block;
    padding: 5px 14px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-pending {
    background: rgba(245, 185, 66, 0.18);
    color: #f5b942;
    border: 1px solid rgba(245, 185, 66, 0.35);
    box-shadow: 0 0 10px rgba(245, 185, 66, 0.15);
}

.badge-correct {
    background: rgba(34, 197, 94, 0.18);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.35);
    box-shadow: 0 0 10px rgba(34, 197, 94, 0.15);
}

.badge-incorrect {
    background: rgba(225, 6, 0, 0.18);
    color: #e10600;
    border: 1px solid rgba(225, 6, 0, 0.35);
    box-shadow: 0 0 10px rgba(225, 6, 0, 0.15);
}

.login-notice {
    text-align: center;
    padding: 25px;
    background: rgba(3, 7, 13, 0.8);
    border: 1px dashed rgba(48, 65, 88, 0.7);
    border-radius: 8px;
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
    .form-grid {
        grid-template-columns: 1fr;
    }
    .hero-container {
        flex-direction: column;
    }
    .form-actions {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    .btn-submit-prediction {
        justify-content: center;
    }
}
</style>

<section class="predictions-page">
    
    <!-- HERO SECTION WITH F1 BACKGROUND IMAGE -->
    <header class="predictions-hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-title-wrap">
                    <span class="hero-slash" aria-hidden="true"></span>
                    <h1>GRAND PRIX <span>PREDICTOR</span></h1>
                </div>
                <p class="hero-subtitle">Predict race winners before lights out and win +50 Fan Points per correct prediction.</p>
            </div>
        </div>
    </header>

    <div class="predictions-container">
        
        <!-- FORM CARD -->
        <div class="prediction-card">
            <div class="card-heading">
                <span class="card-heading-icon">🎯</span> LOCK IN YOUR PREDICTION
            </div>

            <?php if (is_logged_in()): ?>
                <?php if (empty($upcomingRaces)): ?>
                    <p style="color:#8b99ac; margin:0;">No upcoming races available for prediction at this time.</p>
                <?php else: ?>
                    <form method="POST" action="predictions.php">
                        <div class="form-grid">
                            
                            <!-- Select GP -->
                            <div class="form-group-custom">
                                <label>SELECT GRAND PRIX</label>
                                <div class="select-wrapper">
                                    <span class="select-icon-left">🏁</span>
                                    <select name="race_id" required>
                                        <?php foreach ($upcomingRaces as $ur): ?>
                                            <option value="<?php echo $ur['id']; ?>" <?php echo $ur['id'] == $preSelectedRaceId ? 'selected' : ''; ?>>
                                                Round <?php echo $ur['round']; ?> — <?php echo e($ur['grand_prix']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="select-icon-right">▼</span>
                                </div>
                            </div>

                            <!-- Select Driver -->
                            <div class="form-group-custom">
                                <label>PREDICTED RACE WINNER</label>
                                <div class="select-wrapper">
                                    <span class="select-icon-left">👤</span>
                                    <select name="driver_id" required>
                                        <?php foreach ($allDrivers as $ad): ?>
                                            <option value="<?php echo $ad['id']; ?>">
                                                <?php echo e($ad['name']); ?> (<?php echo e($ad['team_name']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="select-icon-right">▼</span>
                                </div>
                            </div>

                        </div>

                        <div class="form-actions">
                            <div class="reward-tag">
                                <span style="color:#f5b942;">★</span> Correct Prediction = <strong>+50 PTS</strong>
                            </div>
                            <button type="submit" name="submit_prediction" class="btn-submit-prediction">
                                SUBMIT PREDICTION <span>→</span>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <div class="login-notice">
                    Please <a href="login.php">Log In</a> to submit race predictions and earn leaderboard points.
                </div>
            <?php endif; ?>
        </div>

        <!-- MY PREDICTION HISTORY CARD -->
        <?php if (is_logged_in()): ?>
            <div class="prediction-card">
                <div class="card-heading">
                    <span class="card-heading-icon">⏱</span> MY PREDICTION HISTORY
                </div>

                <?php if (empty($userPredictions)): ?>
                    <p style="color:#8b99ac; text-align:center; margin:20px 0;">You haven't submitted any predictions yet.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>GRAND PRIX</th>
                                    <th>PREDICTED WINNER</th>
                                    <th>STATUS</th>
                                    <th>POINTS EARNED</th>
                                    <th>SUBMITTED DATE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userPredictions as $up): ?>
                                    <tr>
                                        <td><strong><?php echo e($up['grand_prix']); ?></strong></td>
                                        <td>
                                            <strong><?php echo e($up['driver_name']); ?></strong> 
                                            <span style="color:#63738a; font-size:0.8rem;">(<?php echo e($up['team_name']); ?>)</span>
                                        </td>
                                        <td>
                                            <?php if ($up['prediction_status'] == 'Correct'): ?>
                                                <span class="badge-status badge-correct">Correct</span>
                                            <?php elseif ($up['prediction_status'] == 'Incorrect'): ?>
                                                <span class="badge-status badge-incorrect">Incorrect</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong style="color:#f5b942;"><?php echo $up['points_earned']; ?> PTS</strong></td>
                                        <td style="color:#63738a; font-size:0.85rem;"><?php echo date('M d, Y', strtotime($up['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>