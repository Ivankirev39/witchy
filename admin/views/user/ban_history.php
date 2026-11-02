<section class="admin-content-panel">

<header class="admin-content-panel-header">
    <h3>Moderation history</h3>
    <p>Previous and current bans recorded for this account.</p>
</header>

<?php if (empty($banHistory)): ?>

    <p class="admin-content-empty">
        No previous moderation actions found for this user.
    </p>

<?php else: ?>

    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ban date</th>
                    <th>Reason</th>
                    <th>Issued by</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Revoked by</th>
                    <th>Revocation reason</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($banHistory as $ban): ?>

                    <?php
                    if ($ban["revoked_at"] !== null) {
                        $historyStatus = "Revoked";
                    } elseif (
                        $ban["expires_at"] !== null
                        && strtotime($ban["expires_at"]) <= time()
                    ) {
                        $historyStatus = "Expired";
                    } else {
                        $historyStatus = "Active";
                    }
                    ?>

                    <tr>
                        <td>
                            <?= htmlspecialchars($ban["banned_at"], ENT_QUOTES, "UTF-8") ?>
                        </td>

                        <td>
                            <?= nl2br(htmlspecialchars($ban["reason"], ENT_QUOTES, "UTF-8")) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($ban["banned_by_username"] ?? "Unknown / account deleted", ENT_QUOTES, "UTF-8") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($ban["expires_at"] ?? "Permanent", ENT_QUOTES, "UTF-8") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($historyStatus, ENT_QUOTES, "UTF-8") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($ban["revoked_by_username"] ?? ($ban["revoked_by"] !== null ? "Unknown / account deleted" : "—"), ENT_QUOTES, "UTF-8") ?>
                        </td>

                        <td>
                            <?= $ban["revocation_reason"] !== null
                                ? nl2br(htmlspecialchars($ban["revocation_reason"], ENT_QUOTES, "UTF-8"))
                                : "—"
                            ?>
                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

</section>