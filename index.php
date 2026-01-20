<?php
/*
 * Fájl: index.php
 * Funkció: Főoldal, keretrendszer, navigáció és közös beállítások kezelése.
 * Ez a fájl tölti be a dinamikus feladatmodulokat.
 */

// PHP 8.3 környezet
header('Content-Type: text/html; charset=utf-8');

// Alapértelmezett feladat és mappa
$mappa = 'feladatok/';
$aktualis_feladat = isset($_GET['tipus']) ? $_GET['tipus'] : 'osszeadas';

// Engedélyezett feladatok listája
// MÓDOSÍTÁS: Hozzáadva a számszomszéd
$engedelyezett_feladatok = [
    'osszeadas'    => 'Több tényezős összeadás',
    'osztas'       => 'Maradékos osztás',
    'szorzas'      => 'Szorzás gyakorlása',
    'kerekites'    => 'Kerekítés gyakorlása',
    'szamszomszed' => 'Számszomszédok'
];

if (!array_key_exists($aktualis_feladat, $engedelyezett_feladatok)) {
    $aktualis_feladat = 'osszeadas';
}

// Cím beállítása
$oldal_cim = $engedelyezett_feladatok[$aktualis_feladat];

// --- FUNKCIÓK TÁMOGATÁSA (Konfiguráció) ---
// Itt határozzuk meg, hogy melyik feladathoz milyen gombok legyenek aktívak
$funkcio_tamogatas = [
    'osszeadas'    => ['nehezebb' => true,  'szuper_konnyu' => true],
    'osztas'       => ['nehezebb' => true,  'szuper_konnyu' => false],
    'szorzas'      => ['nehezebb' => true,  'szuper_konnyu' => false],
    'kerekites'    => ['nehezebb' => false, 'szuper_konnyu' => false],
    'szamszomszed' => ['nehezebb' => false, 'szuper_konnyu' => false],
];

// Lekérdezzük, hogy az aktuális feladat támogatja-e az adott funkciókat
$tamogatja_nehezebb = $funkcio_tamogatas[$aktualis_feladat]['nehezebb'];
$tamogatja_szuper_konnyu = $funkcio_tamogatas[$aktualis_feladat]['szuper_konnyu'];

// --- KÖZÖS VÁLTOZÓK INICIALIZÁLÁSA ---
$szamkor_hatar = 100;
if (isset($_POST['szamkor']) && (int)$_POST['szamkor'] > 0) $szamkor_hatar = (int)$_POST['szamkor'];

$oldalak_szama = 1;
if (isset($_POST['oldalak']) && (int)$_POST['oldalak'] > 0) $oldalak_szama = (int)$_POST['oldalak'];

// Csak akkor vesszük figyelembe a POST értéket, ha a funkció támogatott
$nehezebb = $tamogatja_nehezebb && isset($_POST['nehezebb']);
$szuper_konnyu = $tamogatja_szuper_konnyu && isset($_POST['szuper_konnyu']);

// Feladatok tárolója
$feladat_oldalak = []; 

// --- SPECIFIKUS LOGIKA BETÖLTÉSE ---
$fajl_utvonal = $mappa . $aktualis_feladat . '.php';

if (file_exists($fajl_utvonal)) {
    include $fajl_utvonal;
} else {
    echo "Hiba: A feladatfájl nem található: $fajl_utvonal";
    exit;
}

?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $oldal_cim; ?> - Generátor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Közös stílusok */
        .problem { font-size: 1.3rem; font-family: 'Arial', sans-serif; white-space: nowrap; }
        .problem-row { padding: 12px 0; margin-bottom: 10px; border-radius: 8px; page-break-inside: avoid; transition: background-color 0.3s ease; }
        .problem-row:nth-child(even) { background-color: #f8f9fa; }
        
        .answer-box {
            width: 60px; border: none; border-bottom: 2px solid #212529;
            text-align: center; font-weight: bold; font-size: 1.3rem;
            padding: 2px; background-color: #fff; -moz-appearance: textfield;
        }
        .answer-box::-webkit-outer-spin-button, .answer-box::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .answer-box.hiba { border-color: #dc3545; background-color: #f8d7da; }

        @media print {
            .no-print { display: none !important; }
            .container, .container-fluid { width: 100% !important; max-width: none !important; padding: 0; margin: 0; }
            .page-break { page-break-after: always; }
            .page-break:last-child { page-break-after: auto; }
            .worksheet { min-height: 95vh; }
            body.print-kiemelt .problem-row:nth-child(even) { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body.print-kiemelt .answer-box.hiba { border-color: #dc3545 !important; background-color: #f8d7da !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4; margin: 2cm; }
        }
    </style>
</head>
<body>

<div class="container mt-4">
    
    <!-- FEJLÉC ÉS NAVIGÁCIÓ -->
    <div class="card shadow mb-5 no-print border-0">
        <div class="card-header bg-primary text-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0 fw-bold"><i class="fas fa-calculator me-2"></i>Matek Generátor</h4>
                </div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                    <!-- Feladatválasztó Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle fw-bold text-primary shadow-sm" type="button" data-bs-toggle="dropdown">
                            <?php echo $oldal_cim; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($engedelyezett_feladatok as $kulcs => $nev): ?>
                                <li>
                                    <a class="dropdown-item <?php echo $aktualis_feladat === $kulcs ? 'active' : ''; ?>" 
                                       href="?tipus=<?php echo $kulcs; ?>">
                                       <?php echo $nev; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- BEÁLLÍTÁSOK FORM -->
        <div class="card-body bg-light p-4">
            <form action="?tipus=<?php echo $aktualis_feladat; ?>" method="POST">
                <div class="row g-4 align-items-end">
                    
                    <!-- 1. Közös: Számkör -->
                    <div class="col-md-6 col-lg-2">
                        <label for="szamkor" class="form-label fw-bold text-secondary">Számkör</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-hashtag text-muted"></i></span>
                            <input type="number" class="form-control" id="szamkor" name="szamkor" 
                                   value="<?php echo htmlspecialchars($szamkor_hatar); ?>" min="10">
                        </div>
                    </div>

                    <!-- 2. Közös: Oldalak száma -->
                    <div class="col-md-6 col-lg-2">
                        <label for="oldalak" class="form-label fw-bold text-secondary">Oldalak</label>
                        <input type="number" class="form-control" id="oldalak" name="oldalak" 
                               value="<?php echo htmlspecialchars($oldalak_szama); ?>" min="1" max="20">
                    </div>

                    <!-- 3. EGYEDI BEÁLLÍTÁSOK (A feladat fájlból jön) -->
                    <?php if (isset($egyedi_beallitasok_html)) echo $egyedi_beallitasok_html; ?>

                    <!-- 4. Közös: Mód választók (Kondicionális megjelenítés) -->
                    <div class="col-md-12 col-lg-4">
                        <div class="row g-2">
                            <!-- Nehézség kapcsoló -->
                            <div class="col-6">
                                <label class="form-label fw-bold text-secondary">Nehézségi szint</label>
                                <input type="checkbox" class="btn-check" id="nehezebb" name="nehezebb" autocomplete="off" 
                                       <?php echo $nehezebb ? 'checked' : ''; ?>
                                       <?php echo $tamogatja_nehezebb ? '' : 'disabled'; ?>>
                                <label class="btn btn-outline-warning w-100 fw-bold <?php echo $tamogatja_nehezebb ? '' : 'disabled border-secondary text-secondary opacity-25'; ?>" for="nehezebb">
                                    <i class="fas fa-question-circle me-1"></i> Nehezített
                                </label>
                            </div>
                            <!-- Szuper könnyű kapcsoló -->
                            <div class="col-6">
                                <label class="form-label fw-bold text-secondary">Könnyítés</label>
                                <input type="checkbox" class="btn-check" id="szuper_konnyu" name="szuper_konnyu" autocomplete="off" 
                                       <?php echo $szuper_konnyu ? 'checked' : ''; ?>
                                       <?php echo $tamogatja_szuper_konnyu ? '' : 'disabled'; ?>>
                                <label class="btn btn-outline-success w-100 fw-bold <?php echo $tamogatja_szuper_konnyu ? '' : 'disabled border-secondary text-secondary opacity-25'; ?>" for="szuper_konnyu">
                                    <i class="fas fa-feather me-1"></i> Szuper könnyű
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Generálás gomb -->
                    <div class="col-md-12 col-lg-2 text-end ms-auto">
                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                            <i class="fas fa-sync-alt me-2"></i>Generálás
                        </button>
                    </div>
                </div>

                <hr class="my-4 text-muted">
                
                <!-- Alsó sáv: Ellenőrzés és Nyomtatás -->
                <div class="row justify-content-between align-items-center">
                    <div class="col-auto">
                        <button type="button" id="check-button" class="btn btn-outline-info fw-bold">
                            <i class="fas fa-check-double me-2"></i>Ellenőrzés
                        </button>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-center bg-white p-2 rounded border shadow-sm">
                            <select class="form-select form-select-sm me-2 border-0 bg-light" id="print-style" style="width: auto;">
                                <option value="tiszta" selected>Tiszta</option>
                                <option value="kiemelt">Kiemelt</option>
                            </select>
                            <button type="button" id="print-button" class="btn btn-success btn-sm px-3">
                                <i class="fas fa-print me-1"></i> Nyomtatás
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- EREDMÉNYJELZŐK -->
    <div id="success-alert" class="alert alert-success mt-3 no-print shadow-sm border-0" style="display: none;">
        <h4><i class="fas fa-smile-beam me-2"></i>Gratulálok!</h4>
        Minden feladat helyes!
    </div>
    <div id="error-alert" class="alert alert-danger mt-3 no-print shadow-sm border-0" style="display: none;">
        <h4><i class="fas fa-exclamation-triangle me-2"></i>Hoppá!</h4>
        Összesen <strong><span id="error-count">X</span></strong> hibát találtam.
    </div>

    <!-- FELADATLAPOK MEGJELENÍTÉSE -->
    <?php if (empty($feladat_oldalak)): ?>
        <div class="alert alert-info text-center no-print">Kattints a <strong>Generálás</strong> gombra a feladatok elkészítéséhez!</div>
    <?php else: ?>
        <?php foreach ($feladat_oldalak as $oldal_index => $oldal_html): ?>
            <div class="worksheet page-break <?php echo ($oldal_index > 0) ? 'mt-5 mt-print-0' : ''; ?>">
                <h3 class="text-center mb-4 pt-2 fw-bold text-secondary">
                    <?php echo $oldal_cim; ?>
                    <?php if ($oldalak_szama > 1): ?>
                        <small class="text-muted d-block fs-6 mt-1">(<?php echo $oldal_index + 1; ?>. oldal)</small>
                    <?php endif; ?>
                </h3>
                
                <!-- A konkrét feladat HTML kódjának kiírása -->
                <?php echo $oldal_html; ?>
                
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- UNIVERZÁLIS ELLENŐRZŐ ---
        // Minden input mezőt megkeres, aminek van 'data-correct' attribútuma
        const checkButton = document.getElementById('check-button');
        const successAlert = document.getElementById('success-alert');
        const errorAlert = document.getElementById('error-alert');
        const errorCountSpan = document.getElementById('error-count');

        checkButton.addEventListener('click', function() {
            successAlert.style.display = 'none';
            errorAlert.style.display = 'none';
            
            const inputsToCheck = document.querySelectorAll('input[data-correct]');
            let hibasCount = 0;

            inputsToCheck.forEach(input => {
                input.classList.remove('hiba');
                const userVal = parseInt(input.value);
                const correctVal = parseInt(input.dataset.correct);

                if (isNaN(userVal) || userVal !== correctVal) {
                    input.classList.add('hiba');
                    hibasCount++;
                }
            });

            if (inputsToCheck.length > 0) {
                if (hibasCount === 0) {
                    successAlert.style.display = 'block';
                } else {
                    errorCountSpan.textContent = hibasCount;
                    errorAlert.style.display = 'block';
                }
                window.scrollTo(0, 0);
            }
        });

        // --- NYOMTATÁS ---
        const printButton = document.getElementById('print-button');
        printButton.addEventListener('click', function() {
            const printStyle = document.getElementById('print-style').value;
            document.body.classList.remove('print-kiemelt');
            if (printStyle === 'kiemelt') document.body.classList.add('print-kiemelt');
            window.print();
        });
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('print-kiemelt');
        });
    });
</script>
</body>
</html>
<?php /* Utolsó módosítás: 2026. január 10. 22:30:00 */ ?>