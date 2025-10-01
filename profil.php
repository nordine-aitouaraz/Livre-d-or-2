<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

// Configuration de la base de données
try {
    $pdo = new PDO('mysql:host=localhost;dbname=nordine-ait-ouaraz_livreor;charset=utf8', 'nordine-ouaraz', 'Nonozdu92');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$erreurs = [];
$message_succes = '';

// Traitement du formulaire de modification du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau_login = trim($_POST['login'] ?? '');
    $nouveau_password = $_POST['password'] ?? '';
    $confirmation_password = $_POST['confirmation_password'] ?? '';
    
    // Validation
    if (empty($nouveau_login)) $erreurs[] = "Le login est obligatoire.";
    if (!empty($nouveau_password)) {
        if (strlen($nouveau_password) < 6) $erreurs[] = "Le mot de passe doit contenir au moins 6 caractères.";
        if ($nouveau_password !== $confirmation_password) $erreurs[] = "Les mots de passe ne correspondent pas.";
    }
    
    // Vérifier si le login existe déjà (sauf pour l'utilisateur actuel)
    if (!empty($nouveau_login)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE login = ? AND id != ?");
            $stmt->execute([$nouveau_login, $_SESSION['utilisateur_id']]);
            if ($stmt->fetch()) {
                $erreurs[] = "Ce login est déjà utilisé par un autre utilisateur.";
            }
        } catch (PDOException $e) {
            $erreurs[] = "Erreur lors de la vérification du login : " . $e->getMessage();
        }
    }
    
    // Si pas d'erreurs, mettre à jour le profil
    if (empty($erreurs)) {
        try {
            if (!empty($nouveau_password)) {
                // Modifier login et mot de passe
                $password_hash = password_hash($nouveau_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE utilisateurs SET login = ?, mot_de_passe = ? WHERE id = ?");
                $stmt->execute([$nouveau_login, $password_hash, $_SESSION['utilisateur_id']]);
            } else {
                // Modifier seulement le login
                $stmt = $pdo->prepare("UPDATE utilisateurs SET login = ? WHERE id = ?");
                $stmt->execute([$nouveau_login, $_SESSION['utilisateur_id']]);
            }
            
            // Mettre à jour la session
            $_SESSION['utilisateur_login'] = $nouveau_login;
            
            $_SESSION['message_succes'] = "Votre profil a été modifié avec succès.";
            
            // Redirection pour éviter la resoumission
            header('Location: profil.php');
            exit;
        } catch (PDOException $e) {
            $erreurs[] = "Erreur lors de la modification : " . $e->getMessage();
        }
    } else {
        // Stocker les erreurs en session
        $_SESSION['erreurs'] = $erreurs;
        $_SESSION['form_data'] = ['login' => $nouveau_login];
        
        header('Location: profil.php');
        exit;
    }
}

// Récupérer les messages de session et les nettoyer
$message_succes = $_SESSION['message_succes'] ?? '';
unset($_SESSION['message_succes']);

$erreurs = $_SESSION['erreurs'] ?? [];
unset($_SESSION['erreurs']);

$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Récupérer les informations actuelles de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT login FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_SESSION['utilisateur_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $login_actuel = $user_info['login'] ?? '';
} catch (PDOException $e) {
    $login_actuel = '';
    $erreurs[] = "Erreur lors de la récupération des informations : " . $e->getMessage();
}

// Utiliser les données du formulaire ou les données actuelles
$login = $form_data['login'] ?? $login_actuel;

$nom_utilisateur = $_SESSION['utilisateur_prenom'] . ' ' . $_SESSION['utilisateur_nom'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Livre d'Or</title>
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
                        <a href="livre-or.php" class="nav-link">Livre d'Or</a>
                    </li>
                    <li class="nav-item">
                        <a href="profil.php" class="nav-link active">Profil</a>
                    </li>
                    <?php if ($_SESSION['utilisateur_is_admin'] ?? false): ?>
                        <li class="nav-item">
                            <a href="admin.php" class="nav-link">Administration</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="deconnexion.php" class="nav-link">Déconnexion</a>
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
                    <h1>Modifier mon profil</h1>
                    <p class="form-description">Bonjour <?php echo htmlspecialchars($nom_utilisateur); ?>, vous pouvez modifier votre login et votre mot de passe.</p>
                    
                    <?php if (!empty($message_succes)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($message_succes); ?>
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

                    <form method="POST" action="profil.php" class="profil-form">
                        <div class="form-group">
                            <label for="login">Login *</label>
                            <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($login); ?>" required>
                            <small class="form-hint">Votre identifiant de connexion</small>
                        </div>

                        <div class="form-group">
                            <label for="password">Nouveau mot de passe</label>
                            <input type="password" id="password" name="password">
                            <small class="form-hint">Laissez vide si vous ne voulez pas le changer (au moins 6 caractères)</small>
                        </div>

                        <div class="form-group">
                            <label for="confirmation_password">Confirmer le nouveau mot de passe</label>
                            <input type="password" id="confirmation_password" name="confirmation_password">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-full">Modifier mon profil</button>
                        </div>
                    </form>

                    <div class="form-footer">
                        <p><a href="livre-or.php">← Retour au livre d'or</a></p>
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
        document.querySelector('.profil-form').addEventListener('submit', function(e) {
            const login = document.getElementById('login').value;
            const password = document.getElementById('password').value;
            const confirmation = document.getElementById('confirmation_password').value;
            
            if (!login.trim()) {
                e.preventDefault();
                alert('Le login est obligatoire.');
                return false;
            }
            
            if (password && password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return false;
            }
            
            if (password && password !== confirmation) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return false;
            }
        });

        // Auto-hide des messages d'alerte
        const alertMessages = document.querySelectorAll('.alert');
        alertMessages.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
    </script>
</body>
</html>