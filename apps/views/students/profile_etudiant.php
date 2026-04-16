<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion à la base de données
require_once '../../../config/db.php';

// Récupérer l'ID de l'utilisateur depuis la session
$userID = $_SESSION['user_id'];

// Initialiser les variables
$prenom = "";
$nom = "";
$role = "";
$niveau = "Non défini";
$email = "";

// Requête pour obtenir les informations de l'utilisateur
$sql = "SELECT id_personne, prenom, nom, email, role FROM personne WHERE id_personne = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erreur dans la requête SQL : " . $conn->error);
}

// Liage des paramètres
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $etudiant = $result->fetch_assoc();
    $prenom = $etudiant['prenom'];
    $nom = $etudiant['nom'];
    $email = $etudiant['email'];
    $role = $etudiant['role'];
} else {
    // Ne pas terminer le script, juste définir des valeurs par défaut
    $etudiant = [
        'prenom' => 'Non défini',
        'nom' => 'Non défini',
        'email' => 'Non défini',
        'role' => 'Non défini'
    ];
}
$stmt->close();

// REQUETE POUR AFFICHER LE NIVEAU DE L'ETUDIANT
$niveauQuery = "SELECT niveau_filiere FROM etudiant e, personne p WHERE e.id_etudiant = p.id_personne AND id_etudiant = ?";
$stmtniveau = $conn->prepare($niveauQuery);

if (!$stmtniveau) {
    die("Erreur dans la requête SQL : " . $conn->error);
}

// Liage des paramètres
$stmtniveau->bind_param("s", $userID);
$stmtniveau->execute();
$resultniveau = $stmtniveau->get_result();

if ($resultniveau && $resultniveau->num_rows > 0) {
    $rowniveau = $resultniveau->fetch_assoc();
    $niveau = $rowniveau['niveau_filiere'];
} else {
    $niveau = "Non spécifié"; // Valeur par défaut si aucun niveau n'est trouvé
}

// Ajouter le niveau à l'array $etudiant
$etudiant['id'] = $userID;
$etudiant['niveau_filiere'] = $niveau;

// Récupérer les semestres et les matières associées
$semestresQuery = "SELECT DISTINCT type_semestre FROM enseigner";
$stmtSemestres = $conn->prepare($semestresQuery);
$stmtSemestres->execute();
$resultSemestres = $stmtSemestres->get_result();
$semestres = [];

while ($row = $resultSemestres->fetch_assoc()) {
    $semestres[] = $row['type_semestre'];
}
$stmtSemestres->close();

// Tableau pour stocker les matières par semestre
$matieres_par_semestre = [];

foreach ($semestres as $semestre) {
    $sqlMatieres = "
        SELECT m.nom_matiere, m.id_matiere
        FROM matiere m
        JOIN enseigner en ON m.id_matiere = en.id_matiere
        WHERE en.type_semestre = ?
    ";
    $stmtMatieres = $conn->prepare($sqlMatieres);
    
    if (!$stmtMatieres) {
        die("Erreur dans la requête SQL des matières : " . $conn->error);
    }

    // Correction ici → "ss" au lieu de "ii"
    $stmtMatieres->bind_param("s", $semestre);
    $stmtMatieres->execute();
    $resultMatieres = $stmtMatieres->get_result();
    
    $matieres = [];
    while ($rowMatiere = $resultMatieres->fetch_assoc()) {
        $matieres[] = $rowMatiere;
    }
    
    $matieres_par_semestre[$semestre] = $matieres;
    $stmtMatieres->close();
}


// Requête pour obtenir des informations supplémentaires (exemple)
$additionalInfoQuery = "SELECT f.nom_filiere FROM filiere f , etudiant e  WHERE f.id_filiere =e.id_filiere AND id_etudiant = ?";
$stmtAddInfo = $conn->prepare($additionalInfoQuery);

if ($stmtAddInfo) {
    $stmtAddInfo->bind_param("s", $userID);
    $stmtAddInfo->execute();
    $resultAddInfo = $stmtAddInfo->get_result();
    
    if ($resultAddInfo && $resultAddInfo->num_rows > 0) {
        $additionalInfo = $resultAddInfo->fetch_assoc();
        $etudiant['nom_filiere'] = $additionalInfo['nom_filiere'];
    } else {
        $etudiant['nom_filiere'] = "Non spécifiée";
    }
    $stmtAddInfo->close();
}

$stmtniveau->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Profil Étudiant - U-Digital</title>
    <link rel="stylesheet" href="../../../public/assets/css/acceuil_etudiant.css">
    <link rel="stylesheet" href="../../../public/assets/css/profile_etudiant.css">
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
                    <a href="profile_etudiant.php" class="active"><li><i class="bi-person"></i> Mon profil</li></a>
                    <a href="recevoir_cours_etudiant.php"><li><i class="bi-book"></i> Mes cours</li></a>
                    <a href="etudiant_planning.php"><li><i class="bi-calendar"></i> Emploi du temps</li></a>
                    <a href="recevoir_note_etudiant.php"><li><i class="bi-journal"></i> Mes notes</li></a>
                    <a href="actualite.php"><li><i class="bi-newspaper"></i>Actualité</li></a>
                    <a href="../forum/forum.php"><li><i class="bi-envelope"></i> <span>Forum</span></li></a>
                    <a href="aide.php"><li><i class="bi-question-circle"></i>Aide</li></a>                    
                    <a href="#"><li><i class="bi-gear"></i>Paramètres</li></a>
                    <a href="../../../config/logout.php" ><li class="deconnection"><i class="bi-box-arrow-right"></i>Déconnexion</li></a>
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

            <!-- Section principale du profil -->
            <section class="profile-section">
                <div class="profile-header">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($etudiant['prenom'] . '+' . $etudiant['nom']) ?>&background=4361ee&color=fff&size=200" alt="Photo de profil" class="profile-picture">
                    <div class="profile-name">
                        <h2><?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></h2>
                        <p>Étudiant en <?= htmlspecialchars($etudiant['niveau_filiere']) ?>  <?= htmlspecialchars($etudiant['nom_filiere']) ?></p>
                    </div>
                </div>
                
                <h3 class="section-title">Informations personnelles</h3>
                <div class="profile-info-grid">
                    <div class="info-card">
                        <div class="info-title">
                            <i class="bi-person-badge"></i> ID Étudiant
                        </div>
                        <div class="info-value"><?= htmlspecialchars($etudiant['id']) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-title">
                            <i class="bi-envelope"></i> Adresse e-mail
                        </div>
                        <div class="info-value"><?= htmlspecialchars($etudiant['email']) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-title">
                            <i class="bi-list"></i> Matières par semestre
                        </div>
                        <div class="accordion" id="accordionMatieres">
                            <?php foreach ($semestres as $index => $semestre): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $index ?>">
                                        <button class="accordion-button <?= $index != 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="<?= $index == 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $index ?>">
                                            Semestre <?= htmlspecialchars($semestre) ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index == 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $index ?>" data-bs-parent="#accordionMatieres">
                                        <div class="accordion-body">
                                            <?php if (!empty($matieres_par_semestre[$semestre])): ?>
                                                <ul class="course-list">
                                                    <?php foreach ($matieres_par_semestre[$semestre] as $matiere): ?>
                                                        <li class="course-item">
                                                            <i class="bi-journal-text"></i> <?= htmlspecialchars($matiere['nom_matiere']) ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="no-course">Aucune matière trouvée pour ce semestre.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

    
                    <div class="info-card">
                        <div class="info-title">
                            <i class="bi-mortarboard"></i> Niveau d'études
                        </div>
                        <div class="info-value"><?= htmlspecialchars($etudiant['niveau_filiere']) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-title">
                            <i class="bi-book"></i> Filière
                        </div>
                        <div class="info-value"><?= htmlspecialchars($etudiant['nom_filiere']) ?></div>
                    </div>

                </div>
                
                
            </section>
            
        </main>
    </div>
    
    <script>
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

        // Gestion des onglets
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Retirer la classe active de tous les onglets
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                
                // Ajouter la classe active à l'onglet cliqué
                tab.classList.add('active');
                
                // Retirer la classe active de tous les contenus d'onglets
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
                
                // Afficher le contenu de l'onglet correspondant
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Gestion des onglets (après le code existant)
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Déterminer le groupe d'onglets parent
                const tabsContainer = tab.closest('.tabs');
                const tabContentContainer = tabsContainer.nextElementSibling;
                
                // Retirer la classe active de tous les onglets dans ce groupe
                tabsContainer.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                
                // Ajouter la classe active à l'onglet cliqué
                tab.classList.add('active');
                
                // Retirer la classe active de tous les contenus d'onglets dans ce groupe
                tabContentContainer.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
                
                // Afficher le contenu de l'onglet correspondant
                const tabId = tab.getAttribute('data-tab');
                tabContentContainer.querySelector('#' + tabId).classList.add('active');
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>