
<section class="admin-content-panel admin-user-profile-card">

<div class="admin-user-cover">
    <?php if (!empty($user["cover_image"])): ?>
        <img
            src="<?= BASE_URL ?>uploads/profiles/<?= rawurlencode(basename($user["cover_image"])) ?>"
            alt="User cover image"
        >
    <?php else: ?>
        <div class="admin-user-cover-placeholder"></div>
    <?php endif; ?>
</div>

<div class="admin-user-profile-body">

    <div class="admin-user-identity">

        <div class="admin-user-avatar">
            <?php if (!empty($user["profile_image"])): ?>
                <img
                    src="<?= BASE_URL ?>uploads/profiles/<?= rawurlencode(basename($user["profile_image"])) ?>"
                    alt="Profile picture of <?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>"
                >
            <?php else: ?>
                <span>
                    <?= htmlspecialchars(strtoupper(substr($user["username"], 0, 1)), ENT_QUOTES, "UTF-8") ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="admin-user-heading">
            <h3>
                <?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>
            </h3>

            <span class="admin-user-role <?= $user["rank"] === "admin" ? "admin-user-role-admin" : "" ?>">
                <?= htmlspecialchars(ucfirst($user["rank"]), ENT_QUOTES, "UTF-8") ?>
            </span>

            <span class="admin-user-id">
                User ID: <?= (int) $user["user_id"] ?>
            </span>
        </div>

    </div>

    <div class="admin-user-details">

        <div class="admin-user-detail">
            <span class="admin-user-detail-label">Email address</span>
            <span class="admin-user-detail-value">
                <?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?>
            </span>
        </div>

        <div class="admin-user-detail">
            <span class="admin-user-detail-label">Birthdate</span>
            <span class="admin-user-detail-value">
                <?= htmlspecialchars($user["birthdate"] ?: "Not provided", ENT_QUOTES, "UTF-8") ?>
            </span>
        </div>

        <div class="admin-user-detail admin-user-bio">
            <span class="admin-user-detail-label">About this user</span>
            <div class="admin-user-detail-value">
                <?= nl2br(htmlspecialchars($user["bio"] ?: "This user has not added a bio.", ENT_QUOTES, "UTF-8")) ?>
            </div>
        </div>

    </div>

</div>
</section>
