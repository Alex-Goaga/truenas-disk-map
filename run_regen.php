<?php
// run_regen.php

header('Content-Type: text/plain; charset=utf-8');

$mode = isset($_POST['mode']) ? trim($_POST['mode']) : 'cu_smart';

ob_start();

echo "Executare mod: {$mode}\n";
echo str_repeat("=", 60) . "\n\n";

echo "Pasul 1: Curatare fisiere anterioare\n";
include __DIR__ . "/clean_hdd_files.php";
echo "\n";

echo "Pasul 2: Detectare controllere\n";
include __DIR__ . "/detect_controllers.php";
echo "\n";

echo "Pasul 3: Generare fisiere HDD\n";
include __DIR__ . "/generate_hdd_files.php";
echo "\n";

echo "Pasul 4: Asociere Serial ↔ Device\n";
include __DIR__ . "/associate_devices.php";
echo "\n";

echo "Pasul 5: Generare SES\n";
include __DIR__ . "/generate_ses_smart_files.php";
echo "\n";

echo "Pasul 6: Generare Lista Discuri nefolosite\n";
include __DIR__ . "/gen_disk_unused_api.php";
echo "\n";

echo "Pasul 7: Generare Lista per pool\n";
include __DIR__ . "/gen_disk_per_pool_api.php";
echo "\n";

$log = ob_get_clean();
echo $log;
echo "\n=== COMPLET ===\n";
