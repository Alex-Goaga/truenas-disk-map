<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['serial'], $_POST['actiune']))
{
    $serial = $_POST['serial'];
    $actiune = $_POST['actiune']; // 'on' sau 'off'

    $files = glob("hdd_controlere/*_ses");

    if (empty($files))
    {
        echo json_encode([
            "status" => "error",
            "mesaj" => "Nu exista fisiere SES. Te rugam sa rulezi pasii pentru generare: Detectare controllere, generare fisiere HDD si SES."
        ]);
        exit;
    }

    foreach ($files as $file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line)
        {
            list($s, $dev, $loc, $slot, $smart, $cmd_on, $cmd_off) = explode("|", $line);
            if ($s === $serial)
            {
                $cmd = $actiune === 'on' ? $cmd_on : $cmd_off;
                $output = shell_exec($cmd . " 2>&1");
                echo json_encode([
                    "status" => "ok",
                    "executat" => $cmd,
                    "output" => $output,
                    "locatie" => $loc,
                    "slot" => $slot,
                    "device" => $dev
                ]);
                exit;
            }
        }
    }

    echo json_encode([
        "status" => "error",
        "mesaj" => "Serialul '$serial' nu a fost gasit in fisierele SES."
    ]);
}
else
{
    echo json_encode([
        "status" => "error",
        "mesaj" => "Cerere invalida. Trebuie trimise campurile 'serial' si 'actiune'."
    ]);
}
