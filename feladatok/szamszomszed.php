<?php
/*
 * Fájl: feladatok/szamszomszed.php
 * Funkció: Számszomszéd keresési feladatok generálása.
 * Három oszlopos elrendezés: [Alsó Szomszéd] [Szám] [Felső Szomszéd]
 */

// --- BEMENETEK ---
// Alapértelmezett lépték
$szomszed_leptek = 10;

if (isset($_POST['szomszed_leptek']) && in_array((int)$_POST['szomszed_leptek'], [10, 100, 1000])) {
    $szomszed_leptek = (int)$_POST['szomszed_leptek'];
}

// --- HTML BEÁLLÍTÁSOK GENERÁLÁSA ---
$egyedi_beallitasok_html = '
<div class="col-md-6 col-lg-3">
    <label for="szomszed_leptek" class="form-label fw-bold text-secondary">Számszomszéd lépték</label>
    <select class="form-select" id="szomszed_leptek" name="szomszed_leptek">
        <option value="10" ' . ($szomszed_leptek == 10 ? 'selected' : '') . '>10-es</option>
        <option value="100" ' . ($szomszed_leptek == 100 ? 'selected' : '') . '>100-as</option>
        <option value="1000" ' . ($szomszed_leptek == 1000 ? 'selected' : '') . '>1000-es</option>
    </select>
</div>
';

// --- FELADATOK GENERÁLÁSA ---
$feladatok_per_oldal = 24;

for ($p = 0; $p < $oldalak_szama; $p++) {
    
    $html = '';

    // CSS beszúrása a placeholderek elrejtésére nyomtatáskor (Csak az első oldalon)
    if ($p === 0) {
        $html .= '
        <style>
            @media print {
                .answer-box::placeholder {
                    color: transparent !important;
                    opacity: 0 !important;
                }
                .answer-box::-webkit-input-placeholder { color: transparent !important; opacity: 0 !important; }
                .answer-box::-moz-placeholder { color: transparent !important; opacity: 0 !important; }
            }
        </style>';
    }
    
    $html .= '<div class="row">';
    
    // Generálunk 24 feladatot erre az oldalra
    $oldal_feladatai = [];
    while (count($oldal_feladatai) < $feladatok_per_oldal) {
        
        // Szám generálása a számkörön belül
        // Fontos: Olyan számot generáljunk, ami NEM osztható pont a léptékkel,
        // hogy legyenek valódi "kisebb" és "nagyobb" szomszédai.
        
        $min_val = $szomszed_leptek + 1; // Legalább a léptéknél nagyobb legyen
        $max_val = $szamkor_hatar - 1;   // És a határba beleférjen a felső szomszéd is nagyjából
        
        if ($max_val < $min_val) {
             // Ha a számkör túl kicsi a léptékhez, kénytelenek vagyunk kisebb számot adni,
             // vagy korrigálni a logikát. Itt most feltételezzük, hogy a user értelmes számkört ad meg.
             $szam = random_int(1, $szamkor_hatar);
        } else {
             $szam = random_int($min_val, $max_val);
        }
        
        // Ha pont osztható, akkor adjunk hozzá egy kicsit, hogy ne legyen az
        if ($szam % $szomszed_leptek === 0) {
            $szam += random_int(1, $szomszed_leptek - 1);
            // Ha ezzel túlléptük a határt (ritka), vonjunk le inkább
            if ($szam > $szamkor_hatar) {
                $szam -= $szomszed_leptek;
            }
        }

        // Szomszédok kiszámítása
        $also_szomszed = floor($szam / $szomszed_leptek) * $szomszed_leptek;
        $felso_szomszed = ceil($szam / $szomszed_leptek) * $szomszed_leptek;
        
        // Ha véletlenül ugyanaz lenne (mert a szám pont osztható volt, bár fent szűrtük),
        // akkor a felső legyen +lépték. De a ceil/floor logika nem osztható számnál jól működik.
        if ($also_szomszed == $felso_szomszed) {
             $felso_szomszed += $szomszed_leptek;
        }

        $oldal_feladatai[] = [
            'szam' => $szam,
            'also' => $also_szomszed,
            'felso' => $felso_szomszed
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
            $html .= '<div class="problem d-flex align-items-center gap-2">';
            
            // 1. Oszlop: Alsó szomszéd (Input)
            $html .= '<div class="text-center">';
            $html .= '<input type="number" class="answer-box" data-correct="' . $f['also'] . '" placeholder="<">';
            $html .= '</div>';

            // 2. Oszlop: A szám (Középen)
            $html .= '<div class="fw-bold fs-4 text-primary text-center" style="min-width: 70px;">';
            $html .= $f['szam'];
            $html .= '</div>';

            // 3. Oszlop: Felső szomszéd (Input)
            $html .= '<div class="text-center">';
            $html .= '<input type="number" class="answer-box" data-correct="' . $f['felso'] . '" placeholder=">">';
            $html .= '</div>';
            
            $html .= '</div></div>';
        }
        $html .= '</div>'; // col-6 vége
    }

    $html .= '</div>'; // row vége

    $feladat_oldalak[] = $html;
}

/* Utolsó módosítás: 2026. január 10. 22:30:00 */