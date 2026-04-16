<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion à la base de données
require_once '../../../config/db.php';



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $user_id = $_SESSION['user_id'];

    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO topics (user_id, title) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $title);
        if ($stmt->execute()) {
            header("Location: forum.php");
            exit();
        } else {
            $error = "Erreur lors de l'ajout du sujet.";
        }
    } else {
        $error = "Le titre du sujet est requis.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un nouveau sujet - Forum Universitaire</title>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <!-- Animation AOS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    <link rel="stylesheet" href="../../../public/assets/css/nouveau_sujet.css">
</head>
<body>
    <header>
        <div class="container">
            <h1 class="page-title">
                <i class="fas fa-plus-circle"></i> Nouveau sujet
            </h1>
            <a href="forum.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Retour au forum
            </a>
        </div>
    </header>
    
    <div class="container">
        <div class="form-container" data-aos="fade-up">
            <div class="form-header">
                <i class="fas fa-edit"></i> Créer un nouveau sujet de discussion
            </div>
            
            <div class="form-content">
                <?php if (!empty($error)): ?>
                    <div class="error-message" data-aos="fade-in">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="nouveau_sujet.php">
                    <div class="form-group">
                        <label for="title">Titre du sujet</label>
                        <input type="text" name="title" id="title" 
                               placeholder="Ex: Besoin d'aide pour le TD de mathématiques..." 
                               required 
                               value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                    </div>
                    
                    <div class="form-footer">
                        <a href="forum.php" class="back-link">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Publier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialiser AOS (Animation On Scroll)
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Focus sur le champ titre au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('title').focus();
        });
    </script>
</body>
</html><?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion à la base de données
require_once '../../../config/db.php';



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $user_id = $_SESSION['user_id'];

    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO topics (user_id, title) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $title);
        if ($stmt->execute()) {
            header("Location: forum.php");
            exit();
        } else {
            $error = "Erreur lors de l'ajout du sujet.";
        }
    } else {
        $error = "Le titre du sujet est requis.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un nouveau sujet - Forum Universitaire</title>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <!-- Animation AOS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    <link rel="stylesheet" href="../../../public/assets/css/nouveau_sujet.css">
</head>
<body>
    <header>
        <div class="container">
            <h1 class="page-title">
                <i class="fas fa-plus-circle"></i> Nouveau sujet
            </h1>
            <a href="forum.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Retour au forum
            </a>
        </div>
    </header>
    
    <div class="container">
        <div class="form-container" data-aos="fade-up">
            <div class="form-header">
                <i class="fas fa-edit"></i> Créer un nouveau sujet de discussion
            </div>
            
            <div class="form-content">
                <?php if (!empty($error)): ?>
                    <div class="error-message" data-aos="fade-in">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="nouveau_sujet.php">
                    <div class="form-group">
                        <label for="title">Titre du sujet</label>
                        <input type="text" name="title" id="title" 
                               placeholder="Ex: Besoin d'aide pour le TD de mathématiques..." 
                               required 
                               value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                    </div>
                    
                    <div class="form-footer">
                        <a href="forum.php" class="back-link">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Publier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialiser AOS (Animation On Scroll)
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Focus sur le champ titre au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('title').focus();
        });
    </script>
</body>
</html>