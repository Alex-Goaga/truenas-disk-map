<?php
/*
$output = shell_exec("lsblk -ndo NAME,TYPE | grep disk | awk '{print $1}'");
$lines = explode("\n", trim($output));
$data = "";

foreach ($lines as $line) {
    $dev = "/dev/" . trim($line);

    $serial_raw = shell_exec("udevadm info --query=all --name=$dev | grep ID_SERIAL_SHORT= | cut -d= -f2");
    $serial = $serial_raw ? trim($serial_raw) : "";

    if (!$serial) {
        // fallback cu smartctl
        $serial_raw = shell_exec("sudo smartctl -i $dev | grep 'Serial Number' | awk -F: '{print $2}'");
        $serial = $serial_raw ? trim($serial_raw) : "";
    }

    if ($serial)
        $data .= "$serial $dev\n";
}
file_put_contents("serial_cache.txt", $data);
echo "[OK] Serialele au fost asociate.";




$output = shell_exec("lsblk -ndo NAME,TYPE | grep disk | awk '{print $1}'");
$lines = explode("\n", trim($output));

$data = "";

foreach ($lines as $line)
{
    $line = trim($line);

    if ($line)
    {
        $dev = "/dev/" . $line;
        $serial = "";

        // 1. Incercare udevadm
        $serial_raw = shell_exec("udevadm info --query=all --name=$dev 2>/dev/null | grep ID_SERIAL_SHORT= | cut -d= -f2");

        if ($serial_raw)
        {
            $serial = trim($serial_raw);
        }

        // 2. Daca nu exista, incercare smartctl standard
        if ($serial == "")
        {
            $serial_raw = shell_exec("sudo smartctl -i $dev 2>/dev/null | grep 'Serial Number' | awk -F: '{print \$2}'");

            if ($serial_raw)
            {
                $serial = trim($serial_raw);
            }
        }

        // 3. Daca tot nu exista, incercare SAS
        if ($serial == "")
        {
            $serial_raw = shell_exec("sudo smartctl -i -d scsi $dev 2>/dev/null | grep 'Serial number' | awk -F: '{print \$2}'");

            if ($serial_raw)
            {
                $serial = trim($serial_raw);
            }
        }

        if ($serial != "")
        {
            $data .= $serial . " " . $dev . "\n";
        }
    }
}

file_put_contents("serial_cache.txt", $data);

echo "[OK] Serialele au fost asociate.";

*/

$data = "";

// 1. Luam lista corecta de device-uri din smartctl
$scan_output = shell_exec("sudo smartctl --scan 2>/dev/null");
$lines = explode("\n", trim($scan_output));

foreach ($lines as $line)
{
    $line = trim($line);

    if ($line != "")
    {
        // Extrage /dev/sdX
        preg_match('/(\/dev\/[a-zA-Z0-9]+)/', $line, $dev_match);

        if (isset($dev_match[1]))
        {
            $dev = $dev_match[1];
            $serial = "";
            $device_type = "";

            // Extrage -d TYPE daca exista
            preg_match('/-d\s+([a-zA-Z0-9,]+)/', $line, $type_match);

            if (isset($type_match[1]))
            {
                $device_type = trim($type_match[1]);
            }

            // Construim comanda inteligent
            if ($device_type != "")
            {
                $cmd = "sudo smartctl -i -d $device_type $dev 2>/dev/null";
            }
            else
            {
                $cmd = "sudo smartctl -i $dev 2>/dev/null";
            }

            $info = shell_exec($cmd);

            if ($info)
            {
                // Unele returneaza "Serial Number"
                if (preg_match('/Serial Number:\s*(.+)/i', $info, $match))
                {
                    $serial = trim($match[1]);
                }

                // SAS uneori returneaza "Serial number"
                if ($serial == "")
                {
                    if (preg_match('/Serial number:\s*(.+)/i', $info, $match))
                    {
                        $serial = trim($match[1]);
                    }
                }
            }

            if ($serial != "")
            {
                $data .= $serial . " " . $dev . "\n";
            }
        }
    }
}

file_put_contents("serial_cache.txt", $data);

echo "[OK] Serialele au fost asociate eficient.";

?>
