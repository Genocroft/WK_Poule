<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Uitslagen Invoeren';

// Check admin access (user_id === 1)
requireLogin();
if ($_SESSION['user_id'] !== 1) {
    die('Alleen beheerders hebben toegang tot deze pagina.');
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['match_id'])) {
    $match_id = (int)$_POST['match_id'];
    $home_score = isset($_POST['home_score']) ? (int)$_POST['home_score'] : null;
    $away_score = isset($_POST['away_score']) ? (int)$_POST['away_score'] : null;
    
    if ($home_score !== null && $away_score !== null) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update match scores
            $stmt = $pdo->prepare('UPDATE matches SET home_score = ?, away_score = ? WHERE id = ?');
            $stmt->execute([$home_score, $away_score, $match_id]);
            
            // Get all predictions for this match
            $stmt = $pdo->prepare('SELECT p.id, p.user_id, p.predicted_home, p.predicted_away FROM predictions WHERE match_id = ?');
            $stmt->execute([$match_id]);
            $predictions = $stmt->fetchAll();
            
            // Update points for each prediction
            foreach ($predictions as $prediction) {
                $points = calculatePoints(
                    $prediction['predicted_home'],
                    $prediction['predicted_away'],
                    $home_score,
                    $away_score
                );
                
                $updateStmt = $pdo->prepare('UPDATE predictions SET points = ? WHERE id = ?');
                $updateStmt->execute([$points, $prediction['id']]);
            }
            
            // Commit transaction
            $pdo->commit();
            
            $message = 'Uitslag succesvol opgeslagen! Punten zijn berekend voor ' . count($predictions) . ' voorspellingen.';
            $messageType = 'success';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Fout bij opslaan: ' . htmlspecialchars($e->getMessage());
            $messageType = 'error';
        }
    }
}

// Fetch all matches
$stmt = $pdo->query('SELECT * FROM matches ORDER BY match_date ASC');
$matches = $stmt->fetchAll();

// Separate matches with and without scores
$matchesWithoutScores = [];
$matchesWithScores = [];

foreach ($matches as $match) {
    if ($match['home_score'] === null || $match['away_score'] === null) {
        $matchesWithoutScores[] = $match;
    } else {
        $matchesWithScores[] = $match;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Uitslagen Invoeren</h1>
        <p>Voer hier de officiële uitslagen in. De punten worden automatisch berekend.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Wedstrijden zonder uitslag -->
    <?php if (!empty($matchesWithoutScores)): ?>
        <section class="section">
            <h2>Wedstrijden zonder uitslag (<?= count($matchesWithoutScores) ?>)</h2>
            
            <?php foreach ($matchesWithoutScores as $match): ?>
                <form method="POST" class="match-form">
                    <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                    
                    <div class="form-group">
                        <div class="match-header">
                            <strong><?= htmlspecialchars($match['home_team']) ?></strong>
                            <span class="vs">vs</span>
                            <strong><?= htmlspecialchars($match['away_team']) ?></strong>
                            <span class="stage"><?= htmlspecialchars($match['stage']) ?></span>
                            <span class="match-date"><?= date('d M Y H:i', strtotime($match['match_date'])) ?></span>
                        </div>
                        
                        <div class="score-input-group">
                            <div class="score-input">
                                <label for="home_score_<?= $match['id'] ?>">
                                    <?= htmlspecialchars($match['home_team']) ?> doelpunten
                                </label>
                                <input 
                                    type="number" 
                                    id="home_score_<?= $match['id'] ?>" 
                                    name="home_score" 
                                    min="0" 
                                    max="99" 
                                    required
                                    placeholder="0"
                                >
                            </div>
                            
                            <span class="separator">-</span>
                            
                            <div class="score-input">
                                <label for="away_score_<?= $match['id'] ?>">
                                    <?= htmlspecialchars($match['away_team']) ?> doelpunten
                                </label>
                                <input 
                                    type="number" 
                                    id="away_score_<?= $match['id'] ?>" 
                                    name="away_score" 
                                    min="0" 
                                    max="99" 
                                    required
                                    placeholder="0"
                                >
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Opslaan</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <!-- Wedstrijden met uitslag -->
    <?php if (!empty($matchesWithScores)): ?>
        <section class="section">
            <h2>Wedstrijden met uitslag (<?= count($matchesWithScores) ?>)</h2>
            
            <div class="matches-list">
                <?php foreach ($matchesWithScores as $match): ?>
                    <div class="match-card">
                        <span class="played-label">Gespeeld</span>
                        <div class="match-info">
                            <span class="team-home"><?= htmlspecialchars($match['home_team']) ?></span>
                            <span class="match-score">
                                <?= $match['home_score'] ?> - <?= $match['away_score'] ?>
                            </span>
                            <span class="team-away"><?= htmlspecialchars($match['away_team']) ?></span>
                        </div>
                        <div class="match-meta">
                            <span class="stage"><?= htmlspecialchars($match['stage']) ?></span>
                            <span class="match-date"><?= date('d M Y H:i', strtotime($match['match_date'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<style>
.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.page-header p {
    color: #666;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.section {
    margin-bottom: 3rem;
}

.section h2 {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    color: #333;
}

.match-form {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.match-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 1.1rem;
}

.match-header .vs {
    color: #999;
    font-size: 0.9rem;
}

.stage {
    background: #f0f0f0;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.85rem;
    color: #666;
}

.match-date {
    color: #999;
    font-size: 0.9rem;
}

.score-input-group {
    display: flex;
    align-items: flex-end;
    gap: 1rem;
    justify-content: center;
}

.score-input {
    display: flex;
    flex-direction: column;
}

.score-input label {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: #333;
    font-weight: 500;
}

.score-input input {
    width: 100px;
    padding: 0.75rem;
    font-size: 1.5rem;
    text-align: center;
    border: 2px solid #ddd;
    border-radius: 4px;
    transition: border-color 0.2s;
}

.score-input input:focus {
    outline: none;
    border-color: #4CAF50;
    background-color: #f0f8f0;
}

.separator {
    font-size: 1.5rem;
    color: #999;
    margin-bottom: 0.75rem;
}

.matches-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.match-card {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    position: relative;
}

.played-label {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #4CAF50;
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
}

.match-info {
    display: flex;
    align-items: center;
    justify-content: space-around;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.team-home,
.team-away {
    font-weight: 600;
    color: #333;
    flex: 1;
    text-align: center;
}

.match-score {
    font-size: 1.8rem;
    font-weight: 700;
    color: #4CAF50;
    padding: 0 1rem;
}

.match-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #999;
}
</style>
