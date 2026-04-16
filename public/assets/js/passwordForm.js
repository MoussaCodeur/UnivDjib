document.addEventListener('DOMContentLoaded', function () {
    const showBtn = document.getElementById('showPasswordForm');
    const hideBtn = document.getElementById('hidePasswordForm');
    const overlay = document.getElementById('overlay');
    const passwordForm = document.getElementById('passwordForm');

    if (showBtn && hideBtn && overlay && passwordForm) {
        showBtn.addEventListener('click', function () {
            overlay.style.display = 'block';
            passwordForm.style.display = 'block';
        });

        hideBtn.addEventListener('click', function () {
            overlay.style.display = 'none';
            passwordForm.style.display = 'none';
        });
    }
});
