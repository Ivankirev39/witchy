<aside class="admin-review-sidebar">

    <section class="admin-content-panel admin-moderation-panel">

        <h3>Moderation</h3>

        <div class="admin-moderation-author">

            <span class="admin-moderation-label">
                Comment author
            </span>

            <strong>
                <?= htmlspecialchars(
                    $comment["username"] ?? "Unknown",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </strong>

            <span>
                User ID: <?= (int) ($comment["user_id"] ?? 0) ?>
            </span>

        </div>

        <div class="admin-moderation-details">

            <p>
                <strong>Comment ID:</strong>
                <?= (int) $comment["comment_id"] ?>
            </p>

            <p>
                <strong>Post ID:</strong>
                <?= (int) ($comment["post_id"] ?? 0) ?>
            </p>

        </div>

        <div class="admin-moderation-danger">

            <h4>Delete comment</h4>

            <p>
                Permanently removes this comment.
                Any uploaded attachment will be queued for cleanup.
            </p>

            <form
                method="POST"
                action="<?= BASE_URL ?>admin/delete_comment.php"
                onsubmit="return confirm('Are you sure you want to permanently delete this comment? This cannot be undone.');"
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
                    name="return_from"
                    value="<?= htmlspecialchars($returnFrom, ENT_QUOTES, "UTF-8") ?>"
                >

                <input
                    type="hidden"
                    name="return_user_id"
                    value="<?= $returnUserId !== null ? (int) $returnUserId : "" ?>"
                >

                <button
                    type="submit"
                    class="admin-delete-button"
                >
                    Delete comment
                </button>

            </form>

        </div>

    </section>

</aside>
