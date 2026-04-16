<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

$userID = $_SESSION['user_id'];


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
    $row = $result->fetch_assoc();
    $prenom = $row['prenom'];
    $nom = $row['nom'];
    $email = $row['email'];
    $role = $row['role'];
    
    // Vérifier si l'utilisateur est bien président du jury
    if ($role !== 'president_jury') {
        header("Location: Connexion.php");
        exit();
    }
    
    // Générer les initiales pour l'avatar
    $initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
    // Générer une couleur unique basée sur l'ID
    $couleurAvatar = substr(md5($userID), 0, 6);
} else {
    die("Utilisateur non trouvé.");
}

$stmt->close();
$conn->close();

// Obtenir la date et l'heure actuelles
date_default_timezone_set('Europe/Paris');
$dateActuelle = date('d/m/Y');
$heureActuelle = date('H:i');
$jourSemaine = date('l');
$mois = date('F');

// Traduire les noms de jours et mois en français
$joursFrancais = [
    'Monday' => 'Lundi',
    'Tuesday' => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi',
    'Friday' => 'Vendredi',
    'Saturday' => 'Samedi',
    'Sunday' => 'Dimanche'
];

$moisFrancais = [
    'January' => 'janvier',
    'February' => 'février',
    'March' => 'mars',
    'April' => 'avril',
    'May' => 'mai',
    'June' => 'juin',
    'July' => 'juillet',
    'August' => 'août',
    'September' => 'septembre',
    'October' => 'octobre',
    'November' => 'novembre',
    'December' => 'décembre'
];

$jourSemaineFr = $joursFrancais[$jourSemaine];
$moisFr = $moisFrancais[$mois];
$dateFormatee = $jourSemaineFr . ' ' . date('d') . ' ' . $moisFr . ' ' . date('Y');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>U-Digital | Tableau de bord Président du Jury</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/acceuil_president_jury.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <div class="dashboard">
        <!-- Barre latérale -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../../../public/assets/img/U-remove.png" alt="Logo U-Digital" style="width:150px;height:100px;background:white;border-radius:50%">
            </div>
            
            <nav class="sidebar-menu">
                <ul>
                    <li class="active">
                        <a href="acceuil_president_jury.php">
                            <i class="fas fa-home"></i>
                            <span>Accueil</span>
                        </a>
                    </li>
                    <li>
                        <a href="profil_jury.php">
                            <i class="fas fa-user"></i>
                            <span>Profil</span>
                        </a>
                    </li>
                    <li>
                        <a href="consulter_notes_president.php">
                            <i class="fas fa-chart-column"></i>
                            <span>Consultation des notes</span>
                        </a>
                    </li>
                    <li>
                        <a href="generer_attestations.php">
                            <i class="fas fa-check-circle"></i>
                            <span>Attestations</span>
                        </a>
                    </li>
                    <li>
                        <a href="statistiques.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Statistiques</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../../../config/logout.php" class="btn btn-outline" style="color: white; border-color: white;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div style="display: flex; justify-content: center; flex-grow: 1;">
                    <div class="date-time">
                        <div class="date"><?= $dateFormatee ?></div>
                        <div class="time"><?= $heureActuelle ?></div>
                    </div>
                </div>
                
                <div class="user-actions" style="display: flex; align-items: center;">
                    <div class="user-profile">
                        <div class="avatar"><?= $initiales ?></div>
                        <div class="user-info">
                            <h5><?= $prenom . ' ' . $nom ?></h5>
                            <p>Président du Jury</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1>Bienvenue à U-Digital, <?= $prenom ?></h1>
                    <p>Gérez les évaluations et l'anonymat des examens sur votre portail président du jury</p>
                </div>
                <div class="welcome-image">
                    <img src="../../../public/assets/img/U-remove.png"  alt="Président du Jury">
                </div>
            </div>

            <!-- Fonctionnalités -->
            <div class="features-section">
                <h2>Fonctionnalités du Président du Jury</h2>
                <div class="features-container">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-secret"></i>
                        </div>
                        <h3 class="feature-title">Attestations</h3>
                        <p class="feature-description">Gérez les numéros d'anonymat pour les examens et assurez l'équité des évaluations.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="feature-title">Validation Notes</h3>
                        <p class="feature-description">Validez et certifiez les notes des étudiants après les délibérations du jury.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="feature-title">Statistiques</h3>
                        <p class="feature-description">Générez et validez les attestations de réussite pour les étudiants.</p>
                    </div>
                    </div>
                   
            </div>
        </main>
    </div>

    <script>
        // Mettre à jour l'heure en temps réel
        function updateTime() {
            const now = new Date();
            const options = { hour: '2-digit', minute: '2-digit' };
            document.querySelector('.time').textContent = now.toLocaleTimeString('fr-FR', options);
        }

        // Mettre à jour l'heure toutes les minutes
        setInterval(updateTime, 60000);
        
        // Initialiser l'heure au chargement
        updateTime();
    </script>
</body>
</html>