<section class="admin-content-panel admin-user-status-panel">

<header class="admin-content-panel-header">
    <h3>Account status</h3>
    <p>Current moderation status for this account.</p>
</header>

<div class="admin-user-status-content">

    <?php if ($activeBan): ?>

        <div class="admin-user-status admin-user-status-banned">
            <span class="admin-user-status-indicator"></span>
            <strong>Currently banned</strong>
        </div>

        <div class="admin-user-ban-details">

            <p>
                <strong>Reason:</strong>
                <?= htmlspecialchars($activeBan["reason"], ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Banned at:</strong>
                <?= htmlspecialchars($activeBan["banned_at"], ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Expires:</strong>
                <?= htmlspecialchars($activeBan["expires_at"] ?? "No expiry (permanent)", ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Issued by:</strong>
                <?= htmlspecialchars($activeBan["banned_by_username"] ?? "Unknown / account deleted", ENT_QUOTES, "UTF-8") ?>
            </p>

        </div>

        <div class="admin-ban-create">

            <h4>Revoke this ban</h4>
            <p>Provide a reason for lifting the ban. The original ban record will be preserved.</p>

            <form
                method="POST"
                action="<?= BASE_URL ?>admin/view_user.php?id=<?= (int) $userId ?>"
                onsubmit="return confirm('Are you sure you want to revoke this ban?');"
                class="admin-ban-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                >

                <input type="hidden" name="action" value="unban">
                <input type="hidden" name="ban_id" value="<?= (int) $activeBan["ban_id"] ?>">

                <div class="admin-ban-field">
                    <label for="revocation-reason">Reason for revoking ban</label>
                    <textarea
                        id="revocation-reason"
                        name="revocation_reason"
                        rows="3"
                        maxlength="1000"
                        required
                        placeholder="Explain why this ban is being lifted..."
                    ></textarea>
                </div>

                <button type="submit" class="admin-button-secondary">
                    Revoke ban
                </button>

            </form>

        </div>

    <?php else: ?>

        <div class="admin-user-status admin-user-status-active">
            <span class="admin-user-status-indicator"></span>
            <strong>Active account</strong>
        </div>

        <p class="admin-user-status-note">
            No active ban found for this account.
        </p>

        <?php if ($user["rank"] !== "admin" && (int) $user["user_id"] !== (int) $_SESSION["user_id"]): ?>

            <div class="admin-ban-create">

                <h4>Ban this user</h4>
                <p>Provide a reason and choose whether the ban should be temporary or permanent.</p>

                <form
                    method="POST"
                    action="<?= BASE_URL ?>admin/view_user.php?id=<?= (int) $userId ?>"
                    class="admin-ban-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                    >

                    <input type="hidden" name="action" value="ban">

                    <div class="admin-ban-field">
                        <label for="ban-reason">Reason for ban</label>
                        <textarea
                            id="ban-reason"
                            name="reason"
                            rows="4"
                            maxlength="1000"
                            required
                            placeholder="Explain why this account is being banned..."
                        ></textarea>
                    </div>

                    <div class="admin-ban-field">
                        <label for="ban-expiry">Ban expiry (optional)</label>
                        <input
                            type="datetime-local"
                            id="ban-expiry"
                            name="expires_at"
                        >
                        <small>Leave empty for a permanent ban.</small>
                    </div>

                    <button
                        type="submit"
                        class="admin-ban-button"
                        onclick="return confirm('Are you sure you want to ban this user?');"
                    >
                        Ban user
                    </button>

                </form>

            </div>

        <?php else: ?>

            <p class="admin-user-status-note">
                Admin accounts cannot be banned through this page.
            </p>

        <?php endif; ?>

    <?php endif; ?>

</div>
</section>
