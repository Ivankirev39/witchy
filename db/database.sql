-- ============================================================
-- WITCHY DATABASE - INSTALLATION SCRIPT
-- Web Programming & Databases Semester Project
--
-- Creates the complete WITCHY database for a fresh installation.
-- Includes tables, relationships, indexes, views,
-- triggers and fictional sample data.
--
-- IMPORTANT: Do not run against an existing populated database.
-- ============================================================


-- ============================================================
-- 1. CREATE TEST DATABASE
-- ============================================================

CREATE DATABASE IF NOT EXISTS witchy
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE witchy;


-- ============================================================
-- 2. USERS
-- ============================================================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,

    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,

    birthdate DATE DEFAULT NULL,

    `rank` VARCHAR(50) NOT NULL DEFAULT 'user',

    bio TEXT DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL

) ENGINE=InnoDB;


-- ============================================================
-- 3. POSTS
-- ============================================================

CREATE TABLE post (
    post_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    -- Legacy image field retained for compatibility
    image VARCHAR(255) DEFAULT NULL,

    title VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,

    topic VARCHAR(100) DEFAULT NULL,
    sources TEXT DEFAULT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    is_hot BOOLEAN NOT NULL DEFAULT FALSE,
    is_sticky BOOLEAN NOT NULL DEFAULT FALSE,

    CONSTRAINT fk_post_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    INDEX idx_post_created (created_at, post_id)

) ENGINE=InnoDB;


-- ============================================================
-- 4. COMMENTS
-- ============================================================

CREATE TABLE comment (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,
    post_id INT NOT NULL,

    comment TEXT NOT NULL,

    media_file VARCHAR(255) DEFAULT NULL,
    media_type VARCHAR(20) DEFAULT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_comment_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_comment_post
        FOREIGN KEY (post_id)
        REFERENCES post(post_id)
        ON DELETE CASCADE,

    INDEX idx_comment_post_created
        (post_id, created_at),

    INDEX idx_comment_created (created_at)

) ENGINE=InnoDB;


-- ============================================================
-- 5. POST MEDIA
-- ============================================================

CREATE TABLE post_media (
    media_id INT AUTO_INCREMENT PRIMARY KEY,

    post_id INT NOT NULL,

    file VARCHAR(255) NOT NULL,

    media_type ENUM('image', 'video') NOT NULL,

    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 1,

    CONSTRAINT fk_post_media_post
        FOREIGN KEY (post_id)
        REFERENCES post(post_id)
        ON DELETE CASCADE,

    INDEX idx_post_media_order
        (post_id, sort_order, media_id)

) ENGINE=InnoDB;


-- ============================================================
-- 6. POST LIKES
-- ============================================================

CREATE TABLE post_like (
    user_id INT NOT NULL,
    post_id INT NOT NULL,

    PRIMARY KEY (user_id, post_id),

    CONSTRAINT fk_post_like_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_post_like_post
        FOREIGN KEY (post_id)
        REFERENCES post(post_id)
        ON DELETE CASCADE,

    INDEX idx_post_like_post (post_id)

) ENGINE=InnoDB;


-- ============================================================
-- 7. SAVED POSTS
-- ============================================================

CREATE TABLE save (
    user_id INT NOT NULL,
    post_id INT NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, post_id),

    CONSTRAINT fk_save_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_save_post
        FOREIGN KEY (post_id)
        REFERENCES post(post_id)
        ON DELETE CASCADE,

    INDEX idx_save_post (post_id)

) ENGINE=InnoDB;


-- ============================================================
-- 8. FOLLOW RELATIONSHIPS
-- ============================================================

CREATE TABLE follow (
    follower_id INT NOT NULL,
    following_id INT NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (follower_id, following_id),

    CONSTRAINT fk_follow_follower
        FOREIGN KEY (follower_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_follow_following
        FOREIGN KEY (following_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    INDEX idx_follow_following (following_id)

) ENGINE=InnoDB;


-- ============================================================
-- 9. BADGES
-- ============================================================

CREATE TABLE badge (
    badge_id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    image VARCHAR(255) DEFAULT NULL

) ENGINE=InnoDB;


-- ============================================================
-- 10. USER BADGES
-- ============================================================

CREATE TABLE user_badge (
    user_id INT NOT NULL,
    badge_id INT NOT NULL,

    earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, badge_id),

    CONSTRAINT fk_user_badge_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_user_badge_badge
        FOREIGN KEY (badge_id)
        REFERENCES badge(badge_id)
        ON DELETE CASCADE,

    INDEX idx_user_badge_badge (badge_id)

) ENGINE=InnoDB;


-- ============================================================
-- 11. COMMUNITY RULES
-- ============================================================

CREATE TABLE rule (
    rule_id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(150) NOT NULL,

    description TEXT NOT NULL

) ENGINE=InnoDB;


-- ============================================================
-- 12. USER BANS
-- ============================================================

CREATE TABLE ban (
    ban_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    reason TEXT NOT NULL,

    banned_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    expires_at DATETIME DEFAULT NULL,

    CONSTRAINT fk_ban_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    INDEX idx_ban_user_expires
        (user_id, expires_at)

) ENGINE=InnoDB;


-- ============================================================
-- 13. NOTIFICATIONS
-- ============================================================

CREATE TABLE notification (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,
    actor_id INT NOT NULL,
    post_id INT DEFAULT NULL,

    type ENUM('like', 'comment', 'follow') NOT NULL,

    is_read TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notification_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_notification_actor
        FOREIGN KEY (actor_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_notification_post
        FOREIGN KEY (post_id)
        REFERENCES post(post_id)
        ON DELETE CASCADE,

    INDEX idx_notification_inbox
        (user_id, is_read, created_at),

    INDEX idx_notification_actor (actor_id),

    INDEX idx_notification_post (post_id)

) ENGINE=InnoDB;


-- ============================================================
-- 14. SUPPORT REQUESTS
-- ============================================================

CREATE TABLE support_request (
    request_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,

    category VARCHAR(30) NOT NULL,
    subject VARCHAR(150) NOT NULL,

    message TEXT NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'open',

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_support_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    INDEX idx_support_user (user_id),

    INDEX idx_support_status (status)

) ENGINE=InnoDB;


-- ============================================================
-- 15. LOGIN ATTEMPTS
-- ============================================================

CREATE TABLE login_attempt (
    attempt_id BIGINT UNSIGNED
        AUTO_INCREMENT PRIMARY KEY,

    login_key CHAR(64)
        CHARACTER SET ascii
        COLLATE ascii_bin NOT NULL,

    ip_key CHAR(64)
        CHARACTER SET ascii
        COLLATE ascii_bin NOT NULL,

    attempted_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_login_ip_time
        (login_key, ip_key, attempted_at),

    INDEX idx_attempted_at (attempted_at)

) ENGINE=InnoDB;


-- ============================================================
-- 16. FILE DELETION QUEUE
-- ============================================================

CREATE TABLE file_deletion_queue (
    queue_id INT AUTO_INCREMENT PRIMARY KEY,

    folder ENUM(
        'profiles',
        'posts',
        'comments'
    ) NOT NULL,

    filename VARCHAR(255) NOT NULL,

    queued_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    attempts INT UNSIGNED NOT NULL DEFAULT 0,

    last_error VARCHAR(255) DEFAULT NULL,

    UNIQUE KEY unique_pending_file
        (folder, filename)

) ENGINE=InnoDB;


-- ============================================================
-- 17. VIEW 1 - POST ENGAGEMENT
-- ============================================================

CREATE VIEW v_post_engagement AS

SELECT
    p.post_id,
    p.user_id,
    p.title,
    p.topic,
    p.created_at,
    p.is_hot,
    p.is_sticky,

    u.username AS author_username,

    (
        SELECT COUNT(*)
        FROM post_like pl
        WHERE pl.post_id = p.post_id
    ) AS like_count,

    (
        SELECT COUNT(*)
        FROM comment c
        WHERE c.post_id = p.post_id
    ) AS comment_count

FROM post p

INNER JOIN users u
    ON u.user_id = p.user_id;


-- ============================================================
-- 18. VIEW 2 - LATEST COMMENTS
-- ============================================================

CREATE VIEW v_latest_comments AS

SELECT
    c.comment_id,
    c.post_id,
    c.user_id,
    c.comment,
    c.created_at,
    c.media_file,
    c.media_type,

    u.username AS author_username,

    p.title AS post_title

FROM comment c

INNER JOIN users u
    ON u.user_id = c.user_id

INNER JOIN post p
    ON p.post_id = c.post_id;


-- ============================================================
-- 19. TRIGGER 1 - PREVENT SELF FOLLOWING
-- ============================================================

DELIMITER $$

CREATE TRIGGER trg_follow_no_self

BEFORE INSERT ON follow

FOR EACH ROW

BEGIN

    IF NEW.follower_id = NEW.following_id THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Users cannot follow themselves';

    END IF;

END$$


-- ============================================================
-- 20. TRIGGER 2 - PREVENT EMPTY COMMENTS
-- ============================================================

CREATE TRIGGER trg_comment_require_content

BEFORE INSERT ON comment

FOR EACH ROW

BEGIN

    IF TRIM(NEW.comment) = ''
       AND (
           NEW.media_file IS NULL
           OR TRIM(NEW.media_file) = ''
       ) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Comment text or image is required';

    END IF;

END$$

DELIMITER ;


-- ============================================================
-- 21. COMMUNITY RULES - SAMPLE DATA
-- ============================================================

INSERT INTO rule (title, description)
VALUES

(
    'Be Respectful',
    'Treat community members and their beliefs with respect.'
),

(
    'Credit Sources',
    'Credit creators and include sources when appropriate.'
),

(
    'Keep the Community Safe',
    'Do not publish harassment, threats, or harmful content.'
);


-- ============================================================
-- 22. BADGES - SAMPLE DATA
-- ============================================================

INSERT INTO badge (name, image)
VALUES

('Early Contributor', NULL),

('Community Member', NULL);


-- ============================================================
-- 23. FICTIONAL TEST USERS
-- ============================================================

-- These are database-only test accounts.
-- They are deliberately not usable for login.

INSERT INTO users
(username, email, password, `rank`, bio)

VALUES

(
    'demo_learner',
    'demo_learner@example.invalid',
    '!DISABLED_DEMO_LOGIN!',
    'user',
    'Learning about herbs and tarot.'
),

(
    'demo_practitioner',
    'demo_practitioner@example.invalid',
    '!DISABLED_DEMO_LOGIN!',
    'user',
    'Sharing knowledge about spiritual practices.'
);


-- ============================================================
-- 24. SAMPLE POSTS
-- ============================================================

INSERT INTO post
(user_id, title, description, topic, sources)

SELECT
    user_id,
    'Starting with herbs',
    'Which herbs would you recommend to beginners?',
    'Herbs',
    ''

FROM users
WHERE username = 'demo_learner';


INSERT INTO post
(user_id, title, description, topic, sources)

SELECT
    user_id,
    'My first tarot spread',
    'What three-card spreads do you enjoy?',
    'Tarot',
    ''

FROM users
WHERE username = 'demo_practitioner';


INSERT INTO post
(user_id, title, description, topic, sources)

SELECT
    user_id,
    'Full moon inspiration',
    'Sharing some ideas for a simple full moon ritual.',
    'Spells',
    ''

FROM users
WHERE username = 'demo_learner';


-- ============================================================
-- 25. SAMPLE COMMENT
-- ============================================================

INSERT INTO comment
(user_id, post_id, comment)

SELECT
    u.user_id,
    p.post_id,
    'I would recommend rosemary and lavender.'

FROM users u

JOIN post p
    ON p.title = 'Starting with herbs'

WHERE u.username = 'demo_practitioner';


-- ============================================================
-- 26. SAMPLE LIKE
-- ============================================================

INSERT INTO post_like
(user_id, post_id)

SELECT
    u.user_id,
    p.post_id

FROM users u

JOIN post p
    ON p.title = 'My first tarot spread'

WHERE u.username = 'demo_learner';


-- ============================================================
-- 27. SAMPLE FOLLOW
-- ============================================================

INSERT INTO follow
(follower_id, following_id)

SELECT
    a.user_id,
    b.user_id

FROM users a

CROSS JOIN users b

WHERE a.username = 'demo_learner'
AND b.username = 'demo_practitioner';