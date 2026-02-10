<?php
/*
 * Fájl: feladatok/alapmuveletek.php
 * Funkció: Összetett alapműveletek generálása (+, -, *, /) zárójellel.
 * Beállítások: Tagok száma (3-6), Műveletek, Zárójelezés, Minden művelet kötelező.
 * Nehezített mód: Hiányzó tag keresése, triviális műveletek szűrése.
 * Könnyített mód: Kisebb számkör.
 */

// --- BEMENETEK ---

// Tagok száma
$am_tagok = 3;
if (isset($_POST['am_tagok']) && (int)$_POST['am_tagok'] >= 3 && (int)$_POST['am_tagok'] <= 6) {
    $am_tagok = (int)$_POST['am_tagok'];
}

// Zárójelezés
$am_zarojelek = isset($_POST['am_zarojelek']);

// Minden művelet kötelező
$am_minden_muvelet = isset($_POST['am_minden_muvelet']);

// Műveletek
$am_muveletek = [];
if (isset($_POST['am_muveletek']) && is_array($_POST['am_muveletek'])) {
    foreach ($_POST['am_muveletek'] as $op) {
        if (in_array($op, ['+', '-', '*', '/'])) {
            $am_muveletek[] = $op;
        }
    }
}
// Ha nincs kiválasztva semmi, alapértelmezett az összeadás
if (empty($am_muveletek)) {
    $am_muveletek = ['+'];
}

// --- VALIDÁCIÓ: TAGOK SZÁMA HA MINDEN KÖTELEZŐ ---
if ($am_minden_muvelet) {
    $min_tagok = count($am_muveletek) + 1;
    if ($am_tagok < $min_tagok) {
        $am_tagok = $min_tagok; // Automatikus korrekció
    }
}

// --- HTML BEÁLLÍTÁSOK GENERÁLÁSA ---
$egyedi_beallitasok_html = '
<div class="col-md-6 col-lg-2">
    <label for="am_tagok" class="form-label fw-bold text-secondary">Tagok száma</label>
    <input type="number" class="form-control" id="am_tagok" name="am_tagok" 
           value="' . $am_tagok . '" min="3" max="6">
</div>

<div class="col-md-12 col-lg-8">
    <label class="form-label fw-bold text-secondary d-block">Műveletek és Extrák</label>
    <div class="d-flex flex-wrap gap-3 align-items-center border rounded p-2 bg-white">
        <!-- Műveletek -->
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="am_muveletek[]" value="+" id="op_plus" ' . (in_array('+', $am_muveletek) ? 'checked' : '') . '>
            <label class="form-check-label fw-bold" for="op_plus">+</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="am_muveletek[]" value="-" id="op_minus" ' . (in_array('-', $am_muveletek) ? 'checked' : '') . '>
            <label class="form-check-label fw-bold" for="op_minus">−</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="am_muveletek[]" value="*" id="op_mul" ' . (in_array('*', $am_muveletek) ? 'checked' : '') . '>
            <label class="form-check-label fw-bold" for="op_mul">·</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="am_muveletek[]" value="/" id="op_div" ' . (in_array('/', $am_muveletek) ? 'checked' : '') . '>
            <label class="form-check-label fw-bold" for="op_div">:</label>
        </div>
        
        <div class="vr mx-2"></div>
        
        <!-- Zárójel -->
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="am_zarojelek" id="am_zarojelek" ' . ($am_zarojelek ? 'checked' : '') . '>
            <label class="form-check-label" for="am_zarojelek">Zárójelek ( )</label>
        </div>

        <div class="vr mx-2"></div>

        <!-- Minden művelet kötelező -->
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="am_minden_muvelet" id="am_minden_muvelet" ' . ($am_minden_muvelet ? 'checked' : '') . '>
            <label class="form-check-label text-danger fw-bold small" for="am_minden_muvelet">Minden kiválasztott<br>szerepeljen!</label>
        </div>
    </div>
</div>
';

// --- SEGÉDFÜGGVÉNYEK ---

function evaluate_expression($expression) {
    try {
        $expression = preg_replace('/[^0-9\+\-\*\/\(\)\s]/', '', $expression);
        if (empty($expression)) return false;
        $result = @eval("return $expression;");
        if ($result === false || $result === null) return false;
        return $result;
    } catch (Throwable $t) {
        return false;
    }
}

/**
 * Generál egy kifejezést.
 * @param bool $is_hard Ha true, szigorúbban szűri a triviális műveleteket.
 */
function generate_valid_task($tagok, $ops, $use_bracket, $limit, $force_all_ops, $is_hard) {
    $max_retry = 1000; 
    
    for ($i = 0; $i < $max_retry; $i++) {
        $nums = [];
        $operators = [];
        
        $has_mul_div = in_array('*', $ops) || in_array('/', $ops);
        $local_max = $has_mul_div ? floor($limit / 4) : $limit;
        if ($local_max < 2) $local_max = 2;

        if ($force_all_ops) {
            $operators = $ops;
            shuffle($operators);
            while (count($operators) < $tagok - 1) {
                $operators[] = $ops[array_rand($ops)];
            }
            shuffle($operators);
        } else {
            for ($t = 0; $t < $tagok - 1; $t++) {
                $operators[] = $ops[array_rand($ops)];
            }
        }

        // Számok generálása
        for ($t = 0; $t < $tagok; $t++) {
            // Nehezített mód: Kerüljük az 1-et és 0-t, ha lehetséges, kivéve ha csak összeadás van
            $min_val = 1;
            if ($is_hard && $has_mul_div) $min_val = 2; 
            
            $val = random_int($min_val, (in_array('/', $ops) ? 20 : $local_max));
            $nums[] = $val;
        }

        // Zárójelezés
        $bracket_open_idx = -1;
        $bracket_close_idx = -1;
        
        if ($use_bracket && $tagok >= 3) {
            $bracket_open_idx = random_int(0, $tagok - 2);
            $min_close = $bracket_open_idx + 1; 
            $bracket_close_idx = random_int($min_close, $tagok - 1);
            if ($bracket_open_idx == 0 && $bracket_close_idx == $tagok - 1) {
                $bracket_open_idx = -1;
            }
        }

        $expression_str = "";
        $tokens = [];
        
        for ($t = 0; $t < $tagok; $t++) {
            if ($t == $bracket_open_idx) $expression_str .= "(";
            $expression_str .= $nums[$t];
            $tokens[] = ['type' => 'num', 'value' => $nums[$t]];
            if ($t == $bracket_close_idx) $expression_str .= ")";
            
            if ($t < $tagok - 1) {
                $op = $operators[$t];
                $expression_str .= " $op ";
                $tokens[] = ['type' => 'op', 'value' => $op];
            }
        }

        // --- TRIVIÁLIS MŰVELETEK SZŰRÉSE (NEHEZÍTETT MÓDBAN) ---
        if ($is_hard) {
            // Ellenőrizzük a stringet triviális esetekre
            // Pl: " 1 ", "/ 1", "* 1", "- 0", "+ 0"
            // De vigyázni kell a számjegyekkel (11 nem egyenlő 1-gyel).
            // A space-ek miatt a " / 1 " minta jó lehet.
            if (strpos($expression_str, " / 1 ") !== false || 
                strpos($expression_str, " * 1 ") !== false || 
                strpos($expression_str, " - 0 ") !== false || 
                strpos($expression_str, " + 0 ") !== false) {
                continue;
            }
        }

        $result = evaluate_expression($expression_str);

        if ($result !== false && abs($result - round($result)) < 0.0001) {
            $int_res = (int)round($result);
            if ($int_res >= 0 && $int_res <= $limit) {
                
                if ($use_bracket && $bracket_open_idx !== -1 && $is_hard) {
                    $start_pos = strpos($expression_str, '(');
                    $end_pos = strpos($expression_str, ')');
                    $inner_expr = substr($expression_str, $start_pos + 1, $end_pos - $start_pos - 1);
                    $inner_res = evaluate_expression($inner_expr);
                    
                    if ($inner_res === 0 || $inner_res == 1) {
                        continue; 
                    }
                }

                return [
                    'str' => str_replace(['*', '/'], ['·', ':'], $expression_str),
                    'raw_str' => $expression_str,
                    'tokens' => $tokens,
                    'result' => $int_res
                ];
            }
        }
    }
    
    return [
        'str' => "2 + 2 + 2",
        'raw_str' => "2 + 2 + 2",
        'tokens' => [],
        'result' => 6
    ];
}

// --- FELADATOK GENERÁLÁSA ---
$feladatok_per_oldal = 24;

for ($p = 0; $p < $oldalak_szama; $p++) {
    
    $html = '';

    if ($p === 0) {
        $html .= '
        <style>
            @media print {
                @page { margin: 1cm; }
                .col-6.pe-4 { padding-right: 5px !important; }
                .col-6.ps-4 { padding-left: 5px !important; }
            }
        </style>';
    }

    $html .= '<div class="row">';
    
    $oldal_feladatai = [];
    while (count($oldal_feladatai) < $feladatok_per_oldal) {
        
        // PARAMÉTEREK BEÁLLÍTÁSA A MÓDOK SZERINT
        // Alapértelmezett (Alap mód): Normál számkör, nincs extra szűrés
        $aktualis_limit = $szamkor_hatar;
        $szigoru_szures = false;
        
        if ($szuper_konnyu) {
            // Könnyített: Kis számok
            $aktualis_limit = min($szamkor_hatar, 20); // Max 20, vagy ha a user kevesebbet adott meg
        }
        
        if ($nehezebb) {
            // Nehezített: Szigorú szűrés (triviális műveletek tiltása)
            $szigoru_szures = true;
            // Ha Könnyített ÉS Nehezített is aktív, akkor a limit marad kicsi (20), de a szűrés bekapcsol.
        }

        $task = generate_valid_task($am_tagok, $am_muveletek, $am_zarojelek, $aktualis_limit, $am_minden_muvelet, $szigoru_szures);
        
        // MEGJELENÍTÉS LOGIKA
        $display_html = "";
        $correct_val = $task['result'];
        
        // Ha "Nehezített" be van kapcsolva (akár önmagában, akár a könnyítettel együtt),
        // akkor a feladat típusa "Hiányzó tag".
        if ($nehezebb) {
            preg_match_all('/([0-9]+)|([^0-9]+)/u', $task['str'], $matches, PREG_SET_ORDER);
            
            $chunks = [];
            $number_indices = [];
            
            foreach ($matches as $i => $m) {
                $val = $m[0];
                $is_num = is_numeric($val);
                $chunks[] = ['val' => $val, 'is_num' => $is_num];
                if ($is_num) $number_indices[] = $i;
            }
            
            $hidden_chunk_idx = $number_indices[array_rand($number_indices)];
            $correct_val = $chunks[$hidden_chunk_idx]['val'];
            
            foreach ($chunks as $i => $chunk) {
                if ($i === $hidden_chunk_idx) {
                    $display_html .= '<input type="number" class="answer-box mx-1" style="width:50px" data-correct="' . $correct_val . '">';
                } else {
                    $display_html .= $chunk['val'];
                }
            }
            $display_html .= " = <span class='fw-bold text-primary'>" . $task['result'] . "</span>";
            
        } else {
            // Ha NINCS nehezített (tehát Alap vagy csak Könnyített),
            // akkor az eredmény hiányzik.
            $display_html = $task['str'] . " = ";
            $display_html .= '<input type="number" class="answer-box mx-1" data-correct="' . $correct_val . '">';
        }

        $oldal_feladatai[] = $display_html;
    }

    // HTML Renderelés
    $half = ceil(count($oldal_feladatai) / 2);
    $columns = array_chunk($oldal_feladatai, $half);

    foreach ($columns as $colIndex => $columnTasks) {
        $borderClass = ($colIndex === 0) ? 'border-end pe-4' : 'ps-4';
        $html .= '<div class="col-6 ' . $borderClass . '">';
        
        foreach ($columnTasks as $display_html) {
            $html .= '<div class="problem-row d-flex align-items-center justify-content-center">';
            $fontSize = (strlen(strip_tags($display_html)) > 20) ? '1.1rem' : '1.3rem';
            
            $html .= '<div class="problem d-flex align-items-center flex-wrap justify-content-center gap-1" style="font-size: '.$fontSize.';">'; 
            $html .= $display_html;
            $html .= '</div></div>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    $feladat_oldalak[] = $html;
}

/* Utolsó módosítás: 2026. február 10. 21:40:00 */