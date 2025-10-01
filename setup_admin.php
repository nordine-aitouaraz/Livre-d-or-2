<?php
// Script pour s'assurer que la colonne is_admin existe et créer l'admin
echo "<h2>🔧 Configuration Admin pour Plesk</h2>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=nordine-ait-ouaraz_livreor;charset=utf8', 'nordine-ouaraz', 'Nonozdu92');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion MySQL OK<br>";
    
    // Étape 1: Vérifier et créer la colonne is_admin si nécessaire
    $result = $pdo->query("SHOW COLUMNS FROM utilisateurs LIKE 'is_admin'");
    if (!$result->fetch()) {
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
        echo "✅ Colonne is_admin ajoutée<br>";
    } else {
        echo "✅ Colonne is_admin existe déjà<br>";
    }
    
    // Étape 2: Vérifier si l'admin existe
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE login = ?");
    $stmt->execute(['user@admin.com']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin trouvé, mise à jour...<br>";
        // Mettre à jour l'admin existant avec MD5 et is_admin=1
        $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = MD5(?), is_admin = 1 WHERE login = ?");
        $stmt->execute(['Nonozdu92', 'user@admin.com']);
        echo "✅ Admin mis à jour<br>";
    } else {
        echo "➕ Création d'un nouvel admin...<br>";
        // Créer le nouvel admin avec MD5
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, login, mot_de_passe, date_inscription, statut, is_admin) VALUES (?, ?, ?, MD5(?), CURRENT_TIMESTAMP, 'actif', 1)");
        $stmt->execute(['admin', 'systeme', 'user@admin.com', 'Nonozdu92']);
        echo "✅ Nouvel admin créé<br>";
    }
    
    // Étape 3: Vérification finale
    $stmt = $pdo->prepare("SELECT id, nom, prenom, login, is_admin FROM utilisateurs WHERE login = ?");
    $stmt->execute(['user@admin.com']);
    $admin = $stmt->fetch();
    
    if ($admin && $admin['is_admin']) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;'>";
        echo "<h3>🎉 SUCCÈS !</h3>";
        echo "<p><strong>Admin configuré avec succès :</strong></p>";
        echo "<p>• Login: user@admin.com</p>";
        echo "<p>• Mot de passe: Nonozdu92</p>";
        echo "<p>• ID: " . $admin['id'] . "</p>";
        echo "<p>• Statut admin: " . ($admin['is_admin'] ? 'Activé' : 'Désactivé') . "</p>";
        echo "</div>";
        
        echo "<div style='background: #cce5ff; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff;'>";
        echo "<h3>➡️ Prochaines étapes :</h3>";
        echo "<p>1. <a href='connexion.php' target='_blank'>Connectez-vous</a> avec user@admin.com / Nonozdu92</p>";
        echo "<p>2. Vous devriez voir l'onglet 'Administration' dans le menu</p>";
        echo "<p>3. <a href='admin.php' target='_blank'>Accédez à l'administration</a> pour gérer les utilisateurs</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
        echo "<h3>❌ Problème</h3>";
        echo "<p>L'admin n'a pas pu être configuré correctement.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<h3>❌ Erreur</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>