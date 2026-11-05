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
// POST COMMENT WITHOUT RELOAD
document.querySelector(".post-detail-comment-form")
    ?.addEventListener("submit", async (event) => {
        event.preventDefault();

        const form = event.currentTarget;
        const button = form.querySelector('button[type="submit"]');
        const commentsList = document.querySelector(
            ".post-detail-comment-list"
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
                throw new Error("Comment submission failed.");
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || "Could not post comment.");
            }

            // Remove empty-state message
            commentsList.querySelector(
                ".post-detail-no-comments"
            )?.remove();

            // CREATE COMMENT ARTICLE
            const article = document.createElement("article");
            article.className = "post-detail-comment";

            // AUTHOR AVATAR
            const avatarLink = document.createElement("a");
            avatarLink.href = `profile.php?id=${data.user_id}`;
            avatarLink.className = "comment-avatar-link";

            const avatar = document.createElement("img");
            avatar.className = "comment-avatar";
            avatar.src = data.profile_image || "images/default-profile.jpg";
            avatar.alt = `${data.username}'s profile picture`;

            avatarLink.appendChild(avatar);

            // COMMENT CONTENT
            const content = document.createElement("div");
            content.className = "comment-content";

            const meta = document.createElement("div");
            meta.className = "comment-meta";

            const author = document.createElement("strong");
            const authorLink = document.createElement("a");

            authorLink.href = `profile.php?id=${data.user_id}`;
            authorLink.className = "comment-author-link";
            authorLink.textContent = data.username;

            author.appendChild(authorLink);

            const date = document.createElement("span");
            const parsedDate = new Date(
                data.created_at.replace(" ", "T")
            );

            date.textContent = parsedDate.toLocaleDateString("en-GB", {
                day: "numeric",
                month: "short"
            });

            meta.append(author, date);

            // DELETE BUTTON
            const deleteForm = document.createElement("form");
            deleteForm.action = "comment_delete.php";
            deleteForm.method = "POST";
            deleteForm.className = "delete-comment-form";

            const fields = {
                comment_id: data.comment_id,
                post_id: form.querySelector('[name="post_id"]').value,
                csrf_token: form.querySelector('[name="csrf_token"]').value,
                return_to: "post"
            };

            Object.entries(fields).forEach(([name, value]) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = name;
                input.value = value;
                deleteForm.appendChild(input);
            });

            const deleteButton = document.createElement("button");
            deleteButton.type = "submit";
            deleteButton.className = "delete-comment-button";
            deleteButton.textContent = "Delete";

            deleteForm.appendChild(deleteButton);
            meta.appendChild(deleteForm);

            content.appendChild(meta);

            // COMMENT TEXT
            if (data.comment) {
                const text = document.createElement("p");
                text.textContent = data.comment;
                text.style.whiteSpace = "pre-line";
                content.appendChild(text);
            }

            // COMMENT IMAGE
            if (data.media_url) {
                const imageLink = document.createElement("a");
                imageLink.href = data.media_url;

                const image = document.createElement("img");
                image.className = "post-comment-media";
                image.src = data.media_url;
                image.alt = "Comment attachment";
                image.loading = "lazy";

                imageLink.appendChild(image);
                content.appendChild(imageLink);
            }

            article.append(avatarLink, content);
            commentsList.appendChild(article);

            // UPDATE COMMENT COUNT
            const commentCount = document.querySelector(
                ".post-detail-actions .comment-icon + span"
            );

            if (commentCount) {
                commentCount.textContent = data.comment_count;
            }

            // CLEAR FORM AND IMAGE PREVIEW
            form.querySelector('[name="comment"]').value = "";

            form.querySelector(".comment-preview-remove")?.click();

        } catch (error) {
            console.error("Comment submission error:", error);
            alert(error.message || "Something went wrong.");
        } finally {
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

                if (Number(comment.user_id) !== currentUserId) {
                    const reportButton = document.createElement("button");

                    reportButton.type = "button";
                    reportButton.className = "report-button";
                    reportButton.textContent = "Report";
                    reportButton.dataset.reportType = "comment";
                    reportButton.dataset.targetId = comment.comment_id;

                    meta.appendChild(reportButton);
                }

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
// REPORT FUNCTION MODAL
(() => {
    const modal = document.getElementById("report-modal");
    const form = document.getElementById("report-form");

    if (!modal || !form) return;

    const title = document.getElementById("report-modal-title");
    const targetInput = document.getElementById("report-target-id");
    const typeInput = document.getElementById("report-type");
    const reasonInput = document.getElementById("report-reason");
    const detailsInput = document.getElementById("report-details");
    const message = document.getElementById("report-message");
    const submitButton = form.querySelector('[type="submit"]');

    function openReportModal(type, targetId) {
        typeInput.value = type;
        targetInput.value = targetId;

        title.textContent = type === "comment"
            ? "Report comment"
            : "Report user";

        form.reset();

        // Restore the values after resetting
        typeInput.value = type;
        targetInput.value = targetId;

        message.hidden = true;
        message.textContent = "";
        message.className = "report-message";

        reasonInput.value = "";
        detailsInput.value = "";

        submitButton.disabled = false;
        submitButton.textContent = "Submit report";

        modal.hidden = false;
        reasonInput.focus();
    }

    function closeReportModal() {
        modal.hidden = true;
    }

    // Open report modal
    document.addEventListener("click", (event) => {
        const button = event.target.closest(".report-button");

        if (!button) return;

        openReportModal(
            button.dataset.reportType,
            button.dataset.targetId
        );
    });

    // Close buttons
    modal.querySelector(".report-modal-close")
        ?.addEventListener("click", closeReportModal);

    modal.querySelector(".report-cancel-button")
        ?.addEventListener("click", closeReportModal);

    // Close when clicking outside the modal content
    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeReportModal();
        }
    });

    // Close with Escape
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !modal.hidden) {
            closeReportModal();
        }
    });

    // Submit report without reloading
    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        submitButton.disabled = true;
        submitButton.textContent = "Submitting...";

        message.hidden = true;

        try {
            const response = await fetch("report_submit.php", {
                method: "POST",
                body: new FormData(form),
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                }
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || data.error || "Could not submit report."
                );
            }

            message.textContent = data.message;
            message.classList.add("success");
            message.hidden = false;

            form.querySelectorAll("input, select, textarea")
                .forEach(input => {
                    if (input.type !== "hidden") {
                        input.disabled = true;
                    }
                });

            submitButton.textContent = "Report submitted";

        } catch (error) {
            console.error("Report submission error:", error);

            message.textContent = error.message;
            message.classList.add("error");
            message.hidden = false;

            submitButton.disabled = false;
            submitButton.textContent = "Submit report";
        }
    });
})();

// LIKE / UNLIKE COMMENTS WITHOUT RELOADING
document.addEventListener("submit", async (event) => {
    const form = event.target.closest(".comment-like-form");

    if (!form) return;

    event.preventDefault();

    const button = form.querySelector(".comment-like-button");
    const count = form.querySelector(".comment-like-count");

    if (!button || !count || button.disabled) return;

    button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: new FormData(form),
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            }
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Could not update comment like."
            );
        }

        // Update the button state.
        button.classList.toggle("active", data.liked);
        button.setAttribute("aria-pressed", String(data.liked));

        // Update the heart icon.
        button.textContent = data.liked ? "♥" : "♡";

        // Update the like count.
        count.textContent = data.like_count;

    } catch (error) {
        console.error("Comment like error:", error);
        alert(error.message || "Something went wrong. Please try again.");
    } finally {
        button.disabled = false;
    }
});



// REPLY TO COMMENTS
document.addEventListener("click", (event) => {
    const button = event.target.closest(".reply-toggle-button");

    if (!button) return;

    const comment = button.closest(".post-detail-comment");
    const form = comment?.querySelector(":scope > .comment-content > .comment-reply-form");

    if (!form) return;

    form.hidden = !form.hidden;

    if (!form.hidden) {
        form.querySelector("textarea")?.focus();
    }
});


// SUBMIT REPLY WITHOUT RELOADING
document.addEventListener("submit", async (event) => {
    const form = event.target.closest(".comment-reply-form");

    if (!form) return;

    event.preventDefault();

    const button = form.querySelector('button[type="submit"]');
    const textarea = form.querySelector("textarea");
    const comment = form.closest(".post-detail-comment");
    const repliesContainer = comment?.querySelector(":scope > .comment-content > .comment-replies");

    if (!button || !textarea || !repliesContainer) return;

    if (!textarea.value.trim()) {
        textarea.focus();
        return;
    }

    button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: new FormData(form),
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            }
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Could not post reply.");
        }

        const reply = document.createElement("article");
        reply.className = "post-detail-comment post-detail-reply";
        reply.dataset.commentId = data.comment_id;

        // AVATAR
        const avatarLink = document.createElement("a");
        avatarLink.href = `profile.php?id=${data.user_id}`;
        avatarLink.className = "comment-avatar-link";

        const avatar = document.createElement("img");
        avatar.className = "comment-avatar";
        avatar.src = data.profile_image || "images/default-profile.jpg";
        avatar.alt = `${data.username}'s profile picture`;

        avatarLink.appendChild(avatar);

        // REPLY CONTENT
        const content = document.createElement("div");
        content.className = "comment-content";

        // META
        const meta = document.createElement("div");
        meta.className = "comment-meta";

        const author = document.createElement("strong");
        const authorLink = document.createElement("a");

        authorLink.href = `profile.php?id=${data.user_id}`;
        authorLink.className = "comment-author-link";
        authorLink.textContent = data.username;

        author.appendChild(authorLink);

        const date = document.createElement("span");
        const parsedDate = new Date(data.created_at.replace(" ", "T"));

        date.textContent = parsedDate.toLocaleDateString("en-GB", {
            day: "numeric",
            month: "short"
        });

        meta.append(author, date);

        // DELETE FORM
        const deleteForm = document.createElement("form");
        deleteForm.action = "comment_delete.php";
        deleteForm.method = "POST";
        deleteForm.className = "delete-comment-form";

        const fields = {
            comment_id: data.comment_id,
            post_id: form.querySelector('[name="post_id"]').value,
            csrf_token: form.querySelector('[name="csrf_token"]').value,
            return_to: "post"
        };

        Object.entries(fields).forEach(([name, value]) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = name;
            input.value = value;
            deleteForm.appendChild(input);
        });

        const deleteButton = document.createElement("button");
        deleteButton.type = "submit";
        deleteButton.className = "delete-comment-button";
        deleteButton.textContent = "Delete";

        deleteForm.appendChild(deleteButton);
        meta.appendChild(deleteForm);

        content.appendChild(meta);

        // REPLY TEXT
        const text = document.createElement("p");
        text.textContent = data.comment;
        text.style.whiteSpace = "pre-line";

        content.appendChild(text);

        // LIKE BUTTON
        const actions = document.createElement("div");
        actions.className = "comment-actions";

        const likeForm = document.createElement("form");
        likeForm.action = "comment_like.php";
        likeForm.method = "POST";
        likeForm.className = "comment-like-form";

        const likeFields = {
            comment_id: data.comment_id,
            post_id: form.querySelector('[name="post_id"]').value,
            return_to: "post",
            csrf_token: form.querySelector('[name="csrf_token"]').value
        };

        Object.entries(likeFields).forEach(([name, value]) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = name;
            input.value = value;
            likeForm.appendChild(input);
        });

        const likeButton = document.createElement("button");
        likeButton.type = "submit";
        likeButton.className = "comment-like-button";
        likeButton.setAttribute("aria-label", "Like reply");
        likeButton.setAttribute("aria-pressed", "false");
        likeButton.textContent = "♡";

        const likeCount = document.createElement("span");
        likeCount.className = "comment-like-count";
        likeCount.textContent = "0";

        likeForm.append(likeButton, likeCount);
        actions.appendChild(likeForm);

        content.appendChild(actions);

        reply.append(avatarLink, content);
        repliesContainer.appendChild(reply);

        // UPDATE TOTAL COMMENT COUNT
        const commentCount = document.querySelector(
            ".post-detail-actions .comment-icon + span"
        );

        if (commentCount) {
            commentCount.textContent = data.comment_count;
        }

        // CLEAR AND CLOSE REPLY FORM
        textarea.value = "";
        form.hidden = true;

    } catch (error) {
        console.error("Reply submission error:", error);
        alert(error.message || "Something went wrong.");
    } finally {
        button.disabled = false;
    }
});
