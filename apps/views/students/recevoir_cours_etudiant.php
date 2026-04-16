<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion à la base de données
require_once '../../../config/db.php';

// Récupérer l'ID de l'étudiant connecté
$id_etudiant = $_SESSION['user_id'];
$result = null; // Initialiser la variable pour éviter les erreurs
$erreur = ""; // Variable pour stocker les messages d'erreur

// Vérifier si c'est une requête AJAX pour récupérer les matières
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['semestre_et']) && !isset($_POST['rechercher'])) {
    try {
        $semestre = $_POST['semestre_et'];

        // Requête pour récupérer les matières selon le semestre choisi
        $sql = "SELECT DISTINCT m.id_matiere, m.nom_matiere
                FROM etudiant et
                JOIN evaluer ev ON et.id_etudiant = ev.id_etudiant
                JOIN matiere m ON ev.id_matiere = m.id_matiere
                JOIN enseigner e ON m.id_matiere = e.id_matiere
                WHERE e.type_semestre = ? AND et.id_etudiant = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $semestre, $id_etudiant);
        $stmt->execute();
        $result = $stmt->get_result();

        // Générer les options pour le select
        $options = "<option value='' disabled selected>Choisir une matière</option>";
        while ($row = $result->fetch_assoc()) {
            // Ajouter l'attribut data-nom à chaque option
            $options .= "<option value='" . $row['id_matiere'] . "' data-nom='" . htmlspecialchars($row['nom_matiere']) . "'>" . htmlspecialchars($row['nom_matiere']) . "</option>";
        }

        echo $options;
        exit();
    } catch (Exception $e) {
        // En cas d'erreur, retourner un message d'erreur
        echo "<option value=''>Erreur: " . $e->getMessage() . "</option>";
        exit();
    }
}

// Récupérer les infos de l'étudiant connecté
$id_personne = $_SESSION['user_id'];
try {
    $sql = "SELECT p.id_personne, p.nom, p.prenom, p.role, p.email, f.nom_filiere, e.niveau_filiere
        FROM etudiant e
        INNER JOIN personne p ON p.id_personne = e.id_etudiant
        INNER JOIN filiere f ON f.id_filiere = e.id_filiere
        WHERE e.id_etudiant = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_personne);
    $stmt->execute();
    $result = $stmt->get_result();
    $etudiant = $result->fetch_assoc();
    $filiere = $etudiant['nom_filiere'];
    $nom = $etudiant['nom'];
    $prenom = $etudiant['prenom'];
    $userID = $etudiant['id_personne'];
    $role = $etudiant['role'];
    $niveau_etudiant = $etudiant['niveau_filiere'];
} catch (Exception $e) {
    $erreur = "Erreur lors de la récupération des informations de l'étudiant: " . $e->getMessage();
}

// Récupérer les semestres enseignés
try {
    $sql_semestres = "SELECT DISTINCT type_semestre FROM enseigner";
    $result_semestres = $conn->query($sql_semestres);
    $semestres = [];
    while ($row = $result_semestres->fetch_assoc()) {
        $semestres[] = $row['type_semestre'];
    }
} catch (Exception $e) {
    $erreur = "Erreur lors de la récupération des semestres: " . $e->getMessage();
    $semestres = []; // Initialiser un tableau vide en cas d'erreur
}

// Traiter le formulaire de recherche des ressources
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rechercher'])) {
    try {
        $id_matiere = isset($_POST['matier_et']) ? $_POST['matier_et'] : '';
        $type_ressource = isset($_POST['ressource_et']) ? $_POST['ressource_et'] : '';
        $semestre = isset($_POST['semestre_et']) ? $_POST['semestre_et'] : '';

        // Vérifier si les champs sont remplis
        if (empty($id_matiere) || empty($type_ressource) || empty($semestre)) {
            $erreur = "Veuillez remplir tous les champs du formulaire.";
        } else {
            // Requête pour récupérer les ressources filtrées
            $sql = "SELECT DISTINCT r.id_ressource, m.id_matiere, m.nom_matiere, r.type, r.date_depot, r.chemin_fichier 
                    FROM ressource r
                    INNER JOIN recevoir_ressources rs ON r.id_ressource = rs.id_ressource
                    INNER JOIN etudiant et ON et.id_etudiant = rs.id_etudiant
                    INNER JOIN matiere m ON m.id_matiere = r.id_matiere
                    INNER JOIN enseigner e ON m.id_matiere = e.id_matiere
                    WHERE et.id_etudiant = ? 
                    AND m.id_matiere = ?
                    AND r.type = ?
                    AND e.type_semestre = ?";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $id_etudiant, $id_matiere, $type_ressource, $semestre);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                $erreur = "Aucune ressource trouvée pour les critères sélectionnés.";
            }
        }
    } catch (Exception $e) {
        $erreur = "Erreur dans la requête : " . $e->getMessage();
    }
}

// Déterminer automatiquement le semestre actuel
$today = new DateTime();
$year = (int)$today->format('Y');
$month = (int)$today->format('m');

if ($month >= 9 || $month == 1 || $month == 2) {
    $semestre_actuel = "1";
} elseif ($month >= 3 && $month <= 6) {
    $semestre_actuel = "2";
} else {
    $semestre_actuel = ""; // Aucun semestre en juillet/août
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <title>Cours etudiant</title>
    <link rel="stylesheet" href="../../../public/assets/css/recevoir_cours_etudiant.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <div class="dashboard">
        <!-- Barre latérale -->
        <aside class="left-side">
            <div class="logo">
                <img src="../../../public/assets/img/U-remove.png" alt="Logo U-Digital">
            </div>
            <nav>
                <ul>
                    <a href="acceuil_etudiant.php"><li><i class="bi-house"></i> Tableau de bord</li></a>
                    <a href="profile_etudiant.php"><li><i class="bi-person"></i> Mon profil</li></a>
                    <a href="recevoir_cours_etudiant.php" class="active"><li><i class="bi-book"></i> Mes cours</li></a>
                    <a href="etudiant_planning.php"><li><i class="bi-calendar"></i> Emploi du temps</li></a>
                    <a href="recevoir_note_etudiant.php"><li><i class="bi-journal"></i> Mes notes</li></a>
                    <a href="actualite.php"><li><i class="bi-newspaper"></i> Actualité</li></a>
                    <a href="../forum/forum.php"><li><i class="bi-envelope"></i> <span>Forum</span></li></a>
                    <a href="aide.php"><li><i class="bi-question-circle"></i> Aide</li></a>
                    <a href="#"><li><i class="bi-gear"></i> Paramètres</li></a>
                    <a href="../../../config/logout.php" class="deconnection"><li><i class="bi-box-arrow-right"></i> Déconnexion</li></a>
                </ul>
            </nav>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <header>
                <!-- Date et Heure en temps réel -->
                <div class="date-time-display" id="date-time-display">
                    <i class="bi-calendar-date"></i> <span id="date-display"></span> | 
                    <i class="bi-clock"></i> <span id="time-display"></span>
                </div>
                
                <div class="profile-info">
                    
                    <!-- Profil de l'étudiant -->
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($etudiant['prenom'] . '+' . $etudiant['nom']) ?>&background=4361ee&color=fff" alt="User" class="user-avatar">
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></span>
                            <span class="user-role">Étudiant - <?= htmlspecialchars($etudiant['id_personne']) ?> - <?= htmlspecialchars($etudiant['niveau_filiere']) ?></span>
                        </div>
                    </div>
                </div>
            </header>
            <div class="form-container">
                <h2>Rechercher des Ressources</h2>
                <form id="ressourcesForm" method="post" action="">
                    <table class="form-table">
                        <tr>
                            <td class="form-label">Semestre</td>
                            <td>
                                <div class="select-wrapper">
                                    <i class="bi-calendar-event select-icon"></i>
                                    <select name="semestre_et" id="semestre_select" class="with-icon" onchange="chargerMatieres(this.value)">
                                        <?php
                                            echo "<option value='' disabled" . (empty($semestre_actuel) ? " selected" : "") . ">Choisir un semestre</option>";
                                            foreach ($semestres as $sem) {
                                                $selected = ($sem == $semestre_actuel) ? "selected" : "";
                                                echo "<option value='$sem' $selected>$sem</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td class="form-label">Matières</td>
                            <td>
                                <div class="select-wrapper">
                                    <i class="bi-book select-icon"></i>
                                    <select name="matier_et" id="matiere_select" class="with-icon">
                                        <option value="" disabled selected>Choisir une matière</option>
                                    </select>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="form-label">Type de Ressource</td>
                            <td>
                                <div class="select-wrapper">
                                    <i class="bi-file-earmark-text select-icon"></i>
                                    <select name="ressource_et" id="ressource_select" class="with-icon">
                                        <option value="" disabled selected>Indiquer le type de ressource</option>
                                        <option value="Cours">Cours</option>
                                        <option value="TD">TD</option>
                                        <option value="TP">TP</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" name="rechercher" value="1" class="search-btn">
                        <i class="bi-search"></i> Rechercher
                    </button>
                </form>
            </div>
        </main>
        <div id="overlay" <?php if (isset($_POST['rechercher'])) echo 'style="display:block;"'; ?>></div>
        <!-- Liste des ressources -->
        <div id="liste_ressources" <?php if (isset($_POST['rechercher'])) echo 'style="display:block;"'; else echo 'style="display:none;"'; ?>>
            <h2>Liste des Ressources</h2>
            <button onclick="masquerTableau()">X</button>
            
            <?php if (!empty($erreur)) { ?>
                <p style="color: red;"><?php echo $erreur; ?></p>
            <?php } elseif (isset($result) && $result && $result->num_rows > 0) { ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nom de la Matière</th>
                            <th>Type de Ressource</th>
                            <th>Date de Dépôt</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) { 
                            $url_fichier = str_replace(
                                "C:/wamp64/www/GestionDeCoursUniversitaire/",
                                "http://localhost/GestionDeCoursUniversitaire/",
                                $row['chemin_fichier']
                            );
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nom_matiere']); ?></td>
                                <td><?php echo htmlspecialchars($row['type']); ?></td>
                                <td><?php echo htmlspecialchars($row['date_depot']); ?></td>
                                <td>
                                    <!-- Bouton Consulter -->
                                    <a href="<?php echo $url_fichier; ?>" class="consulter-btn" target="_blank" style="margin-left:10px;">
                                        Consulter
                                    </a>
                                    <!-- Bouton Télécharger -->
                                    <a href="<?php echo $url_fichier; ?>" class="download-btn" download>
                                        Télécharger
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else if (isset($_POST['rechercher'])) { ?>
                <p>Aucune ressource trouvée.</p>
            <?php } ?>
        </div>

    </div>
    <script>
        function chargerMatieres(semestre) {
            if (!semestre) return;

            // Ajouter l'effet de chargement
            const matiereSelect = document.getElementById("matiere_select");
            const selectWrapper = matiereSelect.closest('.select-wrapper');
            selectWrapper.classList.add('loading-select');

            var xhr = new XMLHttpRequest();
            xhr.open("POST", window.location.href, true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    // Supprimer l'effet de chargement
                    selectWrapper.classList.remove('loading-select');
                    
                    if (xhr.status === 200) {
                        matiereSelect.innerHTML = xhr.responseText;
                        
                        // Réinitialiser le sélecteur de ressources
                        const ressourceSelect = document.getElementById("ressource_select");
                        ressourceSelect.value = "";
                        ressourceSelect.classList.remove('valid');
                        
                        // Vérifier si l'option TP doit être masquée
                        gererOptionTP();
                        
                        // Ajouter un message d'information
                        const formContainer = document.querySelector('.form-container');
                        const existingMessage = formContainer.querySelector('.form-message');
                        if (existingMessage) {
                            formContainer.removeChild(existingMessage);
                        }
                        
                        const messageDiv = document.createElement('div');
                        messageDiv.className = 'form-message info';
                        messageDiv.innerHTML = '<i class="bi-info-circle"></i> Les matières ont été chargées avec succès.';
                        formContainer.appendChild(messageDiv);
                        
                        // Animation pour faire disparaître le message après 3 secondes
                        setTimeout(() => {
                            messageDiv.style.opacity = '0';
                            messageDiv.style.transition = 'opacity 0.5s ease';
                            setTimeout(() => {
                                if (formContainer.contains(messageDiv)) {
                                    formContainer.removeChild(messageDiv);
                                }
                            }, 500);
                        }, 3000);
                    } else {
                        console.error("Erreur lors de la récupération des matières");
                        
                        // Ajouter un message d'erreur
                        const messageDiv = document.createElement('div');
                        messageDiv.className = 'form-message error';
                        messageDiv.innerHTML = '<i class="bi-exclamation-triangle"></i> Erreur lors du chargement des matières.';
                        document.querySelector('.form-container').appendChild(messageDiv);
                    }
                }
            };

            xhr.send("semestre_et=" + encodeURIComponent(semestre));
        }

        // Valider les sélections
        document.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', function() {
                if (this.value !== "") {
                    this.classList.add('valid');
                } else {
                    this.classList.remove('valid');
                }
            });
        });

        // Validation du formulaire avant soumission
        document.getElementById('ressourcesForm').addEventListener('submit', function(e) {
            const semestre = document.getElementById('semestre_select').value;
            const matiere = document.getElementById('matiere_select').value;
            const ressource = document.getElementById('ressource_select').value;
            
            if (!semestre || !matiere || !ressource) {
                e.preventDefault();
                
                // Afficher un message d'erreur
                const formContainer = document.querySelector('.form-container');
                const existingMessage = formContainer.querySelector('.form-message');
                if (existingMessage) {
                    formContainer.removeChild(existingMessage);
                }
                
                const messageDiv = document.createElement('div');
                messageDiv.className = 'form-message error';
                messageDiv.innerHTML = '<i class="bi-exclamation-triangle"></i> Veuillez remplir tous les champs.';
                formContainer.appendChild(messageDiv);
                
                // Mettre en évidence les champs non remplis
                if (!semestre) document.getElementById('semestre_select').style.borderColor = '#f44336';
                if (!matiere) document.getElementById('matiere_select').style.borderColor = '#f44336';
                if (!ressource) document.getElementById('ressource_select').style.borderColor = '#f44336';
            }
        });

        function masquerTableau() {
            const overlay = document.getElementById("overlay");
            const listeRessources = document.getElementById("liste_ressources");
            
            // Ajouter une classe pour l'animation de disparition
            listeRessources.classList.add('fadeOut');
            
            // Attendre que l'animation se termine avant de cacher complètement
            setTimeout(() => {
                overlay.style.display = "none";
                listeRessources.style.display = "none";
                listeRessources.classList.remove('fadeOut');
            }, 300);
        }

        // Ajouter une classe CSS pour l'animation de disparition
        document.head.insertAdjacentHTML('beforeend', `
            <style>
                @keyframes fadeOut {
                    from { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                    to { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
                }
                .fadeOut {
                    animation: fadeOut 0.3s ease forwards !important;
                }
            </style>
        `);

        // Liste des matières sans TP
        const matieresWithoutTP = [
            "Anglais V",
            "Intelligence Artificielle",
            "Anglais VI",
            "FTI",
            "Langages et Compilation"
        ];

        // Fonction pour gérer l'affichage de l'option TP
        function gererOptionTP() {
            const matiereSelect = document.getElementById("matiere_select");
            const ressourceSelect = document.getElementById("ressource_select");
            const optionTP = ressourceSelect.querySelector('option[value="TP"]');
            
            if (matiereSelect.selectedIndex > 0) {
                const matiereNom = matiereSelect.options[matiereSelect.selectedIndex].getAttribute('data-nom');
                
                // Vérifier si la matière est dans la liste des matières sans TP
                if (matieresWithoutTP.includes(matiereNom)) {
                    // Masquer l'option TP
                    if (optionTP) {
                        optionTP.style.display = "none";
                        // Si TP est déjà sélectionné, on déselectionne
                        if (ressourceSelect.value === "TP") {
                            ressourceSelect.value = "";
                            ressourceSelect.classList.remove('valid');
                        }
                    }
                } else {
                    // Afficher l'option TP
                    if (optionTP) {
                        optionTP.style.display = "";
                    }
                }
            }
        }

        // Modifier l'événement de changement de sélection de matière
        document.getElementById('matiere_select').addEventListener('change', function() {
            if (this.value !== "") {
                this.classList.add('valid');
                gererOptionTP();
            } else {
                this.classList.remove('valid');
            }
        });
        // Affichage de la date et de l'heure en temps réel
        function updateDateTime() {
            let dateElement = document.getElementById("date-display");
            let timeElement = document.getElementById("time-display");
            
            let now = new Date();
            
            // Format date: jour, mois année
            let options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            let dateStr = now.toLocaleDateString('fr-FR', options);
            dateElement.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
            
            // Format heure: hh:mm:ss
            let timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            timeElement.textContent = timeStr;
        }
        
        // Mettre à jour l'heure et la date chaque seconde
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Animation au survol des cartes de fonctionnalités
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transition = 'all 0.3s ease';
            });
        });
    </script>
</body>
</html>