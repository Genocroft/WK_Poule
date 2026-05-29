<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

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

    <div class="split">
        <!-- Profielinformatie -->
        <div class="pool-card">
            <div class="pool-card-header">
                <div>
                    <div class="feature-number">ACCOUNT</div>
                    <h3 class="pool-name">Jouw gegevens</h3>
                </div>
            </div>
            <div style="margin: 1rem 0;">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; color: var(--text-mute); font-size: 0.875rem; margin-bottom: 0.5rem;">Naam</label>
                    <p style="font-size: 1.1rem; font-weight: 500;"><?= htmlspecialchars($user['name']) ?></p>
                </div>
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
