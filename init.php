#!/usr/bin/env php
<?php
/**
 * init.php — Initialise la base de données Railway
 * Usage : php init.php
 * Railway : ajouter "php init.php && php -S 0.0.0.0:$PORT -t public" comme startCommand
 *           si vous voulez l'init auto. Sinon, exécutez manuellement une fois.
 */

require_once __DIR__ . '/config/database.php';

echo "Connexion à la base de données...\n";

try {
    $pdo = getDB();
    echo "Connexion réussie !\n";

    $sql = file_get_contents(__DIR__ . '/config/database.sql');
    // Séparer les statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }
    echo "Base de données initialisée avec succès !\n";
    echo "Compte admin créé : pseudo=Admin / mot de passe=password\n";
    echo "⚠️  CHANGEZ LE MOT DE PASSE ADMIN IMMÉDIATEMENT !\n";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
