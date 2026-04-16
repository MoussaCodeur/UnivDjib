document.addEventListener('DOMContentLoaded', function () {
    // ✅ Filtrage des aides
    const filterBtns = document.querySelectorAll('.filter-btn');
    const aideCards = document.querySelectorAll('.aide-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.filter;

            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            aideCards.forEach(card => {
                card.style.display = (filter === 'all' || card.classList.contains(filter)) ? 'block' : 'none';
            });
        });
    });

    // ✅ Animation au défilement avec Intersection Observer
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = 1;
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });

    aideCards.forEach(card => {
        card.style.opacity = 0;
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.5s ease-out';
        observer.observe(card);
    });
});
