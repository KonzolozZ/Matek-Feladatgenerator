<?php
/*
 * Fájl: feladatok/kerekites.php
 * Funkció: Kerekítési feladatok generálása.
 * Három oszlopos elrendezés: [Kerekített] [Eredeti Szám] [Kerekített]
 */

// --- BEMENETEK ---
// Alapértelmezett kerekítési értékek
$kerekites_bal = 10;
$kerekites_jobb = 100;

if (isset($_POST['kerekites_bal']) && in_array((int)$_POST['kerekites_bal'], [10, 100, 1000])) {
    $kerekites_bal = (int)$_POST['kerekites_bal'];
}

if (isset($_POST['kerekites_jobb']) && in_array((int)$_POST['kerekites_jobb'], [10, 100, 1000])) {
    $kerekites_jobb = (int)$_POST['kerekites_jobb'];
}

// --- HTML BEÁLLÍTÁSOK GENERÁLÁSA ---
// Két lenyíló lista a kerekítés mértékének kiválasztásához
$egyedi_beallitasok_html = '
<div class="col-md-6 col-lg-2">
    <label for="kerekites_bal" class="form-label fw-bold text-secondary">Bal kerekítés</label>
    <select class="form-select" id="kerekites_bal" name="kerekites_bal">
        <option value="10" ' . ($kerekites_bal == 10 ? 'selected' : '') . '>10-es</option>
        <option value="100" ' . ($kerekites_bal == 100 ? 'selected' : '') . '>100-as</option>
        <option value="1000" ' . ($kerekites_bal == 1000 ? 'selected' : '') . '>1000-es</option>
    </select>
</div>
<div class="col-md-6 col-lg-2">
    <label for="kerekites_jobb" class="form-label fw-bold text-secondary">Jobb kerekítés</label>
    <select class="form-select" id="kerekites_jobb" name="kerekites_jobb">
        <option value="10" ' . ($kerekites_jobb == 10 ? 'selected' : '') . '>10-es</option>
        <option value="100" ' . ($kerekites_jobb == 100 ? 'selected' : '') . '>100-as</option>
        <option value="1000" ' . ($kerekites_jobb == 1000 ? 'selected' : '') . '>1000-es</option>
    </select>
</div>
';

// --- FELADATOK GENERÁLÁSA ---
$feladatok_per_oldal = 24;

for ($p = 0; $p < $oldalak_szama; $p++) {
    
    $html = '';

    // CSS beszúrása a placeholderek elrejtésére nyomtatáskor
    // Csak az első oldalhoz fűzzük hozzá, hogy ne ismétlődjön feleslegesen minden oldalon
    if ($p === 0) {
        $html .= '
        <style>
            @media print {
                .answer-box::placeholder {
                    color: transparent !important;
                    opacity: 0 !important;
                }
                /* Webkit browserek (Chrome, Safari) */
                .answer-box::-webkit-input-placeholder {
                    color: transparent !important;
                    opacity: 0 !important;
                }
                /* Mozilla Firefox */
                .answer-box::-moz-placeholder {
                    color: transparent !important;
                    opacity: 0 !important;
                }
            }
        </style>';
    }
    
    $html .= '<div class="row">';
    
    // Generálunk 24 feladatot erre az oldalra
    $oldal_feladatai = [];
    while (count($oldal_feladatai) < $feladatok_per_oldal) {
        
        // Szám generálása a számkörön belül
        // Biztosítjuk, hogy legyen értelme kerekíteni (pl. ne legyen 0 vagy túl kicsi)
        $szam = random_int(5, $szamkor_hatar);
        
        // Kerekítések kiszámítása
        // PHP round() standard kerekítést használ
        $bal_ertek = round($szam / $kerekites_bal) * $kerekites_bal;
        $jobb_ertek = round($szam / $kerekites_jobb) * $kerekites_jobb;

        $oldal_feladatai[] = [
            'szam' => $szam,
            'bal_ertek' => $bal_ertek,
            'jobb_ertek' => $jobb_ertek
        ];
    }

    // HTML renderelés: 2 hasáb
    $half = ceil(count($oldal_feladatai) / 2);
    $columns = array_chunk($oldal_feladatai, $half);

    foreach ($columns as $colIndex => $columnTasks) {
        $borderClass = ($colIndex === 0) ? 'border-end pe-4' : 'ps-4';
        $html .= '<div class="col-6 ' . $borderClass . '">';
        
        foreach ($columnTasks as $f) {
            $html .= '<div class="problem-row d-flex align-items-center justify-content-center">';
            $html .= '<div class="problem d-flex align-items-center gap-3">';
            
            // 1. Oszlop: Bal kerekítés (Input)
            // Helykitöltő (placeholder) mutatja a kerekítés mértékét
            $html .= '<div class="text-center">';
            $html .= '<input type="number" class="answer-box" data-correct="' . $f['bal_ertek'] . '" placeholder="≈' . $kerekites_bal . '">';
            $html .= '</div>';

            // 2. Oszlop: A szám (Középen, nyíllal vagy csak térközzel)
            // Itt most egyszerűen a számot jelenítjük meg középen
            $html .= '<div class="fw-bold fs-4 text-primary text-center" style="min-width: 60px;">';
            $html .= $f['szam'];
            $html .= '</div>';

            // 3. Oszlop: Jobb kerekítés (Input)
            $html .= '<div class="text-center">';
            $html .= '<input type="number" class="answer-box" data-correct="' . $f['jobb_ertek'] . '" placeholder="≈' . $kerekites_jobb . '">';
            $html .= '</div>';
            
            $html .= '</div></div>';
        }
        $html .= '</div>'; // col-6 vége
    }

    $html .= '</div>'; // row vége

    // Hozzáadjuk a kész HTML-t a fő tömbhöz
    $feladat_oldalak[] = $html;
}

/* Utolsó módosítás: 2026. január 10. 22:15:00 */