<section class="admin-content-panel">

    <header class="admin-content-panel-header">
        <h3>Post engagement</h3>
        <p>Engagement statistics for this post.</p>
    </header>

    <div class="admin-post-engagement-grid">

        <article class="admin-post-engagement-card">
            <span>♡</span>
            <strong><?= number_format($engagement["likes"]) ?></strong>
            <small>Total likes</small>
        </article>

        <article class="admin-post-engagement-card">
            <span>☽</span>
            <strong><?= number_format($engagement["comments"]) ?></strong>
            <small>Total comments</small>
        </article>

        <article class="admin-post-engagement-card">
            <span>♧</span>
            <strong><?= number_format($engagement["saves"]) ?></strong>
            <small>Total saves</small>
        </article>

        <article class="admin-post-engagement-card">
            <span>✧</span>
            <strong><?= number_format($engagement["popularity_score"]) ?></strong>
            <small>Popularity score</small>
        </article>

    </div>

    <h4>Recent engagement · Last 48 hours</h4>

    <div class="admin-post-recent-engagement">

        <div>
            <strong><?= number_format($engagement["recent_likes"]) ?></strong>
            <span>Recent likes</span>
        </div>

        <div>
            <strong><?= number_format($engagement["recent_comments"]) ?></strong>
            <span>Recent comments</span>
        </div>

    </div>

    <p class="admin-post-score-note">
        Popularity score = total likes + (total comments × 3).
        Trending eligibility requires at least one like or comment
        in the last 48 hours.
    </p>

</section>