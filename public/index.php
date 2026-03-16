<?php
$pageTitle = 'Accueil - Aéroport Minecraft';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="hero">
    <h1>Aéronoé</h1>
    <p>Voyagez à travers les territoir du serveur de Jules.bb avec style et sécurité. Réservez vos vols, cargaisons et taxis en quelques clics.</p>
</div>

<div class="grid grid-3">
    <div class="card">
        <h3>🛫 Vols Passagers</h3>
        <p>Voyagez confortablement en 1ère, 2ème classe ou en classe VIP. Tarifs avantageux pour les membres réguliers.</p>
        <a href="/reservation.php" class="btn btn-primary btn-block">Réserver un vol</a>
    </div>
    
    <div class="card">
        <h3>📦 Fret & Cargaison</h3>
        <p>Transportez vos marchandises en toute sécurité. De 1 stack à plus de 100, nous avons une solution pour vous.</p>
        <a href="/reservation.php" class="btn btn-primary btn-block">Expédier une cargaison</a>
    </div>
    
    <div class="card">
        <h3>🚕 Service Taxi</h3>
        <p>Besoin d'un transport personnalisé ? Notre service taxi vous emmène où vous voulez, quand vous voulez.</p>
        <a href="/taxis.php" class="btn btn-primary btn-block">Commander un taxi</a>
    </div>
</div>

<div class="card">
    <h2>📋 Tarifs & Classes</h2>
    
    <h3 style="color: var(--secondary); margin-top: 2rem;">Vols Passagers</h3>
    
    <div class="grid grid-3" style="margin-top: 1.5rem;">
        <div>
            <h4 style="color: var(--primary);">2ème Classe</h4>
            <ul style="list-style: none; padding: 0;">
                <li>✓ 1 vol: 5 or/améthyste</li>
                <li>✓ 2 vols: 10 or/améthyste</li>
                <li>✓ 5 vols: 20 or/améthyste</li>
                <li>✓ 10 vols: 5 diamants</li>
            </ul>
        </div>
        
        <div>
            <h4 style="color: var(--primary);">1ère Classe</h4>
            <ul style="list-style: none; padding: 0;">
                <li>✓ 1 vol: 10 or</li>
                <li>✓ 2 vols: 1 diamant</li>
                <li>✓ 5 vols: 3 diamants</li>
                <li>✓ 10 vols: 7 diamants</li>
                <li>✓ 15 vols: 12 diamants</li>
                <li>✓ 20 vols: 16 diamants</li>
                <li>✓ 25 vols: 24 diamants</li>
            </ul>
        </div>
        
        <div>
            <h4 style="color: #ffd700;">Classe VIP ⭐</h4>
            <ul style="list-style: none; padding: 0;">
                <li>✓ 1 vol: Gratuit (tous les 48h) ou 20 or</li>
                <li>✓ 2 vols: 5 diamants</li>
                <li>✓ 5 vols: 15 diamants</li>
                <li>✓ 10 vols: 25 diamants</li>
                <li>✓ 20 vols: 40 diamants</li>
                <li>✓ 25 vols: 1 netherite</li>
            </ul>
            <p style="color: var(--warning); margin-top: 1rem; font-size: 0.9rem;">⚠️ Les VIP bénéficient de -20% sur toutes les autres classes (sauf classe VIP)</p>
        </div>
    </div>
    
    <h3 style="color: var(--secondary); margin-top: 3rem;">Fret & Cargaison</h3>
    <div style="margin-top: 1rem;">
        <p>📦 1 stack = 1 slot</p>
        <ul style="list-style: none; padding: 0; column-count: 2;">
            <li>• 1 stack: 1 diamant</li>
            <li>• 5 stacks: 3 diamants</li>
            <li>• 10 stacks: 5 diamants</li>
            <li>• 20 stacks: 7 diamants</li>
            <li>• 50 stacks: 10 diamants</li>
            <li>• 100 stacks: 15 diamants</li>
            <li>• +100 stacks: +1 diamant par 5 stacks</li>
        </ul>
    </div>
    
    <h3 style="color: var(--secondary); margin-top: 3rem;">Service Taxi</h3>
    <div style="margin-top: 1rem;">
        <ul style="list-style: none; padding: 0;">
            <li>🚕 Aller simple: Prix de base + 15%</li>
            <li>🚕 Retour: Prix de base + 20%</li>
            <li>🚕 Aller-retour: Prix de base × 2</li>
        </ul>
        <p style="color: var(--gray); margin-top: 1rem; font-size: 0.9rem;">Le prix de base dépend de la distance et de la classe choisie</p>
    </div>
    
    <div style="background: rgba(0, 217, 255, 0.1); padding: 1.5rem; border-radius: 10px; margin-top: 2rem; border: 1px solid rgba(0, 217, 255, 0.3);">
        <h4 style="color: var(--primary);">🔒 Vol Masqué</h4>
        <p>Pour seulement 10 diamants supplémentaires, rendez votre réservation privée ! Votre vol n'apparaîtra pas dans les horaires publics, mais restera visible sur votre tableau de bord et celui de l'administration.</p>
    </div>
</div>

<?php if (!isLoggedIn()): ?>
<div class="card" style="background: linear-gradient(135deg, rgba(0, 217, 255, 0.1) 0%, rgba(255, 107, 53, 0.1) 100%); text-align: center;">
    <h2>Prêt à décoller ? ✈️</h2>
    <p style="font-size: 1.2rem; margin: 1.5rem 0;">Créez votre compte dès maintenant et profitez de tous nos services !</p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <a href="/register.php" class="btn btn-primary">S'inscrire</a>
        <a href="/login.php" class="btn btn-secondary">Se connecter</a>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php';
