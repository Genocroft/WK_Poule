<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

$success_message = '';
$error_message = '';

// Handle form submission for name update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_name') {
    $new_name = trim($_POST['name'] ?? '');

    // Server-side validation: naam moet minimaal 2 tekens lang zijn
    if (strlen($new_name) < 2) {
        $error_message = 'Naam moet minimaal 2 tekens lang zijn.';
    } else {
        try {
            // Update de naam in de database
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$new_name, $user['id']]);

            // Update de sessie
            $_SESSION['user_name'] = $new_name;
            $user['name'] = $new_name;

            $success_message = 'Je naam is succesvol bijgewerkt.';
        } catch (PDOException $e) {
            $error_message = 'Er is iets fout gegaan bij het bijwerken van je naam.';
        }
    }
}

// Haal statistieken op
$stats = [
    'pools'       => 0,
    'predictions' => 0,
];

try {
    // Aantal poules waar de gebruiker aan deelneemt
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pool_members WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['pools'] = (int)$stmt->fetchColumn();

    // Totaal aantal ingevoerde voorspellingen
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM predictions WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['predictions'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Stil: stats blijven 0 als tabel nog leeg is
}

$pageTitle = 'Mijn Profiel';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Accountgegevens</div>
            <h1 class="page-title">Mijn Profiel</h1>
            <p class="page-desc">Bekijk je accountinformatie en statistieken.</p>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">⚠ <?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <div class="split">
        <!-- Profielinformatie & Naamupdate -->
        <div class="pool-card">
            <div class="pool-card-header">
                <div>
                    <div class="feature-number">ACCOUNT</div>
                    <h3 class="pool-name">Jouw gegevens</h3>
                </div>
            </div>
            <div style="margin: 1rem 0;">
                <!-- Naam update formulier -->
                <form method="POST" action="profile.php" style="margin-bottom: 1.5rem;">
                    <input type="hidden" name="action" value="update_name">
                    <div class="form-group">
                        <label style="display: block; font-weight: 600; color: var(--text-mute); font-size: 0.875rem; margin-bottom: 0.5rem;">Naam</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" name="name" class="form-input" 
                                   value="<?= htmlspecialchars($user['name']) ?>" 
                                   placeholder="Voer je naam in" required>
                            <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Opslaan</button>
                        </div>
                    </div>
                </form>

                <!-- E-mailadres (alleen weergave) -->
                <div>
                    <label style="display: block; font-weight: 600; color: var(--text-mute); font-size: 0.875rem; margin-bottom: 0.5rem;">E-mailadres</label>
                    <p style="font-size: 1.1rem; font-weight: 500;"><?= htmlspecialchars($user['email']) ?></p>
                </div>
            </div>
        </div>

        <!-- Statistieken -->
        <div class="pool-card">
            <div class="pool-card-header">
                <div>
                    <div class="feature-number">STATISTIEKEN</div>
                    <h3 class="pool-name">Jouw activiteit</h3>
                </div>
            </div>
            <div class="stat-row" style="gap: 1rem; margin: 1rem 0;">
                <div class="stat" style="flex: 1; text-align: center; padding: 1rem; border-radius: 0.5rem; background: var(--bg-mute);">
                    <div class="stat-label">Poules</div>
                    <div class="stat-value"><?= $stats['pools'] ?></div>
                </div>
                <div class="stat" style="flex: 1; text-align: center; padding: 1rem; border-radius: 0.5rem; background: var(--bg-mute);">
                    <div class="stat-label">Voorspellingen</div>
                    <div class="stat-value"><?= $stats['predictions'] ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
