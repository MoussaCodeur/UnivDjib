<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion à la base de données
require_once '../../../config/db.php';

// Récupérer l'ID de l'utilisateur depuis la session
$userID = $_SESSION['user_id'];

// Initialiser les variables
$prenom = "";
$nom = "";
$role = "";
$niveau = "Non défini";

// Requête pour obtenir les informations de l'utilisateur
$sql = "SELECT id_personne, prenom, nom, email, role FROM personne WHERE id_personne = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erreur dans la requête SQL : " . $conn->error);
}

$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $enseignant = $result->fetch_assoc();
    $prenom = $enseignant['prenom'];
    $nom = $enseignant['nom'];
    $userID = $enseignant['id_personne'];
    $role = $enseignant['role'];
} else {
    $enseignant = [
        'prenom' => 'Non défini',
        'nom' => 'Non défini',
        'role' => 'Non défini'
    ];
}
$stmt->close();


// REQUETE POUR AFFICHER LES PLANNINGS DE L'ÉTUDIANT CONNECTÉ
$plannings = [];
$planningQuery = "SELECT p.type_planning, p.date_depot, p.type_semestre, p.chemin_planning
                  FROM planning p 
                  WHERE p.role_personne = 'enseignant' 
                  AND p.id_personne = ?
                  ORDER BY p.date_depot DESC";
$stmtPlanning = $conn->prepare($planningQuery);

if ($stmtPlanning) {
    // Liez le paramètre avec l'ID de l'utilisateur connecté
    $stmtPlanning->bind_param("s", $userID);
    $stmtPlanning->execute();
    $resultPlanning = $stmtPlanning->get_result();
    
    if ($resultPlanning && $resultPlanning->num_rows > 0) {
        while ($row = $resultPlanning->fetch_assoc()) {
            $plannings[] = $row;
        }
    }
}

$stmtPlanning->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planning Enseignant - Université de Djibouti</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../../public/assets/css/enseignant_planning.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <div class="dashboard">
        <!-- Barre latérale moderne -->
        <aside class="left-side">
            <div class="logo">
                <img src="../../../public/assets/img/U-remove.png" alt="Logo U-Digital">
            </div>
            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <a href="acceuil_enseignant.php">
                            <i class="bi bi-house"></i>
                            <span>Accueil</span>
                        </a>
                    </li>
                    <li>
                        <a href="profile_enseignant.php">
                            <i class="bi bi-person"></i>
                            <span>Profil</span>
                        </a>
                    </li>
                    <li>
                        <a href="deposer_cours.php">
                            <i class="bi bi-book"></i>
                            <span>Dépôt des cours</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="enseignant_planning.php">
                            <i class="bi bi-calendar"></i>
                            <span>Consulter le planning</span>
                        </a>
                    </li>
                    <li>
                        <a href="deposer_notes.php">
                            <i class="bi bi-journal"></i>
                            <span>Dépôt des notes</span>
                        </a>
                    </li>
                    <li>
                        <a href="consulter_note.php">
                            <i class="bi bi-search"></i>
                            <span>Consulter les notes</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../../../config/logout.php" class="btn-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </aside>

        <!-- Contenu principal élégant -->
        <main class="main-content">
            <header>
                <!-- Date et Heure en temps réel -->
                <div class="date-time-display" id="date-time-display">
                    <i class="bi bi-calendar-date"></i> <span id="date-display"></span> | 
                    <i class="bi bi-clock"></i> <span id="time-display"></span>
                </div>
                
                <div class="profile-info">
                    <!-- Profil de l'enseignant -->
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($enseignant['prenom'] . '+' . $enseignant['nom']) ?>&background=4361ee&color=fff" alt="User" class="user-avatar">
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($enseignant['prenom'] . ' ' . $enseignant['nom']) ?></span>
                            <span class="user-role">Enseignant - <?= htmlspecialchars($enseignant['id_personne']) ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Section principale -->
            <section class="content-section">
                <div class="section-header">
                    <h1><i class="bi bi-calendar-week"></i> Emplois du temps disponibles</h1>
                    <p>Consultez et téléchargez les plannings de cours et d'examens</p>
                </div>

                <div class="card">
                    <div class="card-body">
                        <!-- Boutons de filtrage -->
                        <div class="filters-container">
                            <div class="filter-group">
                                <label for="semestreFilter"><i class="bi bi-filter-circle"></i> Semestre</label>
                                <select id="semestreFilter" class="form-filter">
                                    <option value="">Tous les semestres</option>
                                    <option value="1">Semestre 1</option>
                                    <option value="2">Semestre 2</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="typeFilter"><i class="bi bi-funnel"></i> Type</label>
                                <select id="typeFilter" class="form-filter">
                                    <option value="">Tous les types</option>
                                    <option value="cours">Cours</option>
                                    <option value="examen">Examen</option>
                                    <option value="rattrapage">Rattrapage</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="dateFilter"><i class="bi bi-calendar-range"></i> Période</label>
                                <select id="dateFilter" class="form-filter">
                                    <option value="">Toutes dates</option>
                                    <option value="7">7 derniers jours</option>
                                    <option value="30">30 derniers jours</option>
                                    <option value="90">3 derniers mois</option>
                                    <option value="365">Cette année</option>
                                </select>
                            </div>
                            
                            <button id="resetFilters" class="btn-reset">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table id="planningsTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type de planning</th>
                                        <th>Type de semestre</th>
                                        <th>Date de dépôt</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plannings as $planning): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(ucfirst($planning['type_planning'])) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($planning['type_semestre'])) ?></td>
                                            <td data-order="<?= strtotime($planning['date_depot']) ?>">
                                                <?= date('d/m/Y', strtotime($planning['date_depot'])) ?>
                                            </td>
                                            <td class="actions">
                                                <a href="<?= htmlspecialchars($planning['chemin_planning']) ?>" target="_blank" class="btn-view" title="Consulter">
                                                    <i class="bi bi-eye"></i> <span class="action-text">Voir</span>
                                                </a>
                                                <a href="<?= htmlspecialchars($planning['chemin_planning']) ?>" download class="btn-download" title="Télécharger">
                                                    <i class="bi bi-download"></i> <span class="action-text">Télécharger</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script src="../../../public/assets/js/planning_enseignant.js"></script>
</body>
</html>