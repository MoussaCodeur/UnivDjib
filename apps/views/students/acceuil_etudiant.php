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

// Requête pour obtenir les informations de l'utilisateur - Correction de la virgule manquante
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
    $userID = $etudiant['id_personne'];
    $role = $etudiant['role'];
} else {
    // Ne pas terminer le script, juste définir des valeurs par défaut
    $etudiant = [
        'prenom' => 'Non défini',
        'nom' => 'Non défini',
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
$etudiant['niveau_filiere'] = $niveau;

$stmtniveau->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <title>Acceuil Etudiant - Université de Djibouti<</title>
    <link rel="stylesheet" href="../../../public/assets/css/acceuil_etudiant.css">
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
                    <a href="acceuil_etudiant.php" class="active"><li><i class="bi-house"></i> Tableau de bord</li></a>
                    <a href="profile_etudiant.php"><li><i class="bi-person"></i> Mon profil</li></a>
                    <a href="recevoir_cours_etudiant.php"><li><i class="bi-book"></i> Mes cours</li></a>
                    <a href="etudiant_planning.php"><li><i class="bi-calendar"></i> Emploi du temps</li></a>
                    <a href="recevoir_note_etudiant.php"><li><i class="bi-journal"></i> Mes notes</li></a>
                    <a href="actualite.php"><li><i class="bi-newspaper"></i>Actualité</li></a>
                    <a href="../forum/forum.php"><li><i class="bi-envelope"></i> <span>Forum</span></li></a>
                    <a href="aide.php"><li><i class="bi-question-circle"></i>Aide</li></a>                    
                    <a href="setting.php"><li><i class="bi-gear"></i>Paramètres</li></a>
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

            <section class="welcome-banner">
                <div>
                    <h1><i>Bienvenue à U-Digital,</i> <?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></h1>
                    <p>Always stay updated in your student portal</p>
                </div>
                <img src="../../../public/assets/img/E-Learning.png" alt="Photo Étudiant">
            </section>

            <!-- Titre de section pour les fonctionnalités -->
            <h2 class="section-header">Nos fonctionnalités</h2>

            <!-- Section des fonctionnalités -->
            <section class="features-section">
                <a href="recevoir_cours_etudiant.php" class="feature-card">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi-book"></i>
                    </div>
                    <h3>Cours</h3>
                    <p>Accédez rapidement à tous vos cours et ressources pédagogiques.</p>
                </div>
                </a>
                <a href="recevoir_note_etudiant.php" class="feature-card">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi-journal-check"></i>
                    </div>
                    <h3>Notes</h3>
                    <p>Consultez vos résultats et suivez votre progression académique.</p>
                </div>
                </a>

                <a href="etudiant_planning.php" class="feature-card">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi-calendar-event"></i>
                    </div>
                    <h3>Planning</h3>
                    <p>Visualisez votre emploi du temps et les prochains événements.</p>
                </div>
                </a>
                
                <a href="../forum/forum.php" class="feature-card">
                <div class="feature-icon">
                    <i class="bi-chat-dots"></i>
                </div>
                <h3>Forum</h3>
                <p>Participez aux discussions avec vos camarades.</p>
                </a>

            </section>
        </main>
    </div>
    
    <script src="../../../public/assets/js/date_time.js"></script>

</body>
</html>