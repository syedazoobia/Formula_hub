<?php
// login.php — User & Admin Login Page
$pageTitle = "Login";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header("Location: " . (is_admin() ? 'admin/dashboard.php' : 'dashboard.php'));
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['toast']     = ['type' => 'success', 'message' => "Welcome back, " . $user['full_name'] . "!"];

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email/username or password.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="login-page-section">
  <div class="container login-container">
    <div class="login-card">
      
      <!-- Top Right Racing Stripes Watermark -->
      <div class="login-card-stripes">
        <span></span><span></span><span></span>
      </div>

      <div class="login-card-header">
        <div class="login-title-row">
          <span class="login-red-bar"></span>
          <span class="login-tag">F1 —</span>
        </div>
        <h2>FAN LOGIN</h2>
        <p>Log in to access your FormulaHub dashboard</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="login-error-alert">
          <?php echo e($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" class="login-form">
        <div class="form-group">
          <label class="form-label">EMAIL OR USERNAME</label>
          <div class="input-icon-wrapper">
            <span class="input-icon">👤</span>
            <input type="text" name="email" class="form-control" required placeholder="Enter your username or email">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">PASSWORD</label>
          <div class="input-icon-wrapper">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" id="login_password" class="form-control" required placeholder="••••••••">
            <span class="password-eye" onclick="toggleLoginPassword('login_password', this)">👁️</span>
          </div>
        </div>

        <button type="submit" class="btn-primary login-submit-btn">
          <span>SIGN IN</span>
          <span class="btn-arrow">→</span>
        </button>
      </form>

      <div class="login-footer-text">
        Don't have an account? <a href="register.php">Register here</a>
      </div>
    </div>
  </div>
</section>

<style>
/* =========================================================
   FULL SCREEN NO-FOOTER LOGIN STYLING
========================================================= */
body {
    overflow-x: hidden;
    background: #03060a;
}

.login-page-section {
    position: relative;
    width: 100%;
    min-height: calc(100vh - 70px); /* Covers full height minus navbar */
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(180deg, rgba(3, 6, 10, 0.2) 0%, rgba(3, 6, 10, 0.7) 100%),
                radial-gradient(circle at 50% 40%, rgba(225, 6, 0, 0.12), transparent 65%),
                url('assets/images/login-bg.png') center center / cover no-repeat;
    padding: 20px;
    box-sizing: border-box;
}

.login-container {
    width: 100%;
    max-width: 460px;
    margin: 0 auto;
}

.login-card {
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
.login-card::before {
    content: '';
    position: absolute;
    top: 0; left: 18%; right: 18%;
    height: 3px;
    background: linear-gradient(90deg, transparent, #ff1a1a, transparent);
    box-shadow: 0 0 15px #ff1a1a;
    border-radius: 3px;
}

/* Top Right Racing Stripes */
.login-card-stripes {
    position: absolute;
    top: 25px;
    right: 25px;
    display: flex;
    gap: 5px;
    transform: skewX(-25deg);
    pointer-events: none;
}

.login-card-stripes span {
    display: block;
    width: 4px;
    height: 22px;
    background: #e10600;
    box-shadow: 0 0 8px rgba(225, 6, 0, 0.8);
}

.login-card-stripes span:nth-child(2) { opacity: 0.7; }
.login-card-stripes span:nth-child(3) { opacity: 0.4; }

.login-card-header {
    margin-bottom: 28px;
}

.login-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.login-red-bar {
    width: 4px;
    height: 18px;
    background: #e10600;
    display: inline-block;
    border-radius: 2px;
}

.login-tag {
    color: #e10600;
    font-size: 0.75rem;
    font-weight: 900;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.login-card-header h2 {
    font-family: var(--font-heading, sans-serif);
    font-size: 2rem;
    font-weight: 900;
    letter-spacing: 1px;
    color: #ffffff;
    margin: 0 0 6px 0;
}

.login-card-header p {
    color: #8b9bb4;
    font-size: 0.83rem;
    margin: 0;
}

.login-error-alert {
    background: rgba(225, 6, 0, 0.15);
    border: 1px solid #e10600;
    color: #fff;
    padding: 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 22px;
    text-align: center;
}

.login-form .form-group {
    margin-bottom: 20px;
}

.login-form .form-label {
    display: block;
    font-size: 0.7rem;
    font-weight: 800;
    color: #8b9bb4;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
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

.login-form .form-control {
    width: 100%;
    background: #05080e;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 12px 40px 12px 42px;
    color: #ffffff;
    font-size: 0.88rem;
    transition: all 0.25s ease;
    box-sizing: border-box;
}

.login-form .form-control:focus {
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

.login-submit-btn {
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

.login-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(225, 6, 0, 0.5);
}

.login-footer-text {
    margin-top: 22px;
    text-align: center;
    font-size: 0.82rem;
    color: #8b9bb4;
}

.login-footer-text a {
    color: #e10600;
    font-weight: 700;
    text-decoration: none;
}

.login-footer-text a:hover {
    text-decoration: underline;
}

@media (max-width: 500px) {
    .login-card {
        padding: 30px 22px;
    }
}
</style>

<script>
function toggleLoginPassword(fieldId, iconElem) {
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