<?php

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_POST['file']))
{
    echo "Fisier lipsa";
    exit;
}

$base = realpath(__DIR__ . "/..");

if ($base === false)
{
    echo "Nu pot determina directorul baza";
    exit;
}

$file = basename($_POST['file']);

$allowed = array(
    "clean_hdd_files.php",
    "detect_controllers.php",
    "generate_hdd_files.php",
    "associate_devices.php",
    "generate_ses_smart_files.php",
    "gen_disk_unused_api.php",
    "gen_disk_per_pool_api.php",
    "run_regen.php"
);

if (!in_array($file, $allowed))
{
    echo "Fisier nepermis";
    exit;
}

$path = $base . "/" . $file;

if (!file_exists($path))
{
    echo "Fisierul nu exista: $file";
    exit;
}

$old_dir = getcwd();

ob_start();

echo "Rulez: $file\n";
echo "====================================\n\n";
echo "Director baza: $base\n\n";

chdir($base);

include $path;

if ($old_dir !== false)
{
    chdir($old_dir);
}

$log = ob_get_clean();

echo $log;
?>