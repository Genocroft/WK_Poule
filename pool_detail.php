<?php
// ChatGPT was hier — mijn eigen brein stond op pauze 🧠💨
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

$pool_id = (int)($_GET['id'] ?? 0);
if ($pool_id <= 0) {
    header('Location: pools.php');
    exit;
}

// Haal de poule op + check of gebruiker lid is
$pool = null;
$members = [];
$ranking = [];
$predictions = [];
$matches = [];

try {
    // Poule + check of user lid is
    $stmt = $pdo->prepare("
        SELECT p.*, u.name AS creator_name
        FROM pools p
        INNER JOIN users u ON u.id = p.created_by
        INNER JOIN pool_members pm ON pm.pool_id = p.id AND pm.user_id = ?
        WHERE p.id = ?
    ");
    $stmt->execute([$user['id'], $pool_id]);
    $pool = $stmt->fetch();

    if (!$pool) {
        header('Location: pools.php');
        exit;
    }

    // Leden ophalen
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, pm.joined_at
        FROM pool_members pm
        INNER JOIN users u ON u.id = pm.user_id
        WHERE pm.pool_id = ?
        ORDER BY pm.joined_at ASC
    ");
    $stmt->execute([$pool_id]);
    $members = $stmt->fetchAll();

    // Ranglijst ophalen met totale punten per lid
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            COALESCE(SUM(p.points), 0) AS total_points
        FROM pool_members pm
        INNER JOIN users u ON u.id = pm.user_id
        LEFT JOIN predictions p ON p.user_id = u.id
        WHERE pm.pool_id = ?
        GROUP BY u.id, u.name
        ORDER BY total_points DESC, u.name ASC
    ");
    $stmt->execute([$pool_id]);
    $ranking = $stmt->fetchAll();

    // Haal alle wedstrijden op met voorspellingen van pool-leden
    $stmt = $pdo->prepare("
        SELECT m.id as match_id, m.home_team, m.away_team, m.match_date, m.stage,
               u.id as user_id, u.name as user_name,
               p.predicted_home, p.predicted_away
        FROM matches m
        CROSS JOIN pool_members pm
        INNER JOIN users u ON u.id = pm.user_id
        LEFT JOIN predictions p ON p.match_id = m.id AND p.user_id = u.id
        WHERE pm.pool_id = ?
        ORDER BY m.match_date ASC, u.name ASC
    ");
    $stmt->execute([$pool_id]);
    $predictions_raw = $stmt->fetchAll();

    // Structureer de data als $predictions[match_id][user_id]
    $matches_list = [];
    foreach ($predictions_raw as $row) {
        $match_id = (int)$row['match_id'];
        $user_id = (int)$row['user_id'];

        // Voeg match toe aan matches_list (met deduplicatie)
        if (!isset($matches_list[$match_id])) {
            $matches_list[$match_id] = [
                'id' => $match_id,
                'home_team' => $row['home_team'],
                'away_team' => $row['away_team'],
                'match_date' => $row['match_date'],
                'stage' => $row['stage']
            ];
        }

        // Voeg voorspelling toe aan predictions array
        if (!isset($predictions[$match_id])) {
            $predictions[$match_id] = [];
        }
        $predictions[$match_id][$user_id] = [
            'name' => $row['user_name'],
            'predicted_home' => $row['predicted_home'],
            'predicted_away' => $row['predicted_away']
        ];
    }

    // Zet matches_list in de juiste volgorde
    ksort($matches_list);
    $matches = array_values($matches_list);

} catch (PDOException $e) {
    die('Fout bij ophalen van poule: ' . htmlspecialchars($e->getMessage()));
}

$pageTitle = $pool['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 24px;">
        <a href="pools.php" class="nav-link" style="padding-left: 0;">← Terug naar poules</a>
    </div>

    <div class="pool-hero">
        <div class="feature-number">POULE</div>
        <h1 class="pool-hero-title"><?= htmlspecialchars($pool['name']) ?></h1>
        <?php if ($pool['description']): ?>
            <p style="color: var(--text-dim); font-size: 16px; max-width: 640px;">
                <?= nl2br(htmlspecialchars($pool['description'])) ?>
            </p>
        <?php endif; ?>
        <div class="pool-hero-code">
            <span class="pool-hero-code-label">Toegangscode:</span>
            <strong><?= htmlspecialchars($pool['access_code']) ?></strong>
        </div>
    </div>

    <!-- Ranglijst -->
    <section class="card" style="margin-bottom: 32px;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Ranglijst</h2>
                <p class="card-subtitle"><?= count($ranking) ?> DEELNEMERS</p>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-lo);">
                        <th style="padding: 12px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-mute); width: 40px;">Pos</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-mute);">Deelnemer</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-mute); width: 100px;">Punten</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking as $position => $member): 
                        $position_num = $position + 1;
                        $is_current_user = (int)$member['id'] === (int)$user['id'];
                        $medal = '';
                        
                        if ($position_num === 1) {
                            $medal = '🥇';
                        } elseif ($position_num === 2) {
                            $medal = '🥈';
                        } elseif ($position_num === 3) {
                            $medal = '🥉';
                        }
                        
                        $row_style = $is_current_user ? 
                            'background-color: var(--bg-deep); border-left: 4px solid var(--field);' : 
                            '';
                    ?>
                        <tr style="border-bottom: 1px solid var(--border-lo); <?= $row_style ?>">
                            <td style="padding: 12px; text-align: center; font-weight: 600;">
                                <?php if ($medal): ?>
                                    <span style="font-size: 20px;"><?= $medal ?></span>
                                <?php else: ?>
                                    <?= $position_num ?>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px; text-align: left;">
                                <div style="font-weight: 500; font-size: 14px;">
                                    <?= htmlspecialchars($member['name']) ?>
                                    <?php if ($is_current_user): ?>
                                        <span style="color: var(--text-mute); font-size: 12px;"> (jij)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding: 12px; text-align: center; font-weight: 600; font-size: 16px;">
                                <?= (int)$member['total_points'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="dash-grid">
        <!-- Deelnemers -->
        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Deelnemers</h2>
                    <p class="card-subtitle"><?= count($members) ?> LEDEN</p>
                </div>
            </div>

            <div class="member-list">
                <?php foreach ($members as $member): ?>
                    <div class="member">
                        <div class="member-avatar">
                            <?= strtoupper(substr(htmlspecialchars($member['name']), 0, 1)) ?>
                        </div>
                        <div class="member-info">
                            <div class="member-name"><?= htmlspecialchars($member['name']) ?></div>
                            <div class="member-email"><?= htmlspecialchars($member['email']) ?></div>
                        </div>
                        <?php if ((int)$member['id'] === (int)$pool['created_by']): ?>
                            <span class="member-badge">Beheerder</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Sidebar -->
        <aside class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Acties</h2>
                    <p class="card-subtitle">BEHEER</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="predictions.php" class="btn btn-primary btn-block">⚽ Voorspellen</a>
                <div style="padding: 16px; background: var(--bg-deep); border: 1px dashed var(--border-hi); border-radius: var(--radius-sm);">
                    <div style="font-family: var(--font-mono); font-size: 11px; color: var(--text-mute); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px;">
                        Deel deze code met vrienden
                    </div>
                    <div style="font-family: var(--font-display); font-size: 24px; color: var(--field); letter-spacing: 0.1em;">
                        <?= htmlspecialchars($pool['access_code']) ?>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Voorspellingen van leden -->
    <section class="card" style="margin-top: 32px;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Voorspellingen van leden</h2>
                <p class="card-subtitle"><?= count($matches) ?> WEDSTRIJDEN</p>
            </div>
        </div>

        <?php if (empty($matches)): ?>
            <div style="padding: 32px; text-align: center; color: var(--text-dim);">
                <p>Nog geen wedstrijden beschikbaar.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-lo);">
                            <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-mute);">Wedstrijd</th>
                            <?php foreach ($members as $member): ?>
                                <th style="padding: 12px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-mute); min-width: 80px;">
                                    <?= htmlspecialchars(substr($member['name'], 0, 8)) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): 
                            $match_id = (int)$match['id'];
                            $date = new DateTime($match['match_date']);
                        ?>
                            <tr style="border-bottom: 1px solid var(--border-lo);">
                                <td style="padding: 12px; text-align: left;">
                                    <div style="font-weight: 600; font-size: 14px;">
                                        <?= htmlspecialchars($match['home_team']) ?> – <?= htmlspecialchars($match['away_team']) ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-dim); margin-top: 4px;">
                                        <?= $date->format('d M Y · H:i') ?>
                                    </div>
                                </td>
                                <?php foreach ($members as $member): 
                                    $user_id = (int)$member['id'];
                                    $prediction = $predictions[$match_id][$user_id] ?? null;
                                ?>
                                    <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 500;">
                                        <?php if ($prediction && $prediction['predicted_home'] !== null): ?>
                                            <span><?= (int)$prediction['predicted_home'] ?> – <?= (int)$prediction['predicted_away'] ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--text-mute); font-weight: 400;">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
