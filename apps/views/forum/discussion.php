<?php 
// Démarrer la session
require_once '../../../config/session.php';

// Connexion à la base de données
require_once '../../../config/db.php';



$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0; 
$fichierJoint = null;  

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['message'])) {     
    $message = trim($_POST['message']);     
    $user_id = $_SESSION['user_id'];  // Assurez-vous que la session est bien démarrée     
    $fichierJoint = null;      
    
    // Gestion du fichier joint     
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {         
        $nomTemporaire = $_FILES['fichier']['tmp_name'];         
        $nomOriginal = basename($_FILES['fichier']['name']);         
        $destination = 'uploads/' . time() . '_' . $nomOriginal;          
        
        if (move_uploaded_file($nomTemporaire, $destination)) {             
            $fichierJoint = $destination;         
        }     
    }      
    
    // Insertion du message avec fichier_joint
    $sql = "INSERT INTO messages (topic_id, user_id, content, created_at, fichier_joint) VALUES (?, ?, ?, NOW(), ?)";     
    $stmt = $conn->prepare($sql);      
    
    if (!$stmt) {         
        die("Erreur SQL : " . $conn->error);  // Affiche l'erreur exacte de la requête     
    }      
    
    $stmt->bind_param("iiss", $topic_id, $user_id, $message, $fichierJoint);     
    $stmt->execute();          
    
    // Redirection ou message de confirmation         
    header("Location: discussion.php?id=" . $topic_id);         
    exit();     
}       

// Récupérer le sujet     
$topic = null;     
$stmt = $conn->prepare("SELECT t.title, t.created_at, u.prenom, u.nom FROM topics t JOIN personne u ON t.user_id = u.id_personne WHERE t.id = ?");     
$stmt->bind_param("i", $topic_id);     
$stmt->execute();     
$topic = $stmt->get_result()->fetch_assoc();      

// Récupérer les messages     
$stmt = $conn->prepare("SELECT m.content, m.created_at, m.fichier_joint, p.prenom, p.nom, m.user_id FROM messages m JOIN personne p ON m.user_id = p.id_personne WHERE m.topic_id = ? ORDER BY m.created_at ASC");     
$stmt->bind_param("i", $topic_id);     
$stmt->execute();     
$messages = $stmt->get_result(); 

// Vérifier si le sujet existe
if (!$topic) {
    header("Location: forum.php");
    exit();
}

// Fonction pour obtenir les initiales d'un nom
function getInitials($prenom, $nom) {
    return strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
}

// Fonction pour générer une couleur basée sur un ID utilisateur
function generateColor($userId) {
    $colors = [
        '#4a6fa5', // Bleu primaire
        '#6186b5', // Bleu secondaire
        '#f8b400', // Or/jaune
        '#45a049', // Vert
        '#e57373', // Rouge clair
        '#9575cd', // Violet
        '#4db6ac', // Turquoise
        '#ff8a65', // Orange
        '#78909c', // Bleu-gris
        '#aed581'  // Vert clair
    ];
    return $colors[$userId % count($colors)];
}

// Formater une date
function formatDateTime($dateTime) {
    $timestamp = strtotime($dateTime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return "À l'instant";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "Il y a " . $minutes . " minute" . ($minutes > 1 ? "s" : "");
    } elseif ($diff < 86400) {
        $heures = floor($diff / 3600);
        return "Il y a " . $heures . " heure" . ($heures > 1 ? "s" : "");
    } elseif ($diff < 172800) {
        return "Hier à " . date('H:i', $timestamp);
    } else {
        return date('d/m/Y à H:i', $timestamp);
    }
}
?>  

<!DOCTYPE html> 
<html lang="fr"> 
<head>     
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($topic['title']) ?>Discussion - Université de Djibouti<</title>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <!-- Animation AOS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    <link rel="stylesheet" href="../../../public/assets/css/discussion.css">
</head> 
<body>
    <header>
        <div class="container">
            <div>
                <h1 class="topic-title"><i class="fas fa-comments"></i> <?= htmlspecialchars($topic['title']) ?></h1>
                <div class="topic-meta">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($topic['prenom'] . ' ' . $topic['nom']) ?> 
                    <span style="margin: 0 8px;">•</span>
                    <i class="far fa-clock"></i> <?= formatDateTime($topic['created_at']) ?>
                </div>
            </div>
            <a href="forum.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Retour au forum
            </a>
        </div>
    </header>
    
    <div class="container">
        <div class="discussion-container" data-aos="fade-up">
            <div class="messages-header">
                <span><i class="fas fa-comment-dots"></i> Discussion</span>
                <span><?= $messages->num_rows ?> message(s)</span>
            </div>
            
            <div class="messages">
                <?php 
                $delay = 100;
                while ($msg = $messages->fetch_assoc()) : 
                    $isCurrentUser = ($msg['user_id'] == $_SESSION['user_id']);
                    $messageClass = $isCurrentUser ? 'my-message' : '';
                    $avatarColor = generateColor($msg['user_id']);
                ?>
                    <div class="message <?= $messageClass ?>" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                        <div class="message-avatar" style="background-color: <?= $avatarColor ?>;">
                            <?= getInitials($msg['prenom'], $msg['nom']) ?>
                        </div>
                        <div class="message-content">
                            <div class="message-header">
                                <div class="message-author">
                                    <?= htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']) ?>
                                    <?php if ($isCurrentUser): ?>
                                        <span class="current-user-indicator">Vous</span>
                                    <?php endif; ?>
                                </div>
                                <div class="message-date">
                                    <i class="far fa-clock"></i> <?= formatDateTime($msg['created_at']) ?>
                                </div>
                            </div>
                            <div class="message-text">
                                <?= nl2br(htmlspecialchars($msg['content'])) ?>
                            </div>
                            <?php if (!empty($msg['fichier_joint'])): ?>
                                <a href="<?= htmlspecialchars($msg['fichier_joint']) ?>" class="message-file" download>
                                    <i class="fas fa-paperclip"></i> Télécharger la pièce jointe
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    $delay += 50;
                endwhile; 
                ?>
            </div>
            
            <div class="reply-container" data-aos="fade-up">
                <form method="POST" enctype="multipart/form-data" class="reply-form">
                    <textarea name="message" placeholder="Répondre à cette discussion..." required></textarea>
                    <div class="form-footer">
                        <div class="file-input-container">
                            <label class="file-input-label">
                                <i class="fas fa-paperclip"></i> Joindre un fichier
                                <input type="file" name="fichier" id="fichierInput">
                            </label>
                            <div class="file-name" id="fileNameDisplay"></div>
                        </div>
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Envoyer
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
        
        // Afficher le nom du fichier sélectionné
        document.getElementById('fichierInput').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : '';
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            
            if (fileName) {
                fileNameDisplay.textContent = 'Fichier sélectionné: ' + fileName;
                fileNameDisplay.style.display = 'block';
            } else {
                fileNameDisplay.style.display = 'none';
            }
        });
        
        // Scroll vers le bas de la conversation
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.querySelector('.messages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
    </script>
</body> 
</html>