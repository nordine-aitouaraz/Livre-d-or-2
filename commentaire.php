<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    $_SESSION['erreur_connexion'] = "Vous devez être connecté pour ajouter un commentaire.";
    header('Location: connexion.php');
    exit;
}

$erreurs = [];
$message_succes = '';

// Traitement du formulaire d'ajout de commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_commentaire'])) {
    $commentaire = trim($_POST['commentaire'] ?? '');
    
    // Validation simple
    if (empty($commentaire)) {
        $erreurs[] = "Le commentaire ne peut pas être vide.";
    } elseif (strlen($commentaire) < 5) {
        $erreurs[] = "Le commentaire doit contenir au moins 5 caractères.";
    } elseif (strlen($commentaire) > 1000) {
        $erreurs[] = "Le commentaire ne peut pas dépasser 1000 caractères.";
    }
    
    // Si pas d'erreurs, enregistrer le commentaire
    if (empty($erreurs)) {
        try {
            // Configuration de la base de données
            $pdo = new PDO('mysql:host=localhost;dbname=nordine-ait-ouaraz_livreor;charset=utf8', 'nordine-ouaraz', 'Nonozdu92');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Vérifier que l'utilisateur existe toujours dans la base
            $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE id = ?");
            $stmt_check->execute([$_SESSION['utilisateur_id']]);
            
            if (!$stmt_check->fetch()) {
                // L'utilisateur n'existe plus, forcer la déconnexion
                session_destroy();
                $_SESSION = [];
                header('Location: connexion.php?erreur=session_invalide');
                exit;
            }
            
            // Insérer le commentaire
            $stmt = $pdo->prepare("INSERT INTO commentaires (titre, commentaire, id_utilisateur, statut, date_creation) VALUES (?, ?, ?, ?, NOW())");
            $titre_auto = "Message de " . $_SESSION['utilisateur_prenom'] . " " . $_SESSION['utilisateur_nom'];
            $statut = 'public';
            
            $stmt->execute([$titre_auto, $commentaire, $_SESSION['utilisateur_id'], $statut]);
            
            // Message de succès et redirection
            $_SESSION['message_succes'] = "Votre commentaire a été publié avec succès !";
            header('Location: livre-or.php');
            exit;
            
        } catch (PDOException $e) {
            $erreurs[] = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}

// Récupérer les données utilisateur pour l'affichage
$nom_complet = $_SESSION['utilisateur_prenom'] . ' ' . $_SESSION['utilisateur_nom'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un commentaire - Livre d'Or</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .commentaire-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .commentaire-form {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            color: #2c3e50;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .form-subtitle {
            color: #7f8c8d;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .textarea-container {
            margin-bottom: 20px;
        }
        
        .textarea-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #34495e;
        }
        
        .textarea-container textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 16px;
            line-height: 1.5;
            resize: vertical;
            transition: border-color 0.3s ease;
        }
        
        .textarea-container textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .char-counter {
            text-align: right;
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .form-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
        
        .btn-submit {
            background: #27ae60;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-submit:hover {
            background: #229954;
        }
        
        .btn-cancel {
            background: #95a5a6;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #7f8c8d;
            text-decoration: none;
            color: white;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
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
                        <a href="livre-or.php" class="nav-link">Livre d'Or</a>
                    </li>
                    <li class="nav-item">
                        <a href="commentaire.php" class="nav-link active">Ajouter un commentaire</a>
                    </li>
                    <li class="nav-item">
                        <a href="profil.php" class="nav-link">Profil</a>
                    </li>
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
        <div class="commentaire-container">
            <div class="commentaire-form">
                <h1 class="form-title">✏️ Ajouter votre commentaire</h1>
                <p class="form-subtitle">Bonjour <?php echo htmlspecialchars($nom_complet); ?>, partagez votre message dans notre livre d'or.</p>
                
                <?php if (!empty($erreurs)): ?>
                    <div class="alert alert-error">
                        <strong>Erreur :</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <?php foreach ($erreurs as $erreur): ?>
                                <li><?php echo htmlspecialchars($erreur); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="commentaire.php" id="commentaireForm">
                    <div class="textarea-container">
                        <label for="commentaire">Votre commentaire :</label>
                        <textarea id="commentaire" name="commentaire" 
                                  placeholder="Écrivez votre message ici... Partagez votre expérience, vos impressions, vos remerciements..."
                                  maxlength="1000" required><?php echo htmlspecialchars($_POST['commentaire'] ?? ''); ?></textarea>
                        <div class="char-counter">
                            <span id="charCount">0</span> / 1000 caractères
                        </div>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" name="ajouter_commentaire" class="btn-submit">📝 Publier mon commentaire</button>
                        <a href="livre-or.php" class="btn-cancel">❌ Annuler</a>
                    </div>
                </form>
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

        // Compteur de caractères
        const textarea = document.getElementById('commentaire');
        const charCount = document.getElementById('charCount');

        function updateCharCount() {
            const count = textarea.value.length;
            charCount.textContent = count;
            
            // Changer la couleur selon le nombre de caractères
            if (count > 900) {
                charCount.style.color = '#e74c3c';
            } else if (count > 800) {
                charCount.style.color = '#f39c12';
            } else {
                charCount.style.color = '#7f8c8d';
            }
        }

        textarea.addEventListener('input', updateCharCount);
        textarea.addEventListener('paste', function() {
            setTimeout(updateCharCount, 10);
        });

        // Initialiser le compteur
        updateCharCount();

        // Focus automatique sur le textarea
        textarea.focus();

        // Validation avant envoi
        document.getElementById('commentaireForm').addEventListener('submit', function(e) {
            const commentaire = textarea.value.trim();
            
            if (commentaire.length === 0) {
                e.preventDefault();
                alert('Veuillez saisir votre commentaire.');
                textarea.focus();
                return false;
            }
            
            if (commentaire.length < 5) {
                e.preventDefault();
                alert('Votre commentaire doit contenir au moins 5 caractères.');
                textarea.focus();
                return false;
            }
            
            // Confirmation avant envoi
            if (!confirm('Êtes-vous sûr de vouloir publier ce commentaire ?')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>