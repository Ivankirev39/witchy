const imageInput = document.getElementById("image");
const preview = document.getElementById("imagePreview");
const previewImage = document.getElementById("imagePreviewImage");
const removeImage = document.getElementById("removeImage");

let previewUrl;

function clearImagePreview() {
    if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
        previewUrl = null;
    }

    imageInput.value = "";
    previewImage.removeAttribute("src");
    preview.hidden = true;
}

imageInput.addEventListener("change", () => {
    if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
        previewUrl = null;
    }

    const file = imageInput.files[0];

    if (!file) {
        preview.hidden = true;
        previewImage.removeAttribute("src");
        return;
    }

    previewUrl = URL.createObjectURL(file);
    previewImage.src = previewUrl;
    preview.hidden = false;
});

removeImage.addEventListener("click", clearImagePreview);