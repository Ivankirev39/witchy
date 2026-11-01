document.addEventListener("DOMContentLoaded", () => {
    const clickableRows = document.querySelectorAll(".admin-clickable-row");

    clickableRows.forEach((row) => {
        const navigateToRow = () => {
            const destination = row.dataset.href;

            if (destination) {
                window.location.href = destination;
            }
        };

        row.addEventListener("click", navigateToRow);

        row.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                navigateToRow();
            }
        });
    });
});