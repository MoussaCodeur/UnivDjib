<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

// Récupérer l'ID de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'];

$role_query = $conn->prepare("SELECT role FROM personne WHERE id_personne = ?");
$role_query->bind_param("i", $user_id);
$role_query->execute();
$role_result = $role_query->get_result()->fetch_assoc();


// Récupération des informations du directeur
$directeur_query = $conn->prepare("
    SELECT p.*, d.departement 
    FROM personne p 
    LEFT JOIN directeur_etude d ON p.id_personne = d.id_directeur
    WHERE p.id_personne = ?
");

// Vérification que la préparation a réussi
if ($directeur_query === false) {
    die("Erreur de préparation de la requête: " . $conn->error);
}

$directeur_query->bind_param("i", $user_id);
$directeur_query->execute();
$directeur = $directeur_query->get_result()->fetch_assoc();
// Statistiques du directeur
$stats_query = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM etudiant) as nb_etudiants,
        (SELECT COUNT(*) FROM filiere) as nb_filieres,
        (SELECT COUNT(*) FROM enseignant) as nb_enseignants,
        (SELECT COUNT(*) FROM matiere) as nb_matieres
");
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();

// Gestion de la mise à jour du profil
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nom = $conn->real_escape_string($_POST['nom']);
    $prenom = $conn->real_escape_string($_POST['prenom']);
    $email = $conn->real_escape_string($_POST['email']);
    
    // Mise à jour des informations de base
    $update_query = $conn->prepare("
        UPDATE personne 
        SET nom = ?, prenom = ?, email = ?
        WHERE id_personne = ?
    ");
    $update_query->bind_param("sssi", $nom, $prenom, $email, $user_id);
    $update_result = $update_query->execute();
    
    
    if ($update_result) {
        $message = 'Profil mis à jour avec succès.';
        $message_type = 'success';
        
        // Mettre à jour les données affichées
        $directeur_query->execute();
        $directeur = $directeur_query->get_result()->fetch_assoc();
    } else {
        $message = 'Erreur lors de la mise à jour du profil.';
        $message_type = 'error';
    }
}
// Gestion du changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Vérification du mot de passe actuel
    $check_pwd = $conn->prepare("SELECT mot_de_passe FROM personne WHERE id_personne = ?");
    $check_pwd->bind_param("i", $user_id);
    $check_pwd->execute();
    $pwd_result = $check_pwd->get_result()->fetch_assoc();
    
    // Récupération du mot de passe stocké
    $stored_password = $pwd_result['mot_de_passe'];
    
    // Vérifions d'abord avec password_verify (si le mot de passe est hashé avec password_hash)
    $password_matches = password_verify($current_password, $stored_password);
    
    // Si ça ne marche pas, essayons une comparaison directe (si le mot de passe est en texte clair)
    if (!$password_matches && $current_password === $stored_password) {
        $password_matches = true;
    }
    
    // Si ça ne marche toujours pas, essayons avec MD5 (si le mot de passe est hashé avec MD5)
    if (!$password_matches && md5($current_password) === $stored_password) {
        $password_matches = true;
    }
    
    if ($password_matches) {
        if ($new_password === $confirm_password) {
            // Pour être sûr, on va maintenant stocker le nouveau mot de passe avec password_hash
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pwd = $conn->prepare("UPDATE personne SET mot_de_passe = ? WHERE id_personne = ?");
            $update_pwd->bind_param("si", $hashed_password, $user_id);
            $update_pwd_result = $update_pwd->execute();
            
            if ($update_pwd_result) {
                $message = 'Mot de passe mis à jour avec succès.';
                $message_type = 'success';
            } else {
                $message = 'Erreur lors de la mise à jour du mot de passe.';
                $message_type = 'error';
            }
        } else {
            $message = 'Les nouveaux mots de passe ne correspondent pas.';
            $message_type = 'error';
        }
    } else {
        $message = 'Mot de passe actuel incorrect.';
        $message_type = 'error';
    }
}
// Définir la page active (pour la navigation)
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Directeur - Université</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    <link rel="stylesheet" href="../../../public/assets/css/profil_directeur.css">
</head>
<body class="bg-gray-50 text-gray-800 transition-all">
    <!-- Overlay pour mobile -->
    <div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="flex">
        <!-- Sidebar - toujours visible sur desktop, caché sur mobile -->
        <aside class="sidebar bg-indigo-900 text-white p-6 shadow-xl w-72">
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center space-x-3">
                    <svg class="h-8 w-8 text-indigo-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                        <path d="M6 12v5c0 2 1 3 3 3h6c2 0 3-1 3-3v-5"/>
                    </svg>
                    <h2 class="text-2xl font-bold tracking-tight">Université</h2>
                </div>
                <button class="md:hidden text-2xl focus:outline-none" onclick="toggleSidebar()">×</button>
            </div>
            
            <div class="space-y-1">
                <a href="directeur_etude.php?page=dashboard" class="flex items-center p-3 <?= $page === 'dashboard' ? 'bg-indigo-800' : 'hover:bg-indigo-800' ?> rounded-lg transition-all">
                    <i class="fas fa-chart-line w-6 text-center mr-3 text-indigo-300"></i>
                    <span>Tableau de bord</span>
                </a>
                
                <a href="#" class="flex items-center p-3 <?= $page === 'profile' ? 'bg-indigo-800' : 'hover:bg-indigo-800' ?> rounded-lg transition-all">
                    <i class="fas fa-user w-6 text-center mr-3 text-indigo-300"></i>
                    <span>Profil</span>
                </a>
            </div>
            
            <div class="absolute bottom-6 left-6 right-6">
                <a href="../../../config/logout.php" class="flex items-center p-3 text-indigo-200 hover:bg-indigo-800 rounded-lg transition-all">
                    <i class="fas fa-sign-out-alt w-6 text-center mr-3"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 lg:p-8 overflow-hidden">
            <!-- Mobile menu button - visible uniquement sur mobile -->
            <div class="md:hidden mb-6">
                <button onclick="toggleSidebar()" class="p-2 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 focus:outline-none transition-all">
                    <i class="fas fa-bars text-lg"></i>
                </button>
            </div>

            <!-- Header -->
            <div class="mb-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-1">Profil Directeur</h1>
                        <p class="text-gray-600">Gérez vos informations personnelles</p>
                    </div>
                    <div class="flex space-x-2">
                        <button id="dark-mode-toggle" class="p-2 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-800 focus:outline-none transition-all">
                            <i class="fas fa-moon"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Message d'alerte -->
            <?php if (!empty($message)): ?>
            <div id="alert-message" class="alert alert-<?= $message_type ?> p-4 mb-6 rounded-lg border">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <?php if ($message_type === 'success'): ?>
                            <i class="fas fa-check-circle text-green-500"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                        <?php endif; ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium"><?= $message ?></p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button type="button" class="inline-flex text-gray-400 hover:text-gray-500" onclick="closeAlert()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Profil et Statistiques -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Profil principal -->
                <div class="lg:col-span-2">
                    <div class="card bg-white p-6 rounded-xl shadow-md border border-gray-100 transition-all">
                        <div class="flex items-center mb-6">
                            <div class="flex-shrink-0 mr-4">
                                <div class="h-24 w-24 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-3xl overflow-hidden">
                                    <?php 
                                    // Initiales pour avatar
                                    $initials = strtoupper(substr($directeur['prenom'] ?? 'A', 0, 1) . substr($directeur['nom'] ?? 'D', 0, 1));
                                    echo $initials;
                                    ?>
                                </div>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold"><?= htmlspecialchars($directeur['prenom'] . ' ' . $directeur['nom']) ?></h2>
                                <p class="text-gray-600">Directeur des études</p>
                                <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($directeur['email']) ?></p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-semibold mb-4">Informations personnelles</h3>
                                <ul class="space-y-3">
                                   
                                    <li class="flex">
                                        <span class="text-gray-500 w-24">Date naissance.:</span>
                                        <span><?= htmlspecialchars($directeur['dateNaissance'] ?? 'Non renseignée') ?></span>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-4">Informations professionnelles</h3>
                                <ul class="space-y-3">
                                    <li class="flex">
                                        <span class="text-gray-500 w-24">Département:</span>
                                        <span><?= htmlspecialchars($directeur['departement'] ?? 'Non renseigné') ?></span>
                                    </li>
                                    <li class="flex">
                                        <span class="text-gray-500 w-24">Rôle:</span>
                                        <span class="px-2 py-1 rounded-full text-xs bg-indigo-100 text-indigo-800">
                                            <?= ucfirst(htmlspecialchars($directeur['role'])) ?>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistiques -->
                <div class="lg:col-span-1">
                    <div class="card bg-white p-6 rounded-xl shadow-md border border-gray-100 transition-all h-full">
                        <h3 class="text-lg font-semibold mb-6">Tableau de bord</h3>
                        <ul class="space-y-6">
                            <li class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Étudiants</p>
                                    <p class="text-xl font-semibold"><?= $stats['nb_etudiants'] ?></p>
                                </div>
                            </li>
                            <li class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Enseignants</p>
                                    <p class="text-xl font-semibold"><?= $stats['nb_enseignants'] ?></p>
                                </div>
                            </li>
                            <li class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Filières</p>
                                    <p class="text-xl font-semibold"><?= $stats['nb_filieres'] ?></p>
                                </div>
                            </li>
                            <li class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Matières</p>
                                    <p class="text-xl font-semibold"><?= $stats['nb_matieres'] ?></p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Onglets pour les formulaires -->
            <div class="mb-6">
                <div class="flex border-b border-gray-200">
                    <button id="tab-edit-profile" class="tab-button py-3 px-6 border-b-2 border-indigo-600 font-medium text-indigo-600">
                        Modifier le profil
                    </button>
                    <button id="tab-change-password" class="tab-button py-3 px-6 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700">
                        Changer le mot de passe
                    </button>
                </div>
            </div>

            <!-- Formulaire d'édition du profil -->
            <div id="form-edit-profile" class="form-tab form-card bg-white p-6 rounded-xl shadow-md border border-gray-100 transition-all mb-8">
                <h3 class="text-lg font-semibold mb-4">Modifier vos informations</h3>
                <form method="post" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="nom" class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($directeur['nom']) ?>" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-base">
                        </div>
                        <div>
                            <label for="prenom" class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($directeur['prenom']) ?>" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-base">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($directeur['email']) ?>" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-base">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" name="update_profile" value="1" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <i class="fas fa-save mr-2"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>

            <!-- Formulaire de changement de mot de passe -->
            <div id="form-change-password" class="form-tab form-card bg-white p-6 rounded-xl shadow-md border border-gray-100 transition-all mb-8 hidden">
                <h3 class="text-lg font-semibold mb-4">Changer votre mot de passe</h3>
                <form method="post" action="">
                    <div class="grid grid-cols-1 gap-6 mb-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
                            <input type="password" id="current_password" name="current_password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-base" required>
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                            <input type="password" id="new_password" name="new_password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-base" required>
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le nouveau mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-base" required>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" name="change_password" value="1" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <i class="fas fa-key mr-2"></i> Changer le mot de passe
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('sidebar-active');
            document.getElementById('sidebar-overlay').classList.toggle('active');
            
            // Empêcher le défilement du body quand le menu est ouvert sur mobile
            document.body.classList.toggle('overflow-hidden', document.querySelector('.sidebar').classList.contains('sidebar-active'));
        }
        
        // Dark mode toggle
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        
        // Check for dark mode preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
        
        // Toggle dark mode
        darkModeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            if (document.documentElement.classList.contains('dark')) {
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
        });
        
        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
            if (event.matches) {
                document.documentElement.classList.add('dark');
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                document.documentElement.classList.remove('dark');
                darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
        });
        
        // Tabs functionality
        const tabButtons = document.querySelectorAll('.tab-button');
        const formTabs = document.querySelectorAll('.form-tab');
        
        // Click event for tab buttons
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active state from all tabs
                tabButtons.forEach(btn => {
                    btn.classList.remove('border-indigo-600', 'text-indigo-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });
                
                // Add active state to clicked tab
                this.classList.add('border-indigo-600', 'text-indigo-600');
                this.classList.remove('border-transparent', 'text-gray-500');
                
                // Hide all form tabs
                formTabs.forEach(tab => tab.classList.add('hidden'));
                
                // Show the appropriate form
                if (this.id === 'tab-edit-profile') {
                    document.getElementById('form-edit-profile').classList.remove('hidden');
                } else if (this.id === 'tab-change-password') {
                    document.getElementById('form-change-password').classList.remove('hidden');
                }
            });
        });
        
        // Close alert message
        function closeAlert() {
            const alert = document.getElementById('alert-message');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }
        }
        
        // Auto-hide alert after 5 seconds
        if (document.getElementById('alert-message')) {
            setTimeout(closeAlert, 5000);
        }
    </script>
</body>
</html>