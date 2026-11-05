
document.addEventListener("DOMContentLoaded", () => {
    const postPage = document.querySelector(".post-detail-page");

    if (!postPage || postPage.dataset.userId !== "0") return;

    const csrfToken = postPage.dataset.repliesCsrf;

    function createReplyElement(reply) {
        const article = document.createElement("article");
        article.className = "post-detail-comment post-detail-reply";
        article.dataset.commentId = reply.comment_id;

        const avatarLink = document.createElement("a");
        avatarLink.href = `profile.php?id=${encodeURIComponent(reply.user_id)}`;
        avatarLink.className = "comment-avatar-link";

        const avatar = document.createElement("img");
        avatar.className = "comment-avatar";
        avatar.src = reply.profile_image || "images/default-profile.jpg";
        avatar.alt = `${reply.username}'s profile picture`;

        avatarLink.appendChild(avatar);

        const content = document.createElement("div");
        content.className = "comment-content";

        const meta = document.createElement("div");
        meta.className = "comment-meta";

        const author = document.createElement("strong");
        const authorLink = document.createElement("a");

        authorLink.href = `profile.php?id=${encodeURIComponent(reply.user_id)}`;
        authorLink.className = "comment-author-link";
        authorLink.textContent = reply.username;

        author.appendChild(authorLink);

        const date = document.createElement("span");
        date.textContent = reply.created_at;

        meta.append(author, date);

        const message = document.createElement("p");
        message.textContent = reply.comment;
        message.style.whiteSpace = "pre-wrap";

        content.append(meta, message);
        article.append(avatarLink, content);

        return article;
    }

    async function loadReplies(commentId, section, button) {
        const repliesContainer = section.querySelector(".post-detail-replies");

        if (!repliesContainer || button.disabled) return;

        const formData = new FormData();

        formData.append("parent_comment_id", commentId);
        formData.append("offset", repliesContainer.dataset.offset);
        formData.append("csrf_token", csrfToken);

        button.disabled = true;

        try {
            const response = await fetch("get_comment_replies.php", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || "Could not load replies.");
            }

            data.replies.forEach(reply => {
                repliesContainer.appendChild(createReplyElement(reply));
            });

            repliesContainer.dataset.offset = data.next_offset;
            repliesContainer.dataset.loaded = "1";

            let moreButton = section.querySelector(".post-detail-load-more-replies");

            if (data.has_more) {
                if (!moreButton) {
                    moreButton = document.createElement("button");
                    moreButton.type = "button";
                    moreButton.className = "load-more-replies-button post-detail-load-more-replies";
                    moreButton.textContent = "View more replies";
                    section.appendChild(moreButton);
                }

                moreButton.hidden = repliesContainer.hidden;

            } else if (moreButton) {
                moreButton.remove();
            }

        } catch (error) {
            console.error("Load replies error:", error);
            repliesContainer.dataset.loaded = "0";
        } finally {
            button.disabled = false;
        }
    }

    postPage.addEventListener("click", async event => {
        const viewButton = event.target.closest(".post-detail-view-replies");

        if (viewButton) {
            const comment = viewButton.closest(".post-detail-comment");
            const section = viewButton.closest(".post-detail-reply-section");

            if (!comment || !section) return;

            const repliesContainer = section.querySelector(".post-detail-replies");

            if (!repliesContainer) return;

            const moreButton = section.querySelector(".post-detail-load-more-replies");

            if (!repliesContainer.hidden) {
                repliesContainer.hidden = true;
                viewButton.textContent = `View ${viewButton.dataset.replyCount} ${Number(viewButton.dataset.replyCount) === 1 ? "reply" : "replies"}`;
                viewButton.setAttribute("aria-expanded", "false");

                if (moreButton) {
                    moreButton.hidden = true;
                }

                return;
            }

            repliesContainer.hidden = false;
            viewButton.textContent = "Hide replies";
            viewButton.setAttribute("aria-expanded", "true");

            if (moreButton) {
                moreButton.hidden = false;
            }

            if (repliesContainer.dataset.loaded !== "1") {
                await loadReplies(
                    viewButton.dataset.commentId,
                    section,
                    viewButton
                );
            }

            return;
        }

        const moreButton = event.target.closest(".post-detail-load-more-replies");

        if (moreButton) {
            const section = moreButton.closest(".post-detail-reply-section");

            if (!section) return;

            const viewButton = section.querySelector(".post-detail-view-replies");

            if (!viewButton) return;

            await loadReplies(
                viewButton.dataset.commentId,
                section,
                moreButton
            );
        }
        const loadMoreButton = replySection.querySelector(".post-detail-load-more-replies");

        if (loadMoreButton) {
            loadMoreButton.hidden = true;
        }
    });
});
