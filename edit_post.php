<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/Post.php";
require_once __DIR__ . "/models/KeywordFilter.php";

function getSafeEditReturnUrl($url)
{
    if (!is_string($url) || $url === "" || preg_match('/[\r\n\\\\]/', $url)) {
        return null;
    }

    $parts = parse_url($url);

    if (
        $parts === false ||
        isset($parts["scheme"]) ||
        isset($parts["host"]) ||
        isset($parts["user"]) ||
        isset($parts["pass"])
    ) {
        return null;
    }

    $path = $parts["path"] ?? "";

    if (
        $path === "" ||
        $path[0] !== "/" ||
        str_starts_with($path, "//") ||
        in_array(basename($path), ["post.php", "edit_post.php"], true)
    ) {
        return null;
    }

    $returnUrl = $path;

    if (!empty($parts["query"])) {
        $returnUrl .= "?" . $parts["query"];
    }

    if (!empty($parts["fragment"])) {
        $returnUrl .= "#" . $parts["fragment"];
    }

    return $returnUrl;
}

$pdoConnector = new \DataAccess\DB\DBConnector();
$pdo = $pdoConnector->connectToDB();

$postModel = new Post($pdo);

$pageTitle = "Edit post | Witchy";
$pageCss = "edit_post.css";

$userId = (int) $_SESSION["user_id"];
$postId = (int) ($_GET["id"] ?? $_POST["post_id"] ?? 0);

$returnUrl = getSafeEditReturnUrl(
    $_POST["return"] ?? $_GET["return"] ?? ""
);

if ($postId <= 0) {
    header("Location: feed.php");
    exit;
}

$postOwnerId = $postModel->getPostOwnerId($postId);

if ($postOwnerId === null) {
    header("Location: feed.php");
    exit;
}

if ($postOwnerId !== $userId) {
    http_response_code(403);
    exit("You are not allowed to edit this post.");
}

$post = $postModel->getPostById($postId, $userId);

if ($post === null) {
    header("Location: feed.php");
    exit;
}

$title = $post["title"];
$description = $post["description"];
$topic = $post["topic"];
$sources = $post["sources"];

$errorMessage = "";

$allowedTopics = [
    "spells",
    "altars",
    "tarot",
    "herbs",
    "crystals",
    "books",
    "artwork",
    "occult-studies",
    "others"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        http_response_code(403);
        exit("Invalid request.");
    }

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $topic = trim($_POST["topic"] ?? "");
    $sources = trim($_POST["sources"] ?? "");

    if ($title === "") {
        $errorMessage = "Please enter a title.";
    } elseif (
        (function_exists("mb_strlen") ? mb_strlen($title) : strlen($title)) > 150
    ) {
        $errorMessage = "The title cannot exceed 150 characters.";
    } elseif (!in_array($topic, $allowedTopics, true)) {
        $errorMessage = "Please select a valid topic.";
    }

    if ($errorMessage === "") {
        $keywordFilter = new KeywordFilter();

        $fieldsToCheck = [
            "Title" => $title,
            "Description" => $description,
            "Sources" => $sources
        ];

        foreach ($fieldsToCheck as $fieldName => $fieldValue) {
            if ($keywordFilter->containsBlockedContent($fieldValue)) {
                $errorMessage =
                    "Your " . strtolower($fieldName) .
                    " contains inappropriate language. Please edit it and try again.";

                break;
            }
        }
    }

    if ($errorMessage === "") {
        try {
            $updated = $postModel->updatePost(
                $postId,
                $userId,
                $title,
                $description,
                $topic,
                $sources
            );

            if ($updated) {
                $redirectUrl = "post.php?id=" . $postId . "&updated=1";

                if ($returnUrl !== null) {
                    $redirectUrl .= "&return=" . rawurlencode($returnUrl);
                }

                header("Location: " . $redirectUrl);
                exit;
            }

            $errorMessage = "The post could not be updated.";
        } catch (Throwable $e) {
            error_log("Post update failed: " . $e->getMessage());
            $errorMessage = "Something went wrong. Please try again.";
        }
    }
}

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="edit-page">

    <header class="edit-header">
        <div>
            <h1>Edit your post</h1>
            <p class="edit-subtitle">Make changes to your post</p>
        </div>
    </header>

    <?php if ($errorMessage !== ""): ?>
        <p class="form-error">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form
        class="edit-form"
        action="edit_post.php?id=<?= $postId ?>"
        method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
        >

        <input
            type="hidden"
            name="post_id"
            value="<?= $postId ?>"
        >

        <input
            type="hidden"
            name="return"
            value="<?= htmlspecialchars($returnUrl ?? "", ENT_QUOTES, "UTF-8") ?>"
        >

        <section class="edit-card">

            <div class="card-header">
                <h2>Post details</h2>
                <p>Update your title and description.</p>
            </div>

            <div class="form-group">
                <label for="title">Title</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="150"
                    value="<?= htmlspecialchars($title, ENT_QUOTES, "UTF-8") ?>"
                    required
                >
                <small>Maximum 150 characters.</small>
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    rows="7"
                ><?= htmlspecialchars($description, ENT_QUOTES, "UTF-8") ?></textarea>
            </div>

        </section>

        <section class="edit-card">

            <div class="card-header">
                <h2>Topic</h2>
                <p>Choose the topic that best fits your post.</p>
            </div>

            <div class="form-group">
                <label for="topic">Topic / Category</label>

                <select id="topic" name="topic" required>
                    <option value="" disabled>Select a topic</option>

                    <option value="spells" <?= $topic === "spells" ? "selected" : "" ?>>Spells</option>
                    <option value="altars" <?= $topic === "altars" ? "selected" : "" ?>>Altars</option>
                    <option value="tarot" <?= $topic === "tarot" ? "selected" : "" ?>>Tarot</option>
                    <option value="herbs" <?= $topic === "herbs" ? "selected" : "" ?>>Herbs</option>
                    <option value="crystals" <?= $topic === "crystals" ? "selected" : "" ?>>Crystals</option>
                    <option value="books" <?= $topic === "books" ? "selected" : "" ?>>Books</option>
                    <option value="artwork" <?= $topic === "artwork" ? "selected" : "" ?>>Artwork</option>
                    <option value="occult-studies" <?= $topic === "occult-studies" ? "selected" : "" ?>>Occult Studies</option>
                    <option value="others" <?= $topic === "others" ? "selected" : "" ?>>Others</option>
                </select>
            </div>

        </section>

        <section class="edit-card">

            <div class="card-header">
                <h2>Sources & references</h2>
                <p>Update any sources or references related to your post.</p>
            </div>

            <div class="form-group">
                <label for="sources">Sources / References</label>

                <textarea
                    id="sources"
                    name="sources"
                    rows="4"
                ><?= htmlspecialchars($sources, ENT_QUOTES, "UTF-8") ?></textarea>
            </div>

        </section>

        <div class="edit-actions">
            <a
                href="post.php?id=<?= $postId ?><?= $returnUrl !== null ? "&return=" . rawurlencode($returnUrl) : "" ?>"
                class="button button-secondary">
                Cancel
            </a>

            <button type="submit" class="button button-primary">
                Save changes
            </button>
        </div>

    </form>

</section>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
