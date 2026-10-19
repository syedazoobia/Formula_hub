<?php
// register.php — Fan Registration Page
$pageTitle = "Register";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check duplicate email / username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = "Username or Email already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role, points) VALUES (?, ?, ?, ?, 'user', 100)");
            if ($insert->execute([$fullName, $username, $email, $hashed])) {
                $newUserId = $pdo->lastInsertId();
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_role'] = 'user';
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Welcome to FormulaHub! You received 100 bonus fan points!'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="register-page-section">
  <div class="container register-container">
    <div class="register-card">
      
      <!-- Top Right Racing Stripes Watermark -->
      <div class="register-card-stripes">
        <span></span><span></span><span></span>
      </div>

      <div class="register-card-header">
        <div class="register-title-row">
          <span class="register-red-bar"></span>
          <span class="register-tag">F1 —</span>
        </div>
        <h2>JOIN FORMULAHUB</h2>
        <p>Create your account to compete in race predictions & leaderboards</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="register-error-alert">
          <?php echo e($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="register.php" class="register-form">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <div class="input-icon-wrapper">
            <span class="input-icon">👤</span>
            <input type="text" name="full_name" class="form-control" required placeholder="e.g. Alex Verstappen">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Username</label>
          <div class="input-icon-wrapper">
            <span class="input-icon">@</span>
            <input type="text" name="username" class="form-control" required placeholder="Choose a unique handle">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-icon-wrapper">
            <span class="input-icon">✉</span>
            <input type="email" name="email" class="form-control" required placeholder="you@example.com">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-icon-wrapper">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" id="reg_password" class="form-control" required placeholder="••••••••">
            <span class="password-eye" onclick="toggleRegPassword('reg_password', this)">👁️</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <div class="input-icon-wrapper">
            <span class="input-icon">🔒</span>
            <input type="password" name="confirm_password" id="reg_confirm" class="form-control" required placeholder="••••••••">
            <span class="password-eye" onclick="toggleRegPassword('reg_confirm', this)">👁️</span>
          </div>
        </div>

        <button type="submit" class="btn-primary register-submit-btn">
          <span>CREATE ACCOUNT</span>
          <span class="btn-arrow">→</span>
        </button>
      </form>

      <div class="register-footer-text">
        Already have an account? <a href="login.php">Login here</a>
      </div>
    </div>
  </div>
</section>

<style>
/* =========================================================
   REGISTER PAGE STYLING (MATCHING LOGIN CARD DESIGN)
========================================================= */
body {
    overflow-x: hidden;
    background: #03060a;
}

.register-page-section {
    position: relative;
    width: 100%;
    min-height: calc(100vh - 70px);
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(180deg, rgba(3, 6, 10, 0.2) 0%, rgba(3, 6, 10, 0.7) 100%),
                radial-gradient(circle at 50% 40%, rgba(225, 6, 0, 0.12), transparent 65%),
                url('assets/images/login-bg.png') center center / cover no-repeat;
    padding: 30px 20px;
    box-sizing: border-box;
}

.register-container {
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
}

.register-card {
    position: relative;
    background: rgba(8, 12, 19, 0.92);
    border: 1px solid rgba(225, 6, 0, 0.38);
    border-radius: 16px;
    padding: 40px 42px;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.85),
                0 0 40px rgba(225, 6, 0, 0.22),
                inset 0 1px 0 rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(18px);
    overflow: hidden;
}

/* Top Glowing Red Line */
.register-card::before {
    content: '';
    position: absolute;
    top: 0; left: 18%; right: 18%;
    height: 3px;
    background: linear-gradient(90deg, transparent, #ff1a1a, transparent);
    box-shadow: 0 0 15px #ff1a1a;
    border-radius: 3px;
}

/* Top Right Racing Stripes */
.register-card-stripes {
    position: absolute;
    top: 25px;
    right: 25px;
    display: flex;
    gap: 5px;
    transform: skewX(-25deg);
    pointer-events: none;
}

.register-card-stripes span {
    display: block;
    width: 4px;
    height: 22px;
    background: #e10600;
    box-shadow: 0 0 8px rgba(225, 6, 0, 0.8);
}

.register-card-stripes span:nth-child(2) { opacity: 0.7; }
.register-card-stripes span:nth-child(3) { opacity: 0.4; }

.register-card-header {
    margin-bottom: 25px;
}

.register-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.register-red-bar {
    width: 4px;
    height: 18px;
    background: #e10600;
    display: inline-block;
    border-radius: 2px;
}

.register-tag {
    color: #e10600;
    font-size: 0.75rem;
    font-weight: 900;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.register-card-header h2 {
    font-family: var(--font-heading, sans-serif);
    font-size: 1.9rem;
    font-weight: 900;
    letter-spacing: 1px;
    color: #ffffff;
    margin: 0 0 6px 0;
}

.register-card-header p {
    color: #8b9bb4;
    font-size: 0.82rem;
    margin: 0;
}

.register-error-alert {
    background: rgba(225, 6, 0, 0.15);
    border: 1px solid #e10600;
    color: #fff;
    padding: 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 20px;
    text-align: center;
}

.register-form .form-group {
    margin-bottom: 16px;
}

.register-form .form-label {
    display: block;
    font-size: 0.7rem;
    font-weight: 800;
    color: #8b9bb4;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 6px;
}

.input-icon-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 14px;
    color: #58667e;
    font-size: 0.88rem;
    pointer-events: none;
}

.register-form .form-control {
    width: 100%;
    background: #05080e;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 11px 40px 11px 42px;
    color: #ffffff;
    font-size: 0.88rem;
    transition: all 0.25s ease;
    box-sizing: border-box;
}

.register-form .form-control:focus {
    outline: none;
    border-color: #e10600;
    box-shadow: 0 0 14px rgba(225, 6, 0, 0.25);
}

.password-eye {
    position: absolute;
    right: 14px;
    color: #8b9bb4;
    cursor: pointer;
    font-size: 1rem;
    user-select: none;
}

.password-eye:hover {
    color: #fff;
}

.register-submit-btn {
    width: 100%;
    background: linear-gradient(90deg, #e10600 0%, #b30500 100%) !important;
    border: none;
    padding: 13px;
    border-radius: 8px;
    color: #fff;
    font-weight: 900;
    letter-spacing: 1px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    margin-top: 10px;
    box-shadow: 0 4px 20px rgba(225, 6, 0, 0.35);
    transition: all 0.2s ease;
}

.register-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(225, 6, 0, 0.5);
}

.register-footer-text {
    margin-top: 20px;
    text-align: center;
    font-size: 0.82rem;
    color: #8b9bb4;
}

.register-footer-text a {
    color: #e10600;
    font-weight: 700;
    text-decoration: none;
}

.register-footer-text a:hover {
    text-decoration: underline;
}

@media (max-width: 500px) {
    .register-card {
        padding: 28px 20px;
    }
}
</style>

<script>
function toggleRegPassword(fieldId, iconElem) {
    const passwordField = document.getElementById(fieldId);
    if (passwordField.type === "password") {
        passwordField.type = "text";
        iconElem.textContent = "🙈";
    } else {
        passwordField.type = "password";
        iconElem.textContent = "👁️";
    }
}
</script>

<script src="assets/js/main.js"></script>
<?php render_toast(); ?>
</body>
</html>