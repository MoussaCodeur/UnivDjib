document.addEventListener('DOMContentLoaded', function () {
    // ✅ Affichage de la date et de l'heure en temps réel
    function updateDateTime() {
        const dateElement = document.getElementById("date-display");
        const timeElement = document.getElementById("time-display");

        if (dateElement && timeElement) {
            const now = new Date();

            // Format date : jour, mois année
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            let dateStr = now.toLocaleDateString('fr-FR', options);
            dateElement.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);

            // Format heure : hh:mm:ss
            let timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            timeElement.textContent = timeStr;
        }
    }

    // Mise à jour chaque seconde
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // ✅ Animation au survol des cartes de fonctionnalités
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transition = 'all 0.3s ease';
        });
    });
});
