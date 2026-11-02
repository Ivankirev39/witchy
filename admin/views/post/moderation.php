<aside class="admin-review-sidebar">

    <section class="admin-content-panel admin-moderation-panel">

        <h3>Moderation status</h3>

        <div class="admin-moderation-author">
            <span class="admin-moderation-label">Post author</span>

            <strong>
                <?= htmlspecialchars($post["username"] ?? "Unknown", ENT_QUOTES, "UTF-8") ?>
            </strong>

            <span>
                User ID: <?= (int) $post["user_id"] ?>
            </span>

            <?php if (!empty($post["user_id"])): ?>
                <a href="<?= BASE_URL ?>admin/view_user.php?id=<?= (int) $post["user_id"] ?>">
                    Review member
                </a>
            <?php endif; ?>
        </div>

        <div class="admin-post-status-list">

            <div>
                <span>Community pin</span>

                <strong>
                    <?= $isPinned ? "Pinned" : "Not pinned" ?>
                </strong>
            </div>

            <div>
                <span>Trending</span>

                <strong>
                    <?= $isTrending ? "Trending" : "Not trending" ?>
                </strong>
            </div>

            <div>
                <span>Recent likes</span>
                <strong><?= number_format($engagement["recent_likes"]) ?></strong>
            </div>

            <div>
                <span>Recent comments</span>
                <strong><?= number_format($engagement["recent_comments"]) ?></strong>
            </div>

        </div>

        <div class="admin-moderation-pin">
            <h4>Community feed</h4>

            <p>
                <?= $isPinned
                    ? "This post is currently pinned at the top of the community feed."
                    : "Pin this post to give it priority in the community feed." ?>
            </p>

            <form
                method="POST"
                action="<?= BASE_URL ?>admin/view_post.php?id=<?= (int) $post["post_id"] ?><?= $returnFrom === "user" && $returnUserId ? "&amp;from=user&amp;return_user_id=" . (int) $returnUserId : "" ?>"
                onsubmit="return confirm('Are you sure you want to <?= $isPinned ? "unpin" : "pin" ?> this post?');"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                >

                <input type="hidden" name="action" value="toggle_sticky">

                <button type="submit">
                    <?= $isPinned ? "Unpin post" : "Pin to community feed" ?>
                </button>

            </form>

        </div>

        <div class="admin-moderation-danger">

            <h4>Delete post</h4>

            <p>
                Permanently removes this post and its associated
                content. Uploaded files are queued for cleanup.
            </p>

            <form
                method="POST"
                action="<?= BASE_URL ?>admin/delete_post.php"
                onsubmit="return confirm('Are you sure you want to permanently delete this post and its comments? This cannot be undone.');"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                >

                <input
                    type="hidden"
                    name="post_id"
                    value="<?= (int) $post["post_id"] ?>"
                >

                <input
                    type="hidden"
                    name="return_from"
                    value="<?= htmlspecialchars($returnFrom, ENT_QUOTES, "UTF-8") ?>"
                >

                <input
                    type="hidden"
                    name="return_user_id"
                    value="<?= $returnUserId !== null ? (int) $returnUserId : "" ?>"
                >

                <button type="submit" class="admin-delete-button">
                    Delete post
                </button>

            </form>

        </div>

    </section>

</aside>
