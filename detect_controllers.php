
<?php
$output = shell_exec("sudo sas3ircu list 2>/dev/null");
$controllers = [];

foreach (explode("\n", $output) as $line) {
    if (preg_match('/^\s*(\d+)\s+SAS/', $line, $matches)) {
        $controllers[] = $matches[1];
    }
}

file_put_contents("controllers.txt", implode("\n", $controllers));
echo "[OK] Controllere gasite: " . implode(", ", $controllers);
?>
