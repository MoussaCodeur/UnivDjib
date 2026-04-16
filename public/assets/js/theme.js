// theme.js
document.addEventListener('DOMContentLoaded', function() {
    // Au chargement de la page
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark');
        document.getElementById('themeToggle').innerHTML = '<i class="bi bi-sun"></i>';
    } else {
        document.documentElement.classList.remove('dark');
        document.getElementById('themeToggle').innerHTML = '<i class="bi bi-moon"></i>';
    }

    // Dans le toggle button
    document.getElementById('themeToggle').addEventListener('click', function() {
        document.documentElement.classList.toggle('dark');
        if (document.documentElement.classList.contains('dark')) {
            this.innerHTML = '<i class="bi bi-sun"></i>';
            localStorage.setItem('theme', 'dark');
        } else {
            this.innerHTML = '<i class="bi bi-moon"></i>';
            localStorage.setItem('theme', 'light');
        }
    });

    // Mobile Menu Toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('overlay').classList.toggle('active');
    });

    // Close menu when clicking overlay
    document.getElementById('overlay').addEventListener('click', function() {
        document.getElementById('sidebar').classList.remove('active');
        document.getElementById('overlay').classList.remove('active');
    });
});