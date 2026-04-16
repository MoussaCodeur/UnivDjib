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
    $prenom = $row['prenom'];
    $nom = $row['nom'];
    $email = $row['email'];
    $role = $row['role'];
} else {
    die("Utilisateur non trouvé.");
}

$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil Assistant - Université de Djibouti</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="../../../public/assets/css/accueil_assistant.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <div class="dashboard">
        <!-- Mobile Menu Toggle -->
        <button class="menu-toggle" id="menuToggle">
            <i class="bi bi-list"></i>
        </button>

        <!-- Overlay for mobile menu -->
        <div class="overlay" id="overlay"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
           <a href="profile_assistant.php" style="text-decoration: none; color: inherit;">
                <div class="sidebar-header">
                    <img src="../../../public/assets/img/U-remove.png" alt="Logo Université" class="sidebar-logo">
                    <h2>Assistant Panel</h2>
                </div>
            </a>

            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="#"><i class="bi bi-house-door"></i> <span>Accueil</span></a>
                    </li>
                    <li>
                        <a href="profile_assistant.php"><i class="bi bi-person"></i> <span>Profil</span></a>
                    </li>
                    <li>
                        <a href="gestion_planning.php"><i class="bi bi-calendar-week"></i> <span>Gestion Planning</span></a>
                    </li>
                    <li>
                        <a href="liste_Evaluer.php"><i class="bi bi-list-check"></i> <span>Évaluations</span></a>
                    </li>
                </ul>
            </nav>
            
            <div class="logout-section">
                <a href="../../../config/logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right"></i> <span>Déconnexion</span>
                </a>
            </div>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-title">
                    <h1>Tableau de Bord</h1>
                    <p class="welcome-text">Bienvenue, <?php echo $prenom . ' ' . $nom; ?></p>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo $nom; ?></span>
                        <span class="user-role"><?php echo $role; ?></span>
                    </div>
                    <span class="user-id"><?php echo $userID; ?></span>
                </div>
            </header>

            <!-- Bannière de bienvenue -->
            <section class="welcome-banner">
                <div class="banner-content">
                    <h2>Bienvenue sur votre espace assistant</h2>
                    <p>Gérez facilement les plannings, les anonymats et les évaluations</p>
                </div>
                <div class="banner-image">
                    <img src="../../../public/assets/img/U-remove.png" alt="Illustration">
                </div>
            </section>

            <!-- Fonctionnalités -->
            <section>
                <h2 class="section-title">Fonctionnalités</h2>
                <div class="features-grid">
                    <a href="gestion_planning.php" class="feature-card">
                        <div class="card-icon">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <h3>Gestion Planning</h3>
                        <p>Déposez et gérez les plannings pour enseignants et étudiants</p>
                    </a>

                    <a href="liste_Evaluer.php" class="feature-card">
                        <div class="card-icon">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h3>Anonymats & Évaluations</h3>
                        <p>Attribuer un identifiant d'anonymat à chaque étudiant évalué par matière</p>
                    </a>
                </div>
            </section>
        </main>
    </div>

    <!-- Dark Mode Toggle -->
    <button class="theme-toggle" id="themeToggle">
        <i class="bi bi-moon"></i>
    </button>

   <script src="../../../public/assets/js/theme.js"></script>
</body>
</html>