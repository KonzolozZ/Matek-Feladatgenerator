<?php
/*
 * Fájl: feladatok/szorzas.php
 * Funkció: Szorzás gyakorlása feladatok generálása és HTML kimenet előállítása.
 */

// --- HTML BEÁLLÍTÁSOK ---
$egyedi_beallitasok_html = '';

// --- GENERÁLÁS ---
$feladatok_per_oldal = 24;

for ($p = 0; $p < $oldalak_szama; $p++) {
    
    $html = '<div class="row">';
    
    $oldal_feladatai = [];
    while (count($oldal_feladatai) < $feladatok_per_oldal) {
        // Generálás: A * B = C
        // A és B legyen legalább 2.
        // A szorzat ne lépje túl a számkört.
        
        $A = random_int(2, $szamkor_hatar / 2); // Durva becslés felső határra
        $max_B = floor($szamkor_hatar / $A);
        
        if ($max_B >= 2) {
            $B = random_int(2, $max_B);
            $C = $A * $B;
            
            // Nehezebb mód: 0 = A hiányzik, 1 = B hiányzik, -1 = Eredmény hiányzik
            $hianyzo = -1;
            if ($nehezebb) {
                $hianyzo = random_int(0, 1);
            }

            $oldal_feladatai[] = ['A' => $A, 'B' => $B, 'C' => $C, 'hianyzo' => $hianyzo];
        }
    }

    // Két hasábos megjelenítés
    $half = ceil(count($oldal_feladatai) / 2);
    $columns = array_chunk($oldal_feladatai, $half);

    foreach ($columns as $colIndex => $columnTasks) {
        $borderClass = ($colIndex === 0) ? 'border-end pe-4' : 'ps-4';
        $html .= '<div class="col-6 ' . $borderClass . '">';
        
        foreach ($columnTasks as $f) {
            $html .= '<div class="problem-row d-flex align-items-center justify-content-center">';
            $html .= '<div class="problem">';
            
            // "A" kirajzolása
            if ($f['hianyzo'] === 0) {
                $html .= '<input type="number" class="answer-box" data-correct="' . $f['A'] . '">';
            } else {
                $html .= '<span>' . $f['A'] . '</span>';
            }

            $html .= ' &middot; '; // Szorzás jel

            // "B" kirajzolása
            if ($f['hianyzo'] === 1) {
                $html .= '<input type="number" class="answer-box" data-correct="' . $f['B'] . '">';
            } else {
                $html .= '<span>' . $f['B'] . '</span>';
            }

            $html .= ' = ';

            // Eredmény kirajzolása
            if ($f['hianyzo'] === -1) {
                $html .= '<input type="number" class="answer-box" data-correct="' . $f['C'] . '">';
            } else {
                $html .= '<span class="fw-bold text-primary">' . $f['C'] . '</span>';
            }
            
            $html .= '</div></div>';
        }
        $html .= '</div>';
    }

    $html .= '</div>';
    $feladat_oldalak[] = $html;
}

/* Utolsó módosítás: 2026. január 10. 18:39:00 */