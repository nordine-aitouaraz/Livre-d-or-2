<?php
// Script simple pour créer l'admin sur Plesk
try {
    $pdo = new PDO('mysql:host=localhost;dbname=nordine-ait-ouaraz_livreor;charset=utf8', 'nordine-ouaraz', 'Nonozdu92');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion réussie à la base Plesk<br>";
    
    // Étape 1: Ajouter la colonne is_admin si elle n'existe pas
    try {
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
        echo "✅ Colonne is_admin ajoutée<br>";
    } catch (PDOException $e) {
        echo "ℹ️ Colonne is_admin existe déjà ou erreur: " . $e->getMessage() . "<br>";
    }
    
    // Étape 2: Créer l'utilisateur admin
    try {
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, login, mot_de_passe, is_admin) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute(['admin', 'systeme', 'user@admin.com', md5('Nonozdu92')]);
        echo "✅ Utilisateur admin créé: user@admin.com / Nonozdu92<br>";
    } catch (PDOException $e) {
        echo "ℹ️ Utilisateur existe peut-être déjà: " . $e->getMessage() . "<br>";
        
        // Essayer de mettre à jour l'utilisateur existant
        try {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET is_admin = 1, mot_de_passe = ? WHERE login = ?");
            $stmt->execute([md5('Nonozdu92'), 'user@admin.com']);
            echo "✅ Utilisateur admin mis à jour<br>";
        } catch (PDOException $e2) {
            echo "❌ Erreur mise à jour: " . $e2->getMessage() . "<br>";
        }
    }
    
    // Vérification finale
    $stmt = $pdo->query("SELECT login, is_admin FROM utilisateurs WHERE is_admin = 1");
    $admins = $stmt->fetchAll();
    echo "<br>Admins dans la base:<br>";
    foreach ($admins as $admin) {
        echo "- " . $admin['login'] . "<br>";
    }
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>

<a href="connexion.php">Tester la connexion</a>