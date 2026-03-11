<?php

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_GET['f']))
{
echo "Fisier lipsa";
exit;
}

$base = realpath(__DIR__ . "/..");

$file = $_GET['f'];

$path = $base . "/" . $file;

if (!file_exists($path))
{
echo "Fisierul nu exista";
exit;
}

echo "Fisier: $file\n";
echo "====================================\n\n";

echo file_get_contents($path);