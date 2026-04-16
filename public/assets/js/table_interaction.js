// table-interaction.js
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        row.addEventListener('mouseover', function() {
            this.classList.add('active');
        });
        
        row.addEventListener('mouseout', function() {
            this.classList.remove('active');
        });
    });
});