
document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("help-search");
    const categoryButtons = document.querySelectorAll(".help-category");
    const faqItems = document.querySelectorAll(".help-faq-item");
    const noResults = document.getElementById("help-no-results");
    let activeCategory = "all";

    function filterQuestions() {
        const searchTerm = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        faqItems.forEach(item => {
            const matchesCategory = activeCategory === "all" || item.dataset.category === activeCategory;
            const matchesSearch = item.textContent.toLowerCase().includes(searchTerm);
            const isVisible = matchesCategory && matchesSearch;

            item.hidden = !isVisible;

            if (!isVisible) {
                item.open = false;
            } else {
                visibleCount++;
            }
        });

        noResults.hidden = visibleCount > 0;
    }

    categoryButtons.forEach(button => {
        button.addEventListener("click", () => {
            activeCategory = button.dataset.category;

            categoryButtons.forEach(category => {
                const isActive = category === button;
                category.classList.toggle("active", isActive);
                category.setAttribute("aria-pressed", String(isActive));
            });

            filterQuestions();
        });
    });

    searchInput.addEventListener("input", filterQuestions);
});
