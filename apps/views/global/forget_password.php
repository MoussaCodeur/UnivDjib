<?php
// Debug PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../../../vendor/autoload.php'; // Inclure l'autoloader de Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Connexion à la base de données
require_once '../../../config/db.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);

    // Vérifier si l'email existe dans la table personne
    $sql = "SELECT id_personne FROM personne WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id_personne = $row['id_personne'];

        // Génération du token et expiration
        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime("+5 hours"));

        // Supprime les anciens tokens pour cet id_personne
        $conn->query("DELETE FROM password_resets WHERE id_personne = '$id_personne'");

        // Insère le nouveau token
        $stmt = $conn->prepare("INSERT INTO password_resets (id_personne, email, token, expiry) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $id_personne, $email, $token, $expiry);
        $stmt->execute();
        $stmt->close();

        // Préparer le lien de réinitialisation
        $resetLink = "https://www.gcnu-plateforme.free.nf/apps/views/global/reset_password.php?token=$token";

        // Envoyer l'email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gestioncoursuniversitaire@gmail.com';
            $mail->Password = 'jevu knsh qkus oxhf';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('gestioncoursuniversitaire@gmail.com', 'Universite de Djibouti');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Réinitialisation de votre mot de passe - Université de Djibouti';
            $mail->Body = '
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { color: #1e3a8a; text-align: center; }
                        .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
                        .button {
                            display: inline-block;
                            padding: 10px 20px;
                            background: linear-gradient(45deg, #ff5722, #ff7043);
                            color: white !important;
                            text-decoration: none;
                            border-radius: 5px;
                            font-weight: bold;
                            margin: 15px 0;
                        }
                        .footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Université de Djibouti</h2>
                            <h3>Réinitialisation de mot de passe</h3>
                        </div>
                        
                        <div class="content">
                            <p>Bonjour,</p>
                            
                            <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte sur la plateforme de Gestion de Cours Universitaires.</p>
                            
                            <p>Pour procéder à la réinitialisation, veuillez cliquer sur le bouton ci-dessous :</p>
                            
                            <p style="text-align: center;">
                                <a href="'.$resetLink.'" class="button">Réinitialiser mon mot de passe</a>
                            </p>
                            
                            <p>Ce lien expirera dans 5 heures. Si vous n\'avez pas demandé cette réinitialisation, veuillez ignorer cet e-mail ou nous contacter immédiatement.</p>
                            
                            <p>Cordialement,<br>L\'équipe de support technique<br>Université de Djibouti</p>
                        </div>
                        
                        <div class="footer">
                            <p>© '.date('Y').' Université de Djibouti - Tous droits réservés</p>
                            <p>Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.</p>
                        </div>
                    </div>
                </body>
                </html>
                ';
            $mail->send();
            $message = "Un e-mail de réinitialisation a été envoyé à votre adresse.";
        } catch (Exception $e) {
            $message = "Erreur lors de l'envoi de l'e-mail : {$mail->ErrorInfo}";
        }
    } else {
        $message = "Aucun compte trouvé avec cet e-mail.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Université de Djibouti</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/forget_password.css">
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
                <i class="fas fa-key"></i>
            </div>
        </div>

        <!-- Formulaire de réinitialisation -->
        <div class="reset-form">
            <h1>Mot de passe oublié</h1>
            <p>Entrez votre adresse e-mail pour recevoir un lien de réinitialisation de votre mot de passe.</p>
            <h2>Réinitialisation</h2>

            <form method="POST" action="" id="resetForm">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email" placeholder="Entrez votre adresse e-mail" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>

                <button type="submit" class="reset-btn">
                    <i class="fas fa-paper-plane"></i> Envoyer le lien
                </button>
            </form>
        </div>
    </div>

    <script>
        // Animation du formulaire
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            // Animation lors de la soumission
            document.querySelector('.loading').style.display = 'block';
        });
    </script>
</body>
</html>