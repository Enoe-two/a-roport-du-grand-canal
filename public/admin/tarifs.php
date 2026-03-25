<?php
$pageTitle = 'Gestion des Tarifs - Admin';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pdo = getDB();
$success = '';
$error   = '';

// ── Actions POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Modifier un tarif individuel
    if ($action === 'update_tarif') {
        $id    = intval($_POST['tarif_id'] ?? 0);
        $prix  = floatval($_POST['prix']   ?? 0);
        $devise= sanitize($_POST['devise'] ?? '');
        if ($id > 0 && $prix >= 0 && $devise) {
            $pdo->prepare("UPDATE tarifs SET prix=?, devise=? WHERE id=?")->execute([$prix, $devise, $id]);
            $success = "Tarif mis à jour.";
        } else {
            $error = "Données invalides.";
        }
    }

    // Appliquer un pourcentage sur une catégorie entière
    if ($action === 'apply_percent') {
        $categorie = sanitize($_POST['categorie'] ?? '');
        $pct       = floatval($_POST['pourcentage'] ?? 0);
        if ($categorie && $pct !== 0.0) {
            $mult = 1 + ($pct / 100);
            // N'applique pas sur les multiplicateurs taxi ni les %, ni les heures
            $pdo->prepare("UPDATE tarifs SET prix = ROUND(prix * ?, 2) WHERE categorie=? AND devise NOT IN ('%','x','h')")
                ->execute([$mult, $categorie]);
            $count = $pdo->rowCount();
            $sign  = $pct > 0 ? '+' : '';
            $success = "Variation de {$sign}{$pct}% appliquée sur ".ucfirst($categorie)." ({$count} tarifs modifiés).";
        } else {
            $error = "Catégorie ou pourcentage invalide.";
        }
    }

    // Appliquer un pourcentage global sur TOUT
    if ($action === 'apply_global') {
        $pct  = floatval($_POST['pourcentage_global'] ?? 0);
        if ($pct !== 0.0) {
            $mult = 1 + ($pct / 100);
            $pdo->prepare("UPDATE tarifs SET prix = ROUND(prix * ?, 2) WHERE devise NOT IN ('%','x','h')")
                ->execute([$mult]);
            $count = $pdo->rowCount();
            $sign  = $pct > 0 ? '+' : '';
            $success = "Variation globale de {$sign}{$pct}% appliquée sur {$count} tarifs.";
        } else {
            $error = "Pourcentage invalide.";
        }
    }

    // Reset aux valeurs par défaut
    if ($action === 'reset_defaults') {
        $defaults = [
            ['vol_2_1',5,'or/améthyste'],['vol_2_2',10,'or/améthyste'],['vol_2_5',20,'or/améthyste'],['vol_2_10',5,'diamants'],
            ['vol_1_1',10,'or'],['vol_1_2',1,'diamants'],['vol_1_5',3,'diamants'],['vol_1_10',7,'diamants'],
            ['vol_1_15',12,'diamants'],['vol_1_20',16,'diamants'],['vol_1_25',24,'diamants'],
            ['vol_v_1',20,'or'],['vol_v_2',5,'diamants'],['vol_v_5',15,'diamants'],['vol_v_10',25,'diamants'],
            ['vol_v_20',40,'diamants'],['vol_v_25',1,'netherite'],
            ['cargo_1',1,'diamants'],['cargo_5',3,'diamants'],['cargo_10',5,'diamants'],
            ['cargo_20',7,'diamants'],['cargo_50',10,'diamants'],['cargo_100',15,'diamants'],
            ['taxi_aller',1.15,'x'],['taxi_retour',1.20,'x'],['taxi_aller_retour',2.00,'x'],
            ['vol_masque_prix',10,'diamants'],['vip_reduction',20,'%'],['vip_vol_gratuit_h',48,'h'],
        ];
        $stmt = $pdo->prepare("UPDATE tarifs SET prix=?, devise=? WHERE cle=?");
        foreach ($defaults as [$cle, $prix, $devise]) $stmt->execute([$prix, $devise, $cle]);
        $success = "Tous les tarifs ont été remis aux valeurs par défaut.";
    }
}

// ── Charger les tarifs groupés ─────────────────────────────────────
$allTarifs = $pdo->query("SELECT * FROM tarifs ORDER BY categorie, id")->fetchAll();
$grouped   = [];
foreach ($allTarifs as $t) $grouped[$t['categorie']][] = $t;

$categories = [
    'vol_classe2' => ['label' => '2ème Classe',      'color' => 'rgba(0,217,255,.1)',  'border' => 'rgba(0,217,255,.3)'],
    'vol_classe1' => ['label' => '1ère Classe',      'color' => 'rgba(72,187,120,.1)', 'border' => 'rgba(72,187,120,.3)'],
    'vol_vip'     => ['label' => 'Classe VIP',        'color' => 'rgba(255,215,0,.1)',  'border' => 'rgba(255,215,0,.3)'],
    'cargaison'   => ['label' => 'Cargaison',         'color' => 'rgba(255,107,53,.1)', 'border' => 'rgba(255,107,53,.3)'],
    'taxi'        => ['label' => 'Taxis',             'color' => 'rgba(155,89,182,.1)', 'border' => 'rgba(155,89,182,.3)'],
    'options'     => ['label' => 'Options & Règles',  'color' => 'rgba(237,137,54,.1)', 'border' => 'rgba(237,137,54,.3)'],
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="hero">
    <h1>💰 Gestion des Tarifs</h1>
    <p>Modifiez les prix individuellement ou appliquez des variations en pourcentage</p>
</div>

<?php if ($success): ?>
    <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- ── Actions globales ──────────────────────────────────────── -->
<div class="grid grid-2">
    <!-- Variation globale -->
    <div class="card">
        <h2>📊 Variation globale</h2>
        <p style="color:var(--gray);margin-bottom:1.2rem;font-size:.95rem;">Augmenter ou réduire TOUS les prix d'un pourcentage.</p>
        <form method="POST">
            <input type="hidden" name="action" value="apply_global">
            <div style="display:flex;gap:1rem;align-items:flex-end;">
                <div class="form-group" style="flex:1;margin:0">
                    <label>Pourcentage (%)</label>
                    <input type="number" name="pourcentage_global" class="form-control" step="0.1" placeholder="-10 ou +20" required>
                </div>
                <button type="submit" class="btn btn-primary" onclick="return confirm('Appliquer cette variation sur TOUS les prix ?')">Appliquer</button>
            </div>
            <p style="color:var(--gray);font-size:.82rem;margin-top:.6rem;">Entrez un nombre négatif pour réduire, positif pour augmenter.</p>
        </form>
    </div>

    <!-- Reset -->
    <div class="card" style="border-color:rgba(245,101,101,.3)">
        <h2 style="color:var(--danger)">🔄 Réinitialiser</h2>
        <p style="color:var(--gray);margin-bottom:1.2rem;font-size:.95rem;">Remet tous les tarifs aux valeurs d'origine du projet.</p>
        <form method="POST">
            <input type="hidden" name="action" value="reset_defaults">
            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Réinitialiser TOUS les tarifs aux valeurs par défaut ?')">
                Réinitialiser tous les tarifs
            </button>
        </form>
    </div>
</div>

<!-- ── Tarifs par catégorie ──────────────────────────────────── -->
<?php foreach ($categories as $catKey => $catInfo): ?>
<?php if (empty($grouped[$catKey])) continue; ?>
<div class="card" style="border-color:<?= $catInfo['border'] ?>">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
        <h2 style="margin:0"><?= $catInfo['label'] ?></h2>

        <!-- Variation sur catégorie -->
        <form method="POST" style="display:flex;gap:.6rem;align-items:center;">
            <input type="hidden" name="action" value="apply_percent">
            <input type="hidden" name="categorie" value="<?= $catKey ?>">
            <input type="number" name="pourcentage" class="form-control" step="0.1" placeholder="%" 
                   style="width:90px;padding:.4rem .7rem;font-size:.9rem;" required>
            <button type="submit" class="btn btn-secondary" style="white-space:nowrap;padding:.4rem .9rem;"
                    onclick="return confirm('Appliquer ce % sur <?= $catInfo['label'] ?> ?')">
                Appliquer %
            </button>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Tarif</th>
                    <th>Prix actuel</th>
                    <th>Devise</th>
                    <th>Modifier</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($grouped[$catKey] as $tarif): ?>
                <tr>
                    <td>
                        <strong style="color:var(--light)"><?= htmlspecialchars($tarif['label']) ?></strong>
                        <?php if ($tarif['description']): ?>
                            <br><small style="color:var(--gray)"><?= htmlspecialchars($tarif['description']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="prix-affiche-<?= $tarif['id'] ?>" style="font-weight:700;color:var(--primary);">
                            <?= number_format((float)$tarif['prix'], 2) ?>
                        </span>
                    </td>
                    <td style="color:var(--gray)"><?= htmlspecialchars($tarif['devise']) ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:.5rem;align-items:center;" 
                              onsubmit="return confirmerModif(this, <?= $tarif['id'] ?>)">
                            <input type="hidden" name="action"   value="update_tarif">
                            <input type="hidden" name="tarif_id" value="<?= $tarif['id'] ?>">
                            <input type="number" name="prix" 
                                   value="<?= number_format((float)$tarif['prix'], 2, '.', '') ?>" 
                                   step="0.01" min="0" 
                                   class="form-control" 
                                   style="width:90px;padding:.4rem .7rem;font-size:.9rem;" required>
                            <select name="devise" class="form-control" style="width:120px;padding:.4rem .5rem;font-size:.85rem;">
                                <?php
                                $devises = ['or','or/améthyste','diamants','netherite','x','%','h'];
                                foreach ($devises as $dev):
                                ?>
                                    <option value="<?= $dev ?>" <?= $tarif['devise']===$dev?'selected':'' ?>><?= $dev ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-success" style="padding:.4rem .8rem;white-space:nowrap;">✓</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<!-- ── Lien retour admin ─────────────────────────────────────── -->
<div style="text-align:center;margin-top:1rem;">
    <a href="/admin/dashboard.php" class="btn btn-secondary">← Retour au dashboard</a>
</div>

<script>
function confirmerModif(form, id) {
    var prix  = form.querySelector('[name=prix]').value;
    var devise = form.querySelector('[name=devise]').value;
    return confirm('Mettre à jour ce tarif à ' + prix + ' ' + devise + ' ?');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
