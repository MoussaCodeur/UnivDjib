<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion à la base de données
require_once '../../../config/db.php';

$userID = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur
try {
    $sql = "SELECT id_personne, prenom, nom, email, role, image_profile FROM personne WHERE id_personne = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $prenom = $user['prenom'];
        $nom = $user['nom'];
        $email = $user['email'];
        $role = $user['role'];
        $image_profile = isset($user['image_profile']) ? $user['image_profile'] : 'profile1.jpg';
        $nom_complet = $prenom . ' ' . $nom;
    } else {
        throw new Exception("Utilisateur non trouvé");
    }
} catch (Exception $e) {
    die("Impossible de récupérer les informations de l'utilisateur.");
}

if ($role !== 'enseignant') {
    header("Location: acces_refuse.php");
    exit();
}

// Récupérer les matières enseignées par l'enseignant
try {
    $sql_matieres = "SELECT DISTINCT m.id_matiere, m.nom_matiere, e.type_semestre,
                    CASE 
                        WHEN e.type_semestre = 1 THEN '2024-09-01'
                        WHEN e.type_semestre = 2 THEN '2025-02-01'
                        ELSE NULL
                    END as date_debut,
                    CASE 
                        WHEN e.type_semestre = 1 THEN '2025-01-30'
                        WHEN e.type_semestre = 2 THEN '2025-05-30'
                        ELSE NULL
                    END as date_fin
                    FROM enseigner e 
                    JOIN matiere m ON e.id_matiere = m.id_matiere 
                    WHERE e.id_enseignant = ?";
    $stmt = $conn->prepare($sql_matieres);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result_matieres = $stmt->get_result();
    
    $matieres = [];
    while ($row = $result_matieres->fetch_assoc()) {
        $matieres[] = $row;
    }
} catch (Exception $e) {
    $message_erreur = "Impossible de récupérer les matières enseignées.";
}

// ID de matière sélectionnée
$id_matiere_selected = isset($_GET['matiere']) ? intval($_GET['matiere']) : (isset($matieres[0]['id_matiere']) ? $matieres[0]['id_matiere'] : 0);

// Variables pour la matière sélectionnée
$date_debut = null;
$date_fin = null;
$semestre = null;
$periode_active = false;
$nom_matiere_selected = '';

if ($id_matiere_selected > 0) {
    foreach ($matieres as $matiere) {
        if ($matiere['id_matiere'] == $id_matiere_selected) {
            $date_debut = $matiere['date_debut'];
            $date_fin = $matiere['date_fin'];
            $semestre = $matiere['type_semestre'];
            $nom_matiere_selected = $matiere['nom_matiere'];
            break;
        }
    }
    
    $aujourdhui = date('Y-m-d');
    $periode_active = ($aujourdhui >= $date_debut && $aujourdhui <= $date_fin);
}

// Récupérer les informations de classe (filière et niveau)
if ($id_matiere_selected > 0) {
    try {
       // Remplacer la requête actuelle par celle-ci pour récupérer aussi le coefficient
$sql_classe = "SELECT DISTINCT f.nom_filiere, m.niveau_filiere, m.coeff 
               FROM enseigner e 
               JOIN matiere m ON e.id_matiere = m.id_matiere 
               JOIN filiere f ON e.id_filiere = f.id_filiere
               WHERE e.id_enseignant = ? AND e.id_matiere = ?";
        $stmt = $conn->prepare($sql_classe);
        $stmt->bind_param("ii", $userID, $id_matiere_selected);
        $stmt->execute();
        $result_classe = $stmt->get_result();
        
        if ($result_classe->num_rows > 0) {
            $classe_info = $result_classe->fetch_assoc();
            $nom_filiere = $classe_info['nom_filiere'];
            $niveau = $classe_info['niveau_filiere'];
        }
    } catch (Exception $e) {
        $message_erreur = "Impossible de récupérer les informations de classe.";
    }
}

// Récupérer les étudiants et leurs notes pour la matière sélectionnée
if ($id_matiere_selected > 0 && isset($nom_filiere)) {
    try {
        $sql_etudiants = "SELECT p.id_personne, p.nom, p.prenom, 
                          e.id_etudiant, e.niveau_filiere as niveau_etudiant, 
                          ev.cc, ev.tp, ev.id_anonymat, a.CF
                          FROM personne p
                          JOIN etudiant e ON p.id_personne = e.id_etudiant 
                          JOIN evaluer ev ON e.id_etudiant = ev.id_etudiant 
                          LEFT JOIN anonymat a ON a.id_anonymat = ev.id_anonymat 
                          WHERE ev.id_matiere = ?
                          AND e.id_filiere = (SELECT id_filiere FROM filiere WHERE nom_filiere = ?)
                          ORDER BY p.nom, p.prenom";
        
        $stmt = $conn->prepare($sql_etudiants);
        $stmt->bind_param("is", $id_matiere_selected, $nom_filiere);
        $stmt->execute();
        $result_etudiants = $stmt->get_result();
        
        $etudiants = [];
        while ($row = $result_etudiants->fetch_assoc()) {
            $etudiants[] = [
                'id_etudiant' => $row['id_etudiant'],
                'nom_complet' => $row['prenom'] . ' ' . $row['nom'],
                'niveau' => $row['niveau_etudiant'], // Ajout du niveau de l'étudiant
                'cc' => $row['cc'],
                'tp' => $row['tp'],
                'id_anonymat' => $row['id_anonymat'],
                'CF' => $row['CF']
            ];
        }
    } catch (Exception $e) {
        $message_erreur = "Impossible de récupérer la liste des étudiants.";
    }
}

// Récupérer les étudiants et leurs notes pour la matière sélectionnée
if ($id_matiere_selected > 0 && isset($nom_filiere)) {
    try {
        $sql_etudiants = "SELECT p.id_personne, p.nom, p.prenom, 
                          e.id_etudiant, e.niveau_filiere as niveau_etudiant, 
                          ev.cc, ev.tp, ev.id_anonymat, a.CF
                          FROM personne p
                          JOIN etudiant e ON p.id_personne = e.id_etudiant 
                          JOIN evaluer ev ON e.id_etudiant = ev.id_etudiant 
                          LEFT JOIN anonymat a ON a.id_anonymat = ev.id_anonymat 
                          WHERE ev.id_matiere = ?
                          AND e.id_filiere = (SELECT id_filiere FROM filiere WHERE nom_filiere = ?)
                          ORDER BY p.nom, p.prenom";
        
        $stmt = $conn->prepare($sql_etudiants);
        $stmt->bind_param("is", $id_matiere_selected, $nom_filiere);
        $stmt->execute();
        $result_etudiants = $stmt->get_result();
        
        $etudiants = [];
        while ($row = $result_etudiants->fetch_assoc()) {
            $etudiants[] = [
                'id_etudiant' => $row['id_etudiant'],
                'nom_complet' => $row['prenom'] . ' ' . $row['nom'],
                'niveau' => $row['niveau_etudiant'], // Ajout du niveau de l'étudiant
                'cc' => $row['cc'],
                'tp' => $row['tp'],
                'id_anonymat' => $row['id_anonymat'],
                'CF' => $row['CF']
            ];
        }
    } catch (Exception $e) {
        $message_erreur = "Impossible de récupérer la liste des étudiants.";
    }
}


// Récupérer les étudiants et leurs notes pour la matière sélectionnée
if ($id_matiere_selected > 0 && isset($nom_filiere)) {
    try {
        $sql_etudiants = "SELECT p.id_personne, p.nom, p.prenom, 
                          e.id_etudiant, ev.cc, ev.tp, ev.id_anonymat, a.CF
                          FROM personne p
                          JOIN etudiant e ON p.id_personne = e.id_etudiant 
                          JOIN evaluer ev ON e.id_etudiant = ev.id_etudiant 
                          LEFT JOIN anonymat a ON a.id_anonymat = ev.id_anonymat 
                          WHERE ev.id_matiere = ?
                          AND e.id_filiere = (SELECT id_filiere FROM filiere WHERE nom_filiere = ?)
                          ORDER BY p.nom, p.prenom";
        
        $stmt = $conn->prepare($sql_etudiants);
        $stmt->bind_param("is", $id_matiere_selected, $nom_filiere);
        $stmt->execute();
        $result_etudiants = $stmt->get_result();
        
        $etudiants = [];
        while ($row = $result_etudiants->fetch_assoc()) {
            $etudiants[] = [
                'id_etudiant' => $row['id_etudiant'],
                'nom_complet' => $row['prenom'] . ' ' . $row['nom'],
                'cc' => $row['cc'],
                'tp' => $row['tp'],
                'id_anonymat' => $row['id_anonymat'],
                'CF' => $row['CF']
            ];
        }
    } catch (Exception $e) {
        $message_erreur = "Impossible de récupérer la liste des étudiants.";
    }
}

// Traitement des formulaires de mise à jour des notes
$message_success = "";
$message_erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$periode_active) {
        $message_erreur = "La période de modification des notes pour cette matière est terminée.";
    } else {
        // Fonction de validation des notes
        function validerNote($note) {
            if ($note === "") return NULL;
            $note = floatval($note);
            if ($note < 0 || $note > 20) return false;
            return $note;
        }
        
        // Mise à jour des notes CC
        if (isset($_POST['deposer_cc']) && isset($_POST['notes_cc'])) {
            $conn->begin_transaction();
            
            try {
                foreach ($_POST['notes_cc'] as $id_etudiant => $note_cc) {
                    $note_validee = validerNote($note_cc);
                    
                    if ($note_validee === false) {
                        throw new Exception("La note CC doit être entre 0 et 20 pour l'étudiant #$id_etudiant.");
                    }
                    
                    $sql_update = "UPDATE evaluer SET cc = ? WHERE id_etudiant = ? AND id_matiere = ?";
                    $stmt = $conn->prepare($sql_update);
                    $stmt->bind_param("dii", $note_validee, $id_etudiant, $id_matiere_selected);
                    $stmt->execute();
                }
                
                $conn->commit();
                $message_success = "Les notes CC ont été mises à jour avec succès.";
                
                // Recharger les données après mise à jour
                header("Location: consulter_note.php?matiere=" . $id_matiere_selected);
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $message_erreur = $e->getMessage();
            }
        }
        
        // Mise à jour des notes TP
        if (isset($_POST['deposer_tp'])) {
            $conn->begin_transaction();
            
            try {
                foreach ($_POST['notes_tp'] as $id_etudiant => $note_tp) {
                    $note_validee = validerNote($note_tp);
                    
                    if ($note_validee === false) {
                        throw new Exception("La note TP doit être entre 0 et 20 pour l'étudiant #$id_etudiant.");
                    }
                    
                    $sql_update = "UPDATE evaluer SET tp = ? WHERE id_etudiant = ? AND id_matiere = ?";
                    $stmt = $conn->prepare($sql_update);
                    $stmt->bind_param("dii", $note_validee, $id_etudiant, $id_matiere_selected);
                    $stmt->execute();
                }
                
                $conn->commit();
                $message_success = "Les notes TP ont été mises à jour avec succès.";
                
                // Recharger les données après mise à jour
                header("Location: consulter_note.php?matiere=" . $id_matiere_selected);
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $message_erreur = $e->getMessage();
            }
        }
        
        // Mise à jour des notes CF
        if (isset($_POST['deposer_cf'])) {
            $conn->begin_transaction();
            
            try {
                foreach ($_POST['notes_cf'] as $id_anonymat => $note_cf) {
                    $note_validee = validerNote($note_cf);
                    
                    if ($note_validee === false) {
                        throw new Exception("La note CF doit être entre 0 et 20 pour l'anonymat #$id_anonymat.");
                    }
                    
                    $sql_update = "UPDATE anonymat SET CF = ? WHERE id_anonymat = ?";
                    $stmt = $conn->prepare($sql_update);
                    $stmt->bind_param("di", $note_validee, $id_anonymat);
                    $stmt->execute();
                }
                
                $conn->commit();
                $message_success = "Les notes CF ont été mises à jour avec succès.";
                
                // Recharger les données après mise à jour
                header("Location: consulter_note.php?matiere=" . $id_matiere_selected);
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $message_erreur = $e->getMessage();
            }
        }
        
        // Génération des fichiers CSV séparés
        if (isset($_POST['telecharger_cc']) || isset($_POST['telecharger_tp']) || isset($_POST['telecharger_cf'])) {
            try {
                $type_notes = '';
                $nom_fichier = '';
                $contenu_csv = '';
                
                if (isset($_POST['telecharger_cc'])) {
                    $type_notes = 'CC';
                    $nom_fichier = 'notes_cc_' . $id_matiere_selected . '_' . date('Y-m-d') . '.csv';
                    $contenu_csv = "ID Étudiant;Nom complet;Note CC\n";
                    
                    foreach ($etudiants as $etudiant) {
                        $contenu_csv .= sprintf(
                            "%s;%s;%s\n",
                            $etudiant['id_etudiant'],
                            $etudiant['nom_complet'],
                            isset($etudiant['cc']) ? $etudiant['cc'] : ''
                        );
                    }
                }
                elseif (isset($_POST['telecharger_tp'])) {
                    $type_notes = 'TP';
                    $nom_fichier = 'notes_tp_' . $id_matiere_selected . '_' . date('Y-m-d') . '.csv';
                    $contenu_csv = "ID Étudiant;Nom complet;Note TP\n";
                    
                    foreach ($etudiants as $etudiant) {
                        $contenu_csv .= sprintf(
                            "%s;%s;%s\n",
                            $etudiant['id_etudiant'],
                            $etudiant['nom_complet'],
                            isset($etudiant['tp']) ? $etudiant['tp'] : ''
                        );
                    }
                }
                elseif (isset($_POST['telecharger_cf'])) {
                    $type_notes = 'CF';
                    $nom_fichier = 'notes_cf_' . $id_matiere_selected . '_' . date('Y-m-d') . '.csv';
                    $contenu_csv = "ID Anonymat;Note CF\n";
                    
                    foreach ($etudiants as $etudiant) {
                        $contenu_csv .= sprintf(
                            "%s;%s\n",
                            isset($etudiant['id_anonymat']) ? $etudiant['id_anonymat'] : 'Non assigné',
                            isset($etudiant['CF']) ? $etudiant['CF'] : ''
                        );
                    }
                }
                
                // Envoyer les en-têtes pour forcer le téléchargement
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $nom_fichier);
                echo $contenu_csv;
                exit();
                
            } catch (Exception $e) {
                $message_erreur = "Erreur lors de la génération du fichier : " . $e->getMessage();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation des notes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    <link rel="stylesheet" href="../../../public/assets/css/consulter_notes.css">
</head>
<body>
    <div class="dashboard">
        <!-- Barre latérale moderne -->
        <aside class="left-side">
            <div class="logo">
                <img src="../../../public/assets/img/U-remove.png" alt="Logo U-Digital">
            </div>
            <nav>
                <ul>
                    <a href="acceuil_enseignant.php"><li><i class="bi bi-house"></i><span>Accueil</span></li></a>
                    <a href="profile_enseignant.php"><li><i class="bi bi-person"></i><span>Profil</span></li></a>
                    <a href="deposer_cours.php"><li><i class="bi bi-book"></i><span>Dépôts des cours</span></li></a>
                    <a href="enseignant_planning.php"><li><i class="bi bi-calendar"></i><span>Consulter les planning</span></li></a>
                    <a href="deposer_notes.php"><li><i class="bi bi-journal"></i><span>Dépôts des notes</span></li></a>
                    <a href="consulter_note.php"><li><i class="bi bi-question-circle"></i><span>Consulter les notes</span></li></a>
                    <div class="deconnection">
                        <a href="../../../config/logout.php"><i class="bi bi-box-arrow-right"></i><span>Déconnexion</span></a>
                    </div>
                </ul>
            </nav>
        </aside>

        <!-- Contenu principal élégant -->
        <main class="main-content">
            <header>
                <div class="date-info">
                    <?php 
                    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
                    $mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                    
                    $jour_semaine = $jours[date('w')];
                    $jour_mois = date('d');
                    $mois_annee = $mois[date('n') - 1];
                    $annee = date('Y');
                    $heure = date('H:i');
                    
                    echo ucfirst($jour_semaine) . ' ' . $jour_mois . ' ' . $mois_annee . ' ' . $annee . ' - ' . $heure;
                    ?>
                </div>
                <div class="profile">
                    <div class="profile-info">
                        <div class="profile-name"><?php echo htmlspecialchars($nom_complet); ?></div>
                        <div class="profile-role">Enseignant</div>
                    </div>
                    <div class="profile-initials">
                        <?php 
                        $initiales = '';
                        if (!empty($prenom)) $initiales .= strtoupper(substr($prenom, 0, 1));
                        if (!empty($nom)) $initiales .= strtoupper(substr($nom, 0, 1));
                        echo htmlspecialchars($initiales);
                        ?>
                    </div>
                </div>
            </header>

            <?php if (!empty($message_erreur)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $message_erreur; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($message_success)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?php echo $message_success; ?>
                </div>
            <?php endif; ?>

            <div class="matiere-selector">
                <h2>Sélectionner une matière</h2>
                <form action="" method="GET">
                    <select name="matiere" id="matiere">
                        <?php foreach ($matieres as $matiere): ?>
                            <option value="<?php echo $matiere['id_matiere']; ?>" <?php echo ($matiere['id_matiere'] == $id_matiere_selected) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($matiere['nom_matiere']); ?> (Semestre <?php echo $matiere['type_semestre']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Afficher les notes</button>
                </form>
            </div>

            <?php if (!empty($etudiants) && $id_matiere_selected > 0): ?>
                <div class="periode-info <?php echo $periode_active ? 'periode-active' : ($aujourdhui < $date_debut ? 'periode-inactive' : 'periode-terminee'); ?>">
                    <h3>Période de modification des notes</h3>
                    <p>
                        Semestre <?php echo $semestre; ?> : 
                        du <?php echo date('d/m/Y', strtotime($date_debut)); ?> 
                        au <?php echo date('d/m/Y', strtotime($date_fin)); ?>
                    </p>
                    <p>
                        <strong>Statut :</strong> 
                        <?php if ($periode_active): ?>
                            <span style="color: var(--success);">Période active - Vous pouvez modifier les notes</span>
                        <?php else: ?>
                            <?php if (date('Y-m-d') < $date_debut): ?>
                                <span style="color: var(--warning);">Période pas encore commencée</span>
                            <?php else: ?>
                                <span style="color: var(--danger);">Période terminée - Consultation seule</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </p>
                    
                    <?php if (!$periode_active): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> La période de modification est <?php echo (date('Y-m-d') < $date_debut) ? 'pas encore commencée' : 'terminée'; ?>. Vous ne pouvez plus modifier les notes pour cette matière.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Matière: <strong><?php echo htmlspecialchars($nom_matiere_selected); ?></strong> | 
                    Filière: <strong><?php echo htmlspecialchars($nom_filiere); ?></strong> | 
                    Niveau: <strong><?php echo htmlspecialchars($niveau); ?></strong> | 
                    Coefficient: <strong><?php echo htmlspecialchars(isset($classe_info['coeff']) ? $classe_info['coeff'] : 'N/A'); ?></strong> | 
                    Nombre d'étudiants: <strong><?php echo count($etudiants); ?></strong>
                </div>

                <!-- Boutons de téléchargement -->
                <div class="download-buttons">
                    <form method="POST" action="">
                        <button type="submit" name="telecharger_cc">
                            <i class="bi bi-download"></i> Télécharger CC (CSV)
                        </button>
                    </form>
                    <form method="POST" action="">
                        <button type="submit" name="telecharger_tp">
                            <i class="bi bi-download"></i> Télécharger TP (CSV)
                        </button>
                    </form>
                    <form method="POST" action="">
                        <button type="submit" name="telecharger_cf">
                            <i class="bi bi-download"></i> Télécharger CF (CSV)
                        </button>
                    </form>
                </div>

                <div class="tabs">
                    <button class="tab active" onclick="openTab(event, 'tab-cc')">Notes CC</button>
                    <button class="tab" onclick="openTab(event, 'tab-tp')">Notes TP</button>
                    <button class="tab" onclick="openTab(event, 'tab-cf')">Notes CF</button>
                </div>

                <div id="tab-cc" class="tab-content active">
                    <form method="POST" action="">
                        <h2>Notes de Contrôle Continu (CC)</h2>
                        <div class="table-container">
                            <table class="notes-table">
                                <thead>
                                    <tr>
                                        <th>ID Étudiant</th>
                                        <th>Nom complet</th>
                                        <th>Note CC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($etudiants as $etudiant): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($etudiant['id_etudiant']); ?></td>
                                            <td><?php echo htmlspecialchars($etudiant['nom_complet']); ?></td>
                                            <td>
                                                <input type="number" name="notes_cc[<?php echo $etudiant['id_etudiant']; ?>]" 
                                                    value="<?php echo !is_null($etudiant['cc']) ? htmlspecialchars($etudiant['cc']) : ''; ?>" 
                                                    step="0.01" min="0" max="20" placeholder="Note CC"
                                                    class="note-input"
                                                    <?php echo !$periode_active ? 'disabled' : ''; ?>>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="action-buttons">
                            <button type="submit" name="deposer_cc" class="primary" <?php echo !$periode_active ? 'disabled' : ''; ?>>
                                <i class="bi bi-save"></i> Enregistrer les notes CC
                            </button>
                        </div>
                    </form>
                </div>

                <div id="tab-tp" class="tab-content">
                    <form method="POST" action="">
                        <h2>Notes de Travaux Pratiques (TP)</h2>
                        <div class="table-container">
                            <table class="notes-table">
                                <thead>
                                    <tr>
                                        <th>ID Étudiant</th>
                                        <th>Nom complet</th>
                                        <th>Note TP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($etudiants as $etudiant): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($etudiant['id_etudiant']); ?></td>
                                            <td><?php echo htmlspecialchars($etudiant['nom_complet']); ?></td>
                                            <td>
                                                <input type="number" name="notes_tp[<?php echo $etudiant['id_etudiant']; ?>]" 
                                                    value="<?php echo !is_null($etudiant['tp']) ? htmlspecialchars($etudiant['tp']) : ''; ?>" 
                                                    step="0.01" min="0" max="20" placeholder="Note TP"
                                                    class="note-input"
                                                    <?php echo !$periode_active ? 'disabled' : ''; ?>>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="action-buttons">
                            <button type="submit" name="deposer_tp" class="primary" <?php echo !$periode_active ? 'disabled' : ''; ?>>
                                <i class="bi bi-save"></i> Enregistrer les notes TP
                            </button>
                        </div>
                    </form>
                </div>

                <div id="tab-cf" class="tab-content">
                    <form method="POST" action="">
                        <h2>Notes de Contrôle Final (CF)</h2>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Les notes CF sont associées aux numéros d'anonymat pour garantir l'impartialité de l'évaluation.
                        </div>
                        <div class="table-container">
                            <table class="notes-table">
                                <thead>
                                    <tr>
                                        <th>ID Anonymat</th>
                                        <th>Note CF</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($etudiants as $etudiant): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(isset($etudiant['id_anonymat']) ? $etudiant['id_anonymat'] : 'Non assigné'); ?></td>
                                            <td>
                                                <?php if (!empty($etudiant['id_anonymat'])): ?>
                                                    <input type="number" name="notes_cf[<?php echo $etudiant['id_anonymat']; ?>]" 
                                                        value="<?php echo !is_null($etudiant['CF']) ? htmlspecialchars($etudiant['CF']) : ''; ?>" 
                                                        step="0.01" min="0" max="20" placeholder="Note CF"
                                                        class="note-input"
                                                        <?php echo !$periode_active ? 'disabled' : ''; ?>>
                                                <?php else: ?>
                                                    <span class="text-muted">Numéro d'anonymat requis</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="action-buttons">
                            <button type="submit" name="deposer_cf" class="primary" <?php echo empty($etudiant['id_anonymat']) || !$periode_active ? 'disabled' : ''; ?>>
                                <i class="bi bi-save"></i> Enregistrer les notes CF
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="../../../public/assets/js/consulter_note_enseignant.js"></script>

</body>
</html>