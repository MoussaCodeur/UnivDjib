<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

$userID = $_SESSION['user_id'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des informations de l'utilisateur
    $userID = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id_personne, prenom, nom, email, role FROM personne WHERE id_personne = ?");
    $stmt->execute([$userID]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Utilisateur non trouvé.");
    }

    $prenom = $user['prenom'];
    $nom = $user['nom'];
    $email = $user['email'];
    $role = $user['role'];
    $initiales = strtoupper(substr($nom, 0, 1) . substr($prenom, 0, 1));
    $couleurAvatar = substr(md5($userID), 0, 6);

    // Récupération des statistiques
    $stats = [
        'enseignants' => $conn->query("SELECT COUNT(*) FROM enseignant")->fetchColumn(),
        'etudiants' => $conn->query("SELECT COUNT(*) FROM etudiant")->fetchColumn(),
        'filieres' => $conn->query("SELECT COUNT(*) FROM filiere")->fetchColumn(),
        'matieres' => $conn->query("SELECT COUNT(*) FROM matiere")->fetchColumn()
    ];

} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
} catch(Exception $e) {
    die($e->getMessage());
}

// Formatage de la date
date_default_timezone_set('Europe/Paris');
$heureActuelle = date('H:i');

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

$jourSemaine = date('l');
$mois = date('F');
$jourSemaineFr = $joursFrancais[$jourSemaine];
$moisFr = $moisFrancais[$mois];
$dateFormatee = $jourSemaineFr . ' ' . date('d') . ' ' . $moisFr . ' ' . date('Y');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Doyen - Université de djibouti</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/acceuil_doyen.css">
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
                        <a href="accueil_doyen.php">
                            <i class="fas fa-home"></i>
                            <span>Accueil</span>
                        </a>
                    </li>
                    <li>
                        <a href="profile_doyen.php">
                            <i class="fas fa-user"></i>
                            <span>Profil</span>
                        </a>
                    </li>
                    <li>
                        <a href="consultation_doyen.php">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Consultation</span>
                        </a>
                    </li>
                    <li>
                        <a href="statistiques_doyen.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Statistiques</span>
                        </a>
                    </li>
                    
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../../../config/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="search-bar">
                </div>
                
                <div class="date-time">
                    <div class="date"><?= $dateFormatee ?></div>
                    <div class="time"><?= $heureActuelle ?></div>
                </div>
                
                <div class="user-actions">
                    <div class="notification-bell">
                    </div>
                    
                    <div class="user-profile">
                        <div class="avatar"><?= $initiales ?></div>
                        <div class="user-info">
                            <h5><?= $nom . ' ' . $prenom ?></h5>
                            <p>Doyen</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1>Bienvenue, Doyen <?= $nom ?></h1>
                    <p>Gérez votre faculté et supervisez les activités académiques en toute simplicité</p>
                </div>
                <div class="welcome-image">
                    <img src="../../../public/assets/img/U-remove.png" alt="Doyen">
                </div>
            </div>

            <!-- Statistiques -->
            <div class="stats-section">
                <h2 class="section-title">Aperçu global</h2>
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['enseignants'] ?></div>
                        <div class="stat-label">Enseignants</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['etudiants'] ?></div>
                        <div class="stat-label">Étudiants</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['filieres'] ?></div>
                        <div class="stat-label">Filières</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['matieres'] ?></div>
                        <div class="stat-label">Matières</div>
                    </div>
                </div>
            </div>

            <!-- Fonctionnalités -->
            <div class="features-section">
                <h2 class="section-title">Fonctionnalités</h2>
                <div class="features-container">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h3 class="feature-title">Gestion des enseignants</h3>
                        <p class="feature-description">Ajoutez, modifiez ou supprimez des enseignants et affectez-les aux matières et filières.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Gestion des étudiants</h3>
                        <p class="feature-description">Inscrivez les étudiants, gérez leurs parcours académiques et consultez leurs résultats.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3 class="feature-title">Gestion des filières</h3>
                        <p class="feature-description">Créez et organisez les filières de votre faculté avec leurs programmes détaillés.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3 class="feature-title">Gestion des matières</h3>
                        <p class="feature-description">Organisez le catalogue des matières enseignées dans votre faculté.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mise à jour de l'heure en temps réel
        function updateTime() {
            const now = new Date();
            const options = { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: false
            };
            document.querySelector('.time').textContent = now.toLocaleTimeString('fr-FR', options);
        }

        // Mettre à jour l'heure toutes les minutes
        setInterval(updateTime, 60000);
        updateTime();

        // Animation des cartes au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .feature-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>