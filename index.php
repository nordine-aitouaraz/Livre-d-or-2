<?php
session_start();

// Récupérer et afficher les messages
$message_bienvenue = $_SESSION['message_bienvenue'] ?? '';
unset($_SESSION['message_bienvenue']);

$message_deconnexion = $_SESSION['message_deconnexion'] ?? '';
unset($_SESSION['message_deconnexion']);

// Vérifier si l'utilisateur est connecté
$utilisateur_connecte = isset($_SESSION['utilisateur_id']);
$nom_utilisateur = '';
if ($utilisateur_connecte) {
    $nom_utilisateur = $_SESSION['utilisateur_prenom'] . ' ' . $_SESSION['utilisateur_nom'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livre d'Or - Accueil</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <h2>📖 Livre d'Or</h2>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link active">Accueil</a>
                    </li>
                    <?php if ($utilisateur_connecte): ?>
                        <li class="nav-item">
                            <a href="livre-or.php" class="nav-link">Livre d'Or</a>
                        </li>
                        <li class="nav-item">
                            <a href="profil.php" class="nav-link">Profil</a>
                        </li>
                        <li class="nav-item">
                            <a href="deconnexion.php" class="nav-link">Déconnexion</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="inscription.php" class="nav-link">Inscription</a>
                        </li>
                        <li class="nav-item">
                            <a href="connexion.php" class="nav-link">Connexion</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="nav-toggle" id="mobile-menu">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <?php if (!empty($message_bienvenue)): ?>
            <div class="alert alert-success" style="margin: 20px auto; max-width: 1200px;">
                <div class="container">
                    <?php echo htmlspecialchars($message_bienvenue); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($message_deconnexion)): ?>
            <div class="alert alert-info" style="margin: 20px auto; max-width: 1200px;">
                <div class="container">
                    <?php echo htmlspecialchars($message_deconnexion); ?>
                </div>
            </div>
        <?php endif; ?>

        <section class="hero">
            <div class="container">
                <?php if ($utilisateur_connecte): ?>
                    <h1>Bonjour <?php echo htmlspecialchars($nom_utilisateur); ?> !</h1>
                    <p class="hero-text">
                        Bienvenue sur votre livre d'or personnel. Vous pouvez maintenant consulter 
                        tous les messages, ajouter vos propres témoignages et interagir avec la communauté.
                    </p>
                    <div class="hero-buttons">
                        <a href="livre-or.php" class="btn btn-primary">Voir le Livre d'Or</a>
                        <a href="commentaire.php" class="btn btn-secondary">Ajouter un commentaire</a>
                    </div>
                <?php else: ?>
                    <h1>Bienvenue sur notre Livre d'Or</h1>
                    <p class="hero-text">
                        Partagez vos expériences, vos témoignages et vos messages avec notre communauté. 
                        Notre livre d'or numérique vous permet de laisser une trace de votre passage 
                        et de découvrir les messages laissés par d'autres visiteurs.
                    </p>
                    <div class="hero-buttons">
                        <a href="inscription.php" class="btn btn-primary">S'inscrire</a>
                        <a href="connexion.php" class="btn btn-secondary">Se connecter</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="features">
            <div class="container">
                <h2>Fonctionnalités</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">✍️</div>
                        <h3>Rédigez vos messages</h3>
                        <p>Partagez vos pensées, témoignages et expériences avec la communauté.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">👥</div>
                        <h3>Communauté active</h3>
                        <p>Rejoignez une communauté de personnes partageant leurs expériences.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🔒</div>
                        <h3>Sécurisé et privé</h3>
                        <p>Vos données sont protégées et votre vie privée respectée.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="about">
            <div class="container">
                <div class="about-content">
                    <h2>À propos du Livre d'Or</h2>
                    <p>
                        Ce livre d'or numérique a été créé pour permettre aux visiteurs de partager 
                        leurs expériences et leurs témoignages. Que vous souhaitiez laisser un message 
                        de remerciement, partager une anecdote ou simplement dire bonjour, ce site 
                        est fait pour vous.
                    </p>
                    <p>
                        Pour commencer, inscrivez-vous gratuitement et rejoignez notre communauté. 
                        Une fois connecté, vous pourrez lire les messages des autres utilisateurs 
                        et laisser vos propres témoignages.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Livre d'Or. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
        // Menu mobile toggle
        const mobileMenu = document.getElementById('mobile-menu');
        const navMenu = document.querySelector('.nav-menu');

        mobileMenu.addEventListener('click', function() {
            mobileMenu.classList.toggle('is-active');
            navMenu.classList.toggle('active');
        });

        // Auto-hide du message de bienvenue après 5 secondes
        const alertMessages = document.querySelectorAll('.alert-success, .alert-info');
        alertMessages.forEach(function(alertMessage) {
            setTimeout(function() {
                alertMessage.style.opacity = '0';
                alertMessage.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    alertMessage.style.display = 'none';
                }, 500);
            }, 5000);
        });
    </script>
</body>
</html>