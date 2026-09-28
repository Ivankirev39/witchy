<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/csrf.php";

$pageTitle = "Edit Profile | Witchy";
$pageCss = "profile.css";

$user_id = $_SESSION["user_id"];

$error = "";


// ========================================
// GET CURRENT USER
// ========================================

$stmt = $conn->prepare("
    SELECT username, email, birthdate, bio
    FROM users
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}


// ========================================
// UPDATE PROFILE
// ========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ----------------------------------------
    // CHECK CSRF TOKEN
    // ----------------------------------------

    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        !isset($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $submittedToken)
    ) {
        $error = "Invalid request. Please try again.";
    }


    // ----------------------------------------
    // GET FORM DATA
    // ----------------------------------------

    if ($error === "") {

        $username = trim($_POST["username"] ?? "");
        $birthdate = trim($_POST["birthdate"] ?? "");
        $bio = trim($_POST["bio"] ?? "");


        // ----------------------------------------
        // VALIDATION
        // ----------------------------------------

        if ($username === "") {

            $error = "Username cannot be empty.";

        } elseif (strlen($username) > 50) {

            $error = "Username cannot be longer than 50 characters.";

        } elseif (strlen($bio) > 500) {

            $error = "Bio cannot be longer than 500 characters.";

        } else {

            // ----------------------------------------
            // CHECK IF USERNAME ALREADY EXISTS
            // ----------------------------------------

            $checkStmt = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE username = ?
                AND user_id != ?
            ");

            $checkStmt->bind_param(
                "si",
                $username,
                $user_id
            );

            $checkStmt->execute();

            $existingUser = $checkStmt
                ->get_result()
                ->fetch_assoc();


            if ($existingUser) {

                $error = "That username is already taken.";

            } else {

                // ----------------------------------------
                // PREPARE BIRTHDATE
                // ----------------------------------------

                if ($birthdate === "") {
                    $birthdate = null;
                }


                // ----------------------------------------
                // UPDATE USER
                // ----------------------------------------

                $updateStmt = $conn->prepare("
                    UPDATE users
                    SET username = ?,
                        birthdate = ?,
                        bio = ?
                    WHERE user_id = ?
                ");

                $updateStmt->bind_param(
                    "sssi",
                    $username,
                    $birthdate,
                    $bio,
                    $user_id
                );


                if ($updateStmt->execute()) {

                    // Keep session username updated
                    $_SESSION["username"] = $username;

                    // Redirect back to profile
                    header("Location: profile.php?updated=1");
                    exit;

                } else {

                    $error = "Something went wrong while updating your profile.";

                }
            }
        }
    }


    // ----------------------------------------
    // KEEP ENTERED VALUES IF VALIDATION FAILS
    // ----------------------------------------

    if ($error !== "") {

        $user["username"] = $_POST["username"] ?? $user["username"];
        $user["birthdate"] = $_POST["birthdate"] ?? $user["birthdate"];
        $user["bio"] = $_POST["bio"] ?? $user["bio"];

    }
}


// ========================================
// PAGE HEADER / SIDEBAR
// ========================================

require_once __DIR__ . "/includes/user_header.php";

?>


<section class="edit-profile-page">

    <!-- PAGE HEADING -->
    <header class="edit-profile-heading">

        <div>
            <h1>Edit Profile</h1>

            <p>
                Update your profile information.
            </p>
        </div>

        <a
            href="profile.php"
            class="edit-profile-back"
        >
            Back to profile
        </a>

    </header>


    <!-- ERROR MESSAGE -->
    <?php if ($error !== ""): ?>

        <div class="edit-profile-message error">

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </div>

    <?php endif; ?>


    <!-- EDIT PROFILE FORM -->
    <form
        action="edit_profile.php"
        method="POST"
        class="edit-profile-form"
    >

        <!-- CSRF TOKEN -->
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                csrf_token(),
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
        >


        <!-- USERNAME -->
        <div class="edit-profile-field">

            <label for="username">
                Username
            </label>

            <input
                type="text"
                id="username"
                name="username"
                maxlength="50"
                value="<?= htmlspecialchars(
                    $user["username"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                required
            >

        </div>


        <!-- EMAIL -->
        <div class="edit-profile-field">

            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                value="<?= htmlspecialchars(
                    $user["email"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                disabled
            >

            <small>
                Your email cannot be changed here.
            </small>

        </div>


        <!-- BIRTHDATE -->
        <div class="edit-profile-field">

            <label for="birthdate">
                Birthday
            </label>

            <input
                type="date"
                id="birthdate"
                name="birthdate"
                value="<?= htmlspecialchars(
                    $user["birthdate"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

        </div>


        <!-- BIO -->
        <div class="edit-profile-field">

            <label for="bio">
                Bio
            </label>

            <textarea
                id="bio"
                name="bio"
                rows="6"
                maxlength="500"
                placeholder="Tell the community a little about yourself..."
            ><?= htmlspecialchars(
                $user["bio"] ?? "",
                ENT_QUOTES,
                "UTF-8"
            ) ?></textarea>

            <small>
                Maximum 500 characters.
            </small>

        </div>


        <!-- FORM ACTIONS -->
        <div class="edit-profile-actions">

            <a
                href="profile.php"
                class="edit-profile-cancel"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="edit-profile-save"
            >
                Save changes
            </button>

        </div>

    </form>

</section>


<?php

require_once __DIR__ . "/includes/user_footer.php";

?>