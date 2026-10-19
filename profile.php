<?php
// profile.php — User Profile Management
$pageTitle = "My Profile | FormulaHub";
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/functions.php';

$user = get_logged_in_user();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $newPass  = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Profile Image Upload Handling
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'user_' . $user['id'] . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/assets/images/avatar/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $upImg = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $upImg->execute([$newFileName, $user['id']]);
                $success = "Profile picture updated successfully!";
            } else {
                $error = "There was an error moving the uploaded file.";
            }
        } else {
            $error = "Upload failed. Allowed formats: JPG, JPEG, PNG, WEBP.";
        }
    }

    // Name Update
    if (!empty($fullName)) {
        $update = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
        $update->execute([$fullName, $user['id']]);
        if (empty($success)) $success = "Personal details updated successfully.";
    }

    // Password Update
    if (!empty($newPass)) {
        if ($newPass !== $confirm) {
            $error = "New passwords do not match.";
        } elseif (strlen($newPass) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $upPass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upPass->execute([$hashed, $user['id']]);
            $success = "Password updated successfully.";
        }
    }

    // Refresh user object
    $user = get_logged_in_user();
}

$badge = get_user_badge($user['points']);
require_once __DIR__ . '/includes/header.php';

// Profile Avatar Logic
$userAvatar = !empty($user['profile_image']) 
    ? 'assets/images/avatar/' . e($user['profile_image']) 
    : 'assets/images/drivers/verstappen.png';
?>

<style>
:root {
    --f1-red: #e10600;
    --f1-red-glow: rgba(225, 6, 0, 0.45);
    --f1-dark-bg: #03060a;
    --f1-card-bg: rgba(8, 12, 19, 0.92);
    --f1-border: rgba(255, 255, 255, 0.08);
    --f1-input-bg: #05080e;
}

body {
    background-color: #020407;
    color: #ffffff;
    margin: 0;
    padding: 0;
}

/* FIX: SEAMLESS WRAPPER THAT CONNECTS DIRECTLY TO FOOTER */
.profile-page-wrapper {
    position: relative;
    min-height: calc(100vh - 80px);
    padding: 50px 20px 60px;
    margin-bottom: 0 !important;
    background: linear-gradient(180deg, rgba(3, 6, 10, 0.4) 0%, rgba(2, 4, 7, 0.95) 100%),
                radial-gradient(circle at 50% 20%, rgba(225,6,0,0.12), transparent 70%),
                url('assets/images/f1-hero-bg.png') center top / cover no-repeat fixed;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

/* PREMIUM CENTERED CARD WITH SHADOW & TOP GLOW */
.profile-card-container {
    position: relative;
    z-index: 5;
    width: 100%;
    max-width: 680px;
    background: var(--f1-card-bg);
    border: 1px solid rgba(225, 6, 0, 0.35);
    box-shadow: 
        0 30px 90px rgba(0, 0, 0, 0.85),
        0 0 45px rgba(225, 6, 0, 0.22),
        inset 0 1px 0 rgba(255, 255, 255, 0.15),
        inset 0 -1px 0 rgba(225, 6, 0, 0.3);
    border-radius: 22px;
    padding: 42px 48px;
    backdrop-filter: blur(18px);
}

/* Top Glow Line Effect */
.profile-card-container::before {
    content: '';
    position: absolute;
    top: 0; left: 15%; right: 15%;
    height: 3px;
    background: linear-gradient(90deg, transparent, #ff1a1a, transparent);
    box-shadow: 0 0 15px #ff1a1a, 0 0 25px #ff1a1a;
    border-radius: 3px;
}

/* USER HEADER SECTION */
.user-profile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 25px;
    border-bottom: 1px solid var(--f1-border);
    margin-bottom: 30px;
}

.user-left-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.avatar-upload-wrapper {
    position: relative;
    width: 80px;
    height: 80px;
}

.user-avatar-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--f1-red);
    box-shadow: 0 0 20px rgba(225, 6, 0, 0.4);
    background: #101520;
}

.avatar-edit-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.25s ease;
    cursor: pointer;
}

.avatar-upload-wrapper:hover .avatar-edit-overlay {
    opacity: 1;
}

.badge-pill {
    display: inline-block;
    background: rgba(225, 6, 0, 0.15);
    border: 1px solid var(--f1-red);
    color: var(--f1-red);
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 1px;
    padding: 3px 10px;
    border-radius: 12px;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.user-display-name {
    font-family: var(--font-heading, sans-serif);
    font-size: 1.7rem;
    font-weight: 900;
    text-transform: uppercase;
    margin: 0 0 4px;
    line-height: 1.1;
    letter-spacing: 0.5px;
}

.user-meta-sub {
    color: #8b9bb4;
    font-size: 0.82rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* RIGHT BADGE BOX */
.user-paddock-box {
    background: rgba(225, 6, 0, 0.08);
    border: 1px solid rgba(225, 6, 0, 0.3);
    border-radius: 12px;
    padding: 12px 18px;
    text-align: center;
}

.paddock-box-title {
    color: var(--f1-red);
    font-size: 0.7rem;
    font-weight: 900;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.paddock-box-sub {
    color: #8b9bb4;
    font-size: 0.72rem;
    margin-top: 2px;
}

/* FORM SECTIONS */
.form-section-title {
    font-family: var(--font-heading, sans-serif);
    font-size: 1rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 28px 0 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section-title span.red-icon {
    color: var(--f1-red);
}

.form-section-subtitle {
    color: #8b9bb4;
    font-size: 0.8rem;
    margin-bottom: 20px;
}

.f1-field-group {
    margin-bottom: 20px;
}

.f1-field-label {
    display: block;
    font-size: 0.7rem;
    font-weight: 800;
    color: #8b9bb4;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.f1-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.f1-input-icon {
    position: absolute;
    left: 16px;
    color: #58667e;
    display: flex;
    align-items: center;
    pointer-events: none;
    font-size: 0.9rem;
}

.f1-control {
    width: 100%;
    background: var(--f1-input-bg);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 13px 42px 13px 46px;
    color: #ffffff;
    font-size: 0.9rem;
    font-weight: 600;
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.f1-control:focus {
    outline: none;
    border-color: var(--f1-red);
    box-shadow: 0 0 14px rgba(225, 6, 0, 0.25);
}

.f1-control:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    background: rgba(5, 8, 14, 0.8);
    padding-right: 16px;
}

/* FUNCTIONAL EYE TOGGLE ICON */
.f1-input-eye {
    position: absolute;
    right: 16px;
    color: #8b9bb4;
    cursor: pointer;
    font-size: 1.1rem;
    user-select: none;
    transition: color 0.2s ease;
}

.f1-input-eye:hover {
    color: #ffffff;
}

.btn-save-profile {
    width: 100%;
    background: linear-gradient(90deg, #e10600 0%, #b30500 100%);
    color: #ffffff;
    border: none;
    padding: 14px 25px;
    border-radius: 8px;
    font-family: var(--font-heading, sans-serif);
    font-size: 0.88rem;
    font-weight: 900;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    margin-top: 32px;
    box-shadow: 0 4px 20px rgba(225, 6, 0, 0.35);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.btn-save-profile:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(225, 6, 0, 0.5);
}

@media (max-width: 768px) {
    .user-profile-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    .profile-card-container { padding: 30px 20px; }
}
</style>

<div class="profile-page-wrapper">

    <!-- MAIN CENTERED PROFILE CARD -->
    <div class="profile-card-container">

        <!-- ALERTS -->
        <?php if (!empty($error)): ?>
            <div style="background:rgba(225,6,0,0.15); border:1px solid var(--f1-red); color:#fff; padding:12px 16px; border-radius:8px; font-size:0.85rem; margin-bottom:25px;">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="background:rgba(34,197,94,0.15); border:1px solid #22c55e; color:#22c55e; padding:12px 16px; border-radius:8px; font-size:0.85rem; margin-bottom:25px;">
                <?php echo e($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="profile.php" enctype="multipart/form-data">

            <!-- USER HEADER SECTION -->
            <div class="user-profile-header">
                <div class="user-left-info">
                    <div class="avatar-upload-wrapper">
                        <img src="<?php echo $userAvatar; ?>" class="user-avatar-img" alt="Profile Picture" id="avatarPreview" onerror="this.src='https://via.placeholder.com/80/101520/FFFFFF?text=F1'">
                        <label for="profile_image_input" class="avatar-edit-overlay" title="Click to upload profile picture">
                            <svg width="20" height="20" fill="none" stroke="#ffffff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><circle cx="12" cy="13" r="3" stroke-width="2"></circle></svg>
                        </label>
                        <input type="file" name="profile_image" id="profile_image_input" accept="image/*" style="display:none;" onchange="previewImage(this);">
                    </div>
                    <div>
                        <span class="badge-pill">👑 <?php echo e($badge['label']); ?></span>
                        <h2 class="user-display-name"><?php echo e($user['full_name']); ?></h2>
                        <div class="user-meta-sub">
                            <span>✉ <?php echo e($user['email']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="user-paddock-box">
                    <div class="paddock-box-title">👑 PADDOCK LEGEND</div>
                    <div class="paddock-box-sub">Top 1% Fan</div>
                </div>
            </div>

            <!-- UPDATE PERSONAL DETAILS -->
            <div class="form-section-title">
                <span class="red-icon">👤</span> UPDATE PERSONAL DETAILS
            </div>
            <div class="form-section-subtitle">
                Keep your information up to date to get the best experience on FormulaHub.
            </div>

            <div class="f1-field-group">
                <label class="f1-field-label">FULL NAME</label>
                <div class="f1-input-wrapper">
                    <span class="f1-input-icon">👤</span>
                    <input type="text" name="full_name" value="<?php echo e($user['full_name']); ?>" class="f1-control" required>
                </div>
            </div>

            <div class="f1-field-group">
                <label class="f1-field-label">USERNAME (IMMUTABLE)</label>
                <div class="f1-input-wrapper">
                    <span class="f1-input-icon">@</span>
                    <input type="text" value="<?php echo e($user['username']); ?>" class="f1-control" disabled>
                </div>
            </div>

            <div class="f1-field-group">
                <label class="f1-field-label">EMAIL ADDRESS (IMMUTABLE)</label>
                <div class="f1-input-wrapper">
                    <span class="f1-input-icon">✉</span>
                    <input type="email" value="<?php echo e($user['email']); ?>" class="f1-control" disabled>
                </div>
            </div>

            <!-- SECURITY & PASSWORD -->
            <div class="form-section-title" style="margin-top:35px;">
                <span class="red-icon">🛡</span> SECURITY & PASSWORD
            </div>
            <div class="form-section-subtitle">
                Keep your account safe and secure.
            </div>

            <div class="f1-field-group">
                <label class="f1-field-label">NEW PASSWORD (LEAVE BLANK TO KEEP CURRENT)</label>
                <div class="f1-input-wrapper">
                    <span class="f1-input-icon">🔒</span>
                    <input type="password" name="new_password" id="new_password" class="f1-control" placeholder="••••••••">
                    <span class="f1-input-eye" id="toggleNewPass" onclick="togglePassword('new_password', this)">👁️</span>
                </div>
            </div>

            <div class="f1-field-group">
                <label class="f1-field-label">CONFIRM NEW PASSWORD</label>
                <div class="f1-input-wrapper">
                    <span class="f1-input-icon">🔒</span>
                    <input type="password" name="confirm_password" id="confirm_password" class="f1-control" placeholder="••••••••">
                    <span class="f1-input-eye" id="toggleConfirmPass" onclick="togglePassword('confirm_password', this)">👁️</span>
                </div>
            </div>

            <button type="submit" class="btn-save-profile">
                💾 SAVE PROFILE CHANGES <span>→</span>
            </button>

        </form>

    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function togglePassword(fieldId, iconElem) {
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>