(() => {
    const imageModal = document.getElementById("image-modal");
    const modalImage = document.getElementById("modal-image");
    const closeButton = document.querySelector(".image-modal-close");

    if (!imageModal || !modalImage || !closeButton) return;

    function closeModal() {
        imageModal.classList.remove("active");
        imageModal.setAttribute("aria-hidden", "true");
    }

    document.addEventListener("click", (event) => {
        const image = event.target.closest(
            ".post-comment-media, .comment-preview-image"
        );

        if (!image || !image.src) return;

        event.preventDefault();
        modalImage.src = image.currentSrc || image.src;
        modalImage.alt = image.alt;
        imageModal.classList.add("active");
        imageModal.setAttribute("aria-hidden", "false");
    });

    closeButton.addEventListener("click", closeModal);

    imageModal.addEventListener("click", (event) => {
        if (event.target === imageModal) closeModal();
    });
})();

(() => {
    const form = document.querySelector(".post-detail-comment-form");
    if (!form) return;

    const input = form.querySelector(".comment-media-input");
    const preview = form.querySelector(".comment-media-preview");
    const image = preview?.querySelector(".comment-preview-image");
    const removeButton = preview?.querySelector(".comment-preview-remove");

    if (!input || !preview || !image || !removeButton) return;

    let previewUrl = null;

    function clearPreview() {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        input.value = "";
        image.removeAttribute("src");
        preview.hidden = true;
    }

    input.addEventListener("change", () => {
        const file = input.files[0];

        if (!file) {
            clearPreview();
            return;
        }

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp",
            "image/gif"
        ];

        if (!allowedTypes.includes(file.type) || file.size > 5 * 1024 * 1024) {
            alert("Choose a JPG, PNG, WEBP or GIF smaller than 5 MB.");
            clearPreview();
            return;
        }

        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        image.src = previewUrl;
        preview.hidden = false;
    });

    removeButton.addEventListener("click", clearPreview);
})();

//DELETE COMMENT WITHOUT RELOAD
document.addEventListener("submit", async (event) => {
    const form = event.target.closest(
        ".post-detail-comment-list .delete-comment-form"
    );

    if (!form) return;

    event.preventDefault();

    if (!confirm("Are you sure you want to delete this comment?")) {
        return;
    }

    const button = form.querySelector(".delete-comment-button");
    const comment = form.closest(".post-detail-comment");
    const commentsList = form.closest(".post-detail-comment-list");
    const commentCount = document.querySelector(
        ".post-detail-actions .comment-icon + span"
    );

    button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: new FormData(form),
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        if (!response.ok) {
            throw new Error("Comment delete request failed.");
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error("Comment could not be deleted.");
        }

        comment.remove();

        if (commentCount) {
            commentCount.textContent = data.comment_count;
        }

        const remainingComments =
            commentsList.querySelectorAll(".post-detail-comment").length;
        const loadMoreButton = document.querySelector(
            ".post-detail-comments .load-more-comments-button"
        );

        if (loadMoreButton) {
            loadMoreButton.dataset.offset = remainingComments;

            if (Number(data.comment_count) <= remainingComments) {
                loadMoreButton.remove();
            }
        }

        const emptyMessage = commentsList.querySelector(
            ".post-detail-no-comments"
        );

        if (Number(data.comment_count) === 0 && !emptyMessage) {
            const message = document.createElement("p");
            message.className = "post-detail-no-comments";
            message.textContent = "No comments yet. Be the first to comment.";
            commentsList.appendChild(message);
        }
    } catch (error) {
        console.error("Delete comment error:", error);
        button.disabled = false;
    }
});

// LOAD MORE COMMENTS — INDIVIDUAL POST
document.querySelectorAll(
    ".post-detail-comments .load-more-comments-button"
).forEach(button => {
    button.addEventListener("click", async () => {
        const commentsList = document.querySelector(
            ".post-detail-comment-list"
        );

        const postId = button.dataset.postId;
        const offset = Number(button.dataset.offset);

        button.disabled = true;
        button.textContent = "Loading...";

        try {
            const response = await fetch(
                `load_more_comments.php?post_id=${encodeURIComponent(postId)}&offset=${offset}`
            );

            if (!response.ok) {
                throw new Error("Failed to load comments.");
            }

            const data = await response.json();

            if (!Array.isArray(data.comments)) {
                throw new Error("Invalid comments response.");
            }

            // Get logged-in user ID from the post page
            const currentUserId = Number(
                document.querySelector(".post-detail-page")?.dataset.userId || 0
            );

            data.comments.forEach(comment => {
                const article = document.createElement("article");
                article.className = "post-detail-comment";

                // AUTHOR AVATAR
                const avatarLink = document.createElement("a");
                avatarLink.href = `profile.php?id=${comment.user_id}`;
                avatarLink.className = "comment-avatar-link";

                const avatar = document.createElement("img");
                avatar.className = "comment-avatar";
                avatar.src = comment.profile_image || "images/default-profile.jpg";
                avatar.alt = `${comment.username}'s profile picture`;

                avatarLink.appendChild(avatar);

                // COMMENT CONTENT
                const content = document.createElement("div");
                content.className = "comment-content";

                const meta = document.createElement("div");
                meta.className = "comment-meta";

                const author = document.createElement("strong");
                const authorLink = document.createElement("a");
                authorLink.href = `profile.php?id=${comment.user_id}`;
                authorLink.className = "comment-author-link";
                authorLink.textContent = comment.username;
                author.appendChild(authorLink);

                const date = document.createElement("span");
                const parsedDate = new Date(
                    comment.created_at.replace(" ", "T")
                );

                date.textContent = parsedDate.toLocaleDateString("en-GB", {
                    day: "numeric",
                    month: "short"
                });

                meta.append(author, date);

                // DELETE BUTTON FOR OWN COMMENTS
                if (Number(comment.user_id) === currentUserId) {
                    const form = document.createElement("form");
                    form.action = "comment_delete.php";
                    form.method = "POST";
                    form.className = "delete-comment-form";

                    const csrfToken = document.querySelector(
                        '.post-detail-comment-form input[name="csrf_token"]'
                    )?.value || "";

                    const fields = {
                        comment_id: comment.comment_id,
                        post_id: postId,
                        csrf_token: csrfToken,
                        return_to: "post"
                    };

                    Object.entries(fields).forEach(([name, value]) => {
                        const input = document.createElement("input");
                        input.type = "hidden";
                        input.name = name;
                        input.value = value;
                        form.appendChild(input);
                    });

                    const deleteButton = document.createElement("button");
                    deleteButton.type = "submit";
                    deleteButton.className = "delete-comment-button";
                    deleteButton.textContent = "Delete";

                    form.appendChild(deleteButton);
                    meta.appendChild(form);
                }

                content.appendChild(meta);

                // COMMENT TEXT
                if (comment.comment) {
                    const text = document.createElement("p");
                    text.textContent = comment.comment;
                    text.style.whiteSpace = "pre-line";
                    content.appendChild(text);
                }

                // COMMENT IMAGE
                if (comment.media_file) {
                    const imageUrl =
                        `uploads/comments/${encodeURIComponent(comment.media_file)}`;

                    const imageLink = document.createElement("a");
                    imageLink.href = imageUrl;
                    imageLink.className = "comment-media-link";

                    const image = document.createElement("img");
                    image.className = "post-comment-media";
                    image.src = imageUrl;
                    image.alt = "Comment attachment";
                    image.loading = "lazy";

                    imageLink.appendChild(image);
                    content.appendChild(imageLink);
                }

                article.append(avatarLink, content);
                commentsList.appendChild(article);
            });

            button.dataset.offset = offset + data.comments.length;

            if (!data.hasMore) {
                button.remove();
            } else {
                button.textContent = "Show more comments";
                button.disabled = false;
            }

        } catch (error) {
            console.error("Load more comments error:", error);
            button.textContent = "Try again";
            button.disabled = false;
        }
    });
});
// SAVE POST WITHOUT RELOADING
document.querySelector(
    '.post-detail-actions form[action="save_post.php"]'
)?.addEventListener("submit", async (event) => {
    event.preventDefault();

    const form = event.currentTarget;
    const button = form.querySelector("button");
    button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: new FormData(form),
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        if (!response.ok) throw new Error("Save request failed.");

        const data = await response.json();
        if (!data.success) throw new Error(data.message || "Save failed.");

        button.classList.toggle("active", data.saved);
        button.setAttribute("aria-label", data.saved ? "Remove from saved" : "Save post");
        button.title = data.saved ? "Saved" : "Save";
    } catch (error) {
        console.error("Save error:", error);
    } finally {
        button.disabled = false;
    }
});

