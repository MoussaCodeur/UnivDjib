function showUploadForm(type) {
    document.getElementById('overlay').style.display = 'block';
    document.getElementById('uploadFormContainer').style.display = 'block';
    document.getElementById('type_ressource').value = type;
}

function hideUploadForm() {
    document.getElementById('overlay').style.display = 'none';
    document.getElementById('uploadFormContainer').style.display = 'none';
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

// Changement de filière -> charger niveaux
document.addEventListener('DOMContentLoaded', () => {
    const filiereSelect = document.getElementById('filiere');
    const niveauSelect = document.getElementById('niveau');
    const semestreSelect = document.getElementById('semestre');
    const matiereSelect = document.getElementById('matiere');

    if (filiereSelect) {
        filiereSelect.addEventListener('change', function() {
            const filiere = this.value;
            if (filiere) {
                fetch('getNiveaux.php?filiere=' + encodeURIComponent(filiere))
                    .then(response => response.json())
                    .then(data => {
                        niveauSelect.innerHTML = '<option value="">Sélectionnez un niveau</option>';
                        data.forEach(niveau => {
                            const option = document.createElement('option');
                            option.value = niveau;
                            option.textContent = niveau;
                            niveauSelect.appendChild(option);
                        });
                    });
            } else {
                niveauSelect.innerHTML = '<option value="">Sélectionnez d\'abord une filière</option>';
            }
        });
    }

    if (semestreSelect) {
        semestreSelect.addEventListener('change', function() {
            const semestre = this.value;
            if (semestre) {
                fetch('getMatieres.php?semestre=' + encodeURIComponent(semestre))
                    .then(response => response.json())
                    .then(data => {
                        matiereSelect.innerHTML = '<option value="">Sélectionnez une matière</option>';
                        data.forEach(matiere => {
                            const option = document.createElement('option');
                            option.value = matiere.id_matiere;
                            option.textContent = matiere.nom_matiere;
                            matiereSelect.appendChild(option);
                        });
                    });
            } else {
                matiereSelect.innerHTML = '<option value="">Sélectionnez d\'abord un semestre</option>';
            }
        });
    }

    // Exécuter hideLoading si la page est rechargée après soumission
    if (window.hideLoadingAfterSubmit) {
        hideLoading();
    }
});
