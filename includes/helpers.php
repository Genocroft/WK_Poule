<?php
// ============================================
// Helper functies
// ============================================

/**
 * Bereken punten op basis van voorspelling en werkelijke uitslag.
 * 
 * @param int $predicted_home Voorspelde score thuis
 * @param int $predicted_away Voorspelde score uit
 * @param int $actual_home Werkelijke score thuis
 * @param int $actual_away Werkelijke score uit
 * @return int Aantal punten
 */
function calculatePoints(int $predicted_home, int $predicted_away, int $actual_home, int $actual_away): int {
    // Exacte uitslag geraden: 3 punten
    if ($predicted_home === $actual_home && $predicted_away === $actual_away) {
        return 3;
    }

    // Bereken winnaar van voorspelling en werkelijkheid
    $predicted_result = $predicted_home <=> $predicted_away;
    $actual_result = $actual_home <=> $actual_away;

    // Goede winnaar + juist doelsaldo: 2 punten
    if ($predicted_result === $actual_result && $predicted_result !== 0) {
        // Winnaar is hetzelfde, nu controleren doelsaldo
        $predicted_diff = abs($predicted_home - $predicted_away);
        $actual_diff = abs($actual_home - $actual_away);
        
        if ($predicted_diff === $actual_diff) {
            return 2;
        }
    }

    // Alleen goede winnaar of gelijkspel: 1 punt
    if ($predicted_result === $actual_result) {
        return 1;
    }

    // Geen punten
    return 0;
}
