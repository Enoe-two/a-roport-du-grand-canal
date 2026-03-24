<?php
// Démarrage de session sécurisé (une seule fois)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ─── Auth ────────────────────────────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function hasRole(string $role): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === $role;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!hasRole('admin')) {
        header('Location: /index.php');
        exit;
    }
}

function requireVIP(): void {
    requireLogin();
    if (!hasRole('vip') && !hasRole('admin')) {
        header('Location: /index.php');
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// ─── Tarifs ──────────────────────────────────────────────────────────────────

function calculerPrixVolSimple(string $classe, int $quantite, bool $isVIP = false): ?array {
    $tarifs = [
        'vip' => [
            1  => ['prix' => 20, 'devise' => 'or'],
            2  => ['prix' => 5,  'devise' => 'diamants'],
            5  => ['prix' => 15, 'devise' => 'diamants'],
            10 => ['prix' => 25, 'devise' => 'diamants'],
            20 => ['prix' => 40, 'devise' => 'diamants'],
            25 => ['prix' => 1,  'devise' => 'netherite'],
        ],
        '1' => [
            1  => ['prix' => 10, 'devise' => 'or'],
            2  => ['prix' => 1,  'devise' => 'diamants'],
            5  => ['prix' => 3,  'devise' => 'diamants'],
            10 => ['prix' => 7,  'devise' => 'diamants'],
            15 => ['prix' => 12, 'devise' => 'diamants'],
            20 => ['prix' => 16, 'devise' => 'diamants'],
            25 => ['prix' => 24, 'devise' => 'diamants'],
        ],
        '2' => [
            1  => ['prix' => 5,  'devise' => 'or/améthyste'],
            2  => ['prix' => 10, 'devise' => 'or/améthyste'],
            5  => ['prix' => 20, 'devise' => 'or/améthyste'],
            10 => ['prix' => 5,  'devise' => 'diamants'],
        ],
    ];

    if ($classe === 'vip' && !$isVIP) return null;
    if ($classe === '2'   && $isVIP)  return null;

    $tarifsClasse = $tarifs[$classe] ?? [];
    $seuils = array_keys($tarifsClasse);
    sort($seuils);

    $tarif = null;
    foreach ($seuils as $seuil) {
        if ($quantite <= $seuil) { $tarif = $tarifsClasse[$seuil]; break; }
    }
    if ($tarif === null) $tarif = $tarifsClasse[end($seuils)];

    // Réduction VIP -20% (sauf classe VIP)
    if ($isVIP && $classe !== 'vip') {
        $tarif = ['prix' => $tarif['prix'] * 0.8, 'devise' => $tarif['devise']];
    }

    return $tarif;
}

function calculerPrixCargaison(int $stacks): array {
    if ($stacks <= 1)   return ['prix' => 1,  'devise' => 'diamants'];
    if ($stacks <= 5)   return ['prix' => 3,  'devise' => 'diamants'];
    if ($stacks <= 10)  return ['prix' => 5,  'devise' => 'diamants'];
    if ($stacks <= 20)  return ['prix' => 7,  'devise' => 'diamants'];
    if ($stacks <= 50)  return ['prix' => 10, 'devise' => 'diamants'];
    if ($stacks <= 100) return ['prix' => 15, 'devise' => 'diamants'];
    return ['prix' => 15 + (int) ceil(($stacks - 100) / 5), 'devise' => 'diamants'];
}

/**
 * CORRECTION BUG : multiplicateurs étaient 0.15 / 0.20 au lieu de 1.15 / 1.20
 */
function calculerPrixTaxi(string $classe, string $type, float $prixBase): float {
    $multiplicateurs = [
        'aller'        => 1.15,
        'retour'       => 1.20,
        'aller_retour' => 2.00,
    ];
    return $prixBase * ($multiplicateurs[$type] ?? 1.0);
}

// ─── Utilitaires ─────────────────────────────────────────────────────────────

function formaterPrix(float $prix, string $devise): string {
    return number_format($prix, 2) . ' ' . $devise;
}

function sanitize(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function displayFlash(): void {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $class = $flash['type'] === 'success' ? 'flash-success' : 'flash-error';
        echo "<div class='flash {$class}'>" . htmlspecialchars($flash['message']) . "</div>";
        unset($_SESSION['flash']);
    }
}

// ─── Carte membre ────────────────────────────────────────────────────────────

function updateCarte(int $userId, string $type, int $quantite): void {
    $pdo = getDB();
    // Upsert
    $pdo->prepare("INSERT IGNORE INTO cartes (user_id) VALUES (?)")->execute([$userId]);
    if ($type === 'vol') {
        $pdo->prepare("UPDATE cartes SET vols_achetes = vols_achetes + ? WHERE user_id = ?")->execute([$quantite, $userId]);
    } elseif ($type === 'taxi') {
        $pdo->prepare("UPDATE cartes SET taxis_achetes = taxis_achetes + ? WHERE user_id = ?")->execute([$quantite, $userId]);
    }
}

function getCarteStats(int $userId): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM cartes WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: ['vols_achetes' => 0, 'vols_utilises' => 0, 'taxis_achetes' => 0, 'taxis_utilises' => 0];
}

// ─── Réservations ────────────────────────────────────────────────────────────

function canModifyReservation(string $dateVol, int $joursAvant): bool {
    $dateVol    = new DateTime($dateVol);
    $maintenant = new DateTime();
    $diff       = $maintenant->diff($dateVol);
    return !$diff->invert && $diff->days >= $joursAvant;
}

function getAdminId(): ?int {
    $pdo  = getDB();
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $row  = $stmt->fetch();
    return $row ? (int) $row['id'] : null;
}

function unreadCount(int $userId): int {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE to_user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}
