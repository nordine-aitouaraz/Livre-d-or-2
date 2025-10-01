<?php
session_start();

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'] ?? '';
    
    $erreurs = [];
    
    // Validation des champs
    if (empty($nom)) $erreurs[] = "Le nom est obligatoire.";
    if (empty($prenom)) $erreurs[] = "Le prénom est obligatoire.";
    if (empty($login)) $erreurs[] = "Le login est obligatoire.";
    if (empty($mot_de_passe)) $erreurs[] = "Le mot de passe est obligatoire.";
    if (strlen($mot_de_passe) < 6) $erreurs[] = "Le mot de passe doit contenir au moins 6 caractères.";
    if ($mot_de_passe !== $confirmation_mot_de_passe) $erreurs[] = "Les mots de passe ne correspondent pas.";
    
    // Si pas d'erreurs, traitement de l'inscription
    if (empty($erreurs)) {
        try {
            // Configuration de la base de données
            $pdo = new PDO('mysql:host=localhost;dbname=nordine-ait-ouaraz_livreor;charset=utf8', 'nordine-ouaraz', 'Nonozdu92');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Vérifier si le login existe déjà
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE login = ?");
            $stmt->execute([$login]);
            
            if ($stmt->fetch()) {
                $erreurs[] = "Ce login est déjà utilisé.";
            } else {
                // Insérer le nouvel utilisateur
                $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, login, mot_de_passe, date_inscription) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$nom, $prenom, $login, $mot_de_passe_hash]);
                
                $_SESSION['message_succes'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                header('Location: connexion.php');
                exit;
            }
        } catch (PDOException $e) {
            $erreurs[] = "Erreur de base de données : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Livre d'Or</title>
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
                        <a href="inscription.php" class="nav-link active">Inscription</a>
                    </li>
                    <li class="nav-item">
                        <a href="connexion.php" class="nav-link">Connexion</a>
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
                    <h1>Inscription</h1>
                    <p class="form-description">Créez votre compte pour rejoindre notre communauté et partager vos messages dans le livre d'or.</p>
                    
                    <?php if (!empty($erreurs)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($erreurs as $erreur): ?>
                                    <li><?php echo htmlspecialchars($erreur); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="inscription.php" class="inscription-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom">Nom *</label>
                                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($nom ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="prenom">Prénom *</label>
                                <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($prenom ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="login">Login *</label>
                            <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($login ?? ''); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="mot_de_passe">Mot de passe *</label>
                                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                                <small class="form-hint">Au moins 6 caractères</small>
                            </div>
                            <div class="form-group">
                                <label for="confirmation_mot_de_passe">Confirmer le mot de passe *</label>
                                <input type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" required>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-full">S'inscrire</button>
                        </div>
                    </form>

                    <div class="form-footer">
                        <p>Vous avez déjà un compte ? <a href="connexion.php">Connectez-vous ici</a></p>
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
        document.querySelector('.inscription-form').addEventListener('submit', function(e) {
            const motDePasse = document.getElementById('mot_de_passe').value;
            const confirmation = document.getElementById('confirmation_mot_de_passe').value;
            
            if (motDePasse !== confirmation) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return false;
            }
            
            if (motDePasse.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return false;
            }
        });
    </script>
</body>
</html>