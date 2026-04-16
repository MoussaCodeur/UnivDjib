// Animation pour la date et heure
function updateDateTime() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit', 
        minute: '2-digit'
    };

    const dateTimeStr = now.toLocaleDateString('fr-FR', options);
    // Vous pouvez utiliser cette valeur si vous souhaitez afficher la date/heure ailleurs
}

// Mettre à jour toutes les minutes
setInterval(updateDateTime, 60000);
updateDateTime();

// Animation au chargement
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.feature-card');
    cards.forEach((card, index) => {
        card.style.animation = `fadeInUp 0.5s ease-out ${index * 0.1}s forwards`;
        card.style.opacity = '0';
    });

    // Ajout dynamique de la keyframe
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
});
