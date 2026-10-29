
<?php

// This script can only run from the command line.
// Visitors cannot execute it through a browser.
if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Forbidden.");
}

require_once __DIR__ . "/../config/db.php";

// Only these upload folders may be cleaned.
$folders = [
    "profiles" => dirname(__DIR__) . "/uploads/profiles/",
    "posts" => dirname(__DIR__) . "/uploads/posts/",
    "comments" => dirname(__DIR__) . "/uploads/comments/"
];

try {
    // Process up to 100 queued files per run.
    $stmt = $conn->query("
        SELECT queue_id, folder, filename
        FROM file_deletion_queue
        ORDER BY queue_id ASC
        LIMIT 100
    ");

    $queuedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $removeQueueEntry = $conn->prepare("
        DELETE FROM file_deletion_queue
        WHERE queue_id = ?
    ");

    $recordFailure = $conn->prepare("
        UPDATE file_deletion_queue
        SET attempts = attempts + 1,
            last_error = ?
        WHERE queue_id = ?
    ");

    $cleaned = 0;
    $failed = 0;

    foreach ($queuedFiles as $item) {
        $id = (int) $item["queue_id"];
        $folder = $item["folder"];
        $filename = $item["filename"];

        // Reject invalid folder names and filenames.
        $validFilename = is_string($filename)
            && $filename !== "."
            && $filename !== ".."
            && basename($filename) === $filename
            && preg_match('/^[a-zA-Z0-9_.-]+$/D', $filename);

        if (!isset($folders[$folder]) || !$validFilename) {
            $recordFailure->execute([
                "Invalid folder or filename.",
                $id
            ]);

            $failed++;
            continue;
        }

        $path = $folders[$folder] . $filename;
        $removed = false;

        // Already missing: nothing left to delete.
        if (!file_exists($path) && !is_link($path)) {
            $removed = true;
        }
        // Only delete regular files, never symbolic links.
        elseif (is_file($path) && !is_link($path)) {
            $removed = @unlink($path);
        }

        if ($removed) {
            $removeQueueEntry->execute([$id]);
            $cleaned++;
        } else {
            $recordFailure->execute([
                "File could not be removed.",
                $id
            ]);

            $failed++;
        }
    }

    echo "Processed: " . count($queuedFiles) . PHP_EOL;
    echo "Cleaned: " . $cleaned . PHP_EOL;
    echo "Still pending: " . $failed . PHP_EOL;

} catch (Throwable $e) {
    error_log("File deletion retry error: " . $e->getMessage());
    exit(1);
}
