<?php
$controllers = file("controllers.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
@mkdir("hdd_controlere");

foreach ($controllers as $ctl) 
{
    $output = shell_exec("sudo sas3ircu $ctl display");
    $lines = explode("\n", $output);

    $enclosure = "";
    $slot = "";
    $serial = "";
    $is_hdd = false; // Flag pentru validare hard disk

    $file = fopen("hdd_controlere/hdd_c_$ctl", "w");

    foreach ($lines as $line) 
    {
        $line = trim($line);

        if (preg_match('/Device is a Hard disk/i', $line)) 
        {
            $is_hdd = true;
        }
        elseif (preg_match('/Device is a /i', $line)) 
        {
            // Dacă apare alt "Device is a ..." flag-ul
            $is_hdd = false;
        }

        if (preg_match('/Enclosure #\s*:\s*(\d+)/', $line, $m)) 
        {
            $enclosure = $m[1];
        } 
        elseif (preg_match('/Slot #\s*:\s*(\d+)/', $line, $m)) 
        {
            $slot = $m[1];
        } 
        elseif (preg_match('/Serial No\s*:\s*(\S+)/', $line, $m)) 
        {
            $serial = $m[1];

            // scriem doar daca este HDD
            if ($is_hdd && isset($enclosure, $slot, $serial)) 
            {
                if ($ctl === "1" && is_numeric($slot)) 
                {
                    $slot = max(0, (int)$slot - 1);
                }

                fwrite($file, "$serial|$enclosure|$slot|$ctl\n");

                // Resetam dupa scriere
                $enclosure = "";
                $slot = "";
                $serial = "";
                $is_hdd = false;
            }
        }
    }
    fclose($file);
}
echo "[OK] Fisiere HDD generate.";
?>
