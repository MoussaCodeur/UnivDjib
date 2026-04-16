<?php

// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

// Récupération sécurisée des informations de l'assistant
$assistant_id = (int)$_SESSION['user_id'];
$sql_assistant = "SELECT departement, niveau FROM assistant WHERE id_assistant = ?";
$stmt = $conn->prepare($sql_assistant);
$stmt->bind_param("i", $assistant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Assistant non trouvé.");
}

$assistant = $result->fetch_assoc();
$niveaux = isset($assistant['niveau']) ? explode(',', $assistant['niveau']) : [];
$departement = $assistant['departement']; 

// Récupération des enseignants
$enseignants = [];
$sql_enseignants = "SELECT p.id_personne, CONCAT(p.nom, ' ', p.prenom) AS nom_complet 
                  FROM personne p 
                  WHERE p.role = 'enseignant'
                  ORDER BY p.nom ASC";
$result_enseignants = $conn->query($sql_enseignants);

if ($result_enseignants) {
    while ($row = $result_enseignants->fetch_assoc()) {
        $enseignants[] = $row;
    }
}

// Messages d'erreur ou de succès
$success_message = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des planning - Universite de Djibouti</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/gestion_planning.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <div class="dashboard">
        <!-- Mobile Menu Toggle -->
        <button class="menu-toggle" id="menuToggle">
            <i class="bi bi-list"></i>
        </button>

        <!-- Overlay for mobile menu -->
        <div class="mobile-overlay" id="mobileOverlay"></div>

        <!-- Barre latérale -->
        <aside class="sidebar" id="sidebar">
            <a href="profil_assistant.php" style="text-decoration: none; color: inherit;">
                <div class="sidebar-header">
                    <img src="../../../public/assets/img/U-remove.png" alt="Logo Université" class="sidebar-logo">
                    <h2>Assistant Panel</h2>
                </div>
            </a>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="acceuil_assistant.php"><i class="bi bi-house-door"></i> <span>Accueil</span></a>
                    </li>
                    <li>
                        <a href="profile_assistant.php"><i class="bi bi-person"></i> <span>Profil</span></a>
                    </li>
                    <li class="active">
                        <a href="gestion_planning.php"><i class="bi bi-calendar-week"></i> <span>Gestion Planning</span></a>
                    </li>
                    <li>
                        <a href="liste_Evaluer.php"><i class="bi bi-list-check"></i> <span>Évaluations</span></a>
                    </li>
                </ul>
                
                <div class="logout-section">
                    <a href="../../../config/logout.php" class="logout-btn">
                        <i class="bi bi-box-arrow-right"></i> <span>Déconnexion</span>
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div class="container">
                <div class="header-container">
                    <h2>📤 Déposer un planning</h2>
                    <a href="acceuil_assistant.php" class="return-button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        Accueil
                    </a>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <?= $success_message ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert error">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <form id="uploadForm" action="upload.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="departement" value="<?= htmlspecialchars($departement) ?>">
                    
                    <div class="form-group">
                        <label for="type">Type de destinataire</label>
                        <select id="type" name="type" required class="form-control">
                            <option value="">-- Sélectionner --</option>
                            <option value="enseignant">👨🏫 Enseignant</option>
                            <option value="etudiant">👩🎓 Étudiants</option>
                        </select>
                    </div>

                    <div id="enseignantDiv" class="form-group" style="display: none;">
                        <label for="id_enseignant">Sélectionner un enseignant</label>
                        <select id="id_enseignant" name="id_enseignant" class="form-control">
                            <option value="">-- Choisir un enseignant --</option>
                            <?php foreach ($enseignants as $e) : ?>
                                <option value="<?= htmlspecialchars($e['id_personne']) ?>">
                                    👤 <?= htmlspecialchars($e['nom_complet']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="etudiantDiv" class="form-group" style="display: none;">
                        <label for="niveau">Niveau des étudiants</label>
                        <select id="niveau" name="niveau" class="form-control">
                            <option value="">-- Sélectionner un niveau --</option>
                            <?php foreach ($niveaux as $n) : ?>
                                <option value="<?= htmlspecialchars($n) ?>">🎓 <?= htmlspecialchars($n) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="type_planning">Type de planning</label>
                        <select id="type_planning" name="type_planning" class="form-control" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="cours">📘 Cours</option>
                            <option value="examen">📝 Examen</option>
                            <option value="autres">📂 Autres</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="semestre">Semestre</label>
                        <select id="semestre" name="semestre" class="form-control" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="1">📅 Semestre 1</option>
                            <option value="2">📅 Semestre 2</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fichier">Fichier PDF</label>
                        <div class="file-input">
                            <input type="file" id="fichier" name="fichier" accept=".pdf" required>
                            <div class="file-label">
                                📁 Glisser-déposer ou choisir un fichier
                            </div>
                        </div>
                        <small style="display: block; margin-top: 0.5rem; color: #7f8c8d;">Taille maximale: 10 Mo</small>
                    </div>

                    <button type="submit" id="submitBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        Publier le planning
                    </button>
                </form>
            </div>
        </main>
    </div>

    <!-- Dark Mode Toggle -->
    <button class="theme-toggle" id="themeToggle">
        <i class="bi bi-moon"></i>
    </button>

    <!-- Overlay de chargement -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Traitement en cours...</div>
            <div class="progress-bar">
                <div class="progress" id="uploadProgress"></div>
            </div>
        </div>
    </div>

   <!-- jQuery (nécessaire pour planning.js) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Vos scripts -->
    <script src="../../../public/assets/js/theme.js"></script>
    <script src="../../../public/assets/js/planning.js"></script>
</body>
</html>