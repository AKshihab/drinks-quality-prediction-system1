<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/config/db.php';
require_login();

const PROFILE_BIO_MAX_LENGTH = 1000;

$error = '';
$profile = null;
$notice = pull_flash();
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$userId = (int) $_SESSION['user_id'];

if (!$pdo instanceof PDO) {
    $error = $database_connection_error;
} elseif ($requestMethod === 'POST') {
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $bioLength = function_exists('mb_strlen')
        ? mb_strlen($bio, 'UTF-8')
        : strlen($bio);

    if (!verify_csrf()) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif ($bioLength > PROFILE_BIO_MAX_LENGTH) {
        $error = 'Bio must contain 1,000 characters or fewer.';
    } else {
        try {
            $update = $pdo->prepare(
                'UPDATE users
                 SET bio = :bio
                 WHERE user_id = :user_id'
            );
            $update->execute([
                'bio' => $bio === '' ? null : $bio,
                'user_id' => $userId,
            ]);

            flash('success', 'Your profile was updated successfully.');
            redirect('profile.php');
        } catch (PDOException $exception) {
            error_log('Profile update failed: ' . $exception->getMessage());
            $error = 'Your profile could not be updated. Check the Week 6 database migration and try again.';
        }
    }
}

if ($pdo instanceof PDO) {
    try {
        $statement = $pdo->prepare(
            'SELECT user_id, full_name, email, role, bio, created_at
             FROM users
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $profile = $statement->fetch();

        if (!$profile) {
            destroy_login_session();
            flash('error', 'Your account could not be found. Please log in again.');
            redirect('login.php');
        }
    } catch (PDOException $exception) {
        error_log('Profile query failed: ' . $exception->getMessage());
        $error = 'Your profile could not be loaded. Check the Week 6 database migration and try again.';
    }
}

render_header('My Profile', 'profile');
?>
<main class="profile-page">
    <section class="container">
        <div class="section-heading">
            <p class="tagline">Protected Account Page</p>
            <h1>My Profile</h1>
            <p>Your account is loaded using the authenticated session ID. The browser never chooses which user record is updated.</p>
        </div>

        <?php if ($notice): ?>
            <div class="page-flash"><div class="flash-message <?php echo e($notice['type']); ?>" role="status"><?php echo e($notice['message']); ?></div></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="page-flash"><div class="flash-message error" role="alert"><?php echo e($error); ?></div></div>
        <?php endif; ?>

        <?php if (is_array($profile)): ?>
            <div class="profile-grid">
                <article class="profile-card" aria-labelledby="account-details-title">
                    <p class="tagline">Database Record</p>
                    <h2 id="account-details-title">Account Details</h2>
                    <dl class="profile-details">
                        <div><dt>Full name</dt><dd><?php echo e($profile['full_name']); ?></dd></div>
                        <div><dt>Email</dt><dd><?php echo e($profile['email']); ?></dd></div>
                        <div><dt>Role</dt><dd><?php echo e($profile['role']); ?></dd></div>
                        <div><dt>Account created</dt><dd><?php echo e($profile['created_at']); ?></dd></div>
                    </dl>

                    <div class="saved-bio">
                        <h3>Saved Bio</h3>
                        <?php if ((string) ($profile['bio'] ?? '') === ''): ?>
                            <p class="empty-bio">No bio has been saved yet.</p>
                        <?php else: ?>
                            <p class="profile-bio"><?php echo e($profile['bio']); ?></p>
                        <?php endif; ?>
                    </div>
                </article>

                <section class="profile-card" aria-labelledby="edit-profile-title">
                    <p class="tagline">Parameterized Update</p>
                    <h2 id="edit-profile-title">Update Bio</h2>
                    <p>The bio is stored with a prepared PDO UPDATE query and escaped with <code>htmlspecialchars()</code> whenever it is displayed.</p>

                    <form class="profile-form" action="profile.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <div class="form-group">
                            <label for="bio">Personal Bio</label>
                            <textarea id="bio" name="bio" rows="8" maxlength="<?php echo PROFILE_BIO_MAX_LENGTH; ?>" placeholder="Tell us a little about yourself."><?php echo e($profile['bio'] ?? ''); ?></textarea>
                            <small class="form-help">Maximum 1,000 characters. HTML and script text is displayed safely, never executed.</small>
                        </div>
                        <button type="submit" class="primary-btn">Update Profile</button>
                    </form>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php render_footer(); ?>
