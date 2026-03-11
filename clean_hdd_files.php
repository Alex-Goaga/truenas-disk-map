<?php

/*
$dir = "hdd_controlere";
$deleted = 0;

if (is_dir($dir)) {
    $files = glob("$dir/*");
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $deleted++;
        }
    }
    echo "[OK] Au fost sterse $deleted fisiere din $dir.";
} else {
    echo "[WARN] Directorul $dir nu exista.";
}
?>


*/


$dir = "hdd_controlere";
$deleted = 0;
$deleted_extra = 0;
$errors = array();

if (is_dir($dir))
{
    $files = glob($dir . "/*");

    if (is_array($files))
    {
        foreach ($files as $file)
        {
            if (is_file($file))
            {
                if (unlink($file))
                {
                    $deleted++;
                }
                else
                {
                    $errors[] = "Nu am putut sterge: " . $file;
                }
            }
        }
    }

    echo "[OK] Au fost sterse " . $deleted . " fisiere din " . $dir . ".\n";
}
else
{
    echo "[WARN] Directorul " . $dir . " nu exista.\n";
}

if (file_exists("serial_cache.txt"))
{
    if (unlink("serial_cache.txt"))
    {
        $deleted_extra++;
        echo "[OK] A fost sters fisierul serial_cache.txt\n";
    }
    else
    {
        $errors[] = "Nu am putut sterge serial_cache.txt";
    }
}
else
{
    echo "[INFO] Fisierul serial_cache.txt nu exista\n";
}

if (file_exists("controllers.txt"))
{
    if (unlink("controllers.txt"))
    {
        $deleted_extra++;
        echo "[OK] A fost sters fisierul controllers.txt\n";
    }
    else
    {
        $errors[] = "Nu am putut sterge controllers.txt";
    }
}
else
{
    echo "[INFO] Fisierul controllers.txt nu exista\n";
}

echo "[OK] Total fisiere extra sterse: " . $deleted_extra . "\n";

if (!empty($errors))
{
    echo "\n[WARN] Probleme la stergere:\n";

    foreach ($errors as $err)
    {
        echo "- " . $err . "\n";
    }
}
?>
