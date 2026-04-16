<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Université de Djibouti</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet"> -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Swiper CSS -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css">
   <link rel="icon" type="image/x-icon" href="../../../Universite.ico">

</head>
<body>

<!------ Section d'en-tête ---->
<section class="header">
    <nav>
        <a href="index.php"><img src="public/assets/img/U-remove.png" alt="Université de Djibouti" ></a>
        
        <div class="nav-links" id="navLinks">
            <i class="fa fa-times" onclick="hidemenu()"></i>
            <ul>
                <li><a href="index.php">ACCUEIL</a></li>
                <li><a href="apps/views/global/about.php">À PROPOS</a></li>
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
                <li><a href="apps/views/global/Connexion.php">SE CONNECTER</a></li>
            </ul>
        </div>
        <i class="fa fa-bars" onclick="showmenu()"></i>
    </nav>
    <div class="text-box">
        <h1>Université de Djibouti</h1>
        <p>« L’éducation est l’arme la plus puissante que l’on puisse utiliser pour changer le monde. »</p>
        <a href="apps/views/global/about.php" class="hero-btn" style="color: white;">En savoir plus</a>
    </div>
</section>


<section class="course">
    <h1>Nos Formations</h1>
    <p>Découvrez les différentes formations proposées par l'Université de Djibouti.</p>
    
    <div class="row">
        <div class="course-col">
            <img src="public/assets/img/informatique.png" alt="Informatique"  class="course-img">
            <h3>Informatique</h3>
            <p>Formation en développement logiciel, cybersécurité et intelligence artificielle.</p>
        </div>

        <div class="course-col">
            <img src="public/assets/img/economie.jpeg" alt="Économie & Gestion"  class="course-img">
            <h3>Économie & Gestion</h3>
            <p>Programmes en gestion d’entreprise, finance et commerce international.</p>
        </div>

        <div class="course-col">
            <img src="public/assets/img/ingenieur.jpg" alt="Sciences de l’Ingénieur"  class="course-img">
            <h3>Sciences de l’Ingénieur</h3>
            <p>Formations en génie civil, électricité et mécanique.</p>
        </div>
    </div>

    <div class="row">
        <div class="course-col">
            <img src="public/assets/img/medecin.jpg" alt="Médecine & Santé"  class="course-img">
            <h3>Médecine & Santé</h3>
            <p>Études en médecine générale, soins infirmiers, pharmacie et santé publique.</p>
        </div>

        <div class="course-col">
            <img src="public/assets/img/logistique.jpeg" alt="Logistique & Transport"  class="course-img">
            <h3>Logistique & Transport</h3>
            <p>Formations en gestion portuaire, supply chain et logistique internationale.</p>
        </div>

        <div class="course-col">
            <img src="public/assets/img/Faculte-lettres.png" alt="Lettres modernes"  class="course-img">
            <h3>Lettres modernes</h3>
            <p>Programmes en littérature, langues étrangères et études culturelles.</p>
        </div>
    </div>
</section>

   
<section class="campus">
    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
            <!-- Slides -->
            <div class="swiper-slide">
                <div class="oval-frame">
                    <img src="public/assets/img/unidji.jpg" alt="Campus Université de Djibouti">
                    <div class="layer">
                        <h3>Campus Principal</h3>
                    </div>
                </div>
            </div>
            <div class="swiper-slide">
                <div class="oval-frame">
                    <img src="public/assets/img/cloture.jpg" alt="Campus Université de Djibouti">
                    <div class="layer">
                        <h3>Fin d'etudes</h3>
                    </div>
                </div>
            </div>
            <div class="swiper-slide">
                <div class="oval-frame">
                    <img src="public/assets/img/cinema.jpg" alt="Campus Université de Djibouti">
                    <div class="layer">
                        <h3>Conference</h3>
                    </div>
                </div>
            </div>
            <!-- Répétez pour les autres slides -->
        </div>
        <!-- Pagination et navigation -->
        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
    </div>
</section>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
<script>
    const swiper = new Swiper('.mySwiper', {
    loop: true,
    centeredSlides: true,
    slidesPerView: 'auto',
    spaceBetween: 30,
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },
    breakpoints: {
        768: {
            spaceBetween: 50
        }
    }
});
</script>

<!---- Contact ---->
<section class="cta">
    <h1>Rejoignez l'Université de Djibouti</h1>
    <a href="tel:+25321355555"  class="hero-btn">Contactez-nous</a>
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
                <!-- <img src="public/assets/img/U-remove.png" alt="Logo Université" class="footer-logo"> -->
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
                            <i class="fa fa-home"></i> <span>Accueil</span>
                        </a>
                    </li>
                    <li>
                        <a href="about.php" class="footer-link">
                            <i class="fa fa-info-circle"></i> <span>A propos</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="footer-link">
                            <i class="fa fa-book"></i> <span>Formations</span>
                        </a>
                    </li>
                
                    <li>
                        <a href="#" class="footer-link">
                            <i class="fa fa-newspaper-o"></i> <span>Actualités</span>
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
                        <a href="tel:+25321355555" class="footer-link">+253 21 35 55 55</a>
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
<script>
    var navLinks = document.getElementById("navLinks");
    var faTimes = document.querySelector(".fa-times");
    var faBars = document.querySelector(".fa-bars");
    var dropdowns = document.querySelectorAll(".dropdown");

    function showmenu() {
        navLinks.style.right = "0";
        faTimes.style.display = "block";
        faBars.style.display = "none";
    }

    function hidemenu() {
        navLinks.style.right = "-200px";
        faTimes.style.display = "none";
        faBars.style.display = "block";
        
        // Ferme tous les sous-menus
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove("active");
            const submenus = dropdown.querySelectorAll(".submenu-content, .dropdown-menu");
            submenus.forEach(submenu => {
                submenu.style.display = "none";
            });
        });
    }

    // Gestion des clics sur les dropdowns
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener("click", function(e) {
            if (window.innerWidth <= 768) { // Seulement en mobile
                e.preventDefault();
                this.classList.toggle("active");
                
                const submenu = this.querySelector(".dropdown-menu");
                if (submenu) {
                    submenu.style.display = this.classList.contains("active") ? "block" : "none";
                }
            }
        });
    });


    // Insère automatiquement l'année actuelle dans le span
    document.getElementById('current-year').textContent = new Date().getFullYear();
</script>



</body>
</html>