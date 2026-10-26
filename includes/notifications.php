<?php
function createNotification(PDO $conn, int $recipientId, int $actorId, string $type, ?int $postId = null): void
{
    if ($recipientId === $actorId || !in_array($type, ["like", "comment", "follow"], true)) {
        return;
    }
    $stmt = $conn->prepare("
        INSERT INTO notification (user_id, actor_id, post_id, type)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$recipientId, $actorId, $postId, $type]);
}