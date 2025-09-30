<?php
session_start();

// Détruire toutes les données de session
session_destroy();

// Rediriger vers la page d'accueil avec un message
session_start();
$_SESSION['message_deconnexion'] = "Vous avez été déconnecté(e) avec succès. À bientôt !";

header('Location: index.php');
exit;
?>