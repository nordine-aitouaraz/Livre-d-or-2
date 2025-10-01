<?php
session_start();

// Vérifier si l'utilisateur est connecté et admin
if (!isset($_SESSION['utilisateur_id']) || !$_SESSION['utilisateur_is_admin']) {
    header('Location: connexion.php?erreur=acces_refuse');
    exit;
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=nordine-ait-ouaraz_livreor;charset=utf8', 'nordine-ouaraz', 'Nonozdu92');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Messages de feedback
$message_succes = $_SESSION['message_succes'] ?? '';
$message_erreur = $_SESSION['message_erreur'] ?? '';
unset($_SESSION['message_succes'], $_SESSION['message_erreur']);

// Traitement des actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Supprimer un utilisateur
        if (isset($_POST['supprimer_utilisateur'])) {
            $user_id = (int)$_POST['user_id'];
            if ($user_id === $_SESSION['utilisateur_id']) {
                $_SESSION['message_erreur'] = "Vous ne pouvez pas vous supprimer vous-même.";
            } else {
                // Solution pour éviter l'erreur #1217 (contrainte de clé étrangère)
                $pdo->beginTransaction();
                
                // Désactiver temporairement les contraintes
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                
                // Supprimer d'abord les commentaires de l'utilisateur
                $stmt = $pdo->prepare("DELETE FROM commentaires WHERE id_utilisateur = ?");
                $stmt->execute([$user_id]);
                
                // Supprimer les sessions si la table existe
                try {
                    $stmt = $pdo->prepare("DELETE FROM sessions WHERE utilisateur_id = ?");
                    $stmt->execute([$user_id]);
                } catch (PDOException $e) {
                    // Table sessions n'existe peut-être pas
                }
                
                // Supprimer l'utilisateur
                $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // Réactiver les contraintes
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                
                $pdo->commit();
                $_SESSION['message_succes'] = "Utilisateur supprimé avec succès.";
            }
            header('Location: admin.php');
            exit;
        }
        
        // Promouvoir/rétrograder un utilisateur admin
        if (isset($_POST['toggle_admin'])) {
            $user_id = (int)$_POST['user_id'];
            $new_admin_status = (int)$_POST['new_admin_status'];
            
            if ($user_id === $_SESSION['utilisateur_id'] && $new_admin_status === 0) {
                $_SESSION['message_erreur'] = "Vous ne pouvez pas retirer vos propres droits d'administrateur.";
            } else {
                // Ajouter la colonne is_admin si elle n'existe pas
                try {
                    $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
                } catch (PDOException $e) {
                    // La colonne existe déjà
                }
                
                $stmt = $pdo->prepare("UPDATE utilisateurs SET is_admin = ? WHERE id = ?");
                $stmt->execute([$new_admin_status, $user_id]);
                
                $action = $new_admin_status ? "promu administrateur" : "rétrogradé utilisateur";
                $_SESSION['message_succes'] = "Utilisateur $action avec succès.";
            }
            header('Location: admin.php');
            exit;
        }
        
        // Supprimer un commentaire
        if (isset($_POST['supprimer_commentaire'])) {
            $comment_id = (int)$_POST['comment_id'];
            $stmt = $pdo->prepare("DELETE FROM commentaires WHERE id = ?");
            $stmt->execute([$comment_id]);
            
            $_SESSION['message_succes'] = "Commentaire supprimé avec succès.";
            header('Location: admin.php');
            exit;
        }
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Réactiver les contraintes en cas d'erreur
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (PDOException $e2) {}
        
        $_SESSION['message_erreur'] = "Erreur lors de l'opération : " . $e->getMessage();
        header('Location: admin.php');
        exit;
    }
}

// Récupérer tous les utilisateurs
try {
    $stmt = $pdo->query("SELECT id, nom, prenom, login, date_inscription, 
                                COALESCE(statut, 'actif') as statut, 
                                COALESCE(is_admin, 0) as is_admin, 
                                (SELECT COUNT(*) FROM commentaires WHERE id_utilisateur = utilisateurs.id) as nb_commentaires
                         FROM utilisateurs 
                         ORDER BY date_inscription DESC");
    $utilisateurs = $stmt->fetchAll();
} catch (PDOException $e) {
    $utilisateurs = [];
    $message_erreur = "Erreur lors du chargement des utilisateurs : " . $e->getMessage();
}

// Récupérer tous les commentaires avec infos utilisateur
try {
    $stmt = $pdo->query("SELECT c.id, c.titre, c.commentaire, c.date_creation, 
                                COALESCE(c.statut, 'public') as statut,
                                u.nom, u.prenom, u.login, u.id as user_id
                         FROM commentaires c
                         JOIN utilisateurs u ON c.id_utilisateur = u.id
                         ORDER BY c.date_creation DESC
                         LIMIT 100");
    $commentaires = $stmt->fetchAll();
} catch (PDOException $e) {
    $commentaires = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Livre d'Or</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-section {
            background: white;
            margin: 20px 0;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .admin-table th,
        .admin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        
        .admin-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .admin-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .admin-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .btn-admin {
            padding: 6px 12px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-admin:hover {
            opacity: 0.8;
        }
        
        .comment-text {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #6c757d;
            border-bottom: 2px solid transparent;
        }
        
        .tab.active {
            color: #495057;
            border-bottom-color: #007bff;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <h2>📖 Livre d'Or - Administration</h2>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a href="livre-or.php" class="nav-link">Livre d'Or</a>
                    </li>
                    <li class="nav-item">
                        <a href="profil.php" class="nav-link">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a href="admin.php" class="nav-link active">Administration</a>
                    </li>
                    <li class="nav-item">
                        <a href="deconnexion.php" class="nav-link">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <div class="admin-container">
            <h1>🛠️ Panneau d'Administration</h1>
            <p>Bienvenue <?php echo htmlspecialchars($_SESSION['utilisateur_prenom']); ?> dans l'interface d'administration du livre d'or.</p>

            <?php if ($message_succes): ?>
                <div class="alert alert-success" id="alert-success">
                    <?php echo htmlspecialchars($message_succes); ?>
                </div>
            <?php endif; ?>

            <?php if ($message_erreur): ?>
                <div class="alert alert-error" id="alert-error">
                    <?php echo htmlspecialchars($message_erreur); ?>
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="admin-section">
                <h2>📊 Statistiques</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($utilisateurs); ?></div>
                        <div>Utilisateurs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($utilisateurs, fn($u) => $u['is_admin'])); ?></div>
                        <div>Administrateurs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($commentaires); ?></div>
                        <div>Commentaires</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($utilisateurs, fn($u) => $u['statut'] === 'actif')); ?></div>
                        <div>Utilisateurs actifs</div>
                    </div>
                </div>
            </div>

            <!-- Onglets -->
            <div class="admin-section">
                <div class="tabs">
                    <button class="tab active" onclick="showTab('users')">👥 Gestion des Utilisateurs</button>
                    <button class="tab" onclick="showTab('comments')">💬 Gestion des Commentaires</button>
                </div>

                <!-- Gestion des utilisateurs -->
                <div id="users-tab" class="tab-content active">
                    <h3>Gestion des Utilisateurs</h3>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Login</th>
                                    <th>Date d'inscription</th>
                                    <th>Statut</th>
                                    <th>Commentaires</th>
                                    <th>Admin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($utilisateurs as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($user['prenom']); ?></td>
                                        <td><?php echo htmlspecialchars($user['login']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></td>
                                        <td><?php echo ucfirst($user['statut']); ?></td>
                                        <td>
                                            <?php if ($user['nb_commentaires'] > 0): ?>
                                                <span style="color: orange; font-weight: bold;"><?php echo $user['nb_commentaires']; ?></span>
                                            <?php else: ?>
                                                <?php echo $user['nb_commentaires']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['is_admin']): ?>
                                                <span class="admin-badge">Admin</span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['id'] !== $_SESSION['utilisateur_id']): ?>
                                                <!-- Toggle Admin -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="new_admin_status" value="<?php echo $user['is_admin'] ? 0 : 1; ?>">
                                                    <button type="submit" name="toggle_admin" 
                                                            class="btn-admin <?php echo $user['is_admin'] ? 'btn-warning' : 'btn-success'; ?>"
                                                            onclick="return confirm('Êtes-vous sûr de vouloir <?php echo $user['is_admin'] ? 'retirer les droits admin' : 'promouvoir admin'; ?> ?')">
                                                        <?php echo $user['is_admin'] ? '⬇️ Rétrograder' : '⬆️ Promouvoir'; ?>
                                                    </button>
                                                </form>
                                                
                                                <!-- Supprimer -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="supprimer_utilisateur" 
                                                            class="btn-admin btn-danger"
                                                            onclick="return confirm('⚠️ ATTENTION !\n\nCette action va supprimer définitivement :\n- L\'utilisateur\n- Tous ses commentaires\n- Toutes ses sessions\n\nCette action est irréversible.\n\nContinuer ?')">
                                                        🗑️ Supprimer
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: #6c757d; font-style: italic;">Vous-même</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Gestion des commentaires -->
                <div id="comments-tab" class="tab-content">
                    <h3>Gestion des Commentaires (100 derniers)</h3>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titre</th>
                                    <th>Commentaire</th>
                                    <th>Auteur</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commentaires as $comment): ?>
                                    <tr>
                                        <td><?php echo $comment['id']; ?></td>
                                        <td><?php echo htmlspecialchars($comment['titre']); ?></td>
                                        <td>
                                            <div class="comment-text" title="<?php echo htmlspecialchars($comment['commentaire']); ?>">
                                                <?php echo htmlspecialchars(substr($comment['commentaire'], 0, 100)); ?>
                                                <?php if (strlen($comment['commentaire']) > 100): ?>...<?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($comment['prenom'] . ' ' . $comment['nom']); ?><br>
                                            <small style="color: #6c757d;">@<?php echo htmlspecialchars($comment['login']); ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($comment['date_creation'])); ?></td>
                                        <td><?php echo ucfirst($comment['statut']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                <button type="submit" name="supprimer_commentaire" 
                                                        class="btn-admin btn-danger"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')">
                                                    🗑️ Supprimer
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Livre d'Or - Interface d'Administration. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
        // Gestion des onglets
        function showTab(tabName) {
            // Masquer tous les contenus d'onglets
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Désactiver tous les onglets
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Afficher le contenu sélectionné
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activer l'onglet cliqué
            event.target.classList.add('active');
        }

        // Masquer automatiquement les alertes après 5 secondes
        setTimeout(function() {
            const alerts = document.querySelectorAll('#alert-success, #alert-error');
            alerts.forEach(alert => {
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>