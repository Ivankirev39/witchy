// Image Modal Functionality
const postImages = document.querySelectorAll(".post-detail-image");
const imageModal = document.getElementById("image-modal");
const modalImage = document.getElementById("modal-image");
const closeButton = document.querySelector(".image-modal-close");

if (postImages.length && imageModal && modalImage && closeButton) {
    postImages.forEach((image) => {
        image.addEventListener("click", () => {
            modalImage.src = image.src;
            modalImage.alt = image.alt;

            imageModal.classList.add("active");
            imageModal.setAttribute("aria-hidden", "false");
        });
    });

    closeButton.addEventListener("click", () => {
        imageModal.classList.remove("active");
        imageModal.setAttribute("aria-hidden", "true");
    });

    imageModal.addEventListener("click", (event) => {
        if (event.target === imageModal) {
            imageModal.classList.remove("active");
            imageModal.setAttribute("aria-hidden", "true");
        }
    });
}


// Profile Cover Image Preview Functionality
const coverInput = document.getElementById("cover_image");
const coverPreview = document.getElementById("cover_preview");

if (coverInput && coverPreview) {

    coverInput.addEventListener("change", function () {

        const file = this.files[0];

        if (!file) {
            return;
        }

        // Only allow image files
        if (!file.type.startsWith("image/")) {
            return;
        }

        const imageUrl = URL.createObjectURL(file);

        // Remove the WITCHY placeholder if it exists
        const placeholder = document.getElementById(
            "cover_preview_placeholder"
        );

        if (placeholder) {
            placeholder.remove();
        }

        // Check if an image already exists
        let previewImage = document.getElementById(
            "cover_preview_image"
        );

        // Create the image if it doesn't exist yet
        if (!previewImage) {

            previewImage = document.createElement("img");

            previewImage.id = "cover_preview_image";
            previewImage.alt = "New profile cover";

            coverPreview.appendChild(previewImage);
        }

        // Show the newly selected image
        previewImage.src = imageUrl;
    });
}

// Like / Unlike Functionality (AJAX)
const likeForms = document.querySelectorAll(".post-like-form");

likeForms.forEach((form) => {
    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const button = form.querySelector('button[type="submit"]');
        const heartCount = button?.querySelector("span");

        if (!button || !heartCount || button.disabled) {
            return;
        }

        // Prevent repeated clicks while the request is processing.
        button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                credentials: "same-origin"
            });

            if (!response.ok) {
                throw new Error("The like request failed.");
            }

            const data = await response.json();

            if (
                !data.success ||
                typeof data.liked !== "boolean" ||
                !Number.isInteger(Number(data.like_count))
            ) {
                throw new Error("Invalid server response.");
            }

            // Update the heart and count without reloading the page.
            button.classList.toggle("active", data.liked);
            button.firstChild.textContent = data.liked ? "♥ " : "♡ ";
            heartCount.textContent = data.like_count;

            button.setAttribute(
                "aria-label",
                data.liked ? "Unlike post" : "Like post"
            );
        } catch (error) {
            console.error("Could not update like:", error);
            alert("We couldn't update your like. Please try again.");
        } finally {
            button.disabled = false;
        }
    });
});