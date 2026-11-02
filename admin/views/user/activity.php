<section class="admin-content-panel admin-user-activity-panel">

<header class="admin-content-panel-header">
    <h3>Activity overview</h3>
    <p>A summary of this member's activity in the community.</p>
</header>

<div class="admin-user-activity-grid">

    <div class="admin-user-activity-card">
        <span class="admin-user-activity-number">
            <?= $postCount ?>
        </span>
        <span class="admin-user-activity-label">Total posts</span>
    </div>

    <div class="admin-user-activity-card">
        <span class="admin-user-activity-number">
            <?= $commentCount ?>
        </span>
        <span class="admin-user-activity-label">Total comments</span>
    </div>

    <div class="admin-user-activity-card">
        <span class="admin-user-activity-number">
            <?= $followerCount ?>
        </span>
        <span class="admin-user-activity-label">Followers</span>
    </div>

    <div class="admin-user-activity-card">
        <span class="admin-user-activity-number">
            <?= $followingCount ?>
        </span>
        <span class="admin-user-activity-label">Following</span>
    </div>

</div>

</section>