<?php
session_start();

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $erreurs = [];
    
    // Validation des champs
    if (empty($login)) $erreurs[] = "Le login est obligatoire.";
    if (empty($password)) $erreurs[] = "Le mot de passe est obligatoire.";
    
    // Si pas d'erreurs, traitement de la connexion
    if (empty($erreurs)) {
        try {
            // Configuration de la base de données
            $pdo = new PDO('mysql:host=localhost;dbname=nordine-ait-ouaraz_livreor;charset=utf8', 'nordine-ouaraz', 'Nonozdu92');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Rechercher l'utilisateur par login
            $stmt = $pdo->prepare("SELECT id, nom, prenom, login, mot_de_passe FROM utilisateurs WHERE login = ?");
            $stmt->execute([$login]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($utilisateur && password_verify($password, $utilisateur['mot_de_passe'])) {
                // Connexion réussie
                $_SESSION['utilisateur_id'] = $utilisateur['id'];
                $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
                $_SESSION['utilisateur_prenom'] = $utilisateur['prenom'];
                $_SESSION['utilisateur_login'] = $utilisateur['login'];
                
                // Message de bienvenue
                $_SESSION['message_bienvenue'] = "Bienvenue " . $utilisateur['prenom'] . " " . $utilisateur['nom'] . " ! Vous êtes maintenant connecté(e).";
                
                // Rediriger vers la page d'accueil
                header('Location: index.php');
                exit;
            } else {
                $erreurs[] = "Login ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $erreurs[] = "Erreur de base de données : " . $e->getMessage();
        }
    }
}

// Afficher le message de succès après inscription
$message_succes = $_SESSION['message_succes'] ?? '';
unset($_SESSION['message_succes']);

// Vérifier les erreurs d'URL
$erreur_session = '';
if (isset($_GET['erreur']) && $_GET['erreur'] === 'session_invalide') {
    $erreur_session = "Votre session a expiré. Veuillez vous reconnecter.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Livre d'Or</title>
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
                        <a href="index.php" class="nav-link">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a href="inscription.php" class="nav-link">Inscription</a>
                    </li>
                    <li class="nav-item">
                        <a href="connexion.php" class="nav-link active">Connexion</a>
                    </li>
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
        <section class="form-section">
            <div class="container">
                <div class="form-container">
                    <h1>Connexion</h1>
                    <p class="form-description">Connectez-vous à votre compte pour accéder au livre d'or et partager vos messages.</p>
                    
                    <?php if (!empty($message_succes)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($message_succes); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($erreur_session)): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($erreur_session); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($erreurs)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($erreurs as $erreur): ?>
                                    <li><?php echo htmlspecialchars($erreur); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="connexion.php" class="connexion-form">
                        <div class="form-group">
                            <label for="login">Login</label>
                            <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($login ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <div class="form-options">
                            <label class="checkbox-container">
                                <input type="checkbox" name="se_souvenir">
                                <span class="checkmark"></span>
                                Se souvenir de moi
                            </label>
                            <a href="#" class="forgot-password">Mot de passe oublié ?</a>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-full">Se connecter</button>
                        </div>
                    </form>

                    <div class="form-footer">
                        <p>Vous n'avez pas encore de compte ? <a href="inscription.php">Inscrivez-vous ici</a></p>
                    </div>

                    <div class="social-login">
                        <div class="divider">
                            <span>ou</span>
                        </div>
                        <p class="social-text">Connectez-vous avec vos réseaux sociaux</p>
                        <div class="social-buttons">
                            <button class="btn btn-social btn-google">
                                <span>🔍</span> Google
                            </button>
                            <button class="btn btn-social btn-facebook">
                                <span>📘</span> Facebook
                            </button>
                        </div>
                    </div>
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

        // Validation du formulaire côté client
        document.querySelector('.connexion-form').addEventListener('submit', function(e) {
            const login = document.getElementById('login').value;
            const password = document.getElementById('password').value;
            
            if (!login || !password) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires.');
                return false;
            }
        });

        // Auto-hide des messages d'alerte après 5 secondes
        const alertMessages = document.querySelectorAll('.alert');
        alertMessages.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            }, 5000);
        });
    </script>
</body>
</html>