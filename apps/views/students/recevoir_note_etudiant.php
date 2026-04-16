<?php

// Démarrer la session
require_once '../../../config/session.php';

// Connexion à la base de données
require_once '../../../config/db.php';


// Définir matieresWithoutTP ici pour qu'il soit disponible partout
$matieresWithoutTP = [
    "Anglais V",
    "Intelligence Artificielle",
    "Anglais VI",
    "FTI",
    "Langages et Compilation"
];

// Récupération des informations étudiant
$user_id = $_SESSION['user_id'];
$etudiant = [];
$niveaux = [];

try {
    // Informations de base de l'étudiant
    $stmt = $conn->prepare("SELECT p.id_personne, p.prenom, p.nom, e.niveau_filiere, f.nom_filiere
                            FROM personne p
                            JOIN etudiant e ON p.id_personne = e.id_etudiant
                            JOIN filiere f ON e.id_filiere = f.id_filiere
                            WHERE p.id_personne = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $etudiant = $result->fetch_assoc();
    $stmt->close();

    if (!$etudiant) {
        throw new Exception("Profil étudiant non trouvé");
    }

    // Récupération des niveaux disponibles
    $result = $conn->query("SELECT DISTINCT niveau_filiere FROM etudiant ORDER BY niveau_filiere");
    $niveaux = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Gestion des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Démarrer la bufferisation pour capturer les éventuelles sorties non désirées
    ob_start();
    
    
    try {
        header('Content-Type: application/json');

        if (isset($_POST['get_semestres'])) {
            $niveau = $_POST['niveau_filiere'];
            
            $stmt = $conn->prepare("SELECT DISTINCT e.type_semestre 
                                  FROM enseigner e
                                  JOIN etudiant et ON et.niveau_filiere = ?
                                  WHERE et.niveau_filiere = ?");
            $stmt->bind_param("ss", $niveau, $niveau);
            $stmt->execute();
            $semestres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (empty($semestres)) {
                echo json_encode(['error' => 'Aucun semestre disponible pour ce niveau']);
                exit();
            }
            
            echo json_encode($semestres);
            exit();
        }

        if (isset($_POST['get_matieres'])) {
            $semestre = $_POST['semestre'];
            $niveau = $_POST['niveau_filiere'];
            
            $stmt = $conn->prepare("SELECT DISTINCT m.id_matiere, m.nom_matiere, m.coeff
                                  FROM matiere m
                                  JOIN enseigner e ON m.id_matiere = e.id_matiere
                                  JOIN etudiant et ON et.niveau_filiere = ?
                                  WHERE e.type_semestre = ? AND et.niveau_filiere = ?");
            $stmt->bind_param("sss", $niveau, $semestre, $niveau);
            $stmt->execute();
            $matieres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (empty($matieres)) {
                echo json_encode(['error' => 'Aucune matière disponible pour ce semestre']);
                exit();
            }
            
            echo json_encode($matieres);
            exit();
        }
        
        if (isset($_POST['get_notes'])) {
            $matiere_id = $_POST['matiere_id'];
            $semestre = $_POST['semestre'];
            
            // À ajuster selon ta logique : session ou POST
            $user_id = $_SESSION['user_id']; // ou $_POST['user_id'];

            // Récupérer les infos de la matière
            $stmt = $conn->prepare("SELECT nom_matiere, coeff FROM matiere WHERE id_matiere = ?");
            $stmt->bind_param("i", $matiere_id);
            $stmt->execute();
            $matiere = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$matiere) {
                echo json_encode(['error' => 'Matière introuvable']);
                exit();
            }

            // Récupérer les notes
            $stmt = $conn->prepare("SELECT e.cc, e.tp, a.cf
                                    FROM evaluer e
                                    JOIN etudiant et ON e.id_etudiant = et.id_etudiant
                                    JOIN anonymat a ON e.id_anonymat = a.id_anonymat
                                    WHERE e.id_etudiant = ? AND e.id_matiere = ?");
            $stmt->bind_param("ii", $user_id, $matiere_id);
            $stmt->execute();
            $notes = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$notes) {
                echo json_encode(['error' => 'Aucune note disponible pour cette matière']);
                exit();
            }

            $response = [
                'nom_matiere' => $matiere['nom_matiere'],
                'coeff' => $matiere['coeff']
            ];

            if (!is_null($notes['cc'])) $response['cc'] = $notes['cc'];
            if (!is_null($notes['tp'])) $response['tp'] = $notes['tp'];
            if (!is_null($notes['cf'])) $response['cf'] = $notes['cf'];

            echo json_encode($response);
            exit();
        }

        if (isset($_POST['get_semester_notes'])) {
            $semestre = $_POST['semestre'];
            $user_id = $_POST['user_id'];
            
            // Liste des matières sans TP
            $matieresWithoutTP = isset($_POST['matieres_without_tp']) 
                ? json_decode($_POST['matieres_without_tp'], true) 
                : ["Anglais V", "Intelligence Artificielle", "Anglais VI", "FTI", "Langages et Compilation"];
            
            try {
                // Récupérer le niveau de l'étudiant
                $stmt = $conn->prepare("SELECT niveau_filiere FROM etudiant WHERE id_etudiant = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $etudiant_info = $result->fetch_assoc();
                $stmt->close();
                
                if (!$etudiant_info) {
                    throw new Exception('Étudiant non trouvé');
                }
                
                $niveau = $etudiant_info['niveau_filiere'];

                // Récupérer les matières du semestre pour ce niveau
                $stmt = $conn->prepare("
                    SELECT DISTINCT m.id_matiere, m.nom_matiere, m.coeff
                    FROM matiere m
                    JOIN enseigner e ON m.id_matiere = e.id_matiere
                    JOIN etudiant et ON et.niveau_filiere = ?
                    WHERE e.type_semestre = ? AND et.niveau_filiere = ?;
                ");
                $stmt->bind_param("sss", $niveau, $semestre, $niveau);
                $stmt->execute();
                $matieres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                
                if (empty($matieres)) {
                    throw new Exception('Aucune matière trouvée pour ce semestre');
                }
                
                $response = [
                    'matieres' => [],
                    'total_matieres' => count($matieres),
                    'matieres_validees' => 0,
                    'credits' => 0,
                    'moyenne' => null
                ];
                
                $totalNotes = 0;
                $totalCoeffs = 0;
                
                foreach ($matieres as $matiere) {
                    // Récupérer les notes
                    $stmt = $conn->prepare("
                        SELECT e.cc, e.tp, a.cf
                        FROM evaluer e
                        JOIN anonymat a ON e.id_anonymat = a.id_anonymat
                        WHERE e.id_etudiant = ? AND e.id_matiere = ?
                    ");
                    $stmt->bind_param("ii", $user_id, $matiere['id_matiere']);
                    $stmt->execute();
                    $notes = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    // Calcul de la note
                    $noteMatiere = null;
                    $hasTP = !in_array($matiere['nom_matiere'], $matieresWithoutTP);
                    
                    if ($notes) {
                        if ($hasTP) {
                            // Matières avec TP: CC*0.2 + TP*0.3 + CF*0.5
                            if (!is_null($notes['cc']) && !is_null($notes['tp']) && !is_null($notes['cf'])) {
                                $noteMatiere = ($notes['cc'] * 0.2) + ($notes['tp'] * 0.3) + ($notes['cf'] * 0.5);
                            }
                        } else {
                            // Matières sans TP: CC*0.5 + CF*0.5
                            if (!is_null($notes['cc']) && !is_null($notes['cf'])) {
                                $noteMatiere = ($notes['cc'] * 0.5) + ($notes['cf'] * 0.5);
                            }
                        }
                    }
                    
                    // Ajout à la réponse
                    $response['matieres'][] = [
                        'nom' => $matiere['nom_matiere'],
                        'coeff' => $matiere['coeff'],
                        'note' => $noteMatiere !== null ? round($noteMatiere, 2) : null,
                        'statut' => $noteMatiere !== null ? ($noteMatiere >= 10 ? 'validé' : 'non validé') : 'en attente'
                    ];
                    
                    // Calcul des stats
                    if ($noteMatiere !== null) {
                        $totalNotes += $noteMatiere * $matiere['coeff'];
                        $totalCoeffs += $matiere['coeff'];
                        
                        if ($noteMatiere >= 10) {
                            $response['matieres_validees']++;
                            $response['credits'] += $matiere['coeff'];
                        }
                    }
                }
                
                // Calcul de la moyenne
                if ($totalCoeffs > 0) {
                    $response['moyenne'] = round($totalNotes / $totalCoeffs, 2);
                }
                
                echo json_encode($response);
                exit();
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
                exit();
            }
        }

    }catch (Exception $e) {
       // Nettoyer toute sortie potentielle avant l'erreur
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }

    // Nettoyer et envoyer la réponse
    ob_end_flush();
    exit();
}

// Déterminer automatiquement le semestre actuel
$today = new DateTime();
$year = (int)$today->format('Y');
$month = (int)$today->format('m');

if ($month >= 9 || $month == 1 || $month == 2) {
    $semestre_actuel = "1";
} elseif ($month >= 3 && $month <= 6) {
    $semestre_actuel = "2";
} else {
    $semestre_actuel = ""; // Aucun semestre en juillet/août
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>U-Digital Dashboard - Consultation des Notes</title>
    <link rel="stylesheet" href="../../../public/assets/css/recevoir_note_etudiant.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <div class="dashboard">
        <!-- Barre latérale -->
        <aside class="left-side">
            <div class="logo">
                <img src="../../../public/assets/img/U-remove.png" alt="Logo U-Digital">
            </div>
            <nav>
                <ul>
                    <a href="acceuil_etudiant.php"><li><i class="bi-house"></i> <span> Tableau de bord</span></li></a>
                    <a href="profile_etudiant.php"><li><i class="bi-person"></i> <span> Mon profil</span></li></a>
                    <a href="recevoir_cours_etudiant.php"><li><i class="bi-book"></i> <span> Mes cours</span></li></a>
                    <a href="etudiant_planning.php"><li><i class="bi-calendar"></i> <span> Emploi du temps</span></li></a>
                    <a href="recevoir_note_etudiant.php" class="active"><li><i class="bi-journal"></i> <span> Mes notes</span></li></a>
                    <a href="#"><li><i class="bi-newspaper"></i> <span>Actualité</span></li></a>
                    <a href="../forum/forum.php"><li><i class="bi-envelope"></i> <span>Forum</span></li></a>
                    <a href="aide.php"><li><i class="bi-question-circle"></i> <span>Aide</span></li></a>
                    <a href="#"><li><i class="bi-gear"></i> <span>Paramètres</span></li></a>
                    <a href="../../../config/logout.php" class="deconnection"><li><i class="bi-box-arrow-right"></i> <span>Déconnexion</span></li></a>
                </ul>
            </nav>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <header>
                <!-- Date et Heure en temps réel -->
                <div class="date-time-display" id="date-time-display">
                    <i class="bi-calendar-date"></i> <span id="date-display"></span> | 
                    <i class="bi-clock"></i> <span id="time-display"></span>
                </div>
                <div class="profile-info">
                    
                    <!-- Profil de l'étudiant -->
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($etudiant['prenom'] . '+' . $etudiant['nom']) ?>&background=4361ee&color=fff" alt="User" class="user-avatar">
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></span>
                            <span class="user-role">Étudiant - <?= htmlspecialchars($etudiant['id_personne']) ?> - <?= htmlspecialchars($etudiant['niveau_filiere']) ?></span>
                        </div>
                    </div>
                </div>
            </header>
    
            <!-- Page Title -->
            <h1 class="page-title">Consultation des Notes</h1>
            
            <!-- Stats Overview -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Moyenne Générale</span>
                        <div class="stat-icon">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="moyenne-generale">--</div>
                    <div class="stat-desc">Toutes matières confondues</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Meilleure Note</span>
                        <div class="stat-icon">
                            <i class="bi bi-trophy"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="meilleure-note">--</div>
                    <div class="stat-desc">La plus haute note obtenue</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Matières Validées</span>
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="matieres-validees">--</div>
                    <div class="stat-desc">Sur le total des matières</div>
                </div>
            </div>

            <!-- Ajoutez cette section après les stats-container et avant le selection-form -->
            <section class="semester-tabs">
                <div class="tabs-header">
                    <button class="tab-btn active" id="tab-s1">Semestre 1</button>
                    <button class="tab-btn" id="tab-s2">Semestre 2</button>
                    <button class="tab-btn" id="tab-bulletin">Bulletin Annuel</button>
                </div>
                
                <div class="tab-content active" id="content-s1">
                    <div class="semester-summary">
                        <div class="summary-card">
                            <h3>Résumé Semestre 1</h3>
                            <div class="summary-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Moyenne S1:</span>
                                    <span class="stat-value" id="moyenne-s1">--</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Crédits obtenus:</span>
                                    <span class="stat-value" id="credits-s1">--</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Matières validées:</span>
                                    <span class="stat-value" id="matieres-validees-s1">--</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Statut:</span>
                                    <span class="stat-value" id="statut-s1">--</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="matieres-list">
                            <h3>Notes par matière</h3>
                            <table class="semester-notes-table">
                                <thead>
                                    <tr>
                                        <th>Matière</th>
                                        <th>Coefficient</th>
                                        <th>Note</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody id="notes-s1">
                                    <!-- Les notes du semestre 1 seront chargées ici -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="tab-content" id="content-s2">
                    <div class="semester-summary">
                        <div class="summary-card">
                            <h3>Résumé Semestre 2</h3>
                            <div class="summary-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Moyenne S2:</span>
                                    <span class="stat-value" id="moyenne-s2">--</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Crédits obtenus:</span>
                                    <span class="stat-value" id="credits-s2">--</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Matières validées:</span>
                                    <span class="stat-value" id="matieres-validees-s2">--</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Statut:</span>
                                    <span class="stat-value" id="statut-s2">--</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="matieres-list">
                            <h3>Notes par matière</h3>
                            <table class="semester-notes-table">
                                <thead>
                                    <tr>
                                        <th>Matière</th>
                                        <th>Coefficient</th>
                                        <th>Note</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody id="notes-s2">
                                    <!-- Les notes du semestre 2 seront chargées ici -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="content-bulletin">
                    <div class="semester-summary">
                        <div class="summary-card">
                            <h3>Résumé Annuel</h3>
                            <div class="summary-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Moyenne S1:</span>
                                    <span class="stat-value" id="bulletin-moyenne-s1">--</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Moyenne S2:</span>
                                    <span class="stat-value" id="bulletin-moyenne-s2">--</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Moyenne Annuelle:</span>
                                    <span class="stat-value" id="bulletin-moyenne-annuelle">--</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Statut:</span>
                                    <span class="stat-value" id="bulletin-statut">--</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Crédits totaux:</span>
                                    <span class="stat-value" id="bulletin-credits">--</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Matières validées:</span>
                                    <span class="stat-value" id="bulletin-matieres-validees">--</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="matieres-list">
                            <h3>Détail des deux semestres</h3>
                            <table class="semester-notes-table">
                                <thead>
                                    <tr>
                                        <th>Semestre</th>
                                        <th>Matière</th>
                                        <th>Coefficient</th>
                                        <th>Note</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody id="notes-bulletin">
                                    <!-- Les notes des deux semestres seront chargées ici -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
    
            <!-- Notes Selection Form -->
            <section class="selection-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="niveau_filiere" class="form-label">Niveau</label>
                        <select id="niveau_filiere" class="form-select">
                            <option value="" selected disabled>Choisir un niveau</option>
                            <?php foreach ($niveaux as $niveau): ?>
                                <option value="<?= htmlspecialchars($niveau['niveau_filiere']) ?>" <?= ($niveau['niveau_filiere'] === $etudiant['niveau_filiere']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($niveau['niveau_filiere']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="semestre" class="form-label">Semestre</label>
                        <select id="semestre" class="form-select" disabled>
                            <option value="" selected disabled>Choisir un semestre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="matiere" class="form-label">Matière</label>
                        <select id="matiere" class="form-select" disabled>
                            <option value="" selected disabled>Choisir une matière</option>
                        </select>
                    </div>
                </div>
                
                <button id="search-btn" class="submit-btn" disabled>
                    <i class="bi bi-search"></i>
                    <span>Afficher les notes</span>
                </button>
            </section>
    
            <!-- Notes Results -->
            <section id="results-container" class="results-container" style="display: none;">
                <div class="matiere-header">
                    <h2 class="matiere-title" id="matiere-title"></h2>
                    <p class="matiere-coeff" id="matiere-coeff"></p>
                </div>
                
                <table class="notes-table">
                    <thead>
                        <tr>
                            <th>Type d'évaluation</th>
                            <th>Note</th>
                            <th>Sur</th>
                            <th>Commentaire</th>
                        </tr>
                    </thead>
                    <tbody id="notes-display">
                        <!-- Les notes seront chargées ici dynamiquement -->
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script>
            document.addEventListener('DOMContentLoaded', function() {
            // Configuration initiale
            const niveauSelect = document.getElementById('niveau_filiere');
            const semestreSelect = document.getElementById('semestre');
            const matiereSelect = document.getElementById('matiere');
            const searchBtn = document.getElementById('search-btn');
            const resultsContainer = document.getElementById('results-container');
            const matiereTitle = document.getElementById('matiere-title');
            const matiereCoeff = document.getElementById('matiere-coeff');
            const notesDisplay = document.getElementById('notes-display');
            
            // Liste des matières sans TP
            const matieresWithoutTP = [
                "Anglais V",
                "Intelligence Artificielle",
                "Anglais VI",
                "FTI",
                "Langages et Compilation"
            ];
            
            // Variables d'état
            let currentNiveau = niveauSelect.value;
            let currentSemestre = '';
            let currentMatiere = '';
            let currentMatiereName = '';

            // Initialisation
            initSemesterTabs();
            updateDateTime();
            setInterval(updateDateTime, 1000);
            
            // Simuler des statistiques pour le dashboard
            document.getElementById('moyenne-generale').textContent = "14.5";
            document.getElementById('meilleure-note').textContent = "18";
            document.getElementById('matieres-validees').textContent = "6/8";

            // Écouteurs d'événements
            niveauSelect.addEventListener('change', handleNiveauChange);
            semestreSelect.addEventListener('change', handleSemestreChange);
            matiereSelect.addEventListener('change', handleMatiereChange);
            searchBtn.addEventListener('click', handleSearchClick);

            // Gestion des onglets de semestre
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Désactiver tous les onglets
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    // Activer l'onglet cliqué
                    this.classList.add('active');
                    const tabId = this.id.replace('tab-', 'content-');
                    document.getElementById(tabId).classList.add('active');
                    
                    // Charger les données du semestre
                    const semestre = this.id.split('-')[1];
                    loadSemesterData(semestre);
                });
            });

            // Fonctions principales
            function initSemesterTabs() {
                // Activer le premier onglet par défaut
                document.querySelector('.tab-btn.active').click();
            }

            function handleNiveauChange() {
                currentNiveau = this.value;
                semestreSelect.disabled = true;
                matiereSelect.disabled = true;
                searchBtn.disabled = true;
                
                if (!currentNiveau) {
                    semestreSelect.innerHTML = '<option value="" selected disabled>Choisir un semestre</option>';
                    return;
                }
                
                semestreSelect.innerHTML = '<option value="" disabled>Chargement...</option>';
                
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'get_semestres=1&niveau_filiere=' + encodeURIComponent(currentNiveau)
                })
                .then(response => {
                    // Vérifier d'abord le content-type
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error(`Réponse non-JSON: ${text.substring(0, 100)}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    
                    if (data.length > 0) {
                        semestreSelect.innerHTML = '<option value="" selected disabled>Choisir un semestre</option>';
                        const semestreActuel = detectCurrentSemestre();
                        data.forEach(semestre => {
                            const selected = semestre.type_semestre == semestreActuel ? 'selected' : '';
                            semestreSelect.innerHTML += `<option value="${semestre.type_semestre}" ${selected}>Semestre ${semestre.type_semestre}</option>`;
                        });

                        if (semestreActuel) {
                            semestreSelect.disabled = false;
                            semestreSelect.dispatchEvent(new Event('change'));
                        }
                        semestreSelect.disabled = false;
                    } else {
                        semestreSelect.innerHTML = '<option value="" selected disabled>Aucun semestre disponible</option>';
                    }
                })
                .catch(error => {
                    semestreSelect.innerHTML = `<option value="" selected disabled>${error.message}</option>`;
                });
            }

            function handleSemestreChange() {
                currentSemestre = this.value;
                matiereSelect.disabled = true;
                searchBtn.disabled = true;
                
                if (!currentSemestre || !currentNiveau) {
                    matiereSelect.innerHTML = '<option value="" selected disabled>Choisir une matière</option>';
                    return;
                }
                
                matiereSelect.innerHTML = '<option value="" disabled>Chargement...</option>';
                
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'get_matieres=1&semestre=' + encodeURIComponent(currentSemestre) + 
                        '&niveau_filiere=' + encodeURIComponent(currentNiveau)
                })
                .then(response => {
                    // Vérifier d'abord le content-type
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error(`Réponse non-JSON: ${text.substring(0, 100)}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    
                    if (data.length > 0) {
                        matiereSelect.innerHTML = '<option value="" selected disabled>Choisir une matière</option>';
                        data.forEach(matiere => {
                            matiereSelect.innerHTML += `<option value="${matiere.id_matiere}" data-name="${matiere.nom_matiere}" data-coeff="${matiere.coeff}">${matiere.nom_matiere}</option>`;
                        });
                        matiereSelect.disabled = false;
                    } else {
                        matiereSelect.innerHTML = '<option value="" selected disabled>Aucune matière disponible</option>';
                    }
                })
                .catch(error => {
                    matiereSelect.innerHTML = `<option value="" selected disabled>${error.message}</option>`;
                });
            }
            
            function handleMatiereChange() {
                const selectedOption = this.options[this.selectedIndex];
                currentMatiere = this.value;
                currentMatiereName = selectedOption.getAttribute('data-name');
                searchBtn.disabled = !currentMatiere;
            }
            
            function handleSearchClick() {
                if (!currentNiveau || !currentSemestre || !currentMatiere) {
                    alert('Veuillez sélectionner un niveau, un semestre et une matière');
                    return;
                }
                
                notesDisplay.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px;">
                            <div style="display: inline-block; margin-right: 10px; width: 20px; height: 20px; border: 3px solid rgba(67, 97, 238, 0.3); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            Chargement des notes...
                        </td>
                    </tr>
                `;
                
                resultsContainer.style.display = 'block';
                
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'get_notes=1&niveau_filiere=' + encodeURIComponent(currentNiveau) + 
                        '&semestre=' + encodeURIComponent(currentSemestre) + 
                        '&matiere_id=' + encodeURIComponent(currentMatiere)
                })
                .then(response => {
                    // Vérifier d'abord le content-type
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error(`Réponse non-JSON: ${text.substring(0, 100)}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    
                    matiereTitle.textContent = data.nom_matiere;
                    matiereCoeff.textContent = `Coefficient: ${data.coeff}`;
                    
                    const hasTP = !matieresWithoutTP.includes(data.nom_matiere) && data.tp !== undefined;
                    
                    let notesHTML = '';
                    
                    if (data.cc !== undefined) {
                        notesHTML += `
                            <tr>
                                <td class="type-note">Contrôle Continu</td>
                                <td><span class="note-value note-cc">${data.cc}</span></td>
                                <td>20</td>
                                <td>${getCommentaireNote(data.cc)}</td>
                            </tr>
                        `;
                    } else {
                        notesHTML += `
                            <tr>
                                <td class="type-note">Contrôle Continu</td>
                                <td colspan="3" class="note-non-disponible">Non noté</td>
                            </tr>
                        `;
                    }
                    
                    if (hasTP) {
                        if (data.tp !== undefined) {
                            notesHTML += `
                                <tr>
                                    <td class="type-note">Travaux Pratiques</td>
                                    <td><span class="note-value note-tp">${data.tp}</span></td>
                                    <td>20</td>
                                    <td>${getCommentaireNote(data.tp)}</td>
                                </tr>
                            `;
                        } else {
                            notesHTML += `
                                <tr>
                                    <td class="type-note">Travaux Pratiques</td>
                                    <td colspan="3" class="note-non-disponible">Non noté</td>
                                </tr>
                            `;
                        }
                    }
                    
                    let noteMatiereHTML = '<td colspan="3" class="note-non-disponible">En attente des évaluations</td>';
                    
                    if (hasTP) {
                        if (data.cc !== undefined && data.tp !== undefined && data.cf !== undefined) {
                            const noteMatiere = (data.cc * 0.2) + (data.tp * 0.3) + (data.cf * 0.5);
                            noteMatiereHTML = `
                                <td><span class="note-value note-finale">${noteMatiere.toFixed(2)}</span></td>
                                <td>20</td>
                                <td>${getCommentaireNote(noteMatiere)}</td>
                            `;
                        }
                    } else {
                        if (data.cc !== undefined && data.cf !== undefined) {
                            const noteMatiere = (data.cc * 0.5) + (data.cf * 0.5);
                            noteMatiereHTML = `
                                <td><span class="note-value note-finale">${noteMatiere.toFixed(2)}</span></td>
                                <td>20</td>
                                <td>${getCommentaireNote(noteMatiere)}</td>
                            `;
                        }
                    }
                    
                    notesHTML += `
                        <tr>
                            
                        </tr>
                    `;
                    
                    notesDisplay.innerHTML = notesHTML;
                    updateStats(data, hasTP);
                })
                .catch(error => {
                    notesDisplay.innerHTML = `
                        <tr>
                            <td colspan="4" style="color: var(--danger); text-align: center; padding: 20px;">
                                ${error.message}
                            </td>
                        </tr>
                    `;
                });
            }

            // Fonctions utilitaires
            function detectCurrentSemestre() {
                const now = new Date();
                const month = now.getMonth() + 1;

                if ((month >= 9 && month <= 12) || (month === 1 || month === 2)) {
                    return "1";
                } else if (month >= 3 && month <= 6) {
                    return "2";
                } else {
                    return "";
                }
            }

            function updateDateTime() {
                const dateElement = document.getElementById("date-display");
                const timeElement = document.getElementById("time-display");
                
                const now = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const dateStr = now.toLocaleDateString('fr-FR', options);
                dateElement.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
                
                const timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                timeElement.textContent = timeStr;
            }

            // Ajoutez ce gestionnaire d'événement avec les autres
            document.getElementById('tab-bulletin').addEventListener('click', function() {
                loadBulletinData();
            });

            // Nouvelle fonction pour charger les données du bulletin
            function loadBulletinData() {
                const bulletinContent = document.getElementById('content-bulletin');
                const notesBulletin = document.getElementById('notes-bulletin');
                
                // Afficher le loader
                notesBulletin.innerHTML = `<tr><td colspan="5" class="text-center"><div class="spinner"></div> Chargement...</td></tr>`;
                
                // Réinitialiser les valeurs
                document.getElementById('bulletin-moyenne-s1').textContent = '--';
                document.getElementById('bulletin-moyenne-s2').textContent = '--';
                document.getElementById('bulletin-moyenne-annuelle').textContent = '--';
                document.getElementById('bulletin-statut').textContent = '--';
                document.getElementById('bulletin-credits').textContent = '--';
                document.getElementById('bulletin-matieres-validees').textContent = '--';

                // Charger les données des deux semestres
                Promise.all([
                    fetchSemesterData('1'),
                    fetchSemesterData('2')
                ])
                .then(([s1Data, s2Data]) => {
                    updateBulletin(s1Data, s2Data);
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    notesBulletin.innerHTML = `
                        <tr>
                            <td colspan="5" class="error-message">
                                Erreur lors du chargement: ${error.message}
                            </td>
                        </tr>
                    `;
                });
            }

            // Fonction pour récupérer les données d'un semestre
            function fetchSemesterData(semestre) {
                const userId = <?= isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null' ?>;
                
                return fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `get_semester_notes=1&semestre=${semestre}&user_id=${userId}&matieres_without_tp=${encodeURIComponent(JSON.stringify(matieresWithoutTP))}`
                })
                .then(response => {
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error(`Réponse non-JSON: ${text.substring(0, 100)}`);
                        });
                    }
                    return response.json();
                });
            }

            // Fonction pour mettre à jour le bulletin
            function updateBulletin(s1Data, s2Data) {
                const notesBulletin = document.getElementById('notes-bulletin');
                
                // Afficher les moyennes par semestre
                document.getElementById('bulletin-moyenne-s1').textContent = s1Data.moyenne !== null ? s1Data.moyenne.toFixed(2) : '--';
                document.getElementById('bulletin-moyenne-s2').textContent = s2Data.moyenne !== null ? s2Data.moyenne.toFixed(2) : '--';
                
                // Calculer la moyenne annuelle
                let moyenneAnnuelle = '--';
                let statut = '--';
                
                if (s1Data.moyenne !== null && s2Data.moyenne !== null) {
                    moyenneAnnuelle = ((s1Data.moyenne + s2Data.moyenne) / 2).toFixed(2);
                    document.getElementById('bulletin-moyenne-annuelle').textContent = moyenneAnnuelle;
                    
                    // Déterminer le statut
                    const moyenneNum = parseFloat(moyenneAnnuelle);
                    if (moyenneNum < 8) {
                        statut = 'Ajourné';
                        document.getElementById('bulletin-statut').className = 'stat-value text-danger';
                    } else if (moyenneNum >= 8 && moyenneNum < 10) {
                        statut = 'Ajourné';
                        document.getElementById('bulletin-statut').className = 'stat-value text-warning';
                    } else {
                        statut = 'Admis';
                        document.getElementById('bulletin-statut').className = 'stat-value text-success';
                    }
                    document.getElementById('bulletin-statut').textContent = statut;
                }
                
                // Calculer les totaux
                const totalCredits = (s1Data.credits || 0) + (s2Data.credits || 0);
                const totalMatieresValidees = (s1Data.matieres_validees || 0) + (s2Data.matieres_validees || 0);
                const totalMatieres = (s1Data.total_matieres || 0) + (s2Data.total_matieres || 0);
                
                document.getElementById('bulletin-credits').textContent = totalCredits;
                document.getElementById('bulletin-matieres-validees').textContent = `${totalMatieresValidees}/${totalMatieres}`;
                
                // Afficher toutes les matières
                let html = '';
                
                // Semestre 1
                if (s1Data.matieres && s1Data.matieres.length > 0) {
                    s1Data.matieres.forEach(matiere => {
                        html += createBulletinRow(matiere, 'S1');
                    });
                }
                
                // Semestre 2
                if (s2Data.matieres && s2Data.matieres.length > 0) {
                    s2Data.matieres.forEach(matiere => {
                        html += createBulletinRow(matiere, 'S2');
                    });
                }
                
                notesBulletin.innerHTML = html || `<tr><td colspan="5" class="text-center">Aucune donnée disponible</td></tr>`;
            }

            // Fonction utilitaire pour créer une ligne du bulletin
            function createBulletinRow(matiere, semestre) {
                const note = matiere.note !== null ? parseFloat(matiere.note) : null;
                let statusClass = 'status-pending';
                let statusText = 'En attente';
                
                if (note !== null) {
                    statusClass = note >= 10 ? 'status-success' : 'status-failed';
                    statusText = note >= 10 ? 'Validé' : 'Non validé';
                }
                
                return `
                    <tr>
                        <td>${semestre}</td>
                        <td>${matiere.nom}</td>
                        <td>${matiere.coeff}</td>
                        <td>${note !== null ? note.toFixed(2) : '--'}</td>
                        <td><span class="${statusClass}">${statusText}</span></td>
                    </tr>
                `;
            }

            function getCommentaireNote(note) {
                if (note >= 16) return '<span style="color: var(--success);">Excellent travail !</span>';
                if (note >= 14) return '<span style="color: var(--info);">Très bon travail</span>';
                if (note >= 12) return '<span style="color: var(--primary);">Bon travail</span>';
                if (note >= 10) return '<span style="color: var(--warning);">Passable</span>';
                return '<span style="color: var(--danger);">Des efforts supplémentaires sont nécessaires</span>';
            }

            function updateStats(data, hasTP) {
                let moyenneGenerale = '--';
                let meilleureNote = '--';
                
                if (data.cc !== null || (hasTP && data.tp !== null)) {
                    let sum = 0;
                    let count = 0;
                    
                    if (data.cc !== null) {
                        sum += parseFloat(data.cc);
                        count++;
                        if (meilleureNote === '--' || parseFloat(data.cc) > parseFloat(meilleureNote)) {
                            meilleureNote = data.cc;
                        }
                    }
                    
                    if (hasTP && data.tp !== null) {
                        sum += parseFloat(data.tp);
                        count++;
                        if (meilleureNote === '--' || parseFloat(data.tp) > parseFloat(meilleureNote)) {
                            meilleureNote = data.tp;
                        }
                    }
                    
                    if (count > 0) {
                        moyenneGenerale = (sum / count).toFixed(1);
                    }
                }
                
                document.getElementById('moyenne-generale').textContent = moyenneGenerale;
                if (meilleureNote !== '--') {
                    document.getElementById('meilleure-note').textContent = meilleureNote;
                }
            }

            function loadSemesterData(semestre) {
                const tbodyId = `notes-${semestre}`;
                const tbody = document.getElementById(tbodyId);
                const moyenneElement = document.getElementById(`moyenne-${semestre}`);
                const creditsElement = document.getElementById(`credits-${semestre}`);
                const matieresValideesElement = document.getElementById(`matieres-validees-${semestre}`);
                
                const userId = <?= isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null' ?>;
                
                // Afficher le loader
                tbody.innerHTML = `<tr><td colspan="4" class="text-center"><div class="spinner"></div> Chargement...</td></tr>`;
                
                // Réinitialiser les stats
                if (moyenneElement) moyenneElement.textContent = '--';
                if (creditsElement) creditsElement.textContent = '--';
                if (matieresValideesElement) matieresValideesElement.textContent = '--/--';
                const statutElement = document.getElementById(`statut-${semestre}`);
                if (statutElement) {
                    statutElement.textContent = '--';
                    statutElement.className = 'stat-value';
                }

                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `get_semester_notes=1&semestre=${semestre.replace('s', '')}&user_id=${userId}&matieres_without_tp=${encodeURIComponent(JSON.stringify(matieresWithoutTP))}`
                })
                .then(response => {
                    // Vérifier d'abord si la réponse est bien du JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error(`Réponse non-JSON: ${text.substring(0, 100)}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    // Mettre à jour les statistiques
                    updateSemesterStats(semestre, data);
                    
                    // Générer le tableau des matières
                    let html = '';
                    if (data.matieres && data.matieres.length > 0) {
                        data.matieres.forEach(matiere => {
                            const note = matiere.note !== null ? parseFloat(matiere.note) : null;
                            let statusClass = 'status-pending';
                            let statusText = 'En attente';
                            
                            if (note !== null) {
                                statusClass = note >= 10 ? 'status-success' : 'status-failed';
                                statusText = note >= 10 ? 'Validé' : 'Non validé';
                            }
                            
                            html += `
                                <tr>
                                    <td>${matiere.nom}</td>
                                    <td>${matiere.coeff}</td>
                                    <td>${note !== null ? note.toFixed(2) : '--'}</td>
                                    <td><span class="${statusClass}">${statusText}</span></td>
                                </tr>
                            `;
                        });
                    } else {
                        html = `<tr><td colspan="4" class="text-center">Aucune donnée disponible pour ce semestre</td></tr>`;
                    }
                    
                    tbody.innerHTML = html;
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="error-message">
                                Erreur lors du chargement: ${error.message}
                            </td>
                        </tr>
                    `;
                    
                    // Mettre à jour les stats avec des valeurs d'erreur
                    if (moyenneElement) moyenneElement.textContent = 'Erreur';
                    if (creditsElement) creditsElement.textContent = 'Erreur';
                    if (matieresValideesElement) matieresValideesElement.textContent = 'Erreur';
                });
            }

            function updateSemesterStats(semestre, data) {
                const moyenneElement = document.getElementById(`moyenne-${semestre}`);
                const creditsElement = document.getElementById(`credits-${semestre}`);
                const matieresValideesElement = document.getElementById(`matieres-validees-${semestre}`);
                const statutElement = document.getElementById(`statut-${semestre}`);
                
                if (moyenneElement) {
                    moyenneElement.textContent = data.moyenne !== null ? data.moyenne.toFixed(2) : '--';
                    if (data.moyenne !== null) {
                        const moyenneClass = data.moyenne >= 10 ? 'text-success' : 'text-danger';
                        moyenneElement.className = 'stat-value ' + moyenneClass;
                        
                        // Mettre à jour le statut du semestre
                        if (statutElement) {
                            let statutText = '--';
                            let statutClass = '';
                            
                            if (data.moyenne >= 10) {
                                statutText = 'Validé';
                                statutClass = 'text-success';
                            } else {
                                statutText = 'Non validé';
                                statutClass = 'text-danger';
                            }
                            
                            statutElement.textContent = statutText;
                            statutElement.className = 'stat-value ' + statutClass;
                        }
                    }
                }
                
                if (creditsElement) {
                    creditsElement.textContent = data.credits || '0';
                }
                
                if (matieresValideesElement) {
                    matieresValideesElement.textContent = 
                        `${data.matieres_validees || 0}/${data.total_matieres || 0}`;
                }
            }

            // Déclenchement initial pour charger les données si un niveau est déjà sélectionné
            if (niveauSelect.value) {
                niveauSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>