<?php
    // Connexion à la base de données
    require_once '../../../config/db.php';

    $message = "";
    $messageType = "error";
    $token = isset($_GET['token']) ? $_GET['token'] : null;

    if ($token) {
        // Sécuriser la requête avec prepared statements
        $stmt = $conn->prepare("SELECT id_personne, email FROM password_resets WHERE token = ? AND expiry > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id_personne = $row['id_personne'];
            $email = $row['email'];

            // Si le formulaire est soumis
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $newPassword = $conn->real_escape_string($_POST['password']);
                
                if (strlen($newPassword) < 6) {
                    $message = "Le mot de passe doit contenir au moins 6 caractères.";
                    $messageType = "error";
                } else {
                    // Hasher le mot de passe
                    $newPasswordHashed = password_hash($newPassword, PASSWORD_DEFAULT);

                    // Mise à jour sécurisée du mot de passe
                    $stmt = $conn->prepare("UPDATE personne SET mot_de_passe = ? WHERE id_personne = ?");
                    $stmt->bind_param("si", $newPasswordHashed, $id_personne);
                    $stmt->execute();
                    $stmt->close();

                    // Suppression du token après succès
                    $stmt = $conn->prepare("DELETE FROM password_resets WHERE id_personne = ?");
                    $stmt->bind_param("i", $id_personne);
                    $stmt->execute();
                    $stmt->close();

                    $message = "Votre mot de passe a été mis à jour avec succès.";
                    $messageType = "success";
                }
            }
        } else {
            $message = "Le lien est invalide ou expiré.";
            $messageType = "error";
        }
    } else {
        $message = "Aucun token fourni.";
        $messageType = "error";
    }

    $conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - Université de Djibouti</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/reset_password.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <!-- Liens de navigation -->
    <a href="../../../index.php" class="home-link">
        <i class="fas fa-home"></i> Accueil
    </a>
    <a href="Connexion.php" class="login-link">
        <i class="fas fa-sign-in-alt"></i> Connexion
    </a>

    <div class="container">
        <!-- Barre de chargement animée -->
        <div class="loading"></div>

        <!-- Section illustration -->
        <div class="illustration">
            <div class="illustration-icon">
                <i class="fas fa-lock"></i>
            </div>
        </div>

        <!-- Formulaire de réinitialisation -->
        <div class="reset-form">
            <h1>Réinitialisation de mot de passe</h1>
            <p>Veuillez entrer votre nouveau mot de passe pour mettre à jour votre compte.</p>
            <h2>Nouveau mot de passe</h2>

            <form method="POST" action="" id="resetForm">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="password">Nouveau mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Entrez votre nouveau mot de passe" required>
                    <i class="fas fa-key input-icon"></i>
                </div>

                <button type="submit" class="reset-btn">
                    <i class="fas fa-check"></i> Réinitialiser le mot de passe
                </button>
            </form>
        </div>
    </div>

    <script>
        // Affichage/masquage du mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.querySelector('.form-group i.input-icon');
            
            passwordIcon.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Changer l'icône en fonction du type
                if (type === 'password') {
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-key');
                } else {
                    this.classList.remove('fa-key');
                    this.classList.add('fa-eye');
                }
            });

            // Animation lors de la soumission
            document.getElementById('resetForm').addEventListener('submit', function(e) {
                document.querySelector('.loading').style.display = 'block';
            });
        });
    </script>
</body>
</html>