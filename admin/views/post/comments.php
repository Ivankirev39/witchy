<section class="admin-content-panel">

    <header class="admin-content-panel-header">
        <h3>Comments (<?= count($comments) ?>)</h3>
        <p>Review and moderate comments left on this post.</p>
    </header>

    <?php if (empty($comments)): ?>

        <div class="admin-content-empty">
            This post has no comments.
        </div>

    <?php else: ?>

        <?php foreach ($comments as $comment): ?>

            <article class="admin-post-comment">

                <p>
                    <strong>
                        <?= htmlspecialchars($comment["username"] ?? "Unknown", ENT_QUOTES, "UTF-8") ?>
                    </strong>

                    ·

                    <?= htmlspecialchars($comment["created_at"] ?? "", ENT_QUOTES, "UTF-8") ?>
                </p>

                <p>
                    <?= nl2br(htmlspecialchars($comment["comment"] ?? "", ENT_QUOTES, "UTF-8")) ?>
                </p>

                <?php if (!empty($comment["media_file"])): ?>
                    <p>
                        <strong>Attached media:</strong>

                        <?= htmlspecialchars(
                            basename($comment["media_file"]),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                        (<?= htmlspecialchars(
                            $comment["media_type"] ?? "unknown",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>)
                    </p>
                <?php endif; ?>

                <div class="admin-comment-actions">

                    <form
                        method="POST"
                        action="<?= htmlspecialchars(BASE_URL . "admin/delete_comment.php", ENT_QUOTES, "UTF-8") ?>"
                        onsubmit="return confirm('Are you sure you want to permanently delete this comment?');"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                        >

                        <input
                            type="hidden"
                            name="comment_id"
                            value="<?= (int) $comment["comment_id"] ?>"
                        >

                        <input
                            type="hidden"
                            name="return_post_id"
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
                            Delete comment
                        </button>

                    </form>

                </div>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</section>
