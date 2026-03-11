<?php
// smart_run.php
header('Content-Type: text/plain; charset=utf-8');

$dev = isset($_POST['device']) ? trim($_POST['device']) : '';
if ($dev === '' || strpos($dev, '/dev/') !== 0) {
    http_response_code(400);
    echo "Device invalid";
    exit;
}

// scapăm argumentul pentru siguranță
$dev = escapeshellarg($dev);
$cmd = "sudo smartctl -x $dev 2>&1";
passthru($cmd);
