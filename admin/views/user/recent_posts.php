<section class="admin-content-panel">

<header class="admin-content-panel-header">
    <h3>Recent posts</h3>
    <p>Latest posts created by this user.</p>
</header>

<?php if (empty($posts)): ?>

    <p class="admin-content-empty">This user has not created any posts.</p>

<?php else: ?>

    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Topic</th>
                    <th>Created</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr
                        class="admin-clickable-row"
                        tabindex="0"
                        role="link"
                        data-href="view_post.php?id=<?= (int) $post["post_id"] ?>&from=user&return_user_id=<?= (int) $user["user_id"] ?>"
                        aria-label="Review post <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                    >
                        <td><?= (int) $post["post_id"] ?></td>
                        <td><?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($post["topic"] ?? "", ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($post["created_at"], ENT_QUOTES, "UTF-8") ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>
