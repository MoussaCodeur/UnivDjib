<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connectez-Vous!</title>
    <!-- Lien vers le fichier CSS externe -->
    <link rel="stylesheet" href="Connexion.css">
</head>
<body>
<?php
// Paramètres de connexion à la base de données
$servername = "localhost";
$username = "root";
$password = ""; // Mot de passe vide
$database = "GestionCourUniversitaire";

// Connexion au serveur MySQL
$conn = new mysqli($servername, $username, $password, $database);

// Vérification de la connexion
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Initialisation des variables
$error = "";

// Traitement du formulaire après soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération et sécurisation des données du formulaire
    $userID = mysqli_real_escape_string($conn, $_POST['userID']);
    $password = $_POST['password']; // Ne pas hacher ici, vérifier avec password_verify()

    // Vérification pour l'administrateur
    if ($userID === "240001404" && $password === "GestionCourUniversitaire24") {
        echo "<script>alert('Connexion réussie en tant qu’administrateur !');</script>";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Tables et colonnes spécifiques pour ID et mot de passe
    $roles = [
        "etudiant" => ["id_column" => "Id_etudiant", "password_column" => "mot_de_passe_etudiant", "redirect" => "acceuil_etudiant.php"],
        "enseignant" => ["id_column" => "Id_enseignant", "password_column" => "mot_de_passe_enseignant", "redirect" => "acceuil_enseignant.php"],
        "assistant" => ["id_column" => "Id_assistant", "password_column" => "mot_de_passe_assistant", "redirect" => "acceuil_assistant.php"],
        "jury" => ["id_column" => "Id_jury", "password_column" => "mot_de_passe_jury", "redirect" => "acceuil_jury.php"]
    ];

    // Vérification dans chaque table
    foreach ($roles as $role => $details) {
        $sql = "SELECT {$details['password_column']} FROM $role WHERE {$details['id_column']} = ?";
        $stmt = $conn->prepare($sql);

        // Vérifiez si la préparation a échoué
        if (!$stmt) {
            die("Erreur dans la requête SQL pour le rôle '$role': " . $conn->error);
        }

        // Liage des paramètres
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            // Récupérer le mot de passe haché de la base
            $row = $result->fetch_assoc();
            $hashedPassword = $row[$details['password_column']];

            // Vérification du mot de passe
            if (password_verify($password, $hashedPassword)) {
                echo "<script>alert('Connexion réussie en tant que $role !');</script>";
                // Redirection vers une page spécifique
                header("Location: {$details['redirect']}");
                exit();
            }
        }

        $stmt->close();
    }

    // Si aucun résultat trouvé ou mot de passe incorrect, afficher une erreur
    $error = "ID ou mot de passe incorrect.";
}

// Fermeture de la connexion
$conn->close();
?>

<div class="form-container">
        <fieldset>
            <legend>Connectez-vous</legend>
            <!-- Le formulaire soumet au fichier lui-même -->
            <form method="POST" action="">
                <!-- Champ ID -->
                <div class="form-group">
                    <label for="userID">ID</label>
                    <input type="text" id="userID"  name="userID" placeholder="Entrez votre ID" required>
                </div>
                <!-- Champ mot de passe -->
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
                        <span class="toggle-password" id="togglePassword">&#128065;</span>
                    </div>
                    <a href="forget_password.php" class="forget-password">Mot de passe oublié ?</a>
                </div>

                <!-- Bouton de connexion -->
                <div class="form-group">
                    <button type="submit">Connexion</button>
                </div>
            </form>
            <?php
            // Affichage du message d'erreur en cas de problème
            if (!empty($error)) {
                echo "<p style='color: red; text-align: center;'>$error</p>";
            }
            ?>
        </fieldset>
    </div>

    <!-- Script JavaScript pour afficher/masquer le mot de passe -->
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            // Change le type de l'input
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Change l'icône du bouton
            this.textContent = type === 'password' ? '🙈' : '👁️';
        });
    </script>
</body>
</html>

