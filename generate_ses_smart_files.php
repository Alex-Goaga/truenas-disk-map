<?php

/*

Inainte de bug
function get_device_by_serial($serial, $cache_file = "serial_cache.txt") {
    if (!file_exists($cache_file)) return "N/A";
    $lines = file($cache_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        list($s, $dev) = explode(" ", $line);
        if ($s === $serial) return $dev;
    }
    return "N/A";
}
*/


/*
============================================================
PROBLEMA
============================================================

Controllerul LSI (sas3ircu) nu afiseaza serialul complet al
discului. El returneaza doar un prefix din serial.

Exemplu din controller:

    sas3ircu display
    Serial No : Z4D3FDVL

In schimb, smartctl returneaza serialul complet:

    smartctl -i /dev/sdb
    Serial Number: Z4D3FDVL0000R612JPA4

Deci avem doua forme diferite ale aceluiasi serial:

    Controller (scurt) : Z4D3FDVL
    SMART (complet)    : Z4D3FDVL0000R612JPA4

In fisierul serial_cache.txt salvam serialul complet:

    Z4D3FDVL0000R612JPA4 /dev/sdb

Dar in fisierele generate din controller apare doar prefixul:

    Z4D3FDVL|2|0|0

Daca am face comparatie exacta:

    if ($s === $serial)

match-ul NU ar functiona deoarece:

    Z4D3FDVL != Z4D3FDVL0000R612JPA4


============================================================
REZOLVARE
============================================================

Facem comparatia folosind prefixul serialului.

Adica verificam daca serialul complet incepe cu serialul
scurt primit de la controller.

Folosim:

    strpos($s, $serial) === 0

unde:
    $serial = serialul scurt din controller
    $s      = serialul complet din serial_cache.txt

Exemplu:

    $serial = Z4D3FDVL
    $s      = Z4D3FDVL0000R612JPA4

strpos($s, $serial) === 0  -> TRUE

Astfel putem asocia corect:

    Z4D3FDVL  -> /dev/sdb


============================================================
CONCLUZIE
============================================================

Controllerul LSI ofera serial scurt,
smartctl ofera serial complet.

Prin compararea pe prefix putem face mapping corect intre:

    Serial (controller) -> Device (/dev/sdX)
============================================================
*/



function get_device_by_serial($serial, $cache_file = "serial_cache.txt")
{
    if (!file_exists($cache_file))
    {
        return "N/A";
    }

    $lines = file($cache_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line)
    {
        list($s, $dev) = explode(" ", $line);

        if (strpos($s, $serial) === 0)
        {
            return $dev;
        }
    }

    return "N/A";
}

/*

 OLD AM SCOS SI ORELE
 
 
function get_smart_status($dev) {
    if ($dev === "N/A") return "X";

    $smart = shell_exec("sudo smartctl -x $dev 2>/dev/null");
    if (!$smart) return "X";

    $realloc = $pending = $uncorrect = $load = $hours = $ata_errors = 0;
    $selftest_fail = false;
    $read_fail = false;

    foreach (explode("\n", $smart) as $line) {
        $line = trim($line);
        if (preg_match('/^5\\s+Reallocated_Sector_Ct\\s+\\S+\\s+\\S+\\s+\\S+\\s+\\S+\\s+\\S+\\s+(\\d+)/', $line, $m)) {
            $realloc = (int)$m[1];
        } elseif (preg_match('/^197\\s+Current_Pending_Sector\\s+\\S+\\s+\\S+\\s+\\S+\\s+\\S+\\s+\\S+\\s+(\\d+)/', $line, $m)) {
            $pending = (int)$m[1];
        } elseif (preg_match('/^198\\s+Offline_Uncorrectable\\s+\\S+\\s+\\S+\\s+\\S+\\s+\\S+\\s+\\S+\\s+(\\d+)/', $line, $m)) {
            $uncorrect = (int)$m[1];
        } elseif (preg_match('/^193\\s+Load_Cycle_Count\\s+\\S+\\s+\\S+\\s+\\S+\\s+\\S+\\s+\\S+\\s+(\\d+)/', $line, $m)) {
            $load = (int)$m[1];
        } elseif (preg_match('/^9\\s+Power_On_Hours\\s+\\S+\\s+\\S+\\s+\\S+\\s+\\S+\\s+\\S+\\s+(\\d+)/', $line, $m)) {
            $hours = (int)$m[1];
        }
    }

    if (preg_match('/Completed:\\s*read failure/i', $smart)) {
        $read_fail = true;
    }

    if (preg_match('/ATA Error Count:\\s*(\\d+)/i', $smart, $m)) {
        $ata_errors = (int)$m[1];
    }

    // Evaluare status
    if ($pending > 0 || $uncorrect > 0 || $realloc > 10 || $ata_errors > 0) {
        return "PERICULOS (Realloc=$realloc / Pending=$pending / Uncorrect=$uncorrect / ATA_Errors=$ata_errors)";
    }

    if (($load > 20000 || $hours > 55000 || ($realloc > 0 && $realloc <= 10))) {
        return "OBOSIT (Realloc=$realloc / Load=$load / Hours=$hours)";
    }

    if ($read_fail || $realloc > 0 || $ata_errors > 0) {
        return "SUSPECT (Realloc=$realloc / ATA_Errors=$ata_errors / ReadFail=" . ($read_fail ? "DA" : "NU") . ")";
    }

    return "OK";
}



*/



function get_smart_status($dev)
{
    if ($dev === "N/A")
    {
        return "X";
    }

    $smart = shell_exec("sudo smartctl -x $dev 2>/dev/null");
    if (!$smart)
    {
        return "X";
    }

    // Variabile
    $realloc = 0;
    $pending = 0;
    $uncorrect = 0;
    $load = 0;
    $hours = 0;
    $crc = 0;
    $ata_errors = 0; // Poate lipsi pe unele modele
    $selftest_fail = false;
    $read_fail = false;
    $overall_passed = true;

    // Parse atribute SMART clasice
    foreach (explode("\n", $smart) as $line)
    {
        $line = trim($line);

        if (preg_match('/^5\s+Reallocated_Sector_Ct\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+(-?\d+)/', $line, $m))
        {
            $realloc = (int)$m[1];
        }
        elseif (preg_match('/^197\s+Current_Pending_Sector\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+(-?\d+)/', $line, $m))
        {
            $pending = (int)$m[1];
        }
        elseif (preg_match('/^198\s+Offline_Uncorrectable\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+(-?\d+)/', $line, $m))
        {
            $uncorrect = (int)$m[1];
        }
        elseif (preg_match('/^193\s+Load_Cycle_Count\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+(-?\d+)/', $line, $m))
        {
            $load = (int)$m[1];
        }
        elseif (preg_match('/^9\s+Power_On_Hours\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+(-?\d+)/', $line, $m))
        {
            $hours = (int)$m[1];
        }
        elseif (preg_match('/^199\s+UDMA_CRC_Error_Count\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+(-?\d+)/', $line, $m))
        {
            $crc = (int)$m[1];
        }
    }

    // Overall health
    if (preg_match('/SMART overall-health .*?:\s*(\S+)/i', $smart, $m))
    {
        if (strtoupper($m[1]) !== 'PASSED')
        {
            $overall_passed = false;
        }
    }

    // Self-test failures (diverse mesaje posibile)
    if (preg_match('/Completed:\s*(read|electrical|servo|unknown)\s+failure/i', $smart))
    {
        $selftest_fail = true;
    }

    // Read failure in self-test (string util cand long test pica pe citire)
    if (preg_match('/Completed:\s*read failure/i', $smart))
    {
        $read_fail = true;
    }

    // ATA Error Count (nu toate modelele il au)
    if (preg_match('/ATA\s+Error\s+Count:\s*(\d+)/i', $smart, $m))
    {
        $ata_errors = (int)$m[1];
    }
    else
    {
        // Daca log-ul zice "No Errors Logged", consideram 0
        if (preg_match('/SMART\s+Error\s+Log.*?\n\s*No\s+Errors\s+Logged/i', $smart))
        {
            $ata_errors = 0;
        }
    }

    // ORDONARE SEVERITATI:
    // 1) MORT: SMART not passed SAU self-test failure SAU combinatii grele
    if (!$overall_passed || $selftest_fail || ($pending > 0 && $uncorrect > 0) || $realloc >= 100)
    {
        return "MORT (Overall=" . ($overall_passed ? "PASSED" : "FAILED") .
               " / SelfTestFail=" . ($selftest_fail ? "DA" : "NU") .
               " / Realloc=$realloc / Pending=$pending / Uncorrect=$uncorrect / CRC=$crc / ATA_Errors=$ata_errors)";
    }

    // 2) PERICULOS: semnale clare de risc
    if ($pending > 0 || $uncorrect > 0 || $realloc > 10 || $crc > 0 || $ata_errors > 0)
    {
        return "PERICULOS (Realloc=$realloc / Pending=$pending / Uncorrect=$uncorrect / CRC=$crc / ATA_Errors=$ata_errors)";
    }

	// 3) OBOSIT: multe cicluri de load/unload sau uzura usoara (putine sectoare realocate)
	if ($load > 20000 || ($realloc > 0 && $realloc <= 10))
	{
		return "OBOSIT (Realloc=$realloc / Load=$load)";
	}


    // 4) SUSPECT: semnale mai slabe, dar de urmarit
    if ($read_fail || $realloc > 0 || $ata_errors > 0)
    {
        return "SUSPECT (Realloc=$realloc / ATA_Errors=$ata_errors / ReadFail=" . ($read_fail ? "DA" : "NU") . ")";
    }

    // 5) OK
    return "OK";
}






// Detectam SES
$cmd = "lsscsi -g | grep enclosu | grep SAS3 | awk '{print \$NF}' | sort";
$ses_devs = explode("\n", trim(shell_exec($cmd)));
$valid_ses = $ses_devs[0] ?? "N/A";
$invalid_ses = $ses_devs[1] ?? "N/A";
$exp_ses = trim(shell_exec("lsscsi -g | grep enclosu | grep 4U60 | awk '{print \$NF}' | sort | head -n1"));

foreach (glob("hdd_controlere/hdd_c_*") as $file) {
    if (str_contains($file, "_ses")) continue;

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $enclosure_counter = [];
    $enclosure_to_lines = [];

    foreach ($lines as $line) {
        list($serial, $enclosure, $slot, $ctrl) = explode("|", $line);
        $enclosure_counter[$enclosure] = ($enclosure_counter[$enclosure] ?? 0) + 1;
        $enclosure_to_lines[$enclosure][] = [$serial, $enclosure, $slot, $ctrl];
    }

    if (empty($enclosure_counter)) continue;

    arsort($enclosure_counter);
    $enc_keys = array_keys($enclosure_counter);
    $fata_enc = $enc_keys[0];
    $spate_enc = $enc_keys[1] ?? null;

    $enclosure_map = [$fata_enc => "Fata"];
    if ($spate_enc) $enclosure_map[$spate_enc] = "Spate";

    $ctrl = explode("_", basename($file))[2];
    $is_expansion = $ctrl === "1";
    $label = $is_expansion ? "CutieExterna" : "CutieLocala";

    $ses_map = [
        "Fata" => $is_expansion ? $exp_ses : $valid_ses,
        "Spate" => $is_expansion ? null : $invalid_ses
    ];

    foreach ($enclosure_map as $enc => $poz) {
        $output_file = $file . "_" . strtolower($poz) . "_ses";
        $out = fopen($output_file, "w");

        foreach ($enclosure_to_lines[$enc] as [$serial, $enclosure, $slot, $ctrl]) {
            $device = get_device_by_serial($serial);
            $smart_status = get_smart_status($device);
            $ses = $ses_map[$poz] ?? "N/A";
            $cmd_on = "sudo sg_ses --index=$slot --set=ident $ses";
            $cmd_off = "sudo sg_ses --index=$slot --clear=ident $ses";
            fwrite($out, "$serial|$device|$label-$poz|$slot|$smart_status|$cmd_on|$cmd_off\n");
        }

        fclose($out);
    }
}
echo "[OK] SES files generate.";
?>
