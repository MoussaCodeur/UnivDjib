<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';


// Récupérer l'ID de l'utilisateur depuis la session
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
    $id_personne = $row['id_personne'];
    $prenom = $row['prenom'];
    $nom = $row['nom'];
    $email = $row['email'];
    $role = $row['role'];
    
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
    <title>Tableau de bord Enseignant</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/acceuil_enseignant.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <div class="dashboard">
        <!-- Barre latérale moderne -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../../../public/assets/img/U-remove.png" alt="Logo Université">
            </div>
            
            <nav class="sidebar-menu">
                <ul>
                    <li class="active">
                        <a href="accueil_enseignant.php">
                            <i class="fas fa-home"></i>
                            <span>Accueil</span>
                        </a>
                    </li>
                    <li>
                        <a href="profile_enseignant.php">
                            <i class="fas fa-user"></i>
                            <span>Profil</span>
                        </a>
                    </li>
                    <li>
                        <a href="deposer_cours.php">
                            <i class="fas fa-book"></i>
                            <span>Dépôts des cours</span>
                        </a>
                    </li>
                    <li>
                        <a href="enseignant_planning.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Consulter les planning</span>
                        </a>
                    </li>
                    <li>
                        <a href="deposer_notes.php">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Dépôts des notes</span>
                        </a>
                    </li>
                    <li>
                        <a href="consulter_note.php">
                            <i class="fas fa-search"></i>
                            <span>Consulter les notes</span>
                        </a>
                    </li>
                    
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../../../config/logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </aside>

        <!-- Contenu principal élégant -->
        <main class="main-content">
            <!-- Header moderne -->
            <div class="header">
                <div class="user-profile">
                    <div class="avatar"><?= $initiales ?></div>
                    <div class="user-info">
                        <h5><?= $nom . ' ' . $prenom ?></h5>
                        <p><?= $id_personne ?> | Enseignant</p>
                    </div>
                </div>
            </div>

            <!-- Welcome Banner moderne -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1>Bienvenue, <?= $nom ?></h1>
                    <p>Gérez facilement vos cours, étudiants et activités pédagogiques depuis votre espace personnel.</p>
                </div>
            </div>

            <!-- Fonctionnalités - Design moderne -->
            <div class="features-section">
                <h2 class="section-title">Fonctionnalités principales</h2>
                <div class="features-container">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3 class="feature-title">Gestion des Cours</h3>
                        <p class="feature-description">Publiez et organisez vos supports de cours, ressources pédagogiques et documents pour vos étudiants.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3 class="feature-title">Évaluation</h3>
                        <p class="feature-description">Saisissez et gérez les notes de vos étudiants avec un système intuitif et personnalisable.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3 class="feature-title">Emploi du temps</h3>
                        <p class="feature-description">Consultez et planifiez vos séances de cours, réunions et événements académiques.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../../public/assets/js/main.js"></script>

</body>
</html>