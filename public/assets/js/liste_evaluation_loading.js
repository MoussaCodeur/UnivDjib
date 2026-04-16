// loading.js
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const evaluationForm = document.getElementById('evaluationForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const uploadProgress = document.getElementById('uploadProgress');
    
    if (!loadingOverlay) return;
    
    function showLoading() {
        loadingOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideLoading() {
        loadingOverlay.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function simulateProgress() {
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 10;
            if (progress > 90) progress = 90;
            uploadProgress.style.width = progress + '%';
        }, 300);
        
        setTimeout(() => {
            clearInterval(interval);
            uploadProgress.style.width = '100%';
        }, 3000);
    }
    
    if (filterForm) {
        filterForm.addEventListener('submit', showLoading);
    }
    
    if (evaluationForm) {
        evaluationForm.addEventListener('submit', function() {
            showLoading();
            simulateProgress();
        });
    }
});