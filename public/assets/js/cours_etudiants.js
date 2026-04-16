function chargerMatieres(semestre) {
    if (!semestre) return;

    var xhr = new XMLHttpRequest();
    xhr.open("POST", window.location.href, true); // Envoie la requête au même fichier
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                document.getElementById("matiere_select").innerHTML = xhr.responseText;
            } else {
                console.error("Erreur lors de la récupération des matières");
            }
        }
    };

    xhr.send("semestre_et=" + encodeURIComponent(semestre));
}

function afficherTableau() {
    // Vérifier si tous les champs sont remplis
    const semestre = document.querySelector("select[name='semestre_et']").value;
    const matiere = document.querySelector("select[name='matier_et']").value;
    const ressource = document.querySelector("select[name='ressource_et']").value;

    if (!semestre || !matiere || !ressource) {
        alert("Veuillez remplir tous les champs du formulaire.");
        return;
    }

    // Afficher l'ID de l'étudiant et l'ID de la matière dans une alerte
    alert(`ID de l'étudiant : <?php echo $id_etudiant; ?>\nID de la matière : ${matiere}\nType de ressources : ${ressource}`);
    document.getElementById("overlay").style.display = "block";
    document.getElementById("liste_ressources").style.display = "block";
}

function masquerTableau() {
    document.getElementById("overlay").style.display = "none";
    document.getElementById("liste_ressources").style.display = "none";
}
