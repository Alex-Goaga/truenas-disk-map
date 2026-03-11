<?php
// gen_disk_per_pool_api.php
// Preia pool-urile din TrueNAS prin API si scrie numele pool-ului, data_disks si spare_disks
// in hdd_controlere/disk_per_pool.txt (JSON pretty).

// ====== CONFIG ======
require_once __DIR__ . "/config_api.php";



$target_dir = __DIR__ . "/hdd_controlere";
$target_file = $target_dir . "/disk_per_pool.txt";

// ====== HELPER: apel API GET ======
function truenas_api_get($url, $api_key, $verify_tls)
{
    $ch = curl_init();
    $headers = array(
        "Authorization: Bearer " . $api_key,
        "Accept: application/json"
    );

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($verify_tls)
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }
    else
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0)
    {
        die("Eroare cURL ($errno): $err\n");
    }

    if ($code < 200 || $code >= 300)
    {
        die("Eroare HTTP $code la $url\nRaspuns: " . $resp . "\n");
    }

    return $resp;
}

// ====== MAIN ======
if (!is_dir($target_dir))
{
    if (!mkdir($target_dir, 0775, true))
    {
        die("Eroare: nu pot crea directorul: " . $target_dir . "\n");
    }
}

// 1) Luam lista de pool-uri (include si topologia in mod normal)
$pools_json = truenas_api_get($API_URL . "/pool", $API_KEY, $VERIFY_TLS);
$pools = json_decode($pools_json, true);

if (!is_array($pools))
{
    die("Eroare: raspuns pool invalid sau non-JSON.\n");
}

$result = array();

// Parcurgem fiecare pool si extragem data_disks si spare_disks
foreach ($pools as $pool)
{
    $pool_name = "";
    $data_disks = array();
    $spare_disks = array();

    if (isset($pool["name"]))
    {
        $pool_name = $pool["name"];
    }
    else
    {
        $pool_name = "(fara_nume)";
    }

    // In multe versiuni, topologia este in $pool["topology"]
    if (isset($pool["topology"]) && is_array($pool["topology"]))
    {
        // DATA: vdev-uri cu children->disk
        if (isset($pool["topology"]["data"]) && is_array($pool["topology"]["data"]))
        {
            foreach ($pool["topology"]["data"] as $vdev)
            {
                if (isset($vdev["children"]) && is_array($vdev["children"]))
                {
                    foreach ($vdev["children"] as $child)
                    {
                        if (isset($child["disk"]) && $child["disk"] !== null)
                        {
                            $dname = trim($child["disk"]);
                            if ($dname !== "")
                            {
                                $data_disks[] = $dname;
                            }
                        }
                    }
                }
            }
        }

        // SPARE: uneori e "spare", alteori "spares" (defensiv)
        if (isset($pool["topology"]["spare"]) && is_array($pool["topology"]["spare"]))
        {
            foreach ($pool["topology"]["spare"] as $sp)
            {
                if (isset($sp["disk"]) && $sp["disk"] !== null)
                {
                    $sname = trim($sp["disk"]);
                    if ($sname !== "")
                    {
                        $spare_disks[] = $sname;
                    }
                }
            }
        }
        else
        if (isset($pool["topology"]["spares"]) && is_array($pool["topology"]["spares"]))
        {
            foreach ($pool["topology"]["spares"] as $sp)
            {
                if (isset($sp["disk"]) && $sp["disk"] !== null)
                {
                    $sname = trim($sp["disk"]);
                    if ($sname !== "")
                    {
                        $spare_disks[] = $sname;
                    }
                }
            }
        }
    }

    // Unicizare + sortare, ca sa semene cu sort -u
    $data_disks = array_values(array_unique($data_disks));
    sort($data_disks, SORT_STRING);

    $spare_disks = array_values(array_unique($spare_disks));
    sort($spare_disks, SORT_STRING);

    $result[] = array(
        "name" => $pool_name,
        "data_disks" => $data_disks,
        "spare_disks" => $spare_disks
    );
}

// Scriem frumos (pretty JSON) in fisier
$pretty = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($pretty === false)
{
    die("Eroare la json_encode: " . json_last_error_msg() . "\n");
}

$f = fopen($target_file, "w");
if ($f === false)
{
    die("Eroare: nu pot deschide fisierul pentru scriere: " . $target_file . "\n");
}
fwrite($f, $pretty . "\n");
fclose($f);

echo "Generat: " . $target_file . "\n";
