<?php

// Démarrer la session après les headers
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Connexion.php");
    exit();
}

// Configuration de la base de données
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'db'   => 'gestioncouruniversitaire'
];

// Définir matieresWithoutTP ici pour qu'il soit disponible partout
$matieresWithoutTP = [
    "Anglais V",
    "Intelligence Artificielle",
    "Anglais VI",
    "FTI",
    "Langages et Compilation"
];

// Connexion sécurisée à la base de données
try {
    $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['db']);
    if ($conn->connect_error) {
        throw new Exception("Échec de la connexion : " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Récupération des informations étudiant
$user_id = $_SESSION['user_id'];
$etudiant = [];
$niveaux = [];

try {
    // Informations de base de l'étudiant
    $stmt = $conn->prepare("SELECT p.id_personne, p.prenom, p.nom, e.niveau, e.nom_filiere 
                           FROM personne p 
                           JOIN etudiant e ON p.id_personne = e.id_etudiant 
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
    $result = $conn->query("SELECT DISTINCT niveau FROM etudiant ORDER BY niveau");
    $niveaux = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Gestion des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        if (isset($_POST['get_semestres'])) {
            $niveau = $_POST['niveau'];
            
            $stmt = $conn->prepare("SELECT DISTINCT e.type_semestre 
                                  FROM enseigner e
                                  JOIN etudiant et ON et.niveau = ?
                                  WHERE et.niveau = ?");
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
            $niveau = $_POST['niveau'];
            
            $stmt = $conn->prepare("SELECT DISTINCT m.id_matiere, m.nom_matiere, m.coeff
                                  FROM matiere m
                                  JOIN enseigner e ON m.id_matiere = e.id_matiere
                                  JOIN etudiant et ON et.niveau = ?
                                  WHERE e.type_semestre = ? AND et.niveau = ?");
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
            
            // Récupérer le niveau de l'étudiant
            $stmt = $conn->prepare("SELECT niveau FROM etudiant WHERE id_etudiant = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $etudiant_info = $result->fetch_assoc();
            $stmt->close();
            
            if (!$etudiant_info) {
                echo json_encode(['error' => 'Étudiant non trouvé']);
                exit();
            }
            
            $niveau = $etudiant_info['niveau'];
            
            // Récupérer toutes les matières du semestre pour cet étudiant et son niveau
            $stmt = $conn->prepare("
                SELECT m.id_matiere, m.nom_matiere, m.coeff
                FROM matiere m
                JOIN enseigner e ON m.id_matiere = e.id_matiere
                WHERE e.type_semestre = ? AND e.niveau = ?
            ");
            $stmt->bind_param("ss", $semestre, $niveau);
            $stmt->execute();
            $matieres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
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
                // Récupérer les notes pour chaque matière
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
                
                // Calculer la note de la matière
                $noteMatiere = null;
                $hasTP = !in_array($matiere['nom_matiere'], $matieresWithoutTP);
                
                if ($notes) {
                    if ($hasTP) {
                        if (isset($notes['cc']) && isset($notes['tp']) && isset($notes['cf'])) {
                            $noteMatiere = ($notes['cc'] * 0.2) + ($notes['tp'] * 0.3) + ($notes['cf'] * 0.5);
                        }
                    } else {
                        if (isset($notes['cc']) && isset($notes['cf'])) {
                            $noteMatiere = ($notes['cc'] * 0.5) + ($notes['cf'] * 0.5);
                        }
                    }
                }
                
                // Ajouter à la réponse
                $response['matieres'][] = [
                    'nom' => $matiere['nom_matiere'],
                    'coeff' => $matiere['coeff'],
                    'note' => $noteMatiere
                ];
                
                // Calculer les stats si la note est disponible
                if ($noteMatiere !== null) {
                    $totalNotes += $noteMatiere * $matiere['coeff'];
                    $totalCoeffs += $matiere['coeff'];
                    
                    if ($noteMatiere >= 10) {
                        $response['matieres_validees']++;
                        $response['credits'] += $matiere['coeff']; // Supposant que 1 coeff = 1 crédit
                    }
                }
            }
            
            // Calculer la moyenne générale du semestre
            if ($totalCoeffs > 0) {
                $response['moyenne'] = $totalNotes / $totalCoeffs;
            }
            
            echo json_encode($response);
            exit();
            
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
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
    <link rel="stylesheet" href="recevoir_note_etudiant.css">
</head>
<body>
    <div class="dashboard">
        <!-- Barre latérale -->
        <aside class="left-side">
            <div class="logo">
                <img src="../Images/ud.jpg" alt="Logo U-Digital">
            </div>
            <nav>
                <ul>
                    <a href="acceuil_etudiant.php"><li><i class="bi-house"></i> <span>Accueil</span></li></a>
                    <a href="profile_etudiant.php"><li><i class="bi-person"></i> <span>Profil</span></li></a>
                    <a href="recevoir_cours_etudiant.php"><li><i class="bi-book"></i> <span>Cours</span></li></a>
                    <a href="planning_etudiant.php"><li><i class="bi-calendar"></i> <span>Planning</span></li></a>
                    <a href="recevoir_note_etudiant.php" class="active"><li><i class="bi-journal"></i> <span>Notes</span></li></a>
                    <a href="#"><li><i class="bi-newspaper"></i> <span>Actualité</span></li></a>
                    <a href="#"><li><i class="bi-question-circle"></i> <span>Aide</span></li></a>
                    <a href="#"><li><i class="bi-gear"></i> <span>Paramètres</span></li></a>
                    <a href="deconnexion.php" class="deconnection"><li><i class="bi-box-arrow-right"></i> <span>Déconnexion</span></li></a>
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
                    <!-- Notifications -->
                    <div class="notifications">
                        <i class="bi-bell"></i>
                        <span class="badge">3</span>
                    </div>
                    <!-- Profil de l'étudiant -->
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($etudiant['prenom'] . '+' . $etudiant['nom']) ?>&background=4361ee&color=fff" alt="User" class="user-avatar">
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></span>
                            <span class="user-role">Étudiant - <?= htmlspecialchars($etudiant['id_personne']) ?> - <?= htmlspecialchars($etudiant['niveau']) ?></span>
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
            </section>
    
            <!-- Notes Selection Form -->
            <section class="selection-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="niveau" class="form-label">Niveau</label>
                        <select id="niveau" class="form-select">
                            <option value="" selected disabled>Choisir un niveau</option>
                            <?php foreach ($niveaux as $niveau): ?>
                                <option value="<?= htmlspecialchars($niveau['niveau']) ?>" <?= ($niveau['niveau'] === $etudiant['niveau']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($niveau['niveau']) ?>
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
            const niveauSelect = document.getElementById('niveau');
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
                "Fondements Théoriques de l'informatique",
                "Langages et Compilation"
            ];
            
            // Simuler des statistiques pour le dashboard (à remplacer par vos vraies données)
            document.getElementById('moyenne-generale').textContent = "14.5";
            document.getElementById('meilleure-note').textContent = "18";
            document.getElementById('matieres-validees').textContent = "6/8";
            
            let currentNiveau = niveauSelect.value;
            let currentSemestre = '';
            let currentMatiere = '';
            let currentMatiereName = '';
            
            niveauSelect.addEventListener('change', function() {
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
                    body: 'get_semestres=1&niveau=' + encodeURIComponent(currentNiveau)
                })
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau');
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

                        // Simuler l'événement "change" pour charger les matières automatiquement si semestre détecté
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
            });

            function detectCurrentSemestre() {
                const now = new Date();
                const year = now.getFullYear();
                const month = now.getMonth() + 1; // 0 = janvier, donc +1

                if ((month >= 9 && month <= 12) || (month === 1 || month === 2)) {
                    return "1";
                } else if (month >= 3 && month <= 6) {
                    return "2";
                } else {
                    return ""; // Hors période (juillet-août), on laisse vide par défaut
                }
            }

            semestreSelect.addEventListener('change', function() {
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
                        '&niveau=' + encodeURIComponent(currentNiveau)
                })
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau');
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
            });
            
            matiereSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                currentMatiere = this.value;
                currentMatiereName = selectedOption.getAttribute('data-name');
                searchBtn.disabled = !currentMatiere;
            });
            
            searchBtn.addEventListener('click', function() {
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
                    body: 'get_notes=1&niveau=' + encodeURIComponent(currentNiveau) + 
                        '&semestre=' + encodeURIComponent(currentSemestre) + 
                        '&matiere_id=' + encodeURIComponent(currentMatiere)
                })
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau');
                    return response.json();
                })
                // Dans la fonction qui traite la réponse fetch (remplacez la partie concernée)
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    
                    // Afficher le nom et le coefficient de la matière
                    matiereTitle.textContent = data.nom_matiere;
                    matiereCoeff.textContent = `Coefficient: ${data.coeff}`;
                    
                    // Vérifier si la matière a des TP
                    const hasTP = !matieresWithoutTP.includes(data.nom_matiere) && data.tp !== undefined;
                    
                    // Construire l'affichage des notes dans le tableau
                    let notesHTML = '';
                    
                    // Afficher la note de contrôle continu si disponible
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
                    
                    // Afficher les notes de TP uniquement si la matière a des TP
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
                    
                    // Calculer et afficher la note de la matière
                    let noteMatiereHTML = '<td colspan="3" class="note-non-disponible">En attente des évaluations</td>';
                    
                    if (hasTP) {
                        // Calcul avec TP: CC*0.2 + TP*0.3 + CF*0.5
                        if (data.cc !== undefined && data.tp !== undefined && data.cf !== undefined) {
                            const noteMatiere = (data.cc * 0.2) + (data.tp * 0.3) + (data.cf * 0.5);
                            noteMatiereHTML = `
                                <td><span class="note-value note-finale">${noteMatiere.toFixed(2)}</span></td>
                                <td>20</td>
                                <td>${getCommentaireNote(noteMatiere)}</td>
                            `;
                        }
                    } else {
                        // Calcul sans TP: CC*0.5 + CF*0.5
                        if (data.cc !== undefined && data.cf !== undefined) {
                            const noteMatiere = (data.cc * 0.5) + (data.cf * 0.5);
                            noteMatiereHTML = `
                                <td><span class="note-value note-finale">${noteMatiere.toFixed(2)}</span></td>
                                <td>20</td>
                                <td>${getCommentaireNote(noteMatiere)}</td>
                            `;
                        }
                    }
                    
                    // Ajouter la ligne pour la note de matière
                    notesHTML += `
                        <tr>
                            <td class="type-note">Note de la matière</td>
                            ${noteMatiereHTML}
                        </tr>
                    `;
                    
                    notesDisplay.innerHTML = notesHTML;
                    
                    // Mise à jour des statistiques
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
            });
            
            // Helper pour générer un commentaire basé sur la note
            function getCommentaireNote(note) {
                if (note >= 16) return '<span style="color: var(--success);">Excellent travail !</span>';
                if (note >= 14) return '<span style="color: var(--info);">Très bon travail</span>';
                if (note >= 12) return '<span style="color: var(--primary);">Bon travail</span>';
                if (note >= 10) return '<span style="color: var(--warning);">Passable</span>';
                return '<span style="color: var(--danger);">Des efforts supplémentaires sont nécessaires</span>';
            }
            
            // Mise à jour des statistiques (simulée)
            function updateStats(data, hasTP) {
                // Cette fonction est une simulation, à adapter selon vos besoins
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
                
                // Déclenchement initial pour charger les données si un niveau est déjà sélectionné
                if (niveauSelect.value) {
                    niveauSelect.dispatchEvent(new Event('change'));
                }
            });

            // Affichage de la date et de l'heure en temps réel
            function updateDateTime() {
                let dateElement = document.getElementById("date-display");
                let timeElement = document.getElementById("time-display");
                
                let now = new Date();
                
                // Format date: jour, mois année
                let options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                let dateStr = now.toLocaleDateString('fr-FR', options);
                dateElement.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
                
                // Format heure: hh:mm:ss
                let timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                timeElement.textContent = timeStr;
            }

            // Mettre à jour l'heure et la date chaque seconde
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // Animation au survol des cartes de fonctionnalités
            document.querySelectorAll('.feature-card').forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transition = 'all 0.3s ease';
                });
            });

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
                    
                    // Charger les données du semestre si nécessaire
                    const semestre = this.id.split('-')[1];
                    loadSemesterData(semestre);
                });
            })

            // Fonction pour charger les données d'un semestre
            function loadSemesterData(semestre) {
                const tbodyId = `notes-${semestre}`;
                const tbody = document.getElementById(tbodyId);
                const userId = <?= isset($etudiant['id_personne']) ? $etudiant['id_personne'] : 'null' ?>;
                
                tbody.innerHTML = `<tr><td colspan="4" class="text-center"><div class="spinner"></div> Chargement...</td></tr>`;

                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `get_semester_notes=1&semestre=${semestre.replace('s', '')}&user_id=${userId}`
                })
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    
                    // Mettre à jour les statistiques du semestre
                    updateSemesterStats(semestre, data);
                    
                    // Générer le tableau des matières
                    let html = '';
                    if (data.matieres && data.matieres.length > 0) {
                        data.matieres.forEach(matiere => {
                            const status = matiere.note >= 10 ? 
                                '<span class="status-success">Validé</span>' : 
                                '<span class="status-failed">Non validé</span>';
                            
                            html += `
                                <tr>
                                    <td>${escapeHtml(matiere.nom)}</td>
                                    <td>${escapeHtml(matiere.coeff)}</td>
                                    <td>${matiere.note !== null ? matiere.note.toFixed(2) : '--'}</td>
                                    <td>${matiere.note !== null ? status : 'En attente'}</td>
                                </tr>
                            `;
                        });
                    } else {
                        html = `<tr><td colspan="4" class="text-center">Aucune donnée disponible</td></tr>`;
                    }
                    
                    tbody.innerHTML = html;
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="error-message">
                                Erreur lors du chargement: ${escapeHtml(error.message)}
                            </td>
                        </tr>
                    `;
                });
            }

            function updateSemesterStats(semestre, data) {
                const moyenneElement = document.getElementById(`moyenne-${semestre}`);
                const creditsElement = document.getElementById(`credits-${semestre}`);
                const matieresValideesElement = document.getElementById(`matieres-validees-${semestre}`);
                
                if (moyenneElement) {
                    moyenneElement.textContent = data.moyenne !== null ? data.moyenne.toFixed(2) : '--';
                    if (data.moyenne !== null) {
                        moyenneElement.className = 'stat-value ' + 
                            (data.moyenne >= 10 ? 'text-success' : 'text-danger');
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

            function escapeHtml(unsafe) {
                if (typeof unsafe !== 'string') return unsafe;
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            // Ajoutez cette fonction pour initialiser le chargement des onglets au démarrage
            document.addEventListener('DOMContentLoaded', function() {
                // Charger les données du semestre 1 par défaut
                loadSemesterData('s1');
                
                // S'assurer que les gestionnaites d'événements des onglets sont bien définis
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
            });
        </script>
</body>
</html>