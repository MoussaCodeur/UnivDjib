<?php
// Activation des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Connexion a la base de donnees
require_once '../../../config/db.php';

// Initialisation des variables
$error = "";

// Traitement du formulaire après soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération et sécurisation des données du formulaire
    $userID = mysqli_real_escape_string($conn, $_POST['userID']);
    $password = $_POST['password']; // Ne pas hacher ici, vérifier avec password_verify()

    // Requête pour vérifier l'utilisateur dans la table `personne`
    $sql = "SELECT id_personne, mot_de_passe, role FROM personne WHERE id_personne = ?";
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
        $stored_password = $row['mot_de_passe'];

        // Vérification du mot de passe
        if (password_needs_rehash($stored_password, PASSWORD_DEFAULT) || !password_verify($password, $stored_password)) {
            // Si le mot de passe n'est pas chiffré ou ne correspond pas, vérifiez en clair
            if ($password === $stored_password) {
                // Mot de passe en clair correspond
                $_SESSION['user_id'] = $row['id_personne'];
                $_SESSION['role'] = $row['role'];
                redirectUser($row['role']);
            } else {
                $error = "ID ou mot de passe incorrect.";
            }
        } else {
            // Mot de passe chiffré correspond
            $_SESSION['user_id'] = $row['id_personne'];
            $_SESSION['role'] = $row['role'];
            redirectUser($row['role']);
        }
    } else {
        $error = "ID ou mot de passe incorrect.";
    }

    $stmt->close();
}

// Fermeture de la connexion
$conn->close();

// Fonction pour rediriger l'utilisateur en fonction de son rôle
function redirectUser($role) {
    switch ($role) {
        case 'etudiant':
            header("Location: ../students/acceuil_etudiant.php");
            exit();
        case 'enseignant':
            header("Location: ../teachers/acceuil_enseignant.php");
            exit();
        case 'assistant':
            header("Location: ../assistants/acceuil_assistant.php");
            exit();
        case 'president_jury':
            header("Location: ../jury/acceuil_president_jury.php");
            exit();
        case 'directeur':
            header("Location: ../directeur/directeur_etude.php");
            exit();
        case 'doyen':
            header("Location: ../doen/accueil_doyen.php");
            exit();
        case 'admin':
            header("Location: ../admin/admin_dashboard.php");
            exit();
        default:
            $error = "Rôle non reconnu.";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Université de Djibouti</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/connexion.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <!-- Lien de retour à l'accueil -->
    <a href="../../../index.php" class="home-link">
        <i class="fas fa-home"></i> Accueil
    </a>

    <div class="container">
        <!-- Barre de chargement animée -->
        <div class="loading"></div>

        <!-- Section Présentation -->
        <div class="presentation">
            <h1>Université de Djibouti</h1>
            <p>Votre portail d'accès à l'excellence académique. Enseignement, recherche et innovation au service de notre communauté.</p>
        </div>

        <!-- Formulaire de Connexion -->
        <div class="login-form">
            <h1>Bienvenue <span class="dynamic-text"></span></h1>
            <p>Accédez instantanément à un univers de ressources et de données depuis votre tableau de bord personnalisé 🚀</p>
            <h2>Connexion</h2>
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="userID">Identifiant</label>
                    <input type="text" id="userID" name="userID" placeholder="Entrez votre ID utilisateur" required>
                    <i class="fas fa-user input-icon"></i>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
                    <i class="fas fa-lock input-icon"></i>
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
                <a href="forget_password.php" class="forget-password">Mot de passe oublié ?</a>
            </form>
            <?php
            // Affichage du message d'erreur en cas de problème
            if (!empty($error)) {
                echo "<div class='error-message'><i class='fas fa-exclamation-circle'></i> $error</div>";
            }
            ?>
        </div>
    </div>

    <script>
        // Liste des rôles anonymisés pour les utilisateurs dfor site universitaire
        // const words = ["Étudiant", "Enseignant", "Assistant", "Administrateur", "Jury", "Chercheur"];
        const words = ["Apprenant", "Formateur", "Support", "Gestionnaire", "Évaluateur", "Contributeur"];

        let wordIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        const dynamicText = document.querySelector(".dynamic-text");
        
        function typeEffect() {
            const currentWord = words[wordIndex];
            
            if (isDeleting) {
                dynamicText.textContent = currentWord.substring(0, charIndex - 1);
                charIndex--;
            } else {
                dynamicText.textContent = currentWord.substring(0, charIndex + 1);
                charIndex++;
            }
            
            // Délai entre chaque caractère
            let typeSpeed = isDeleting ? 80 : 120;
            
            // Si le mot est complètement tapé
            if (!isDeleting && charIndex === currentWord.length) {
                // Pause avant de commencer à effacer
                typeSpeed = 1000;
                isDeleting = true;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                // Passer au mot suivant
                wordIndex = (wordIndex + 1) % words.length;
                // Pause avant de commencer le nouveau mot
                typeSpeed = 500;
            }
            
            setTimeout(typeEffect, typeSpeed);
        }
        
        // Démarrer l'effet de frappe
        typeEffect();
        
        // Fonctionnalité pour afficher/masquer le mot de passe
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Animation du formulaire de connexion
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // Animation lors de la soumission
            document.querySelector('.loading').style.display = 'block';
        });
    </script>
</body>
</html>