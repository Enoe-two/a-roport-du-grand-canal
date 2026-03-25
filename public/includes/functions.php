<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>86400,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
require_once __DIR__.'/../config/database.php';

/* ── Auth ─────────────────────────────────────────────────────── */
function isLoggedIn(): bool { return isset($_SESSION['user_id']); }
function hasRole(string $r): bool { return isLoggedIn() && ($_SESSION['user_role']??'') === $r; }

function requireLogin(): void {
    if (!isLoggedIn()) { header('Location: /login.php'); exit; }
}
function requireAdmin(): void {
    requireLogin();
    if (!hasRole('admin')) { header('Location: /index.php'); exit; }
}
function requireVIP(): void {
    requireLogin();
    if (!hasRole('vip') && !hasRole('admin')) { header('Location: /index.php'); exit; }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $s = getDB()->prepare("SELECT * FROM users WHERE id=?");
    $s->execute([$_SESSION['user_id']]);
    return $s->fetch() ?: null;
}

/* ── Flash messages ───────────────────────────────────────────── */
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type'=>$type,'message'=>$msg];
}
function displayFlash(): void {
    if (!isset($_SESSION['flash'])) return;
    $f = $_SESSION['flash'];
    $c = $f['type']==='success' ? 'flash-success' : 'flash-error';
    echo "<div class='flash {$c}'>".htmlspecialchars($f['message'])."</div>";
    unset($_SESSION['flash']);
}

/* ── Sanitize ─────────────────────────────────────────────────── */
function sanitize(string $d): string {
    return htmlspecialchars(strip_tags(trim($d)), ENT_QUOTES, 'UTF-8');
}

/* ── Tarifs depuis BDD ────────────────────────────────────────── */
function getTarifs(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    try {
        $rows = getDB()->query("SELECT cle, prix, devise FROM tarifs")->fetchAll();
        $cache = [];
        foreach ($rows as $r) $cache[$r['cle']] = ['prix'=>(float)$r['prix'],'devise'=>$r['devise']];
    } catch (Exception $e) { $cache = []; }
    return $cache;
}

function getTarif(string $cle): ?array {
    $t = getTarifs();
    return $t[$cle] ?? null;
}

function calculerPrixVolSimple(string $classe, int $qte, bool $isVIP=false): ?array {
    if ($classe==='vip' && !$isVIP) return null;
    if ($classe==='2'   && $isVIP)  return null;

    // Map classe → préfixe clé BDD
    $prefix = ['2'=>'vol_2_','1'=>'vol_1_','vip'=>'vol_v_'][$classe] ?? null;
    if (!$prefix) return null;

    // Seuils pour chaque classe
    $seuils = [
        '2'  => [1,2,5,10],
        '1'  => [1,2,5,10,15,20,25],
        'vip'=> [1,2,5,10,20,25],
    ][$classe];

    $seuil_choisi = end($seuils);
    foreach ($seuils as $s) { if ($qte <= $s) { $seuil_choisi = $s; break; } }

    $tarif = getTarif($prefix.$seuil_choisi);
    if (!$tarif) return null;

    // Réduction VIP
    if ($isVIP && $classe !== 'vip') {
        $reduc = (float)(getTarif('vip_reduction')['prix'] ?? 20);
        $tarif = ['prix' => round($tarif['prix'] * (1 - $reduc/100), 2), 'devise' => $tarif['devise']];
    }
    return $tarif;
}

function calculerPrixCargaison(int $s): array {
    $seuils = [1=>'cargo_1',5=>'cargo_5',10=>'cargo_10',20=>'cargo_20',50=>'cargo_50',100=>'cargo_100'];
    $cle = 'cargo_100';
    foreach ($seuils as $seuil => $k) { if ($s <= $seuil) { $cle = $k; break; } }
    $tarif = getTarif($cle);
    if ($s > 100) {
        $base = (float)($tarif['prix'] ?? 15);
        return ['prix' => $base + (int)ceil(($s-100)/5), 'devise' => 'diamants'];
    }
    return $tarif ?? ['prix'=>15,'devise'=>'diamants'];
}

function calculerPrixTaxi(string $classe, string $type, float $base): float {
    $cle = ['aller'=>'taxi_aller','retour'=>'taxi_retour','aller_retour'=>'taxi_aller_retour'][$type] ?? null;
    $mult = $cle ? (float)(getTarif($cle)['prix'] ?? 1.15) : 1.15;
    return round($base * $mult, 2);
}

function getPrixVolMasque(): float {
    return (float)(getTarif('vol_masque_prix')['prix'] ?? 10);
}

function formaterPrix(float $p, string $d): string {
    return number_format($p, 2).' '.$d;
}

/* ── Carte membre ─────────────────────────────────────────────── */
function updateCarte(int $uid, string $type, int $qte): void {
    $pdo = getDB();
    $pdo->prepare("INSERT IGNORE INTO cartes (user_id) VALUES (?)")->execute([$uid]);
    if ($type==='vol')
        $pdo->prepare("UPDATE cartes SET vols_achetes=vols_achetes+? WHERE user_id=?")->execute([$qte,$uid]);
    elseif ($type==='taxi')
        $pdo->prepare("UPDATE cartes SET taxis_achetes=taxis_achetes+? WHERE user_id=?")->execute([$qte,$uid]);
}

function getCarteStats(int $uid): array {
    $s = getDB()->prepare("SELECT * FROM cartes WHERE user_id=?");
    $s->execute([$uid]);
    return $s->fetch() ?: ['vols_achetes'=>0,'vols_utilises'=>0,'taxis_achetes'=>0,'taxis_utilises'=>0];
}

/* ── Réservations ─────────────────────────────────────────────── */
function canModifyReservation(string $dateVol, int $joursAvant): bool {
    $d = new DateTime($dateVol); $n = new DateTime();
    $diff = $n->diff($d);
    return !$diff->invert && $diff->days >= $joursAvant;
}

/* ── Messagerie ───────────────────────────────────────────────── */
function getAdminId(): ?int {
    $s = getDB()->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
    $r = $s->fetch(); return $r ? (int)$r['id'] : null;
}

function unreadCount(int $uid): int {
    $s = getDB()->prepare("SELECT COUNT(*) FROM messages WHERE to_user_id=? AND is_read=0");
    $s->execute([$uid]); return (int)$s->fetchColumn();
}
