<section class="admin-content-panel">

<header class="admin-content-panel-header">
    <h3>Recent comments</h3>
    <p>Latest comments written by this user.</p>
</header>

<?php if (empty($comments)): ?>

    <p class="admin-content-empty">This user has not written any comments.</p>

<?php else: ?>

    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Comment</th>
                    <th>Related post</th>
                    <th>Created</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($comments as $comment): ?>

                    <?php
                    $commentPreview = $comment["comment"];

                    if (function_exists("mb_strimwidth")) {
                        $commentPreview = mb_strimwidth(
                            $commentPreview,
                            0,
                            90,
                            "..."
                        );
                    } elseif (strlen($commentPreview) > 90) {
                        $commentPreview = substr(
                            $commentPreview,
                            0,
                            87
                        ) . "...";
                    }
                    ?>

                    <tr
                        class="admin-clickable-row"
                        tabindex="0"
                        role="link"
                        data-href="<?= htmlspecialchars(
                            BASE_URL . "admin/view_comment.php?id="
                            . (int) $comment["comment_id"]
                            . "&from=user&return_user_id="
                            . (int) $user["user_id"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        aria-label="Review comment <?= (int) $comment["comment_id"] ?>"
                    >
                        <td><?= (int) $comment["comment_id"] ?></td>

                        <td>
                            <?= htmlspecialchars($commentPreview, ENT_QUOTES, "UTF-8") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($comment["post_title"] ?? "Unavailable", ENT_QUOTES, "UTF-8") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($comment["created_at"], ENT_QUOTES, "UTF-8") ?>
                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

</section>
