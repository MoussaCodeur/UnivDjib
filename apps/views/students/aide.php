<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aides Étudiants(es) - Université de Djibouti</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    <link rel="stylesheet" href="../../../public/assets/css/aide_etudiant.css">
</head>
<body>
    <a href="acceuil_etudiant.php" class="return-button">
        <i class="fas fa-arrow-left"></i>
        <span>Retour à l'espace étudiant</span>
    </a>
    <div class="aide-container">
        <div class="aide-header">
            <h1><i class="fas fa-hands-helping"></i> Aides Étudiantes</h1>
            <p>Découvrez toutes les aides mises à votre disposition par l'Université de Djibouti</p>
            
            <div class="filters">
                <button class="filter-btn active" data-filter="all">Toutes</button>
                <button class="filter-btn" data-filter="financial">Financières</button>
                <button class="filter-btn" data-filter="health">Santé</button>
                <button class="filter-btn" data-filter="housing">Logement</button>
            </div>
        </div>

        <div class="emergency-banner">
            <i class="fas fa-exclamation-triangle fa-2x"></i>
            <div>
                <h3>Aide d'Urgence 24h/24</h3>
                <p>Contactez le service social étudiant : <strong>+253 21 35 55 55</strong></p>
            </div>
        </div>

        <div class="aide-grid">
            <!-- Aide Financière -->
            <div class="aide-card financial">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h3>D-Money</h3>
                </div>
                <span class="badge financial">Bourse d'Études</span>
                <div class="aide-details">
                    <div class="detail-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Jusqu'à 500,000 FD/mois</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-user-graduate"></i>
                        <span>Étudiants régulièrement inscrits</span>
                    </div>
                    <p>Bourse mensuelle sur critères sociaux et académiques</p>
                </div>
                <a href="#" class="cta-button">Postuler en ligne</a>
            </div>

            <!-- Couverture Santé -->
            <div class="aide-card health">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <h3>CNSS Étudiant</h3>
                </div>
                <span class="badge health">Couverture Santé</span>
                <div class="aide-details">
                    <div class="detail-item">
                        <i class="fas fa-hospital"></i>
                        <span>100% couverture hospitalière</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-prescription-bottle"></i>
                        <span>70% remboursement médicaments</span>
                    </div>
                    <p>Accès au réseau de santé national</p>
                </div>
                <a href="#" class="cta-button">Activer ma carte</a>
            </div>

            <!-- Logement -->
            <div class="aide-card housing">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3>Résidences Universitaires</h3>
                </div>
                <span class="badge housing">Hébergement</span>
                <div class="aide-details">
                    <div class="detail-item">
                        <i class="fas fa-bed"></i>
                        <span>Chambres individuelles/partagées</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-wifi"></i>
                        <span>Internet haut débit inclus</span>
                    </div>
                    <p>À partir de 50,000 FD/mois selon profil</p>
                </div>
                <a href="#" class="cta-button">Demander un logement</a>
            </div>

            <!-- Aide supplémentaire -->
            <div class="aide-card financial">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3>Fonds de Solidarité</h3>
                </div>
                <span class="badge financial">Aide Exceptionnelle</span>
                <div class="aide-details">
                    <div class="detail-item">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span>Aide ponctuelle jusqu'à 1,000,000 FD</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-file-alt"></i>
                        <span>Dossier à constituer</span>
                    </div>
                    <p>Pour situations exceptionnelles (médicales, familiales)</p>
                </div>
                <a href="#" class="cta-button">En savoir plus</a>
            </div>
        </div>
    </div>

    <script src="../../../public/assets/js/aide_etudiant.js"></script>

</body>
</html>