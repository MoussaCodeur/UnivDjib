function chargerMatieres(semestre, id_enseignant) {
    if (!semestre || !id_enseignant) return;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "charger_matieres.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById("matiere_select").innerHTML = xhr.responseText;
        }
    };

    xhr.send("semestre_et=" + encodeURIComponent(semestre) + "&id_enseignant=" + encodeURIComponent(id_enseignant));
}

function showUploadForm(type) {
    document.getElementById('overlay').style.display = 'block';
    document.getElementById('form-selection').style.display = 'block';
    document.getElementById('type_note').value = type;

    // Masquer tous les formulaires
    document.getElementById('form-cc').style.display = 'none';
    document.getElementById('form-tp').style.display = 'none';
    document.getElementById('form-cf').style.display = 'none';
}

function hideUploadForm() {
    document.getElementById('overlay').style.display = 'none';
    document.getElementById('form-selection').style.display = 'none';
    document.getElementById('form-cc').style.display = 'none';
    document.getElementById('form-tp').style.display = 'none';
    document.getElementById('form-cf').style.display = 'none';
}

// Affichage du chargement
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
    document.body.classList.add('blur-content');
    simulateProgress();
}

function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
    document.body.classList.remove('blur-content');
}

function simulateProgress() {
    let progress = 0;
    const progressBar = document.querySelector('.progress');
    const interval = setInterval(() => {
        progress += 5;
        progressBar.style.width = progress + '%';
        if (progress >= 100) clearInterval(interval);
    }, 150);
}

document.addEventListener('DOMContentLoaded', function () {
    if (window.hideLoadingAfterSubmit) {
        hideLoading();
    }

    // Pour tests/démos uniquement
    // setTimeout(showLoading, 1000);
    // setTimeout(hideLoading, 4000);
});
