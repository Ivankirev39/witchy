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