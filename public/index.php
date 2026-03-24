<?php
$pageTitle = 'Accueil - Aéroport du Grand Canal';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero">
    <h1>✈️ Bienvenue à l'Aéroport du Grand Canal</h1>
    <p>Voyagez à travers le monde Minecraft avec style. Réservez vols, cargaisons et taxis en quelques clics.</p>
</div>

<div class="grid grid-3">
    <div class="card">
        <h3>🛫 Vols Passagers</h3>
        <p>Voyagez en 2ème, 1ère classe ou en classe VIP. Réductions pour les membres réguliers.</p>
        <a href="/reservation.php" class="btn btn-primary btn-block">Réserver un vol</a>
    </div>
    <div class="card">
        <h3>📦 Fret &amp; Cargaison</h3>
        <p>Transportez vos marchandises en toute sécurité. De 1 stack à +100, on a votre solution.</p>
        <a href="/reservation.php" class="btn btn-primary btn-block">Expédier une cargaison</a>
    </div>
    <div class="card">
        <h3>🚕 Service Taxi</h3>
        <p>Transport personnalisé à la demande. Aller simple, retour ou aller-retour.</p>
        <a href="/taxis.php" class="btn btn-primary btn-block">Commander un taxi</a>
    </div>
</div>

<div class="card">
    <h2>📋 Tarifs &amp; Classes</h2>

    <h3 style="color:var(--secondary);margin-top:2rem;">Vols Passagers</h3>
    <div class="grid grid-3" style="margin-top:1.5rem;">
        <div>
            <h4 style="color:var(--primary);">2ème Classe</h4>
            <ul class="tarif-list">
                <li>1 vol : 5 or/améthyste</li>
                <li>2 vols : 10 or/améthyste</li>
                <li>5 vols : 20 or/améthyste</li>
                <li>10 vols : 5 diamants</li>
            </ul>
        </div>
        <div>
            <h4 style="color:var(--primary);">1ère Classe</h4>
            <ul class="tarif-list">
                <li>1 vol : 10 or</li>
                <li>2 vols : 1 diamant</li>
                <li>5 vols : 3 diamants</li>
                <li>10 vols : 7 diamants</li>
                <li>15 vols : 12 diamants</li>
                <li>20 vols : 16 diamants</li>
                <li>25 vols : 24 diamants</li>
            </ul>
        </div>
        <div>
            <h4 style="color:#ffd700;">Classe VIP ⭐</h4>
            <ul class="tarif-list">
                <li>1 vol : 20 or (ou gratuit/48h)</li>
                <li>2 vols : 5 diamants</li>
                <li>5 vols : 15 diamants</li>
                <li>10 vols : 25 diamants</li>
                <li>20 vols : 40 diamants</li>
                <li>25 vols : 1 netherite</li>
            </ul>
            <p style="color:var(--warning);font-size:.9rem;margin-top:.8rem;">⚠️ VIP : -20% sur classes 1 &amp; 2</p>
        </div>
    </div>

    <h3 style="color:var(--secondary);margin-top:2.5rem;">Fret &amp; Cargaison</h3>
    <div class="grid grid-3" style="margin-top:1rem;">
        <?php
        $cargos = [
            ['1 stack','1 diamant'],['5 stacks','3 diamants'],['10 stacks','5 diamants'],
            ['20 stacks','7 diamants'],['50 stacks','10 diamants'],['100 stacks','15 diamants'],
        ];
        foreach ($cargos as [$q,$p]):
        ?>
        <div style="background:rgba(0,0,0,.2);padding:.8rem;border-radius:8px;">
            <strong style="color:var(--light);"><?= $q ?></strong><br>
            <span style="color:var(--primary);"><?= $p ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <p style="color:var(--gray);margin-top:.8rem;font-size:.9rem;">+100 stacks : +1 diamant par 5 stacks supplémentaires</p>

    <h3 style="color:var(--secondary);margin-top:2.5rem;">Service Taxi</h3>
    <ul class="tarif-list" style="margin-top:1rem;">
        <li>→ Aller simple : Prix de base × 1,15</li>
        <li>← Retour : Prix de base × 1,20</li>
        <li>↔ Aller-Retour : Prix de base × 2</li>
    </ul>

    <div style="background:rgba(0,217,255,.1);padding:1.5rem;border-radius:10px;margin-top:2rem;border:1px solid rgba(0,217,255,.3);">
        <h4 style="color:var(--primary);">🔒 Vol Masqué</h4>
        <p>Pour seulement 10 diamants supplémentaires, votre vol n'apparaît pas dans les horaires publics — visible uniquement sur votre tableau de bord et celui de l'administration.</p>
    </div>
</div>

<?php if (!isLoggedIn()): ?>
<div class="card" style="background:linear-gradient(135deg,rgba(0,217,255,.1) 0%,rgba(255,107,53,.1) 100%);text-align:center;">
    <h2>Prêt à décoller ? ✈️</h2>
    <p style="font-size:1.2rem;margin:1.5rem 0;">Créez votre compte et profitez de tous nos services !</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
        <a href="/register.php" class="btn btn-primary">S'inscrire</a>
        <a href="/login.php" class="btn btn-secondary">Se connecter</a>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
