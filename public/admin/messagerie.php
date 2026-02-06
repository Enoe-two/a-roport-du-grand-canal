<?php
$pageTitle = 'Messagerie Admin - Aéroport Minecraft';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$currentUser = getCurrentUser();
$pdo = getDB();
$success = '';
$error = '';

// Utilisateur sélectionné pour la conversation
$selectedUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$selectedUser = null;

if ($selectedUserId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$selectedUserId]);
    $selectedUser = $stmt->fetch();
}

// Envoi d'un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedUserId) {
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    if (empty($subject) || empty($message)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO messages (from_user_id, to_user_id, subject, message) 
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$currentUser['id'], $selectedUserId, $subject, $message])) {
            $success = "Message envoyé avec succès";
            $_POST = [];
        } else {
            $error = "Erreur lors de l'envoi du message";
        }
    }
}

// Récupérer tous les utilisateurs avec le nombre de messages non lus
$stmt = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM messages m 
            WHERE m.from_user_id = u.id AND m.to_user_id = {$currentUser['id']} AND m.is_read = 0
           ) as unread_count
    FROM users u
    WHERE u.role != 'admin' AND u.status = 'approved'
    ORDER BY u.pseudo_minecraft ASC
");
$users = $stmt->fetchAll();

// Messages de la conversation sélectionnée
$messages = [];
if ($selectedUserId && $selectedUser) {
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
    $stmt->execute([$currentUser['id'], $selectedUserId, $selectedUserId, $currentUser['id']]);
    $messages = $stmt->fetchAll();
    
    // Marquer les messages reçus comme lus
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE to_user_id = ? AND from_user_id = ? AND is_read = 0
    ");
    $stmt->execute([$currentUser['id'], $selectedUserId]);
}

// Compter tous les messages non lus
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE to_user_id = ? AND is_read = 0");
$stmt->execute([$currentUser['id']]);
$totalUnread = $stmt->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="hero">
    <h1>💬 Messagerie Administrateur</h1>
    <p>Communiquez avec tous les utilisateurs</p>
</div>

<?php if ($success): ?>
    <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="grid grid-3">
    <div class="stat-card">
        <span class="stat-value"><?= count($users) ?></span>
        <span class="stat-label">Utilisateurs Actifs</span>
    </div>
    
    <div class="stat-card" style="<?= $totalUnread > 0 ? 'border-color: var(--danger);' : '' ?>">
        <span class="stat-value" style="<?= $totalUnread > 0 ? 'color: var(--danger);' : '' ?>"><?= $totalUnread ?></span>
        <span class="stat-label">Messages Non Lus</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $selectedUserId ? count($messages) : 0 ?></span>
        <span class="stat-label">Messages Conversation</span>
    </div>
</div>

<div class="grid" style="grid-template-columns: 300px 1fr; gap: 2rem; align-items: start;">
    <!-- Liste des utilisateurs -->
    <div class="card" style="position: sticky; top: 100px;">
        <h2>📋 Utilisateurs</h2>
        
        <?php if (empty($users)): ?>
            <p style="text-align: center; color: var(--gray); padding: 2rem;">
                Aucun utilisateur
            </p>
        <?php else: ?>
            <div style="margin-top: 1rem; max-height: 600px; overflow-y: auto;">
                <?php foreach ($users as $user): ?>
                    <a href="?user_id=<?= $user['id'] ?>" 
                       class="card" 
                       style="display: block; margin-bottom: 0.5rem; background: <?= $selectedUserId === $user['id'] ? 'rgba(0, 217, 255, 0.2)' : 'rgba(0, 217, 255, 0.05)' ?>; border: 1px solid <?= $selectedUserId === $user['id'] ? 'var(--primary)' : 'rgba(0, 217, 255, 0.2)' ?>; text-decoration: none; transition: all 0.3s;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-weight: 700; color: var(--light); margin: 0;">
                                    <?= htmlspecialchars($user['pseudo_minecraft']) ?>
                                </p>
                                <p style="font-size: 0.85rem; color: var(--gray); margin: 0.2rem 0 0 0;">
                                    <?php if ($user['role'] === 'vip'): ?>
                                        <span class="badge badge-vip" style="font-size: 0.75rem;">VIP</span>
                                    <?php else: ?>
                                        Membre
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ($user['unread_count'] > 0): ?>
                                <span class="badge" style="background: var(--danger);">
                                    <?= $user['unread_count'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Conversation -->
    <div>
        <?php if (!$selectedUser): ?>
            <div class="card" style="text-align: center; padding: 4rem 2rem;">
                <p style="font-size: 3rem; margin-bottom: 1rem;">💬</p>
                <h2 style="color: var(--gray);">Sélectionnez un utilisateur</h2>
                <p style="color: var(--gray);">Choisissez un utilisateur dans la liste pour voir la conversation</p>
            </div>
        <?php else: ?>
            <!-- En-tête conversation -->
            <div class="card" style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="color: var(--primary); margin: 0;">
                            Conversation avec <?= htmlspecialchars($selectedUser['pseudo_minecraft']) ?>
                        </h2>
                        <p style="color: var(--gray); margin: 0.5rem 0 0 0;">
                            <?= htmlspecialchars($selectedUser['pseudo_discord']) ?>
                            <?php if ($selectedUser['role'] === 'vip'): ?>
                                <span class="badge badge-vip">VIP</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Formulaire nouveau message -->
            <div class="card">
                <h3>✉️ Nouveau Message</h3>
                <form method="POST" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label for="subject">Sujet</label>
                        <input type="text" id="subject" name="subject" class="form-control" required 
                               value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                               placeholder="Ex: Réponse à votre demande">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" class="form-control" rows="4" required 
                                  placeholder="Écrivez votre message ici..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Envoyer</button>
                </form>
            </div>
            
            <!-- Historique -->
            <div class="card">
                <h3>📬 Historique (<?= count($messages) ?> messages)</h3>
                
                <?php if (empty($messages)): ?>
                    <p style="text-align: center; color: var(--gray); padding: 3rem;">
                        Aucun message échangé avec cet utilisateur
                    </p>
                <?php else: ?>
                    <div style="margin-top: 1.5rem;">
                        <?php foreach ($messages as $msg): ?>
                            <div class="card" style="margin-bottom: 1rem; background: <?= $msg['from_user_id'] == $currentUser['id'] ? 'rgba(0, 217, 255, 0.05)' : 'rgba(255, 107, 53, 0.05)' ?>; border-left: 4px solid <?= $msg['from_user_id'] == $currentUser['id'] ? 'var(--primary)' : 'var(--secondary)' ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                    <div>
                                        <p style="font-weight: 700; color: <?= $msg['from_user_id'] == $currentUser['id'] ? 'var(--primary)' : 'var(--secondary)' ?>; margin: 0;">
                                            <?= $msg['from_user_id'] == $currentUser['id'] ? '👨‍💼 Vous (Admin)' : '👤 ' . htmlspecialchars($msg['from_pseudo']) ?>
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
        <?php endif; ?>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
