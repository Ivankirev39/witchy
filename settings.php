<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";

$pageTitle = "Account Settings | Witchy";
$pageCss = "settings.css";

$error = $_SESSION["delete_error"] ?? "";
unset($_SESSION["delete_error"]);

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="profile-page">

    <div class="profile-section-heading">
        <h1>Account Settings</h1>
    </div>

    <section class="profile-info-grid">

        <article class="profile-panel">
            <h2>Your Profile</h2>
            <p>
                Manage your profile information, picture,
                cover image and bio.
            </p>

            <p>
                <a href="edit_profile.php" class="profile-edit-button">
                    Edit Profile
                </a>
            </p>
        </article>

        <article class="profile-panel">
            <h2>Delete Account</h2>

            <p>
                Permanently deleting your account will remove
                your profile, posts, comments, likes, saves,
                follows and other associated community data.
            </p>

            <p>
                This action cannot be undone.
            </p>

            <?php if ($error !== ""): ?>
                <div class="edit-profile-message error" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form action="delete_account.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                >

                <div class="edit-profile-field">
                    <label for="password">
                        Confirm your password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="edit-profile-field">
                    <label>
                        <input
                            type="checkbox"
                            name="confirm_delete"
                            value="1"
                            required
                        >
                        I understand that my account and
                        associated content will be permanently deleted.
                    </label>
                </div>

                <div class="edit-profile-actions">
                    <a href="profile.php" class="edit-profile-cancel">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="edit-profile-save"
                        style="background-color: #a53549; color: white;"
                    >
                        Permanently Delete Account
                    </button>
                </div>

            </form>
        </article>

    </section>

</section>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
