// profile.js
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du formulaire de mot de passe
    const showPasswordBtn = document.getElementById('showPasswordForm');
    const hidePasswordBtn = document.getElementById('hidePasswordForm');
    const overlay = document.getElementById('overlay');
    const passwordForm = document.getElementById('passwordForm');

    // Vérifie que les éléments existent avant d'ajouter des écouteurs
    if (showPasswordBtn && hidePasswordBtn && overlay && passwordForm) {
        showPasswordBtn.addEventListener('click', function() {
            overlay.style.display = 'block';
            passwordForm.style.display = 'block';
        });

        hidePasswordBtn.addEventListener('click', function() {
            overlay.style.display = 'none';
            passwordForm.style.display = 'none';
        });
    }
});