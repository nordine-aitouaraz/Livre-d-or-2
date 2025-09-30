<?php
session_start();

// Configuration de la base de données
try {
    $pdo = new PDO('mysql:host=localhost;dbname=livreor;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Vérifier si l'utilisateur est connecté
$utilisateur_connecte = isset($_SESSION['utilisateur_id']);

// Traitement de la suppression de commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_commentaire']) && $utilisateur_connecte) {
    $commentaire_id = (int)($_POST['commentaire_id'] ?? 0);
    
    if ($commentaire_id > 0) {
        try {
            // Vérifier que le commentaire appartient à l'utilisateur connecté
            $stmt = $pdo->prepare("SELECT id FROM commentaires WHERE id = ? AND id_utilisateur = ?");
            $stmt->execute([$commentaire_id, $_SESSION['utilisateur_id']]);
            
            if ($stmt->fetch()) {
                // Supprimer le commentaire
                $stmt = $pdo->prepare("DELETE FROM commentaires WHERE id = ? AND id_utilisateur = ?");
                $stmt->execute([$commentaire_id, $_SESSION['utilisateur_id']]);
                
                $_SESSION['message_succes'] = "Votre commentaire a été supprimé avec succès.";
            } else {
                $_SESSION['message_erreur'] = "Vous ne pouvez supprimer que vos propres commentaires.";
            }
        } catch (PDOException $e) {
            $_SESSION['message_erreur'] = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } else {
        $_SESSION['message_erreur'] = "Commentaire introuvable.";
    }
    
    // Redirection pour éviter la resoumission
    header('Location: livre-or.php');
    exit;
}

// Récupérer les messages de session
$message_succes = $_SESSION['message_succes'] ?? '';
unset($_SESSION['message_succes']);

$message_erreur = $_SESSION['message_erreur'] ?? '';
unset($_SESSION['message_erreur']);

// Récupérer tous les commentaires triés par date (du plus récent au plus ancien)
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.commentaire, c.date_creation, c.id_utilisateur, u.login 
        FROM commentaires c 
        JOIN utilisateurs u ON c.id_utilisateur = u.id 
        WHERE c.statut = 'public'
        ORDER BY c.date_creation DESC
    ");
    $stmt->execute();
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $commentaires = [];
    $erreur_db = "Erreur lors de la récupération des commentaires : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livre d'Or</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .livre-or-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .commentaire-item {
            background: #fff;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3498db;
        }
        
        .commentaire-meta {
            font-size: 0.9em;
            color: #7f8c8d;
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .commentaire-texte {
            color: #2c3e50;
            line-height: 1.6;
            font-size: 1.1em;
        }
        
        .add-comment-link {
            background: #27ae60;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 30px;
            transition: background-color 0.3s;
        }
        
        .add-comment-link:hover {
            background: #229954;
            text-decoration: none;
            color: white;
        }
        
        .no-comments {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .login-prompt {
            background: #e8f4f8;
            border: 1px solid #bee5eb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .login-prompt a {
            color: #0066cc;
            text-decoration: none;
            font-weight: bold;
        }
        
        .login-prompt a:hover {
            text-decoration: underline;
        }
        
        .commentaire-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
            text-align: right;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85em;
            transition: background-color 0.3s;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        .mes-commentaires {
            background-color: #e8f5e8;
            border-left-color: #27ae60;
        }
    </style>
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
                        <a href="livre-or.php" class="nav-link active">Livre d'Or</a>
                    </li>
                    <?php if ($utilisateur_connecte): ?>
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
        <div class="livre-or-container">
            <h1>📚 Livre d'Or</h1>
            <p>Découvrez les messages et témoignages laissés par nos visiteurs.</p>
            
            <?php if (!empty($message_succes)): ?>
                <div class="alert alert-success" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    ✅ <?php echo htmlspecialchars($message_succes); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($message_erreur)): ?>
                <div class="alert alert-error" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    ❌ <?php echo htmlspecialchars($message_erreur); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($erreur_db)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($erreur_db); ?>
                </div>
            <?php endif; ?>

            <?php if ($utilisateur_connecte): ?>
                <a href="commentaire.php" class="add-comment-link">✏️ Ajouter un commentaire</a>
            <?php else: ?>
                <div class="login-prompt">
                    <p>Pour ajouter un commentaire, veuillez vous <a href="connexion.php">connecter</a> ou vous <a href="inscription.php">inscrire</a>.</p>
                </div>
            <?php endif; ?>

            <div class="commentaires-liste">
                <?php if (empty($commentaires)): ?>
                    <div class="no-comments">
                        <p>Aucun commentaire pour le moment. Soyez le premier à partager votre message !</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($commentaires as $commentaire): ?>
                        <div class="commentaire-item <?php echo ($utilisateur_connecte && $commentaire['id_utilisateur'] == $_SESSION['utilisateur_id']) ? 'mes-commentaires' : ''; ?>">
                            <div class="commentaire-meta">
                                posté le <?php echo date('d/m/Y', strtotime($commentaire['date_creation'])); ?> par <?php echo htmlspecialchars($commentaire['login']); ?>
                                <?php if ($utilisateur_connecte && $commentaire['id_utilisateur'] == $_SESSION['utilisateur_id']): ?>
                                    <span style="color: #27ae60; font-weight: bold;">(Votre commentaire)</span>
                                <?php endif; ?>
                            </div>
                            <div class="commentaire-texte">
                                <?php echo nl2br(htmlspecialchars($commentaire['commentaire'])); ?>
                            </div>
                            
                            <?php if ($utilisateur_connecte && $commentaire['id_utilisateur'] == $_SESSION['utilisateur_id']): ?>
                                <div class="commentaire-actions">
                                    <form method="POST" action="livre-or.php" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ? Cette action est irréversible.');">
                                        <input type="hidden" name="commentaire_id" value="<?php echo $commentaire['id']; ?>">
                                        <button type="submit" name="supprimer_commentaire" class="btn-delete">
                                            🗑️ Supprimer
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
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

        // Auto-hide des messages de succès et d'erreur
        const alertSuccess = document.querySelector('.alert-success');
        const alertError = document.querySelector('.alert-error');
        
        [alertSuccess, alertError].forEach(function(alert) {
            if (alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>