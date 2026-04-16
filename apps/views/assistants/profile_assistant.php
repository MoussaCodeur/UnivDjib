<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion à la base de données
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Profile Assistant - Universite de Djibouti</title>
    <link rel="stylesheet" href="../../../public/assets/css/profile_assistant.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <div class="dashboard">
        <!-- Mobile Menu Toggle -->
        <button class="menu-toggle" id="menuToggle">
            <i class="bi bi-list"></i>
        </button>

        <!-- Overlay for mobile menu -->
        <div class="mobile-overlay" id="mobileOverlay"></div>

        <!-- Barre latérale -->
        <aside class="sidebar" id="sidebar">
            <a href="profil_assistant.php" style="text-decoration: none; color: inherit;">
                <div class="sidebar-header">
                    <img src="../../../public/assets/img/U-remove.png" alt="Logo Université" class="sidebar-logo">
                    <h2>Assistant Panel</h2>
                </div>
            </a>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="acceuil_assistant.php"><i class="bi bi-house-door"></i> <span>Accueil</span></a>
                    </li>
                    <li class="active">
                        <a href="#"><i class="bi bi-person"></i> <span>Profil</span></a>
                    </li>
                    <li>
                        <a href="gestion_planning.php"><i class="bi bi-calendar-week"></i> <span>Gestion Planning</span></a>
                    </li>
                    <li>
                        <a href="liste_Evaluer.php"><i class="bi bi-list-check"></i> <span>Évaluations</span></a>
                    </li>
                </ul>
                
                <div class="logout-section">
                    <a href="../../../config/logout.php" class="logout-btn">
                        <i class="bi bi-box-arrow-right"></i> <span>Déconnexion</span>
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-title">
                    <h1>Profil Assistant</h1>
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

            <div class="profile">
                <div class="welcome">
                    <div>
                        <h2>Personnaliser votre profil</h2>
                        <p>Consultez votre profil | Changez votre mot de passe en un seul clic</p>
                    </div>
                    <img src="../../../public/assets/img/U-remove.png" alt="User">
                </div>

                <div class="categorie">
                    <div class="cat1">
                        <label>ID : <?php echo $userID; ?></label>
                        <label>Fonction : <?php echo $role; ?></label>
                    </div>
    
                    <div class="cat1">
                        <label>Nom : <?php echo $nom; ?></label>
                        <label>Prénom : <?php echo $prenom; ?></label>
                        <label>Email : <?php echo $email; ?></label>
                    </div>
                </div>

                <div class="password">
                    <label>Modifier votre mot de passe</label>
                    <button type="submit" id="showPasswordForm">Cliquez ici</button>
                </div>

                <div class="change-password">
                    <div id="passwordForm">
                        <form method="post">
                            <h2>Veuillez modifier vos mots de passe</h2>
                            <label>Nouveau mot de passe :</label>
                            <input type="password" name="new_password" required>
                            <label>Confirmer le mot de passe :</label>
                            <input type="password" name="confirm_password" required>
                            <div class="div-boutton">
                                <button type="button" class="retourner" id="hidePasswordForm">Retourner</button>
                                <button type="submit" class="button" name="change_password">Valider</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ajout de l'overlay pour le flou -->
                <div id="overlay"></div>
            </div>
        </main>
    </div>

    <!-- Dark Mode Toggle -->
    <button class="theme-toggle" id="themeToggle">
        <i class="bi bi-moon"></i>
    </button>
    
    <!-- Chargement des scripts JS -->
    <script src="../../../public/assets/js/theme.js"></script>
    <script src="../../../public/assets/js/profile_assistant.js"></script>
</body>
</html>