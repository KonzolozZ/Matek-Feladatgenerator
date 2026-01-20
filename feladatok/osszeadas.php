<?php
/*
 * Fájl: feladatok/osszeadas.php
 * Funkció: Több tényezős összeadás feladatok generálása és HTML kimenet előállítása.
 * Módosítás: Szuper könnyű mód (helyiérték bontás) implementálása.
 */

// --- BEMENETEK ---
// Egyedi beállítás: Tényezők száma
$tenyezok_szama = 3;
if (isset($_POST['tenyezok']) && (int)$_POST['tenyezok'] >= 2 && (int)$_POST['tenyezok'] <= 6) {
    $tenyezok_szama = (int)$_POST['tenyezok'];
}

// --- HTML BEÁLLÍTÁSOK GENERÁLÁSA ---
// Ezt a változót fogja kiírni az index.php a formban
$egyedi_beallitasok_html = '
<div class="col-md-6 col-lg-2">
    <label for="tenyezok" class="form-label fw-bold text-secondary">Tagok száma</label>
    <input type="number" class="form-control" id="tenyezok" name="tenyezok" 
           value="' . $tenyezok_szama . '" min="2" max="6">
</div>
';

// --- FELADATOK GENERÁLÁSA ---
// Csak akkor futtatjuk, ha POST kérés történt (vagy alapból, ha szeretnénk, hogy mindig legyen feladat)
$feladatok_per_oldal = 24;
$osszes_feladat_szama = $feladatok_per_oldal * $oldalak_szama;

// Itt generáljuk le a feladatokat oldalakra bontva
for ($p = 0; $p < $oldalak_szama; $p++) {
    
    // Kezdődik az oldal HTML felépítése
    $html = '<div class="row">';
    
    // Generálunk 24 feladatot erre az oldalra
    $oldal_feladatai = [];
    while (count($oldal_feladatai) < $feladatok_per_oldal) {
        $aktualis_tenyezok = [];
        $aktualis_osszeg = 0;
        
        // --- LOGIKA ELÁGAZÁS: SZUPER KÖNNYŰ VAGY NORMÁL ---
        if ($szuper_konnyu) {
            // Szuper könnyű: Egy számot generálunk, és felbontjuk helyiértékekre (pl. 123 -> 100 + 20 + 3)
            $random_num = random_int(10, $szamkor_hatar);
            
            $szazas = floor($random_num / 100) * 100;
            $tizes = floor(($random_num % 100) / 10) * 10;
            $egyes = $random_num % 10;
            
            // Csak a nem nulla helyiértékeket adjuk hozzá (pl. 105 -> 100 + 5)
            if ($szazas > 0) $aktualis_tenyezok[] = $szazas;
            if ($tizes > 0) $aktualis_tenyezok[] = $tizes;
            if ($egyes > 0) $aktualis_tenyezok[] = $egyes;
            
            // Ha véletlenül 0 lenne (bár a random_int 10-től indul), újra generáljuk
            if (empty($aktualis_tenyezok)) continue;

            $aktualis_osszeg = $random_num;

            // A tényezők száma itt dinamikus, attól függ, hány nem nulla számjegy volt
            $temp_tenyezok_szama = count($aktualis_tenyezok);

        } else {
            // Normál mód: Véletlenszerű tagok generálása
            $temp_tenyezok_szama = $tenyezok_szama;

            for ($i = 0; $i < $temp_tenyezok_szama; $i++) {
                $maradek_hely = $szamkor_hatar - $aktualis_osszeg;
                if ($i === $temp_tenyezok_szama - 1) {
                    $max = $maradek_hely;
                    $min = 1;
                } else {
                    $max = floor($maradek_hely / 1.5); 
                    if ($max < 2) $max = 2;
                    $min = 1;
                }
                if ($min > $max) $max = $min;
                $szam = random_int($min, $max);
                $aktualis_tenyezok[] = $szam;
                $aktualis_osszeg += $szam;
            }
        }

        // Feladat rögzítése (csak ha érvényes)
        if ($aktualis_osszeg <= $szamkor_hatar && ($aktualis_osszeg > ($szamkor_hatar / 5) || $szuper_konnyu)) {
            $hianyzo_index = -1;
            
            // Nehezebb módban valamelyik tag hiányzik.
            // Szuper könnyűnél is lehet nehezített, ekkor a helyiérték bontás egyik tagja hiányzik.
            if ($nehezebb) {
                // Szuper könnyű módban a tényezők száma változó
                $max_idx = count($aktualis_tenyezok) - 1;
                $hianyzo_index = random_int(0, $max_idx);
            }
            
            $oldal_feladatai[] = [
                'tenyezok' => $aktualis_tenyezok,
                'osszeg' => $aktualis_osszeg,
                'hianyzo_index' => $hianyzo_index
            ];
        }
    }

    // HTML renderelés: 2 hasáb
    $half = ceil(count($oldal_feladatai) / 2);
    $columns = array_chunk($oldal_feladatai, $half);

    foreach ($columns as $colIndex => $columnTasks) {
        $borderClass = ($colIndex === 0) ? 'border-end pe-4' : 'ps-4';
        $html .= '<div class="col-6 ' . $borderClass . '">';
        
        foreach ($columnTasks as $f) {
            $html .= '<div class="problem-row d-flex align-items-center justify-content-center">';
            $html .= '<div class="problem">';
            
            $utolso_index = count($f['tenyezok']) - 1;
            foreach ($f['tenyezok'] as $idx => $szam) {
                if ($idx === $f['hianyzo_index']) {
                    // Hiányzó tag: input mező, benne a helyes válasz data-correct-ben
                    $html .= '<input type="number" class="answer-box" data-correct="' . $szam . '">';
                } else {
                    $html .= '<span>' . $szam . '</span>';
                }

                if ($idx < $utolso_index) $html .= ' + ';
            }
            
            $html .= ' = ';

            if ($f['hianyzo_index'] === -1) {
                // Eredmény hiányzik
                $html .= '<input type="number" class="answer-box" data-correct="' . $f['osszeg'] . '">';
            } else {
                // Eredmény látszik
                $html .= '<span class="fw-bold text-primary">' . $f['osszeg'] . '</span>';
            }
            
            $html .= '</div></div>';
        }
        $html .= '</div>'; // col-6 vége
    }

    $html .= '</div>'; // row vége

    // Hozzáadjuk a kész HTML-t a fő tömbhöz
    $feladat_oldalak[] = $html;
}

/* Utolsó módosítás: 2026. január 10. 18:39:00 */