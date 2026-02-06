<?php
$pageTitle = 'Messagerie - Aéroport Minecraft';
require_once __DIR__ . '/../../includes/functions.php';

requireVIP();

$currentUser = getCurrentUser();
$pdo = getDB();
$success = '';
$error = '';

// Récupérer l'ID de l'admin
$stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$admin = $stmt->fetch();
$adminId = $admin ? $admin['id'] : null;

if (!$adminId) {
    $error = "Aucun administrateur disponible pour le moment";
}

// Envoi d'un nouveau message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adminId) {
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    if (empty($subject) || empty($message)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO messages (from_user_id, to_user_id, subject, message) 
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$currentUser['id'], $adminId, $subject, $message])) {
            $success = "Message envoyé avec succès";
            $_POST = []; // Réinitialiser le formulaire
        } else {
            $error = "Erreur lors de l'envoi du message";
        }
    }
}

// Récupérer tous les messages de la conversation avec l'admin
if ($adminId) {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u_from.pseudo_minecraft as from_pseudo, 
               u_to.pseudo_minecraft as to_pseudo
        FROM messages m
        JOIN users u_from ON m.from_user_id = u_from.id
        JOIN users u_to ON m.to_user_id = u_to.id
        WHERE (m.from_user_id = ? AND m.to_user_id = ?)
           OR (m.from_user_id = ? AND m.to_user_id = ?)
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$currentUser['id'], $adminId, $adminId, $currentUser['id']]);
    $messages = $stmt->fetchAll();
    
    // Marquer les messages reçus comme lus
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE to_user_id = ? AND from_user_id = ? AND is_read = 0
    ");
    $stmt->execute([$currentUser['id'], $adminId]);
} else {
    $messages = [];
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="hero">
    <h1>💬 Messagerie</h1>
    <p>Communiquez avec l'administration</p>
</div>

<?php if ($success): ?>
    <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="grid grid-2">
    <!-- Nouveau message -->
    <div class="card">
        <h2>✉️ Nouveau Message</h2>
        <?php if ($adminId): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="subject">Sujet</label>
                    <input type="text" id="subject" name="subject" class="form-control" required 
                           value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                           placeholder="Ex: Question sur ma réservation">
                </div>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" class="form-control" rows="6" required 
                              placeholder="Écrivez votre message ici..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Envoyer</button>
            </form>
        <?php else: ?>
            <p style="color: var(--danger);">Service de messagerie temporairement indisponible</p>
        <?php endif; ?>
    </div>
    
    <!-- Statistiques -->
    <div class="card">
        <h2>📊 Statistiques</h2>
        <div style="margin-top: 1.5rem;">
            <div style="padding: 1rem; background: rgba(0, 217, 255, 0.1); border-radius: 8px; margin-bottom: 1rem;">
                <p style="font-size: 2rem; font-weight: 700; color: var(--primary); margin: 0;">
                    <?= count($messages) ?>
                </p>
                <p style="color: var(--gray); margin: 0;">Messages au total</p>
            </div>
            
            <div style="padding: 1rem; background: rgba(72, 187, 120, 0.1); border-radius: 8px;">
                <p style="font-size: 2rem; font-weight: 700; color: var(--success); margin: 0;">
                    <?php 
                    $messagesEnvoyes = array_filter($messages, function($m) use ($currentUser) {
                        return $m['from_user_id'] == $currentUser['id'];
                    });
                    echo count($messagesEnvoyes);
                    ?>
                </p>
                <p style="color: var(--gray); margin: 0;">Messages envoyés</p>
            </div>
        </div>
    </div>
</div>

<!-- Historique des messages -->
<div class="card">
    <h2>📬 Historique de la Conversation</h2>
    
    <?php if (empty($messages)): ?>
        <p style="text-align: center; color: var(--gray); padding: 3rem;">
            Aucun message pour le moment. Envoyez votre premier message !
        </p>
    <?php else: ?>
        <div style="margin-top: 1.5rem;">
            <?php foreach ($messages as $msg): ?>
                <div class="card" style="margin-bottom: 1.5rem; background: <?= $msg['from_user_id'] == $currentUser['id'] ? 'rgba(0, 217, 255, 0.05)' : 'rgba(255, 107, 53, 0.05)' ?>; border-left: 4px solid <?= $msg['from_user_id'] == $currentUser['id'] ? 'var(--primary)' : 'var(--secondary)' ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <p style="font-weight: 700; color: <?= $msg['from_user_id'] == $currentUser['id'] ? 'var(--primary)' : 'var(--secondary)' ?>; margin: 0;">
                                <?= $msg['from_user_id'] == $currentUser['id'] ? '👤 Vous' : '👨‍💼 Admin' ?>
                            </p>
                            <p style="font-size: 0.9rem; color: var(--gray); margin: 0;">
                                <?= date('d/m/Y à H:i', strtotime($msg['created_at'])) ?>
                            </p>
                        </div>
                        <?php if ($msg['from_user_id'] != $currentUser['id'] && !$msg['is_read']): ?>
                            <span class="badge" style="background: var(--danger);">Nouveau</span>
                        <?php endif; ?>
                    </div>
                    
                    <h4 style="color: var(--light); margin-bottom: 0.5rem;">
                        <?= htmlspecialchars($msg['subject']) ?>
                    </h4>
                    
                    <p style="color: var(--gray); white-space: pre-wrap; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card" style="background: rgba(0, 217, 255, 0.05);">
    <h3 style="color: var(--primary);">ℹ️ Comment utiliser la messagerie ?</h3>
    <ul style="list-style: none; padding: 0; margin-top: 1rem;">
        <li style="margin: 0.8rem 0;">
            ✉️ Utilisez la messagerie pour toute question concernant vos réservations
        </li>
        <li style="margin: 0.8rem 0;">
            ⏱️ L'administration vous répondra dans les plus brefs délais
        </li>
        <li style="margin: 0.8rem 0;">
            📝 Soyez précis dans vos demandes (numéro de réservation, dates, etc.)
        </li>
        <li style="margin: 0.8rem 0;">
            🔔 Vous serez notifié lors de nouvelles réponses
        </li>
    </ul>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
