<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/User.php";

$pageTitle = "Edit Profile | Witchy";
$pageCss = "edit_profile.css";

$user_id = (int) $_SESSION["user_id"];
$error = "";

$userModel = new User($conn);
$user = $userModel->getUserById($user_id);

if (!$user) {
    http_response_code(404);
    exit("User not found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        $error = "Invalid request. Please try again.";
    }

    if ($error === "") {
        $username = trim($_POST["username"] ?? "");
        $birthdate = trim($_POST["birthdate"] ?? "");
        $bio = trim($_POST["bio"] ?? "");

        if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
            $error = "Username must be 3-50 characters and only contain letters, numbers or underscores.";
        } elseif (strlen($bio) > 500) {
            $error = "Bio cannot be longer than 500 characters.";
        } elseif ($birthdate !== "" && (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate) ||
            !checkdate(
                (int) substr($birthdate, 5, 2),
                (int) substr($birthdate, 8, 2),
                (int) substr($birthdate, 0, 4)
            ) ||
            $birthdate > date("Y-m-d")
        )) {
            $error = "Please enter a valid birthday.";
        }
    }

    if ($error === "") {
        try {
            if ($userModel->checkUsername($username, $user_id)) {
                $error = "That username is already taken.";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Something went wrong while checking your username.";
        }
    }

    if ($error === "") {
        $profile_image = $user["profile_image"] ?? null;
        $cover_image = $user["cover_image"] ?? null;
        $newFiles = [];

        $uploads = [
            "profile_image" => [
                "prefix" => "profile_",
                "label" => "profile picture"
            ],
            "cover_image" => [
                "prefix" => "cover_",
                "label" => "cover image"
            ]
        ];

        foreach ($uploads as $field => $settings) {
            if (
                !isset($_FILES[$field]) ||
                $_FILES[$field]["error"] === UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }

            $file = $_FILES[$field];
            $label = $settings["label"];

            if ($file["error"] !== UPLOAD_ERR_OK) {
                $error = "Something went wrong while uploading the " . $label . ".";
                break;
            }

            if ($file["size"] > 10 * 1024 * 1024) {
                $error = ucfirst($label) . " cannot be larger than 10 MB.";
                break;
            }

            if (!is_uploaded_file($file["tmp_name"])) {
                $error = "Invalid uploaded file.";
                break;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file["tmp_name"]);

            $allowedTypes = [
                "image/jpeg" => "jpg",
                "image/png" => "png",
                "image/webp" => "webp"
            ];

            if (!isset($allowedTypes[$mimeType])) {
                $error = "Only JPG, PNG and WebP images are allowed.";
                break;
            }

            $imageInfo = @getimagesize($file["tmp_name"]);

            if ($imageInfo === false) {
                $error = "The uploaded image is not valid.";
                break;
            }

            $filename = $settings["prefix"] . $user_id . "_" .
                bin2hex(random_bytes(8)) . "." . $allowedTypes[$mimeType];

            $uploadDirectory = __DIR__ . "/uploads/profiles/";
            $uploadPath = $uploadDirectory . $filename;

            if (
                !is_dir($uploadDirectory) &&
                !mkdir($uploadDirectory, 0755, true)
            ) {
                $error = "Could not create the profile image folder.";
                break;
            }

            if (!move_uploaded_file($file["tmp_name"], $uploadPath)) {
                $error = "Could not save the " . $label . ".";
                break;
            }

            $newFiles[] = $uploadPath;
            ${$field} = "uploads/profiles/" . $filename;
        }

        if ($error === "") {
            if ($birthdate === "") {
                $birthdate = null;
            }

            try {
                $userModel->updateProfile(
                    $user_id,
                    $username,
                    $birthdate,
                    $bio,
                    $profile_image,
                    $cover_image
                );

                $_SESSION["username"] = $username;

                header("Location: profile.php?updated=1");
                exit;

            } catch (PDOException $e) {
                error_log($e->getMessage());

                if (
                    $e->getCode() === "23000" &&
                    (int) ($e->errorInfo[1] ?? 0) === 1062
                ) {
                    $error = "That username is already taken.";
                } else {
                    $error = "Something went wrong while updating your profile.";
                }
            }
        }

        if ($error !== "") {
            foreach ($newFiles as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    if ($error !== "") {
        $user["username"] = $_POST["username"] ?? $user["username"];
        $user["birthdate"] = $_POST["birthdate"] ?? $user["birthdate"];
        $user["bio"] = $_POST["bio"] ?? $user["bio"];
    }
}

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="edit-profile-page">
    <header class="edit-profile-heading">
        <div>
            <h1>Edit Profile</h1>
            <p>Update your profile information.</p>
        </div>
        <a href="profile.php" class="edit-profile-back">Back to profile</a>
    </header>

    <?php if ($error !== ""): ?>
        <div class="edit-profile-message error" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form action="edit_profile.php" method="POST" enctype="multipart/form-data" class="edit-profile-form">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
        >

        <div class="edit-profile-picture-field">
            <div class="edit-profile-picture-preview">
                <?php if (!empty($user["profile_image"])): ?>
                    <img
                        src="<?= htmlspecialchars($user["profile_image"], ENT_QUOTES, "UTF-8") ?>"
                        alt="Current profile picture"
                    >
                <?php else: ?>
                    <img src="images/default-profile.jpg" alt="Default profile picture">
                <?php endif; ?>
            </div>

            <div class="edit-profile-picture-controls">
                <label for="profile_image">Profile picture</label>
                <input
                    type="file"
                    id="profile_image"
                    name="profile_image"
                    accept="image/jpeg,image/png,image/webp"
                >
                <small>JPG, PNG or WebP. Maximum 10 MB.</small>
            </div>
        </div>

        <div class="edit-profile-cover-field">
            <div class="edit-profile-cover-preview" id="cover_preview">
                <?php if (!empty($user["cover_image"])): ?>
                    <img
                        id="cover_preview_image"
                        src="<?= htmlspecialchars($user["cover_image"], ENT_QUOTES, "UTF-8") ?>"
                        alt="Current profile cover"
                    >
                <?php else: ?>
                    <span id="cover_preview_placeholder">WITCHY</span>
                <?php endif; ?>
            </div>

            <div class="edit-profile-cover-controls">
                <label for="cover_image">Profile cover</label>
                <input
                    type="file"
                    id="cover_image"
                    name="cover_image"
                    accept="image/jpeg,image/png,image/webp"
                >
                <small>JPG, PNG or WebP. Maximum 10 MB.</small>
            </div>
        </div>

        <div class="edit-profile-field">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                minlength="3"
                maxlength="50"
                pattern="[A-Za-z0-9_]{3,50}"
                title="Use 3-50 letters, numbers or underscores"
                value="<?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>"
                required
            >
        </div>

        <div class="edit-profile-field">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                value="<?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?>"
                disabled
            >
            <small>Your email cannot be changed here.</small>
        </div>

        <div class="edit-profile-field">
            <label for="birthdate">Birthday</label>
            <input
                type="date"
                id="birthdate"
                name="birthdate"
                max="<?= date("Y-m-d") ?>"
                value="<?= htmlspecialchars($user["birthdate"] ?? "", ENT_QUOTES, "UTF-8") ?>"
            >
        </div>

        <div class="edit-profile-field">
            <label for="bio">Bio</label>
            <textarea
                id="bio"
                name="bio"
                rows="6"
                maxlength="500"
                placeholder="Tell the community a little about yourself..."
            ><?= htmlspecialchars($user["bio"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea>
            <small>Maximum 500 characters.</small>
        </div>

        <div class="edit-profile-actions">
            <a href="profile.php" class="edit-profile-cancel">Cancel</a>
            <button type="submit" class="edit-profile-save">Save changes</button>
        </div>
    </form>
</section>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
