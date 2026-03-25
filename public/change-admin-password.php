<?php
require_once __DIR__ . '/config/database.php';

$nouveauMotDePasse = 'E!g3t93hgx';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Changement MDP</title>";
echo "<style>body{font-family:Arial;max-width:600px;margin:50px auto;padding:20px;background:#1a1a1a;color:#fff;}";
echo "h1{color:#00d9ff;}.success{color:#48bb78;}.error{color:#f56565;}</style></head><body>";
echo "<h1>🔐 Changement Mot de Passe Admin</h1>";

try {
    $pdo = getDB();
    
    // Hasher le nouveau mot de passe
    $hash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
    
    // Mettre à jour
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE pseudo_minecraft = 'Admin'");
    $stmt->execute([$hash]);
    
    echo "<p class='success'>✅ Mot de passe admin changé avec succès !</p>";
    echo "<p><strong>Nouveau mot de passe :</strong> " . htmlspecialchars($nouveauMotDePasse) . "</p>";
    echo "<p style='margin-top:30px;'><a href='/login.php' style='color:#00d9ff;'>→ Se connecter</a></p>";
    echo "<hr><p class='error'><strong>⚠️ SUPPRIMEZ CE FICHIER IMMÉDIATEMENT !</strong></p>";
    echo "<p>Commande : <code>rm public/change-admin-password.php && git commit -am 'Remove password script' && git push</code></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>