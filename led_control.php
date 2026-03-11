<?php
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Use POST.\n");
}

$cmd = isset($_POST['cmd']) ? trim($_POST['cmd']) : '';
if ($cmd === '') {
    http_response_code(400);
    exit("Missing cmd.\n");
}


if (!preg_match('/^(sudo\s+)?(\/[A-Za-z0-9._\-\/]+\/)?sg_ses(\s|$)/', $cmd)) {
    http_response_code(400);
    exit("Comanda nepermisa.\n");
}

$last = $cmd . ' 2>&1';
$output = [];
$code = 0;

exec($last, $output, $code);

$body = trim(implode("\n", $output));

if ($code === 0) {
    echo "Comanda executată cu succes.\n";
    if ($body !== '') echo $body . "\n";
    echo "CMD: $cmd\n"; 
} else {
    http_response_code(500); // opțional, dar util pentru .fail() în JS
    echo "Eroare (exit $code)\n";
    if ($body !== '') echo $body . "\n";
    echo "CMD: $cmd\n";
}


/* FOSTUL COD / nu mai merge corect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd']))
{
    $cmd = $_POST['cmd'];
    if (preg_match('/^sudo sg_ses/', $cmd))  // protectie
    {
        $output = shell_exec($cmd . " 2>&1");
        echo "Comanda executata: $cmd\n\n$output";
    }
    else
    {
        echo "Comanda nepermisa.";
    }
}
else
{
    echo "Cerere invalida.";
}


*/
?>