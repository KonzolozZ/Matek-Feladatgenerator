<?php
/*
 * Fájl: feladatok/osztas.php
 * Funkció: Maradékos osztás feladatok generálása és HTML kimenet előállítása.
 */

// --- HTML BEÁLLÍTÁSOK GENERÁLÁSA ---
// Nincs extra input, ezért üres div, vagy egyszerű üzenet
$egyedi_beallitasok_html = '';

// --- FELADATOK GENERÁLÁSA ---
$feladatok_per_oldal = 24; // Visszaállítva 24-re az optimális kitöltéshez

for ($p = 0; $p < $oldalak_szama; $p++) {
    
    $html = '<div class="row">';
    
    // Generálás
    $oldal_feladatai = [];
    while (count($oldal_feladatai) < $feladatok_per_oldal) {
        $B = random_int(2, 10); // Osztó
        $A = random_int(3, $szamkor_hatar); // Osztandó

        if ($A % $B !== 0 && $A > $B) {
            $E = floor($A / $B);
            $M = $A % $B;
            $oldal_feladatai[] = ['A' => $A, 'B' => $B, 'E' => $E, 'M' => $M];
        }
    }

    // Renderelés
    $html .= '<div class="col-12">';
    
    foreach ($oldal_feladatai as $f) {
        $html .= '<div class="row problem-row align-items-center">';
        
        // 1. Oszlop: Osztás (Középre igazítva)
        $html .= '<div class="col-6 border-end pe-4"><div class="problem d-flex justify-content-center align-items-center">';
        $html .= '<span>' . $f['A'] . ' : ' . $f['B'] . ' = </span>';
        $html .= '<input type="number" class="answer-box me-2" data-correct="' . $f['E'] . '">';
        $html .= '<span class="small text-muted">mar.</span> ';
        $html .= '<input type="number" class="answer-box" data-correct="' . $f['M'] . '">';
        $html .= '</div></div>';

        // 2. Oszlop: Ellenőrzés (Középre igazítva)
        $html .= '<div class="col-6 ps-4"><div class="problem d-flex justify-content-center align-items-center">';
        
        // LOGIKA CSERE: A nehezebb módban nincs segítség
        if ($nehezebb) {
            // Nehezebb: Teljes ellenőrzés beírása (Nincs előre beírt szám)
            $html .= '<input type="number" class="answer-box" data-correct="' . $f['E'] . '">';
            $html .= ' * ' . $f['B'] . ' + ';
            $html .= '<input type="number" class="answer-box" data-correct="' . $f['M'] . '">';
            $html .= ' = ' . $f['A'];
        } else {
            // Könnyebb: Segítségként ott a hányados, csak a maradékot kell beírni
            $html .= '<span class="fw-bold text-primary">' . $f['E'] . '</span>';
            $html .= ' * ' . $f['B'] . ' + ';
            $html .= '<input type="number" class="answer-box" data-correct="' . $f['M'] . '">';
            $html .= ' = ' . $f['A'];
        }
        $html .= '</div></div>'; // Problem vége
        
        $html .= '</div>'; // problem-row vége
    }

    $html .= '</div>'; // col-12 vége
    $html .= '</div>'; // row vége

    $feladat_oldalak[] = $html;
}

/* Utolsó módosítás: 2026. január 10. 22:12:00 */