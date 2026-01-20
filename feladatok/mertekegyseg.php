<?php
/*
 * Fájl: feladatok/mertekegyseg.php
 * Funkció: Mértékegység átváltás, összehasonlítás és műveletek gyakorlása.
 * Típusok: Hosszúság, Űrtartalom, Tömeg.
 * Feladattípusok: Relációk (<, >, =) és Műveletek (+, -).
 * Bővítve: Szuper könnyű mód és Javított Nehezített mód (Számkörhöz és ésszerűséghez igazítva).
 */

// --- KONFIGURÁCIÓ ÉS BEMENETEK ---

// Mértékegység típusok és egységeik (Váltószám az alapegységhez)
$mertekegyseg_adatok = [
    'hossz' => [
        'nev' => 'Hosszúság',
        'egysegek' => [
            'mm' => 1,
            'cm' => 10,
            'dm' => 100,
            'm'  => 1000,
            'km' => 1000000
        ]
    ],
    'urtartalom' => [
        'nev' => 'Űrtartalom',
        'egysegek' => [
            'ml' => 1,
            'cl' => 10,
            'dl' => 100,
            'l'  => 1000,
            'hl' => 100000
        ]
    ],
    'tomeg' => [
        'nev' => 'Tömeg',
        'egysegek' => [
            'mg'  => 1,
            'g'   => 1000,
            'dkg' => 10000,
            'kg'  => 1000000,
            'q'   => 100000000,
            't'   => 1000000000
        ]
    ]
];

// Feladattípusok
$feladat_tipusok = [
    'vegyes' => 'Vegyes feladatok',
    'osszehasonlitas' => 'Összehasonlítás (<, >, =)',
    'muvelet' => 'Műveletek (+, -)'
];

// Bemenetek kezelése
$mertek_tipus = isset($_POST['mertek_tipus']) && array_key_exists($_POST['mertek_tipus'], $mertekegyseg_adatok) ? $_POST['mertek_tipus'] : 'hossz';
$feladat_mod = isset($_POST['feladat_mod']) && array_key_exists($_POST['feladat_mod'], $feladat_tipusok) ? $_POST['feladat_mod'] : 'vegyes';

// KIVÁLASZTOTT EGYSÉGEK KEZELÉSE (CHECKBOXOK)
$kivalasztott_egysegek = [];
if (isset($_POST['egysegek']) && is_array($_POST['egysegek'])) {
    foreach ($_POST['egysegek'] as $egyseg) {
        if (array_key_exists($egyseg, $mertekegyseg_adatok[$mertek_tipus]['egysegek'])) {
            $kivalasztott_egysegek[] = $egyseg;
        }
    }
}
if (empty($kivalasztott_egysegek)) {
    $kivalasztott_egysegek = array_keys($mertekegyseg_adatok[$mertek_tipus]['egysegek']);
}

// Szűkített egység lista a generáláshoz
$aktiv_egysegek = [];
foreach ($kivalasztott_egysegek as $egyseg) {
    $aktiv_egysegek[$egyseg] = $mertekegyseg_adatok[$mertek_tipus]['egysegek'][$egyseg];
}

// --- HTML BEÁLLÍTÁSOK GENERÁLÁSA ---
$js_kod = "
<script>
function frissitEgysegCheckboxokat() {
    var tipus = document.getElementById('mertek_tipus').value;
    var kontener = document.getElementById('egyseg_checkbox_kontener');
    var osszes = kontener.children;
    for (var i = 0; i < osszes.length; i++) {
        if (osszes[i].dataset.tipus === tipus) {
            osszes[i].style.display = 'inline-block';
        } else {
            osszes[i].style.display = 'none';
        }
    }
}
document.addEventListener('DOMContentLoaded', frissitEgysegCheckboxokat);
</script>
";

$egyedi_beallitasok_html = $js_kod;

$egyedi_beallitasok_html .= '
<div class="col-md-6 col-lg-3">
    <label for="mertek_tipus" class="form-label fw-bold text-secondary">Mértékegység típus</label>
    <select class="form-select" id="mertek_tipus" name="mertek_tipus" onchange="frissitEgysegCheckboxokat()">';
foreach ($mertekegyseg_adatok as $kulcs => $adat) {
    $selected = ($mertek_tipus === $kulcs) ? 'selected' : '';
    $egyedi_beallitasok_html .= "<option value=\"$kulcs\" $selected>{$adat['nev']}</option>";
}
$egyedi_beallitasok_html .= '
    </select>
</div>

<div class="col-md-6 col-lg-3">
    <label for="feladat_mod" class="form-label fw-bold text-secondary">Feladat típus</label>
    <select class="form-select" id="feladat_mod" name="feladat_mod">';
foreach ($feladat_tipusok as $kulcs => $nev) {
    $selected = ($feladat_mod === $kulcs) ? 'selected' : '';
    $egyedi_beallitasok_html .= "<option value=\"$kulcs\" $selected>$nev</option>";
}
$egyedi_beallitasok_html .= '
    </select>
</div>

<div class="col-12 mt-2" id="egyseg_checkbox_kontener">
    <label class="form-label fw-bold text-secondary d-block mb-1 small">Használható mértékegységek:</label>';

foreach ($mertekegyseg_adatok as $t_kulcs => $t_adat) {
    foreach ($t_adat['egysegek'] as $egyseg => $valto) {
        $checked = '';
        if ($t_kulcs === $mertek_tipus && in_array($egyseg, $kivalasztott_egysegek)) {
            $checked = 'checked';
        }
        $style = ($t_kulcs === $mertek_tipus) ? 'inline-block' : 'none';
        
        $egyedi_beallitasok_html .= "
        <div class=\"form-check form-check-inline me-3\" data-tipus=\"$t_kulcs\" style=\"display:$style\">
            <input class=\"form-check-input\" type=\"checkbox\" name=\"egysegek[]\" value=\"$egyseg\" id=\"check_$egyseg\" $checked>
            <label class=\"form-check-label\" for=\"check_$egyseg\">$egyseg</label>
        </div>";
    }
}
$egyedi_beallitasok_html .= '</div>';

// --- SEGÉDFÜGGVÉNYEK ---

/**
 * Generál egy értéket úgy, hogy a LÁTHATÓ szám ne haladja meg a $max_ertek-et.
 */
function general_elem($egysegek, $max_ertek, $fix_egyseg = null) {
    if ($fix_egyseg) {
        $egyseg = $fix_egyseg;
        $valto = $egysegek[$egyseg];
    } else {
        $kulcsok = array_keys($egysegek);
        $egyseg = $kulcsok[array_rand($kulcsok)];
        $valto = $egysegek[$egyseg];
    }

    if ($max_ertek < 1) $max_ertek = 1;
    
    // Véletlen szám generálása (ez maga a számjegy, pl. 100 [ml])
    $ertek = random_int(1, (int)$max_ertek);
    
    return [
        'ertek' => $ertek,
        'egyseg' => $egyseg,
        'alap' => $ertek * $valto // Átváltva alapegységre a számításokhoz
    ];
}

/**
 * Szűri a használható egységeket egy adott bázis egységhez (pl. 'ml') képest.
 * Csak azokat adja vissza, amik nem "túl távoliak" (pl. nem keverjük a ml-t a hl-lel).
 * @param array $osszes_egyseg Az összes elérhető egység tömbje
 * @param string $bazis_egyseg A referencia egység kulcsa (pl. 'ml')
 * @return array Szűrt egység tömb
 */
function szur_kompatibilis_egysegek($osszes_egyseg, $bazis_egyseg) {
    $kulcsok = array_keys($osszes_egyseg);
    $bazis_index = array_search($bazis_egyseg, $kulcsok);
    
    // Csak a közvetlen szomszédokat (kisebb/nagyobb) engedjük, vagy max 2 lépést.
    // Így elkerülhető a ml <-> hl keveredés.
    $szurt = [];
    foreach ($kulcsok as $index => $kulcs) {
        if (abs($index - $bazis_index) <= 2) {
            $szurt[$kulcs] = $osszes_egyseg[$kulcs];
        }
    }
    return $szurt;
}

// --- FELADATOK GENERÁLÁSA ---
$feladatok_per_oldal = 24;

for ($p = 0; $p < $oldalak_szama; $p++) {
    
    $html = '<div class="row">';
    
    $oldal_feladatai = [];
    $probalkozasok = 0;
    $max_probalkozas = 3000;

    while (count($oldal_feladatai) < $feladatok_per_oldal && $probalkozasok < $max_probalkozas) {
        $probalkozasok++;
        
        $aktualis_tipus = $feladat_mod;
        if ($feladat_mod === 'vegyes') {
            $aktualis_tipus = (random_int(0, 1) === 0) ? 'osszehasonlitas' : 'muvelet';
        }

        // ----------------------------------------------------------------
        // 1. SZUPER KÖNNYŰ MÓD
        // ----------------------------------------------------------------
        if ($szuper_konnyu) {
            $kulcsok = array_keys($aktiv_egysegek);
            $fix_egyseg = $kulcsok[array_rand($kulcsok)];
            
            if ($aktualis_tipus === 'osszehasonlitas') {
                $A = random_int(1, $szamkor_hatar);
                $B = random_int(1, $szamkor_hatar);
                
                $rel = '=';
                if ($A < $B) $rel = '<';
                if ($A > $B) $rel = '>';
                
                $oldal_feladatai[] = [
                    'mode' => 'se_relacio',
                    'A' => $A, 'B' => $B, 'egyseg' => $fix_egyseg, 'sol' => $rel
                ];

            } else {
                $op = (random_int(0, 1) === 0) ? '+' : '-';
                $A = random_int(1, $szamkor_hatar);
                $B = random_int(1, $szamkor_hatar);
                
                if ($op === '-') {
                    if ($A < $B) { $t = $A; $A = $B; $B = $t; }
                    $res = $A - $B;
                } else {
                    if ($A + $B > $szamkor_hatar) {
                         $B = $szamkor_hatar - $A;
                         if ($B < 1) $B = 1; 
                    }
                    $res = $A + $B;
                }
                
                $oldal_feladatai[] = [
                    'mode' => 'se_muvelet',
                    'A' => $A, 'B' => $B, 'op' => $op, 'egyseg' => $fix_egyseg, 'sol' => $res
                ];
            }
        }
        // ----------------------------------------------------------------
        // 2. NEHEZÍTETT MÓD
        // ----------------------------------------------------------------
        elseif ($nehezebb) {
            // Láncolt műveletek, de OKOSABBAN
            
            $tenyezo_szam_bal = random_int(3, 4);
            $elemek_bal = [];
            $aktualis_osszeg_alap = 0;
            $sikerult_bal = true;
            
            // Kiválasztunk egy "vezér" mértékegységet a feladathoz.
            // Minden elem ehhez lesz közeli, hogy elkerüljük az extrém váltásokat (pl. ml vs hl).
            $vezer_kulcsok = array_keys($aktiv_egysegek);
            $vezer_egyseg = $vezer_kulcsok[array_rand($vezer_kulcsok)];
            
            // Szűrjük az elérhető egységeket a vezér köré
            $kompatibilis_egysegek = szur_kompatibilis_egysegek($aktiv_egysegek, $vezer_egyseg);

            // Az első tag generálása (lehet nagyobb, a számkör határig)
            $elso_elem = general_elem($kompatibilis_egysegek, $szamkor_hatar);
            $elemek_bal[] = ['op' => '+', 'adat' => $elso_elem];
            $aktualis_osszeg_alap = $elso_elem['alap'];

            for($i=1; $i<$tenyezo_szam_bal; $i++) {
                $op = (random_int(0,1)===0)?'+':'-';
                
                // A többi tag legyen kisebb, hogy ne szálljon el az összeg.
                // Pl. max a számkör negyede.
                $limit = floor($szamkor_hatar / 4);
                if ($limit < 1) $limit = 1;

                $uj_elem = general_elem($kompatibilis_egysegek, $limit);
                
                // Ellenőrzések
                $temp_osszeg = $aktualis_osszeg_alap;
                if ($op === '+') $temp_osszeg += $uj_elem['alap'];
                else $temp_osszeg -= $uj_elem['alap'];

                // 1. Ne legyen negatív
                if ($temp_osszeg < 0) {
                    $op = '+';
                    $temp_osszeg = $aktualis_osszeg_alap + $uj_elem['alap'];
                }
                
                // 2. Ne legyen túl nagy az EREDMÉNY ALAPEGYSÉGBEN
                // Ez a kritikus pont. Ha az alapegységben vett érték (pl. ml) sokkal nagyobb,
                // mint amit a gyerek fel tud fogni (pl. a számkör 1000, de az eredmény 50000 ml = 50 l),
                // akkor baj van.
                // Korlát: Az összeg (alapegységben) ne haladja meg a (számkör * vezér_egyseg_valto)-t.
                // Így ha a vezér a liter (1000), és a számkör 100, akkor max 100 liter (100.000 ml) lehet.
                $limit_alap = $szamkor_hatar * $aktiv_egysegek[$vezer_egyseg] * 1.2; // kis ráhagyás
                
                if ($temp_osszeg > $limit_alap) {
                    $sikerult_bal = false; break;
                }

                $aktualis_osszeg_alap = $temp_osszeg;
                $elemek_bal[] = ['op' => $op, 'adat' => $uj_elem];
            }
            
            if (!$sikerult_bal) continue;

            if ($aktualis_tipus === 'osszehasonlitas') {
                // Jobb oldal generálása (egyetlen érték, ami hasonló méretű)
                $cel_alap = $aktualis_osszeg_alap;
                $modosito = random_int(-20, 20); // +/- 20%
                if ($modosito == 0) $modosito = 10;
                
                $B_alap = $cel_alap * (1 + ($modosito / 100));
                if ($B_alap < 1) $B_alap = 1;
                
                // Próbáljuk meg ezt az értéket kifejezni valamilyen kompatibilis mértékegységben
                $jo_egysegek = [];
                foreach ($kompatibilis_egysegek as $egyseg => $valto) {
                    $ertek = $B_alap / $valto;
                    // Ésszerű méretű szám legyen
                    if ($ertek >= 1 && $ertek <= $szamkor_hatar * 1.5) {
                        $jo_egysegek[] = $egyseg;
                    }
                }
                
                if (empty($jo_egysegek)) continue;
                
                $valasztott_egyseg = $jo_egysegek[array_rand($jo_egysegek)];
                $valto = $aktiv_egysegek[$valasztott_egyseg];
                
                $B_ertek = round($B_alap / $valto);
                $B_alap_vegleges = $B_ertek * $valto;
                
                $elemek_jobb = [['op' => '+', 'adat' => ['ertek' => $B_ertek, 'egyseg' => $valasztott_egyseg, 'alap' => $B_alap_vegleges]]];
                
                $rel = '=';
                if ($aktualis_osszeg_alap < $B_alap_vegleges) $rel = '<';
                if ($aktualis_osszeg_alap > $B_alap_vegleges) $rel = '>';

                $oldal_feladatai[] = [
                    'mode' => 'hard_relacio',
                    'bal' => $elemek_bal,
                    'jobb' => $elemek_jobb,
                    'sol' => $rel
                ];

            } else {
                // Művelet: [Eredmény] kiszámítása
                // Olyan cél egységet keresünk, ami a KOMPATIBILIS listában van, és egész számot ad
                $lehetseges_celok = [];
                foreach($kompatibilis_egysegek as $e => $v) {
                    if ($aktualis_osszeg_alap % $v === 0) {
                        $ertek = $aktualis_osszeg_alap / $v;
                        if ($ertek <= $szamkor_hatar * 1.5) {
                            $lehetseges_celok[] = $e;
                        }
                    }
                }
                
                if (empty($lehetseges_celok)) continue;

                $cel_egyseg = $lehetseges_celok[array_rand($lehetseges_celok)];
                $cel_valto = $aktiv_egysegek[$cel_egyseg];
                $eredmeny_szam = $aktualis_osszeg_alap / $cel_valto;
                
                $oldal_feladatai[] = [
                    'mode' => 'hard_muvelet',
                    'bal' => $elemek_bal,
                    'cel_egyseg' => $cel_egyseg,
                    'sol' => $eredmeny_szam
                ];
            }

        }
        // ----------------------------------------------------------------
        // 3. NORMÁL MÓD (2 Tényezős, vegyes)
        // ----------------------------------------------------------------
        else {
            // Itt is alkalmazzuk a kompatibilitás szűrést
            $vezer_kulcsok = array_keys($aktiv_egysegek);
            $vezer_egyseg = $vezer_kulcsok[array_rand($vezer_kulcsok)];
            $kompatibilis = szur_kompatibilis_egysegek($aktiv_egysegek, $vezer_egyseg);

            $A = general_elem($kompatibilis, $szamkor_hatar);
            $B = general_elem($kompatibilis, $szamkor_hatar);
            
            // Ha a generált számjegy nagyobb, mint a számkör, dobjuk
            if ($A['ertek'] > $szamkor_hatar || $B['ertek'] > $szamkor_hatar) continue;

            if ($aktualis_tipus === 'osszehasonlitas') {
                $rel = '=';
                if ($A['alap'] < $B['alap']) $rel = '<';
                if ($A['alap'] > $B['alap']) $rel = '>';
                
                $oldal_feladatai[] = [
                    'mode' => 'norm_relacio',
                    'A' => $A, 'B' => $B, 'sol' => $rel
                ];
            } else {
                // Művelet
                $op = (random_int(0, 1) === 0) ? '+' : '-';
                if ($op === '+') {
                    $res_alap = $A['alap'] + $B['alap'];
                } else {
                    if ($A['alap'] < $B['alap']) { $t=$A; $A=$B; $B=$t; }
                    $res_alap = $A['alap'] - $B['alap'];
                }

                // Cél egység keresése (csak a kompatibilisek közül!)
                $lehetseges = [];
                foreach($kompatibilis as $e => $v) {
                    if ($res_alap % $v === 0) {
                        $val = $res_alap / $v;
                        if ($val <= $szamkor_hatar * 1.5) {
                            $lehetseges[] = $e;
                        }
                    }
                }
                
                if (empty($lehetseges)) continue;

                $cel_egyseg = $lehetseges[array_rand($lehetseges)];
                $res_val = $res_alap / $aktiv_egysegek[$cel_egyseg];

                $oldal_feladatai[] = [
                    'mode' => 'norm_muvelet',
                    'A' => $A, 'B' => $B, 'op' => $op, 'cel_egyseg' => $cel_egyseg, 'sol' => $res_val
                ];
            }
        }
    }

    // HTML Renderelés
    $half = ceil(count($oldal_feladatai) / 2);
    $columns = array_chunk($oldal_feladatai, $half);

    foreach ($columns as $colIndex => $columnTasks) {
        $borderClass = ($colIndex === 0) ? 'border-end pe-4' : 'ps-4';
        $html .= '<div class="col-6 ' . $borderClass . '">';
        
        foreach ($columnTasks as $f) {
            $html .= '<div class="problem-row d-flex align-items-center justify-content-center">';
            
            // Kisebb betűméret a nehéz feladatoknak
            $fontSize = (strpos($f['mode'], 'hard') !== false) ? '0.9rem' : '1.1rem';
            $html .= '<div class="problem d-flex align-items-center flex-wrap justify-content-center gap-2" style="font-size: '.$fontSize.';">'; 
            
            // --- SZUPER KÖNNYŰ ---
            if ($f['mode'] === 'se_relacio') {
                $html .= "<span>{$f['A']} {$f['egyseg']}</span>";
                $html .= '<input type="text" class="answer-box mx-2" style="width: 40px;" data-correct="' . $f['sol'] . '">';
                $html .= "<span>{$f['B']} {$f['egyseg']}</span>";
            }
            elseif ($f['mode'] === 'se_muvelet') {
                $html .= "<span>{$f['A']} {$f['egyseg']} {$f['op']} {$f['B']} {$f['egyseg']} = </span>";
                $html .= '<input type="number" class="answer-box mx-2" data-correct="' . $f['sol'] . '">';
                $html .= "<span>{$f['egyseg']}</span>";
            }
            // --- NORMÁL ---
            elseif ($f['mode'] === 'norm_relacio') {
                $html .= "<span>{$f['A']['ertek']} {$f['A']['egyseg']}</span>";
                $html .= '<input type="text" class="answer-box mx-2" style="width: 40px;" data-correct="' . $f['sol'] . '">';
                $html .= "<span>{$f['B']['ertek']} {$f['B']['egyseg']}</span>";
            }
            elseif ($f['mode'] === 'norm_muvelet') {
                $html .= "<span>{$f['A']['ertek']} {$f['A']['egyseg']} {$f['op']} {$f['B']['ertek']} {$f['B']['egyseg']} = </span>";
                $html .= '<input type="number" class="answer-box mx-2" data-correct="' . $f['sol'] . '">';
                $html .= "<span>{$f['cel_egyseg']}</span>";
            }
            // --- NEHEZÍTETT ---
            elseif ($f['mode'] === 'hard_relacio') {
                // Bal oldal
                foreach($f['bal'] as $idx => $elem) {
                    if ($idx > 0) $html .= " {$elem['op']} ";
                    $html .= "{$elem['adat']['ertek']}{$elem['adat']['egyseg']}";
                }
                
                $html .= '<input type="text" class="answer-box mx-2" style="width: 40px;" data-correct="' . $f['sol'] . '">';
                
                // Jobb oldal
                foreach($f['jobb'] as $idx => $elem) {
                    if ($idx > 0) $html .= " {$elem['op']} ";
                    $html .= "{$elem['adat']['ertek']}{$elem['adat']['egyseg']}";
                }
            }
            elseif ($f['mode'] === 'hard_muvelet') {
                foreach($f['bal'] as $idx => $elem) {
                    if ($idx > 0) $html .= " {$elem['op']} ";
                    $html .= "{$elem['adat']['ertek']}{$elem['adat']['egyseg']}";
                }
                $html .= " = ";
                $html .= '<input type="number" class="answer-box mx-2" data-correct="' . $f['sol'] . '">';
                $html .= "<span>{$f['cel_egyseg']}</span>";
            }
            
            $html .= '</div></div>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    $feladat_oldalak[] = $html;
}

/* Utolsó módosítás: 2026. január 20. 09:40:00 */