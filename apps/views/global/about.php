<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Université de Djibouti</title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    
    <!------ En-tête ---->
    <section class="sub-header">
        <nav>
            <a href="../../../index.php"><img src="../../../public/assets/img/U-remove.png" alt="Logo Université de Djibouti"></a>
            <div class="nav-links" id="navLinks">
                <i class="fa fa-times" onclick="hidemenu()"></i>
                <ul>
                    <li><a href="../../../index.php">ACCUEIL</a></li>
                    <li><a href="about.php">À PROPOS</a></li>
                    <li class="dropdown">
                        <a href="#">FORMATIONS</a>
                        <!-- Sous-menu -->
                        <ul class="dropdown-menu">
                            <li class="submenu">
                                <a href="#">Facultés <i class="fa fa-caret-down"></i></a>
                                <div class="submenu-content">
                                    <div class="filieres">
                                        <p><a href="#">Faculté des Sciences</a></p>
                                        <p><a href="#">Faculté d’Ingénieur</a></p>
                                        <p><a href="#">Faculté de Médecine</a></p>
                                        <p><a href="#">Faculté de Droit & d'economies gestion</a></p>
                                        <p><a href="#">Faculté FLASH</a></p>
                                    </div>
                                </div>
                            </li>
                            <li class="submenu">
                                <a href="#">Instituts <i class="fa fa-caret-down"></i></a>
                                <div class="submenu-content">
                                    <div class="instituts">
                                        <p><a href="#">Institut Universitaire de technologie industrielles</a></p>
                                        <p><a href="#">Institut Universitaire de technologie tertiare</a></p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </li>
                    <li><a href="Connexion.php">SE CONNECTER</a></li>
                </ul>
            </div>
            <i class="fa fa-bars" onclick="showmenu()"></i>
        </nav>
    </section>  

    <section class="about-us">
        <div class="col">
            <div class="about-col">
                <h1>L'Université de Djibouti, un centre d'excellence</h1>
                <p>L'Université de Djibouti est un établissement d'enseignement supérieur de référence, offrant des formations variées et adaptées aux besoins du marché. Nous nous engageons à fournir une éducation de qualité et à accompagner nos étudiants vers la réussite.</p>
                <a href="#" class="hero-btn red-btn">Explorer Maintenant</a>
            </div>
        </div>
    </section>

    <!-- Après la section about-us et avant la FAQ -->
    <section class="university-history">
        <div class="history-container">
            <div class="history-content">
                <h2>Historique de l'Université de Djibouti</h2>
                <div class="history-text">
                    <img src="../../../public/assets/img/U-remove.png" alt="Logo historique de l'Université" class="history-logo">
                    <p>Fruit de la volonté du Président de la République, Son Excellence M. Ismaïl Omar Guelleh, la décision de transformer en 2006 le Pôle Universitaire de Djibouti (PUD) en une université de plein exercice se révèle aujourd'hui une réussite.</p>
                    <p>L'Université de Djibouti (UD) offre aujourd'hui à plus de onze milles (11 000) étudiants, trente cinq (35) filières d'enseignement distinctes, répartis entre ses sept (07) composantes pédagogiques.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="faculties-section">
        <div class="container">
            <h2>Nos Formations</h2>
            <div class="faculties-grid">
                <div class="faculty-category">
                    <h3><i class="fa fa-graduation-cap"></i> Facultés</h3>
                    <ul>
                        <li>Faculté des Sciences (FS)</li>
                        <li>Faculté de Droit et d'Économie Gestion (FDEG)</li>
                        <li>Faculté des Lettres, Langues et Sciences Humaines (FLLSH)</li>
                        <li>Faculté d'Ingénierie (FI)</li>
                        <li>Faculté de Médecine (FM)</li>
                    </ul>
                </div>
                
                <div class="faculty-category">
                    <h3><i class="fa fa-university"></i> Instituts</h3>
                    <ul>
                        <li>Institut Universitaire de Technologie (IUT)</li>
                        <li>Institut Universitaire de Technologie Industrielle (IUTI)</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    
    <!-- FAQ -->
    <section class="faq">
        <h1>FAQ - Foire Aux Questions</h1>
        <div class="faq-container">
            <div class="faq-item">
                <button class="faq-question">1. Comment postuler à l'Université de Djibouti ?</button>
                <div class="faq-answer">
                    <p>Vous pouvez postuler directement en ligne via notre portail d'admission. Assurez-vous d'avoir tous les documents requis, tels que votre relevé de notes et une copie de votre carte d'identité.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">2. Comment puis-je consulter mes résultats et mes notes ?</button>
                <div class="faq-answer">
                    <p>Les résultats et les notes sont accessibles sur la plateforme en ligne de l'université. Connectez-vous avec vos identifiants étudiants pour y accéder.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">3. Quels sont les horaires des cours ?</button>
                <div class="faq-answer">
                    <p>Les horaires des cours sont publiés chaque semestre sur le site web. Ils sont également disponibles dans votre espace étudiant.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">4. Est-il possible de suivre des cours en ligne ?</button>
                <div class="faq-answer">
                    <p>Oui, l'Université de Djibouti offre des formations à distance dans certains programmes. Vous pouvez consulter les options disponibles via votre espace étudiant.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">5. Qui contacter en cas de problème avec l'inscription ?</button>
                <div class="faq-answer">
                    <p>Si vous avez des problèmes avec votre inscription, veuillez contacter le service des inscriptions à l'adresse suivante : Contact@ud.edu.dj.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">6. Quels sont les programmes disponibles à l'Université de Djibouti ?</button>
                <div class="faq-answer">
                    <p>Nous offrons une large gamme de programmes dans divers domaines tels que l'informatique, l'économie, la médecine, l'ingénierie, et bien d'autres. Vous pouvez consulter la liste complète des formations sur notre site web sous la section "Formations".</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">7. Comment contacter un professeur ou un membre du personnel ?</button>
                <div class="faq-answer">
                    <p>Vous pouvez contacter vos professeurs via la plateforme en ligne ou par email. Les coordonnées sont également disponibles sur les pages de chaque département.</p>
                </div>
            </div>
        </div>
    </section>    
    
<script>
    // Sélectionner toutes les questions
    const faqQuestions = document.querySelectorAll('.faq-question');

    // Ajouter un événement de clic sur chaque question
    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            // Cibler la réponse associée à cette question
            const answer = this.nextElementSibling;
            
            // Vérifier si la réponse est déjà ouverte
            if (answer.classList.contains('open')) {
                // Fermer la réponse en retirant la classe "open"
                answer.classList.remove('open');
            } else {
                // Fermer toutes les autres réponses ouvertes
                document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
                
                // Ouvrir la réponse actuelle
                answer.classList.add('open');
            }
        });
    });
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelector(".text-box").style.opacity = "1";
    document.querySelector(".text-box").style.transform = "translateY(0)";
});
</script>
    <footer class="footer">
    <div class="footer-container">
        <div class="footer-grid">
            <!-- Section Université -->
            <div class="footer-section">
                <!-- <img src="../Images/U-remove.png" alt="Logo Université" class="footer-logo"> -->
                <h4 class="footer-title">Université de Djibouti</h4>
                <p class="footer-motto">L'excellence au service du développement</p>
                <div class="social-vertical">
                    <a href="https://www.facebook.com/UniversiteDeDjibouti/" target="_blank" class="social-link">
                        <i class="fa fa-facebook-square"></i>
                        <span>Facebook</span>
                    </a>
                    <a href="https://twitter.com/univdjibouti" target="_blank" class="social-link">
                        <i class="fa fa-twitter-square"></i>
                        <span>Twitter</span>
                    </a>
                    <a href="https://www.linkedin.com/school/universite-de-djibouti/" target="_blank" class="social-link">
                        <i class="fa fa-linkedin-square"></i>
                        <span>LinkedIn</span>
                    </a>
                    <a href="https://www.youtube.com/@universitededjibouti4015" target="_blank" class="social-link">
                        <i class="fa fa-youtube-play"></i>
                        <span>@universitededjibouti4015</span>
                    </a>
                </div>
            </div>

            <!-- Liens rapides -->
            <div class="footer-section">
                <h4 class="footer-title">Navigation</h4>
                <ul class="footer-links">
                    <p class="footer-motto">L'excellence au service des informations</p>
                    <li>
                        <a href="index.php" class="footer-link">
                            <i class="fa fa-home"></i> <span> Accueil</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="footer-link">
                            <i class="fa fa-university"></i> <span> Admissions</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="footer-link">
                            <i class="fa fa-book"></i> <span> Formations</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="footer-link">
                            <i class="fa fa-newspaper-o"></i> <span> Actualités</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contacts -->
            <div class="footer-section">
                <h4 class="footer-title">Nous contacter</h4>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fa fa-map-marker"></i>
                        <p>Croisement RN2-RN5 | B.P : 1904 Djibouti<br>République de Djibouti</p>
                    </div>
                    <div class="contact-item">
                        <i class="fa fa-phone"></i>
                        <p>+253 21 35 55 55</p>
                    </div>
                    <div class="contact-item">
                        <i class="fa fa-envelope"></i>
                        <a href="mailto:ud@univ.edu.dj" class="footer-link">ud@univ.edu.dj</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© <span id="current-year"></span> Université de Djibouti - Tous droits réservés</p>
        </div>
    </div>
</footer>
    <!------------ JavaScript pour le menu ---->
    <script>
        var navLinks = document.getElementById("navLinks");
        function showmenu(){
            navLinks.style.right = "0";
        }
        function hidemenu(){
            navLinks.style.right = "-200px";
        }

        // Insère automatiquement l'année actuelle dans le span
        document.getElementById('current-year').textContent = new Date().getFullYear();
    </script>
</body>
</html>
