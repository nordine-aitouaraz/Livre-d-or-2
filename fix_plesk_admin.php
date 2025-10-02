<?php
echo "<h2>🔧 Correctif pour la base de données Plesk</h2>\n";
echo "<p>Ce script va ajouter la colonne 'is_admin' manquante et créer un utilisateur admin.</p>\n";

try {
    // Configuration Plesk
    $pdo = new PDO('mysql:host=localhost;dbname=nordine-ait-ouaraz_livreor;charset=utf8', 'nordine-ouaraz', 'Nonozdu92');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connexion à la base de données Plesk réussie</p>\n";
    echo "<p><strong>Base de données :</strong> nordine-ait-ouaraz_livreor (MySQL Plesk)</p>\n";
    
    // Vérifier si la colonne is_admin existe
    echo "<h3>🔍 Vérification de la structure de la table utilisateurs</h3>\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM utilisateurs LIKE 'is_admin'");
    
    if ($stmt->rowCount() == 0) {
        echo "<p>⚠️ La colonne 'is_admin' n'existe pas. Ajout en cours...</p>\n";
        
        // Ajouter la colonne is_admin
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER mot_de_passe");
        echo "<p>✅ Colonne 'is_admin' ajoutée avec succès !</p>\n";
    } else {
        echo "<p>✅ La colonne 'is_admin' existe déjà</p>\n";
    }
    
    // Afficher la structure actuelle
    echo "<h3>📋 Structure actuelle de la table utilisateurs :</h3>\n";
    $stmt = $pdo->query("DESCRIBE utilisateurs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background: #f0f0f0;'><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th></tr>\n";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Vérifier les utilisateurs existants
    echo "<h3>👥 Utilisateurs existants :</h3>\n";
    $stmt = $pdo->query("SELECT id, nom, prenom, login, is_admin FROM utilisateurs ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Nom</th><th>Prénom</th><th>Login</th><th>Admin</th></tr>\n";
        foreach ($users as $user) {
            $adminBadge = $user['is_admin'] ? "👑 Oui" : "👤 Non";
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['nom']}</td>";
            echo "<td>{$user['prenom']}</td>";
            echo "<td>{$user['login']}</td>";
            echo "<td>{$adminBadge}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>Aucun utilisateur trouvé dans la base.</p>\n";
    }
    
    // Créer ou mettre à jour l'utilisateur admin
    echo "<h3>🔑 Configuration de l'utilisateur admin :</h3>\n";
    
    // Vérifier si l'admin existe déjà
    $stmt = $pdo->prepare("SELECT id, login FROM utilisateurs WHERE login = ? OR login = ?");
    $stmt->execute(['admin', 'user@admin.com']);
    $adminExistant = $stmt->fetch();
    
    if ($adminExistant) {
        // Mettre à jour l'utilisateur existant
        $stmt = $pdo->prepare("UPDATE utilisateurs SET is_admin = 1, mot_de_passe = ? WHERE id = ?");
        $stmt->execute([md5('Nonozdu92'), $adminExistant['id']]);
        echo "<p>✅ Utilisateur existant (ID: {$adminExistant['id']}, Login: {$adminExistant['login']}) mis à jour avec les droits admin</p>\n";
        $admin_login = $adminExistant['login'];
    } else {
        // Créer un nouvel utilisateur admin
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, login, mot_de_passe, is_admin) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute(['admin', 'systeme', 'user@admin.com', md5('Nonozdu92')]);
        echo "<p>✅ Nouvel utilisateur admin créé</p>\n";
        $admin_login = 'user@admin.com';
    }
    
    // Statistiques finales
    echo "<h3>📊 Statistiques finales :</h3>\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs");
    $total_users = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs WHERE is_admin = 1");
    $total_admins = $stmt->fetch()['total'];
    
    echo "<ul>\n";
    echo "<li><strong>Total utilisateurs :</strong> $total_users</li>\n";
    echo "<li><strong>Total administrateurs :</strong> $total_admins</li>\n";
    echo "</ul>\n";
    
    // Informations de connexion
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h4>🎉 Configuration terminée avec succès !</h4>\n";
    echo "<p><strong>Identifiants admin pour Plesk :</strong></p>\n";
    echo "<ul>\n";
    echo "<li><strong>Login :</strong> $admin_login</li>\n";
    echo "<li><strong>Mot de passe :</strong> Nonozdu92</li>\n";
    echo "<li><strong>URL :</strong> <a href='connexion.php'>connexion.php</a></li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur de base de données : " . $e->getMessage() . "</p>\n";
    echo "<p>Vérifiez vos paramètres de connexion Plesk.</p>\n";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    max-width: 900px; 
    margin: 20px auto; 
    padding: 20px; 
    line-height: 1.6; 
}
h2, h3 { color: #333; }
p { margin: 10px 0; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { font-weight: bold; }
</style>

<div style="margin-top: 30px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;">
    <h4>⚠️ Important :</h4>
    <ul>
        <li>Supprimez ce fichier après utilisation pour des raisons de sécurité</li>
        <li>Ce script est spécifiquement conçu pour votre configuration Plesk</li>
        <li>Le mot de passe admin utilise MD5 pour la compatibilité</li>
    </ul>
</div>

<p>
    <a href="connexion.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🔑 Tester la connexion admin
    </a>
</p>