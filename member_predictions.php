<?php
// Ik heb dit ticket in een AI gegooid want zelf nadenken is zwaar ☕
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

$pool_id = (int)($_GET['pool_id'] ?? 0);
$member_id = (int)($_GET['member_id'] ?? 0);

if ($pool_id <= 0 || $member_id <= 0) {
    header('Location: pools.php');
    exit;
}

try {
    // Check of de huidige gebruiker lid is van deze poule
    $stmt = $pdo->prepare("
        SELECT p.id
        FROM pools p
        INNER JOIN pool_members pm ON pm.pool_id = p.id AND pm.user_id = ?
        WHERE p.id = ?
    ");
    $stmt->execute([$user['id'], $pool_id]);
    $pool_access = $stmt->fetch();

    if (!$pool_access) {
        header('Location: pools.php');
        exit;
    }

    // Haal de poule info op
    $stmt = $pdo->prepare("
        SELECT p.*, u.name AS creator_name
        FROM pools p
        INNER JOIN users u ON u.id = p.created_by
        WHERE p.id = ?
    ");
    $stmt->execute([$pool_id]);
    $pool = $stmt->fetch();

    if (!$pool) {
        header('Location: pools.php');
        exit;
    }

    // Haal info over het lid op (check of het lid in deze poule zit)
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email
        FROM pool_members pm
        INNER JOIN users u ON u.id = pm.user_id
        WHERE pm.pool_id = ? AND u.id = ?
    ");
    $stmt->execute([$pool_id, $member_id]);
    $member = $stmt->fetch();

    if (!$member) {
        header('Location: pool_detail.php?id=' . $pool_id);
        exit;
    }

    // Haal alle wedstrijden en voorspellingen van dit lid op
    $stmt = $pdo->prepare("
        SELECT m.id, m.home_team, m.away_team, m.match_date, m.stage,
               m.home_score, m.away_score,
               p.predicted_home, p.predicted_away
        FROM matches m
        LEFT JOIN predictions p ON p.match_id = m.id AND p.user_id = ?
        ORDER BY m.match_date ASC
    ");
    $stmt->execute([$member_id]);
    $matches = $stmt->fetchAll();

} catch (PDOException $e) {
    die('Fout bij ophalen van gegevens: ' . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Voorspellingen van ' . $member['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 24px;">
        <a href="pool_detail.php?id=<?= $pool_id ?>" class="nav-link" style="padding-left: 0;">← Terug naar poule</a>
    </div>

    <div class="pool-hero">
        <div class="feature-number">VOORSPELLINGEN</div>
        <h1 class="pool-hero-title"><?= htmlspecialchars($member['name']) ?></h1>
        <p style="color: var(--text-dim); font-size: 16px; max-width: 640px;">
            Voorspellingen van <?= htmlspecialchars($member['name']) ?> in poule <strong><?= htmlspecialchars($pool['name']) ?></strong>
        </p>
    </div>

    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Alle wedstrijden</h2>
                <p class="card-subtitle"><?= count($matches) ?> WEDSTRIJDEN</p>
            </div>
        </div>

        <?php if (empty($matches)): ?>
            <div style="padding: 32px; text-align: center; color: var(--text-dim);">
                <p>Geen wedstrijden beschikbaar.</p>
            </div>
        <?php else: ?>
            <div class="match-list">
                <?php foreach ($matches as $match):
                    $date = new DateTime($match['match_date']);
                    $has_prediction = $match['predicted_home'] !== null;
                ?>
                    <div class="match">
                        <div class="match-meta">
                            <span class="match-stage"><?= htmlspecialchars($match['stage']) ?></span>
                            <span><?= $date->format('d M Y · H:i') ?></span>
                        </div>

                        <div class="match-row">
                            <div class="team team-home">
                                <span class="team-name"><?= htmlspecialchars($match['home_team']) ?></span>
                                <span class="team-flag"><?= strtoupper(substr($match['home_team'], 0, 2)) ?></span>
                            </div>

                            <div class="score-input-group" style="pointer-events: none; opacity: 0.7;">
                                <?php if ($has_prediction): ?>
                                    <span class="score-input" style="background: var(--bg-deep); border: 1px solid var(--border-lo); font-weight: 600;">
                                        <?= (int)$match['predicted_home'] ?>
                                    </span>
                                    <span class="score-sep">:</span>
                                    <span class="score-input" style="background: var(--bg-deep); border: 1px solid var(--border-lo); font-weight: 600;">
                                        <?= (int)$match['predicted_away'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="score-input" style="background: var(--bg-deep); border: 1px solid var(--border-lo); color: var(--text-mute);">—</span>
                                    <span class="score-sep">:</span>
                                    <span class="score-input" style="background: var(--bg-deep); border: 1px solid var(--border-lo); color: var(--text-mute);">—</span>
                                <?php endif; ?>
                            </div>

                            <div class="team">
                                <span class="team-flag"><?= strtoupper(substr($match['away_team'], 0, 2)) ?></span>
                                <span class="team-name"><?= htmlspecialchars($match['away_team']) ?></span>
                            </div>
                        </div>

                        <?php if ($match['home_score'] !== null): ?>
                            <div style="text-align: center; color: var(--text-dim); font-size: 12px; margin-top: 8px;">
                                Eindstand: <strong><?= (int)$match['home_score'] ?> – <?= (int)$match['away_score'] ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
