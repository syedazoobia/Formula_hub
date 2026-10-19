<?php
// polls.php — Fan Polls & Voting (Database Image Dynamic Showcase)
$pageTitle = "Official Fan Polls";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Handle Vote Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    require_login();
    $optionId = (int)($_POST['option_id'] ?? 0);
    if ($optionId > 0) {
        $update = $pdo->prepare("UPDATE poll_options SET votes = votes + 1 WHERE id = ?");
        $update->execute([$optionId]);

        // Award +15 Fan Points for voting!
        add_user_points($_SESSION['user_id'], 15);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Your vote has been recorded! +15 Fan Points awarded!'];
        header("Location: polls.php");
        exit();
    }
}

// Fetch Active Polls
$polls = $pdo->query("SELECT * FROM polls WHERE status = 'Active' ORDER BY created_at DESC")->fetchAll();

// Robust Helper Function to Resolve Driver/Team Images
if (!function_exists('resolveOptionImage')) {
    function resolveOptionImage($opt, $pdo) {
        $text = strtolower(trim($opt['option_text']));

        // Check if explicit image set on option
        if (!empty($opt['image'])) {
            $img = basename($opt['image']);
            if (file_exists(__DIR__ . '/assets/images/drivers/' . $img)) return 'assets/images/drivers/' . $img;
            if (file_exists(__DIR__ . '/assets/images/teams/' . $img)) return 'assets/images/teams/' . $img;
            if (file_exists(__DIR__ . '/assets/images/avatar/' . $img)) return 'assets/images/avatar/' . $img;
        }

        // Search Drivers table
        $drivers = $pdo->query("SELECT id, name, image FROM drivers")->fetchAll();
        foreach ($drivers as $d) {
            $driverName = strtolower($d['name']);
            // Extract first/last name matching
            $nameParts = explode(' ', $driverName);
            $lastName = end($nameParts);
            
            if (strpos($text, $driverName) !== false || strpos($text, $lastName) !== false) {
                if (!empty($d['image'])) {
                    $img = basename($d['image']);
                    if (file_exists(__DIR__ . '/assets/images/drivers/' . $img)) return 'assets/images/drivers/' . $img;
                }
            }
        }

        // Search Teams table
        $teams = $pdo->query("SELECT id, name, logo, car_image FROM teams")->fetchAll();
        foreach ($teams as $t) {
            $teamName = strtolower($t['name']);
            // Extract key team identifier (e.g. 'ferrari', 'mclaren', 'red bull')
            $cleanTeam = str_replace(['scuderia', 'oracle', 'formula 1 team', 'hp'], '', $teamName);
            $cleanTeam = trim($cleanTeam);

            if (strpos($text, $teamName) !== false || (!empty($cleanTeam) && strpos($text, $cleanTeam) !== false)) {
                if (!empty($t['logo'])) {
                    $img = basename($t['logo']);
                    if (file_exists(__DIR__ . '/assets/images/teams/' . $img)) return 'assets/images/teams/' . $img;
                }
                if (!empty($t['car_image'])) {
                    $img = basename($t['car_image']);
                    if (file_exists(__DIR__ . '/assets/images/teams/' . $img)) return 'assets/images/teams/' . $img;
                }
            }
        }

        return '';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Page Layout */
.polls-page {
    --page-max: 1280px;
    position: relative;
    isolation: isolate;
    overflow: hidden;
    padding: 0 0 70px;
    color: #f4f6fa;
    background: #05090e;
    min-height: 85vh;
}

/* HERO SECTION BANNER WITH F1 IMAGE BACKGROUND */
.polls-hero {
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
        url("assets/images/poll_hero.jpg")
        center / cover no-repeat;
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

.hero-badge-quote {
    font-family: var(--font-heading, sans-serif);
    font-size: 1.25rem;
    font-weight: 850;
    font-style: italic;
    color: #ffffff;
    text-align: right;
    opacity: 0.9;
    text-shadow: 0 0 15px rgba(225, 6, 0, 0.5);
}

/* MAIN CONTAINER */
.polls-container {
    width: min(92%, var(--page-max));
    margin-inline: auto;
    display: flex;
    flex-direction: column;
    gap: 35px;
}

/* POLL CARD STRUCTURE */
.poll-card {
    display: grid;
    grid-template-columns: 360px 1fr;
    background: linear-gradient(145deg, rgba(13, 22, 35, 0.95) 0%, rgba(5, 10, 17, 0.98) 70%, rgba(3, 6, 11, 1) 100%);
    border: 1px solid rgba(50, 68, 92, 0.45);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(8px);
}

/* LEFT FEATURED IMAGE POSTER SQUARE BOX */
.poll-poster {
    position: relative;
    background: radial-gradient(circle at center, rgba(225, 6, 0, 0.35) 0%, rgba(5, 9, 15, 0.95) 100%), #05090e;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 24px;
    overflow: hidden;
    border-right: 1px solid rgba(50, 68, 92, 0.4);
    min-height: 320px;
}

.poster-bg-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center top;
    opacity: 0.9;
    filter: brightness(1.05) contrast(1.1);
    z-index: 0;
    transition: transform 0.4s ease;
}

.poll-card:hover .poster-bg-img {
    transform: scale(1.05);
}

.poster-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(3, 7, 13, 0.5) 0%, rgba(3, 7, 13, 0.1) 50%, rgba(3, 7, 13, 0.85) 100%);
    z-index: 1;
}

.poster-badge {
    position: relative;
    z-index: 2;
    align-self: flex-start;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(34, 197, 94, 0.18);
    border: 1px solid rgba(34, 197, 94, 0.4);
    color: #22c55e;
    font-size: 0.68rem;
    font-weight: 850;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

.poster-badge-dot {
    width: 6px;
    height: 6px;
    background: #22c55e;
    border-radius: 50%;
    box-shadow: 0 0 8px #22c55e;
}

.poster-footer-logo {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #ffffff;
    font-weight: 900;
    font-style: italic;
    font-size: 0.9rem;
    letter-spacing: 1px;
}

.poster-f1-icon {
    color: #e10600;
    font-size: 1.1rem;
}

/* RIGHT POLL CONTENT */
.poll-main-content {
    padding: 30px 35px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.poll-question-header {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 25px;
}

.poll-icon {
    color: #e10600;
    font-size: 1.4rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.poll-question-title {
    font-size: 1.25rem;
    font-weight: 850;
    color: #ffffff;
    margin: 0;
    line-height: 1.3;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* POLL OPTIONS LIST */
.poll-options-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-bottom: 25px;
}

.poll-option-row {
    position: relative;
    background: rgba(3, 7, 13, 0.7);
    border: 1px solid rgba(48, 65, 88, 0.6);
    border-radius: 8px;
    padding: 12px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
    transition: border-color 0.2s ease, background 0.2s ease;
}

.poll-option-row:hover {
    border-color: rgba(225, 6, 0, 0.4);
    background: rgba(5, 12, 22, 0.85);
}

.option-progress-bar {
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    background: linear-gradient(90deg, rgba(225, 6, 0, 0.2) 0%, rgba(225, 6, 0, 0.7) 100%);
    border-right: 3px solid #e10600;
    z-index: 0;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 0 10px rgba(225, 6, 0, 0.4);
}

.option-left-content {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 12px;
}

.option-radio-input {
    accent-color: #e10600;
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.option-avatar-img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    object-position: center top;
    border: 1.5px solid rgba(225, 6, 0, 0.6);
    box-shadow: 0 2px 6px rgba(0,0,0,0.5);
    background: #10141d;
    flex-shrink: 0;
}

.option-label-text {
    font-size: 0.92rem;
    font-weight: 700;
    color: #ffffff;
    cursor: pointer;
}

.option-right-stats {
    position: relative;
    z-index: 2;
    font-size: 1rem;
    font-weight: 850;
    color: #e10600;
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.option-votes-count {
    font-size: 0.78rem;
    color: #7b8a9e;
    font-weight: 600;
}

.poll-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 15px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.total-votes-tag {
    font-size: 0.82rem;
    color: #8b99ac;
}

.total-votes-tag strong {
    color: #ffffff;
}

.btn-vote-submit {
    background: linear-gradient(135deg, #e10600 0%, #b30400 100%);
    color: #ffffff;
    border: none;
    padding: 10px 24px;
    border-radius: 6px;
    font-weight: 850;
    font-size: 0.8rem;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(225, 6, 0, 0.4);
    transition: all 0.2s ease;
}

.btn-vote-submit:hover {
    background: linear-gradient(135deg, #ff0800 0%, #d10500 100%);
    box-shadow: 0 6px 20px rgba(225, 6, 0, 0.6);
    transform: translateY(-2px);
}

.btn-vote-login {
    background: linear-gradient(135deg, #e10600 0%, #b30400 100%);
    color: #ffffff;
    border: none;
    padding: 10px 22px;
    border-radius: 6px;
    font-weight: 850;
    font-size: 0.78rem;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(225, 6, 0, 0.4);
    transition: all 0.2s ease;
}

.btn-vote-login:hover {
    background: linear-gradient(135deg, #ff0800 0%, #d10500 100%);
    box-shadow: 0 6px 20px rgba(225, 6, 0, 0.6);
    transform: translateY(-2px);
}

@media (max-width: 900px) {
    .poll-card {
        grid-template-columns: 1fr;
    }
    .poll-poster {
        min-height: 220px;
    }
}
</style>

<div class="polls-page">
    <!-- HERO SECTION WITH F1 BACKGROUND IMAGE -->
    <header class="polls-hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-title-wrap">
                    <span class="hero-slash" aria-hidden="true"></span>
                    <h1>OFFICIAL <span>FAN POLLS</span></h1>
                </div>
                <p class="hero-subtitle">Have your say on hot Formula 1 topics and earn Fan Points for voting.</p>
            </div>
            <div class="hero-badge-quote">
                "Your Opinion <br>Drives The Community"
            </div>
        </div>
    </header>

    <div class="polls-container">
        <?php foreach ($polls as $pollIndex => $poll): ?>
            <?php
                // Fetch poll options
                $oStmt = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id = ? ORDER BY votes DESC");
                $oStmt->execute([$poll['id']]);
                $options = $oStmt->fetchAll();

                $totalVotes = array_sum(array_column($options, 'votes'));
                
                // Map resolved images to options
                foreach ($options as &$opt) {
                    $opt['resolved_img'] = resolveOptionImage($opt, $pdo);
                }
                unset($opt);

                // Poster Image Logic (Top voted option image or fallback)
                $posterImg = '';
                if (!empty($options[0]['resolved_img'])) {
                    $posterImg = $options[0]['resolved_img'];
                } else {
                    $posterImg = 'assets/images/teams/redbull_car.jpg';
                }
            ?>

            <div class="poll-card">
                <!-- LEFT FEATURED POSTER -->
                <div class="poll-poster">
                    <img src="<?php echo e($posterImg); ?>" alt="Leading Option Showcase" class="poster-bg-img" onerror="this.src='assets/images/teams/ferrari_car.jpg';">
                    <div class="poster-overlay"></div>
                    
                    <div class="poster-badge">
                        <span class="poster-badge-dot"></span> ACTIVE POLL
                    </div>

                    <div class="poster-footer-logo">
                        <span class="poster-f1-icon">🏎</span> FAN POLL
                    </div>
                </div>

                <!-- RIGHT POLL MAIN CONTENT -->
                <div class="poll-main-content">
                    <div class="poll-question-header">
                        <span class="poll-icon">📊</span>
                        <h2 class="poll-question-title"><?php echo e($poll['question']); ?></h2>
                    </div>

                    <form method="POST" action="polls.php">
                        <div class="poll-options-list">
                            <?php foreach ($options as $opt): ?>
                                <?php
                                    $pct = $totalVotes > 0 ? round(($opt['votes'] / $totalVotes) * 100) : 0;
                                    $optAvatar = $opt['resolved_img'];
                                ?>
                                <div class="poll-option-row">
                                    <!-- Dynamic Red Progress Fill Bar -->
                                    <div class="option-progress-bar" style="width: <?php echo $pct; ?>%;"></div>

                                    <div class="option-left-content">
                                        <?php if (is_logged_in()): ?>
                                            <input type="radio" name="option_id" id="opt-<?php echo $opt['id']; ?>" value="<?php echo $opt['id']; ?>" class="option-radio-input" required>
                                        <?php endif; ?>

                                        <?php if (!empty($optAvatar)): ?>
                                            <img src="<?php echo e($optAvatar); ?>" class="option-avatar-img" alt="<?php echo e($opt['option_text']); ?>" onerror="this.style.display='none';">
                                        <?php endif; ?>

                                        <label for="opt-<?php echo $opt['id']; ?>" class="option-label-text">
                                            <?php echo e($opt['option_text']); ?>
                                        </label>
                                    </div>

                                    <div class="option-right-stats">
                                        <?php echo $pct; ?>%
                                        <span class="option-votes-count">(<?php echo $opt['votes']; ?> votes)</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="poll-footer">
                            <div class="total-votes-tag">
                                🕒 Total Votes: <strong><?php echo $totalVotes; ?></strong>
                            </div>

                            <?php if (is_logged_in()): ?>
                                <button type="submit" name="vote" class="btn-vote-submit">SUBMIT VOTE →</button>
                            <?php else: ?>
                                <a href="login.php" class="btn-vote-login">LOG IN TO VOTE →</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>