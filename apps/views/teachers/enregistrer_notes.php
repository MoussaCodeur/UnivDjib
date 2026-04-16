<?php

// Connexion à la base de données
require_once '../../../config/db.php';

// Fonction pour valider que la note est entre 0 et 20
function validateNote($note) {
    $note = floatval($note);
    if ($note < 0 || $note > 20) {
        return false;
    }
    return true;
}

// Traitement des notes CC
if (isset($_POST['submit_cc'])) {
    $notes_cc = $_POST['notes_cc'];
    $errors = [];
    
    foreach ($notes_cc as $nom_etudiant => $note) {
        if (!validateNote($note)) {
            $errors[] = "La note CC pour $nom_etudiant doit être comprise entre 0 et 20.";
            continue;
        }
        
        // Préparer la requête SQL pour mettre à jour la note CC
        $sql = "UPDATE evaluer SET cc = :note WHERE id_etudiant = (SELECT id_personne FROM personne WHERE CONCAT(nom, ' ', prenom) = :nom_etudiant)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':note' => $note, ':nom_etudiant' => $nom_etudiant]);
    }
    
    if (empty($errors)) {
        echo "Notes CC enregistrées avec succès!";
    } else {
        echo "Erreurs lors de l'enregistrement des notes CC:<br>";
        foreach ($errors as $error) {
            echo "- $error<br>";
        }
    }
}

// Traitement des notes TP
if (isset($_POST['submit_tp'])) {
    $notes_tp = $_POST['notes_tp'];
    $errors = [];
    
    foreach ($notes_tp as $nom_etudiant => $note) {
        if (!validateNote($note)) {
            $errors[] = "La note TP pour $nom_etudiant doit être comprise entre 0 et 20.";
            continue;
        }
        
        // Préparer la requête SQL pour mettre à jour la note TP
        $sql = "UPDATE evaluer SET tp = :note WHERE id_etudiant = (SELECT id_personne FROM personne WHERE CONCAT(nom, ' ', prenom) = :nom_etudiant)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':note' => $note, ':nom_etudiant' => $nom_etudiant]);
    }
    
    if (empty($errors)) {
        echo "Notes TP enregistrées avec succès!";
    } else {
        echo "Erreurs lors de l'enregistrement des notes TP:<br>";
        foreach ($errors as $error) {
            echo "- $error<br>";
        }
    }
}

// Traitement des notes CF
if (isset($_POST['submit_cf'])) {
    $notes_cf = $_POST['notes_cf'];
    $errors = [];
    
    foreach ($notes_cf as $id_etudiant => $note_cf) {
        if (!validateNote($note_cf)) {
            $errors[] = "La note CF pour l'étudiant avec l'ID $id_etudiant doit être comprise entre 0 et 20.";
            continue;
        }
        
        try {
            // Démarrer une transaction
            $pdo->beginTransaction();
            
            // Récupérer l'id_matiere (fixé à 1 pour cet exemple)
            $id_matiere = 1;
            
            // Générer un id_anonymat unique pour cet étudiant
            $id_anonymat = mt_rand(1000, 9999);
            
            // ID du président du jury (fixé à 16 pour cet exemple)
            $id_president = 16;
            
            // Vérifier que l'id_anonymat est unique
            $unique_id = false;
            while (!$unique_id) {
                $sql_check_id = "SELECT COUNT(*) FROM Anonymat WHERE id_president = :id_president AND id_anonymat = :id_anonymat";
                $stmt_check_id = $pdo->prepare($sql_check_id);
                $stmt_check_id->execute([':id_president' => $id_president, ':id_anonymat' => $id_anonymat]);
                $count = $stmt_check_id->fetchColumn();
                
                if ($count > 0) {
                    // Si cet ID existe déjà, générer un nouveau
                    $id_anonymat = mt_rand(1000, 9999);
                } else {
                    $unique_id = true;
                }
            }
            
            // Insérer dans la table Anonymat
            $sql_insert_anonymat = "INSERT INTO Anonymat (id_anonymat, id_president, CF) 
                                    VALUES (:id_anonymat, :id_president, :CF)";
            $stmt_insert_anonymat = $pdo->prepare($sql_insert_anonymat);
            $stmt_insert_anonymat->execute([
                ':id_anonymat' => $id_anonymat,
                ':id_president' => $id_president,
                ':CF' => $note_cf
            ]);
            
            // Vérifier si une évaluation existe déjà pour cet étudiant et cette matière
            $sql_check_eval = "SELECT id_evaluation FROM Evaluer 
                              WHERE id_etudiant = :id_etudiant AND id_matiere = :id_matiere";
            $stmt_check_eval = $pdo->prepare($sql_check_eval);
            $stmt_check_eval->execute([
                ':id_etudiant' => $id_etudiant,
                ':id_matiere' => $id_matiere
            ]);
            $existing_eval = $stmt_check_eval->fetch(PDO::FETCH_ASSOC);
            
            $date_evaluation = date('Y-m-d'); // Date actuelle
            
            if ($existing_eval) {
                // Mettre à jour l'évaluation existante
                $sql_update_eval = "UPDATE Evaluer 
                                    SET date_evaluation = :date_evaluation, id_anonymat = :id_anonymat 
                                    WHERE id_evaluation = :id_evaluation";
                $stmt_update_eval = $pdo->prepare($sql_update_eval);
                $stmt_update_eval->execute([
                    ':date_evaluation' => $date_evaluation,
                    ':id_anonymat' => $id_anonymat,
                    ':id_evaluation' => $existing_eval['id_evaluation']
                ]);
            } else {
                // Générer un ID unique pour l'évaluation
                $id_evaluation = mt_rand(100, 9999);
                
                // Vérifier que l'id_evaluation est unique
                $unique_eval_id = false;
                while (!$unique_eval_id) {
                    $sql_check_eval_id = "SELECT COUNT(*) FROM Evaluer WHERE id_evaluation = :id_evaluation";
                    $stmt_check_eval_id = $pdo->prepare($sql_check_eval_id);
                    $stmt_check_eval_id->execute([':id_evaluation' => $id_evaluation]);
                    $count = $stmt_check_eval_id->fetchColumn();
                    
                    if ($count > 0) {
                        // Si cet ID existe déjà, générer un nouveau
                        $id_evaluation = mt_rand(100, 9999);
                    } else {
                        $unique_eval_id = true;
                    }
                }
                
                // Insérer une nouvelle évaluation
                $sql_insert_eval = "INSERT INTO Evaluer (id_evaluation, date_evaluation, cc, tp, note, id_matiere, id_etudiant, id_anonymat)
                                   VALUES (:id_evaluation, :date_evaluation, NULL, NULL, NULL, :id_matiere, :id_etudiant, :id_anonymat)";
                $stmt_insert_eval = $pdo->prepare($sql_insert_eval);
                $stmt_insert_eval->execute([
                    ':id_evaluation' => $id_evaluation,
                    ':date_evaluation' => $date_evaluation,
                    ':id_matiere' => $id_matiere,
                    ':id_etudiant' => $id_etudiant,
                    ':id_anonymat' => $id_anonymat
                ]);
            }
            
            // Valider la transaction
            $pdo->commit();
            
        } catch (PDOException $e) {
            // En cas d'erreur, annuler la transaction
            $pdo->rollBack();
            $errors[] = "Erreur lors de l'enregistrement pour l'étudiant ID $id_etudiant: " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        echo "<script>alert('Notes CF déposées avec succès sous anonymat !');</script>";
    } else {
        echo "Erreurs lors de l'enregistrement des notes CF:<br>";
        foreach ($errors as $error) {
            echo "- $error<br>";
        }
    }
}

// Affichage pour vérifier les données
function displayEvaluationsWithAnonymat() {
    global $pdo;
    $sql = "SELECT e.id_evaluation, e.id_etudiant, p.nom, p.prenom, e.cc, e.tp, a.CF, 
                   e.id_anonymat, e.id_matiere, m.nom_matiere
            FROM evaluer e
            LEFT JOIN anonymat a ON e.id_anonymat = a.id_anonymat
            LEFT JOIN personne p ON e.id_etudiant = p.id_personne
            LEFT JOIN matiere m ON e.id_matiere = m.id_matiere
            ORDER BY p.nom, p.prenom";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($evaluations) > 0) {
        echo "<h3>Liste des évaluations avec anonymat</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID Évaluation</th><th>Étudiant</th><th>Matière</th><th>CC</th><th>TP</th><th>CF</th><th>ID Anonymat</th></tr>";
        
        foreach ($evaluations as $eval) {
            echo "<tr>";
            echo "<td>" . $eval['id_evaluation'] . "</td>";
            echo "<td>" . $eval['nom'] . " " . $eval['prenom'] . " (ID: " . $eval['id_etudiant'] . ")</td>";
            echo "<td>" . $eval['nom_matiere'] . " (ID: " . $eval['id_matiere'] . ")</td>";
            echo "<td>" . ($eval['cc'] !== null ? $eval['cc'] : 'N/A') . "</td>";
            echo "<td>" . ($eval['tp'] !== null ? $eval['tp'] : 'N/A') . "</td>";
            echo "<td>" . ($eval['CF'] !== null ? $eval['CF'] : 'N/A') . "</td>";
            echo "<td>" . ($eval['id_anonymat'] !== null ? $eval['id_anonymat'] : 'N/A') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucune évaluation trouvée.</p>";
    }
}

// Appeler la fonction d'affichage si besoin
// displayEvaluationsWithAnonymat();
?>