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