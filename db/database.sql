CREATE DATABASE IF NOT EXISTS witchy;
USE witchy;

CREATE TABLE user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    birthdate DATE,
    rank VARCHAR(50),
    bio TEXT,
    profile_image VARCHAR(255)
)

CREATE TABLE post(
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, image VARCHAR(255),
    title VARCHAR(150) NOT NULL, description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_hot BOOLEAN DEFAULT FALSE,
    is_sticky BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (user_id)
    REFERENCES user(user_id)
    ON DELETE CASCADE
)

CREATE TABLE comment (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES user(user_id)
    ON DELETE CASCADE,

    FOREIGN KEY (post_id)
    REFERENCES post(post_id)
    ON DELETE CASCADE
)

CREATE TABLE `like` (
    user_id INT NOT NULL,
    post_id INT NOT NULL,

    PRIMARY KEY (user_id, post_id),

    FOREIGN KEY (user_id)
    REFERENCES user(user_id)
    ON DELETE CASCADE,


    FOREIGN KEY (post_id)
    REFERENCES post(post_id)
    ON DELETE CASCADE
)

CREATE TABLE save (
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, post_id),

    FOREIGN KEY (user_id)
    REFERENCES user(user_id)
    ON DELETE CASCADE,

    FOREIGN KEY (post_id)
    REFERENCES post(post_id)
    ON DELETE CASCADE
)


