<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion à la base de données
require_once '../../../config/db.php';


// Gestion de la recherche
$searchQuery = "";
$whereClause = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchQuery = trim($_GET['search']);
    $searchTerm = $conn->real_escape_string($searchQuery);
    $whereClause = "WHERE t.title LIKE '%$searchTerm%' OR u.prenom LIKE '%$searchTerm%' OR u.nom LIKE '%$searchTerm%'";
}

$sql = "SELECT t.id, t.title, t.created_at, u.prenom, u.nom
                FROM topics t 
                JOIN personne u ON t.user_id = u.id_personne 
                $whereClause
                ORDER BY t.created_at DESC";
try {
    $result = $conn->query($sql);
    
    // Si la requête échoue, afficher l'erreur
    if (!$result) {
        echo "Erreur MySQL: " . $conn->error;
        $result = null; // Pour éviter les erreurs plus tard
    }
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
    $result = null; // Pour éviter les erreurs plus tard
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum des Étudiants</title>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <!-- Animation AOS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    <link rel="stylesheet" href="../../../public/assets/css/forum.css">
</head>
<body>
    <header>
        <div class="container">
            <h1 class="page-title"><i class="fas fa-comments"></i> Forum des Étudiants</h1>
            <a href="nouveau_sujet.php" class="new-topic-btn">
                <i class="fas fa-plus-circle"></i> Nouveau sujet
            </a>

            <a href="../students/acceuil_etudiant.php" class="new-topic-btn">
                <i class="fas fa-arrow-left"></i> Retour au acceuil
            </a>
        </div>
    </header>
    
    <div class="container">
        <!-- Formulaire de recherche -->
        <div class="search-container" data-aos="fade-up">
            <form method="get" action="forum.php">
                <input type="text" name="search" placeholder="Rechercher un sujet ou un auteur..." value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit"><i class="fas fa-search"></i> Rechercher</button>
            </form>
        </div>
        
        <!-- Affichage des résultats de recherche -->
        <?php if (!empty($searchQuery)): ?>
            <div class="search-results" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-filter"></i> Résultats pour "<?= htmlspecialchars($searchQuery) ?>"
                <a href="forum.php" class="cancel-search">
                    <i class="fas fa-times"></i> Annuler la recherche
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Liste des sujets -->
        <div class="topics-container" data-aos="fade-up" data-aos-delay="200">
            <div class="topics-header">
                <span><i class="fas fa-list"></i> Sujets de discussion</span>
                <span><?= $result && is_object($result) ? $result->num_rows : 0 ?> sujet(s)</span>
            </div>
            
            <ul class="topics-list">
                <?php if ($result && is_object($result) && $result->num_rows > 0): ?>
                    <?php $delay = 300; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <li data-aos="fade-right" data-aos-delay="<?= $delay ?>">
                            <a href="discussion.php?id=<?= $row['id'] ?>" class="topic-title">
                                <i class="fas fa-comment-dots"></i> <?= htmlspecialchars($row['title']) ?>
                            </a>
                            <div class="topic-meta">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($row['prenom'] . ' ' . $row['nom']) ?> 
                                <span style="margin: 0 8px;">•</span>
                                <i class="far fa-clock"></i> <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                            </div>
                            <?php /* Pièce jointe désactivée car champ non disponible
                            <?php if (!empty($row['file_path'])): ?>
                                <a href="<?= htmlspecialchars($row['file_path']) ?>" class="file-download" download="<?= htmlspecialchars($row['file_name'] ?? 'fichier') ?>">
                                    <i class="fas fa-paperclip"></i> Télécharger le fichier joint
                                </a>
                            <?php endif; ?>
                            */ ?>
                        </li>
                        <?php $delay += 50; // Incrémente le délai pour chaque élément ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="empty-state" data-aos="fade-up">
                        <i class="fas fa-search"></i>
                        <p>Aucun sujet trouvé<?= !empty($searchQuery) ? ' pour votre recherche' : '' ?>.</p>
                        <?php if ($conn->error): ?>
                            <p class="error-message">Erreur: <?= htmlspecialchars($conn->error) ?></p>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
            </ul>
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
    </script>
</body>
</html>