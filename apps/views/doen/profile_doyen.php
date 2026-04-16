<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

$userID = $_SESSION['user_id'];


$sql = "SELECT id_personne, prenom, nom, email, role FROM personne WHERE id_personne = ?";
$stmt = $conn->prepare($sql);
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

// Changement mot de passe
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updatePasswordQuery = "UPDATE personne SET mot_de_passe = ? WHERE id_personne = ?";
        $stmtUpdatePassword = $conn->prepare($updatePasswordQuery);
        $stmtUpdatePassword->bind_param("ss", $hashed_password, $userID);
        
        if ($stmtUpdatePassword->execute()) {
            echo "<script>alert('Mot de passe mis à jour avec succès.');</script>";
        } else {
            echo "<script>alert('Erreur lors de la mise à jour du mot de passe.');</script>";
        }
        $stmtUpdatePassword->close();
    } else {
        echo "<script>alert('Les mots de passe ne correspondent pas.');</script>";
    }
}

$mois_fr = ['', 'jan', 'fév', 'mar', 'avr', 'mai', 'juin', 'juil', 'août', 'sep', 'oct', 'nov', 'déc'];
$jours_fr = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$jour_semaine = $jours_fr[date('w')];
$jour = date('d');
$mois = $mois_fr[date('n')];
$annee = date('Y');
$date = "$jour_semaine $jour $mois $annee";
$heure = date('H:i');

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Doyen | Université de djibouti</title>
    <link rel="stylesheet" href="../../../public/assets/css/profile_doyen.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <button class="mobile-nav-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <div class="dashboard">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="../../../public/assets/img/U-remove.png" alt="Logo U-Digital" style="width:150px;height:100px;background:white;border-radius:50%;margin-left:30px">
            </div>
            
            <nav class="sidebar-menu">
                <div class="menu-item">
                    <a href="accueil_doyen.php">
                        <i class="bi bi-house-door"></i>
                        <span>Accueil</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="profile_doyen.php" class="active">
                        <i class="bi bi-person"></i>
                        <span>Profil</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="consultation_doyen.php">
                        <i class="bi bi-people"></i>
                        <span>Consultation</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="statistiques_doyen.php">
                        <i class="bi bi-bar-chart"></i>
                        <span>Statistiques</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="../../../config/logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Déconnexion</span>
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <div class="breadcrumb">
                    <a href="acceuil_doyen.php">Accueil</a>
                    <span>/</span>
                    <strong>Profil</strong>
                </div>
                <div class="header-right">
                    <div class="date-time"><?php echo "$date $heure"; ?></div>
                    <div class="user-profile">
                        <div class="avatar"><?php echo strtoupper(substr($nom, 0, 1) . substr($prenom, 0, 1)); ?></div>
                        <div class="user-info">
                            <h3><?php echo "$nom $prenom"; ?></h3>
                            <p><?php echo $role; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <div class="profile-header">
                    <h2>Bienvenue, Doyen <?php echo $prenom; ?></h2>
                    <p>Gérez votre profil et vos informations en toute sécurité sur la plateforme U-Digital</p>
                    <img src="../../../public/assets/img/U-remove.png" alt="Photo de profil" class="profile-picture">
                </div>

                <div class="profile-body">
                    <div class="profile-info">
                        <div class="info-card">
                            <h4>Identifiant</h4>
                            <p><?php echo $userID; ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Rôle</h4>
                            <p><?php echo $role; ?> <span class="role-badge">Doyen</span></p>
                        </div>
                        <div class="info-card">
                            <h4>Nom complet</h4>
                            <p>Prof. <?php echo "$prenom $nom"; ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Adresse email</h4>
                            <p><?php echo $email; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="password-section">
                <div class="section-header">
                    <i class="bi bi-shield-lock"></i>
                    <div>
                        <h3>Sécurité du compte</h3>
                        <p>Pour la sécurité de votre compte, nous vous recommandons de changer régulièrement votre mot de passe.</p>
                    </div>
                </div>

                <button type="button" id="showPasswordForm" class="change-password-btn">
                    <i class="bi bi-key"></i> Modifier mon mot de passe
                </button>

                <form method="post" action="" id="passwordForm" class="password-form">
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" required placeholder="Entrez votre mot de passe actuel">
                    </div>
                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" required placeholder="Entrez votre nouveau mot de passe">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirmez votre nouveau mot de passe">
                    </div>
                    <div class="button-group">
                        <button type="button" id="hidePasswordForm" class="btn btn-secondary">
                            <i class="bi bi-x"></i> Annuler
                        </button>
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="bi bi-check2"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Toggle password form
        document.getElementById('showPasswordForm').addEventListener('click', function() {
            document.getElementById('passwordForm').style.display = 'block';
            this.style.display = 'none';
            document.getElementById('passwordForm').scrollIntoView({ behavior: 'smooth' });
        });

        document.getElementById('hidePasswordForm').addEventListener('click', function() {
            document.getElementById('passwordForm').style.display = 'none';
            document.getElementById('showPasswordForm').style.display = 'inline-flex';
        });

        // Mobile sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            this.classList.toggle('bi-list');
            this.classList.toggle('bi-x');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth <= 576) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target) && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    sidebarToggle.classList.remove('bi-x');
                    sidebarToggle.classList.add('bi-list');
                }
            }
        });

        // Maintain PHP functionality
        <?php if (isset($_POST['change_password'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('passwordForm').style.display = 'block';
            document.getElementById('showPasswordForm').style.display = 'none';
            document.getElementById('passwordForm').scrollIntoView();
        });
        <?php endif; ?>
    </script>
</body>
</html>