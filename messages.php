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

// Traitement de la suppression de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_message'])) {
    $message_id = (int)($_POST['message_id'] ?? 0);
    
    if ($message_id > 0) {
        try {
            // Vérifier que le message appartient à l'utilisateur connecté
            $stmt = $pdo->prepare("SELECT id FROM commentaires WHERE id = ? AND id_utilisateur = ?");
            $stmt->execute([$message_id, $_SESSION['utilisateur_id']]);
            
            if ($stmt->fetch()) {
                // Supprimer le message
                $stmt = $pdo->prepare("DELETE FROM commentaires WHERE id = ? AND id_utilisateur = ?");
                $stmt->execute([$message_id, $_SESSION['utilisateur_id']]);
                
                $_SESSION['message_succes'] = "Votre message a été supprimé avec succès.";
            } else {
                $_SESSION['erreurs'] = ["Vous ne pouvez supprimer que vos propres messages."];
            }
        } catch (PDOException $e) {
            $_SESSION['erreurs'] = ["Erreur lors de la suppression : " . $e->getMessage()];
        }
    } else {
        $_SESSION['erreurs'] = ["Message introuvable."];
    }
    
    // Redirection pour éviter la resoumission
    header('Location: messages.php');
    exit;
}

// Traitement du formulaire d'ajout de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_message'])) {
    $titre = trim($_POST['titre'] ?? '');
    $commentaire = trim($_POST['commentaire'] ?? '');
    
    // Validation
    if (empty($titre)) $erreurs[] = "Le titre est obligatoire.";
    if (empty($commentaire)) $erreurs[] = "Le commentaire est obligatoire.";
    
    // Si pas d'erreurs, insérer le message
    if (empty($erreurs)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO commentaires (titre, commentaire, id_utilisateur, date_creation) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$titre, $commentaire, $_SESSION['utilisateur_id']]);
            
            $_SESSION['message_succes'] = "Votre message a été ajouté avec succès !";
            
            // Redirection pour éviter la resoumission
            header('Location: messages.php');
            exit;
        } catch (PDOException $e) {
            $erreurs[] = "Erreur lors de l'ajout du message : " . $e->getMessage();
        }
    } else {
        // Stocker les erreurs et les données du formulaire en session
        $_SESSION['erreurs'] = $erreurs;
        $_SESSION['form_data'] = ['titre' => $titre, 'commentaire' => $commentaire];
        
        // Redirection pour éviter la resoumission
        header('Location: messages.php');
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

// Récupérer les données du formulaire (soit depuis la session, soit vides)
$titre = $form_data['titre'] ?? '';
$commentaire = $form_data['commentaire'] ?? '';

// Récupérer tous les messages triés par date
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom 
        FROM commentaires c 
        JOIN utilisateurs u ON c.id_utilisateur = u.id 
        WHERE c.statut = 'public'
        ORDER BY c.date_creation DESC
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $messages = [];
    $erreurs[] = "Erreur lors de la récupération des messages : " . $e->getMessage();
}

$nom_utilisateur = $_SESSION['utilisateur_prenom'] . ' ' . $_SESSION['utilisateur_nom'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Livre d'Or</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .messages-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .add-message-form {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        .messages-list {
            display: grid;
            gap: 20px;
        }
        
        .message-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3498db;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .message-title {
            color: #2c3e50;
            font-size: 1.4em;
            font-weight: bold;
            margin: 0;
        }
        
        .message-meta {
            text-align: right;
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .message-author {
            font-weight: bold;
            color: #34495e;
        }
        
        .message-date {
            display: block;
            margin-top: 5px;
        }
        
        .message-content {
            color: #34495e;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .form-rating {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }
        
        .toggle-form-btn {
            margin-bottom: 20px;
        }
        
        .form-hidden {
            display: none;
        }
        
        .no-messages {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
        }
        
        .message-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            gap: 10px;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .message-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .message-meta {
                text-align: left;
            }
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
                        <a href="messages.php" class="nav-link active">Messages</a>
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
        <div class="messages-container">
            <h1>📝 Messages du Livre d'Or</h1>
            <p>Bienvenue <?php echo htmlspecialchars($nom_utilisateur); ?> ! Découvrez les messages de la communauté et partagez le vôtre.</p>
            
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

            <!-- Bouton pour afficher/masquer le formulaire -->
            <button type="button" class="btn btn-primary toggle-form-btn" onclick="toggleForm()">
                ✏️ Ajouter un nouveau message
            </button>

            <!-- Formulaire d'ajout de message -->
            <div class="add-message-form form-hidden" id="messageForm">
                <h2>✨ Ajouter votre message</h2>
                <form method="POST" action="messages.php">
                    <div class="form-group">
                        <label for="titre">Titre de votre message *</label>
                        <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($titre ?? ''); ?>" required 
                               placeholder="Ex: Une expérience formidable !">
                    </div>

                    <div class="form-group">
                        <label for="commentaire">Votre message *</label>
                        <textarea id="commentaire" name="commentaire" required rows="6" 
                                  placeholder="Partagez votre expérience, vos impressions, vos remerciements..."><?php echo htmlspecialchars($commentaire ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="ajouter_message" class="btn btn-primary">📤 Publier mon message</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleForm()">Annuler</button>
                    </div>
                </form>
            </div>

            <!-- Liste des messages -->
            <div class="messages-list">
                <h2>💬 Tous les messages (<?php echo count($messages); ?>)</h2>
                
                <?php if (empty($messages)): ?>
                    <div class="no-messages">
                        <p>Aucun message pour le moment. Soyez le premier à partager votre expérience !</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message-card">
                            <div class="message-header">
                                <h3 class="message-title"><?php echo htmlspecialchars($message['titre']); ?></h3>
                                <div class="message-meta">
                                    <div class="message-author">
                                        👤 <?php echo htmlspecialchars($message['prenom'] . ' ' . $message['nom']); ?>
                                    </div>
                                    <div class="message-date">
                                        📅 <?php echo date('d/m/Y à H:i', strtotime($message['date_creation'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="message-content">
                                <?php echo nl2br(htmlspecialchars($message['commentaire'])); ?>
                            </div>
                            
                            <?php if ($message['id_utilisateur'] == $_SESSION['utilisateur_id']): ?>
                                <div class="message-actions">
                                    <form method="POST" action="messages.php" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.');">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <button type="submit" name="supprimer_message" class="btn-delete btn-small">
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

        // Toggle formulaire d'ajout
        function toggleForm() {
            const form = document.getElementById('messageForm');
            const btn = document.querySelector('.toggle-form-btn');
            
            if (form.classList.contains('form-hidden')) {
                form.classList.remove('form-hidden');
                btn.textContent = '❌ Annuler';
                form.scrollIntoView({ behavior: 'smooth' });
            } else {
                form.classList.add('form-hidden');
                btn.textContent = '✏️ Ajouter un nouveau message';
            }
        }

        // Gestion des étoiles de notation
        const stars = document.querySelectorAll('.form-rating input[type="radio"]');
        const labels = document.querySelectorAll('.form-rating label');
        
        labels.forEach((label, index) => {
            label.addEventListener('mouseover', function() {
                highlightStars(index);
            });
            
            label.addEventListener('click', function() {
                const value = this.getAttribute('for').replace('star', '');
                document.querySelector(`input[value="${value}"]`).checked = true;
            });
        });
        
        document.querySelector('.form-rating').addEventListener('mouseleave', function() {
            const checked = document.querySelector('.form-rating input[type="radio"]:checked');
            if (checked) {
                const index = parseInt(checked.value) - 1;
                highlightStars(index);
            } else {
                resetStars();
            }
        });
        
        function highlightStars(index) {
            labels.forEach((label, i) => {
                if (i <= index) {
                    label.style.color = '#f39c12';
                } else {
                    label.style.color = '#ddd';
                }
            });
        }
        
        function resetStars() {
            labels.forEach(label => {
                label.style.color = '#ddd';
            });
        }

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

        // Ouvrir automatiquement le formulaire s'il y a des erreurs
        <?php if (!empty($erreurs)): ?>
            document.getElementById('messageForm').classList.remove('form-hidden');
            document.querySelector('.toggle-form-btn').textContent = '❌ Annuler';
        <?php endif; ?>
    </script>
</body>
</html>