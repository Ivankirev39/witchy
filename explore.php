<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Explore | Witchy";
$pageCss = "explore.css";

$posts = [];

$sql = "
    SELECT
        p.post_id,
        p.user_id,
        p.title,
        p.description,
        p.image,
        p.topic,
        p.created_at,
        u.username
    FROM post p
    INNER JOIN users u
        ON u.user_id = p.user_id
    ORDER BY p.created_at DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="explore-page">

    <header class="explore-header">
        <h1>Explore</h1>
        <p>
            Discover posts from the Witchy community.
        </p>
    </header>

    <section class="explore-content">

    </section>

</section>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>