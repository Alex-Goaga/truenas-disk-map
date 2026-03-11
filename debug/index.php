<?php
?>
<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Debug HDD Pipeline</title>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

<style>

body
{
background:#1e1e1e;
color:#eee;
}

pre
{
background:#111;
color:#8fff8f;
padding:15px;
border-radius:6px;
max-height:500px;
overflow:auto;
}

.btn-step
{
width:100%;
margin-bottom:10px;
}

</style>

</head>

<body class="container mt-4">

<h3 class="mb-4">Debug HDD Pipeline</h3>

<div class="row">

<div class="col-md-3">

<button class="btn btn-primary btn-step" onclick="runStep('clean_hdd_files.php')">
Pas 1 - Clean fisiere
</button>

<button class="btn btn-primary btn-step" onclick="runStep('detect_controllers.php')">
Pas 2 - Detect controllers
</button>

<button class="btn btn-primary btn-step" onclick="runStep('generate_hdd_files.php')">
Pas 3 - Generate HDD files
</button>

<button class="btn btn-primary btn-step" onclick="runStep('associate_devices.php')">
Pas 4 - Associate devices
</button>

<button class="btn btn-primary btn-step" onclick="runStep('generate_ses_smart_files.php')">
Pas 5 - Generate SES
</button>

<button class="btn btn-primary btn-step" onclick="runStep('gen_disk_unused_api.php')">
Pas 6 - Disk unused
</button>

<button class="btn btn-primary btn-step" onclick="runStep('gen_disk_per_pool_api.php')">
Pas 7 - Disk per pool
</button>

<hr>

<button class="btn btn-danger btn-step" onclick="runStep('run_regen.php')">
Ruleaza TOT pipeline
</button>

<hr>

<h5>Preview fisiere</h5>

<?php

$base = realpath(__DIR__ . "/..");

$files_static = array(
    "controllers.txt",
    "serial_cache.txt"
);

foreach ($files_static as $f)
{
    $path = $base . "/" . $f;

    if (file_exists($path))
    {
        echo '<button class="btn btn-secondary btn-step" onclick="showFile(\''.$f.'\')">'.$f.'</button>';
    }
}

$dir = $base . "/hdd_controlere";

if (is_dir($dir))
{
    echo "<hr>";
    echo "<h6>hdd_controlere</h6>";

    $files = scandir($dir);

    foreach ($files as $file)
    {
        if ($file != "." && $file != "..")
        {
            $relative = "hdd_controlere/" . $file;

            echo '<button class="btn btn-secondary btn-step" onclick="showFile(\''.$relative.'\')">'.$file.'</button>';
        }
    }
}
else
{
    echo "<div class='text-warning'>Folder hdd_controlere nu exista</div>";
}

?>


</div>

<div class="col-md-9">

<h5>Log</h5>

<pre id="log">
Astept comenzi...
</pre>

</div>

</div>

<script>

function runStep(file)
{

fetch("debug_run.php",
{
method:"POST",
headers:
{
'Content-Type':'application/x-www-form-urlencoded'
},
body:"file="+file
})
.then(function(r)
{
return r.text()
})
.then(function(t)
{
document.getElementById("log").textContent=t
})

}

function showFile(file)
{

fetch("debug_file.php?f="+encodeURIComponent(file))
.then(function(r)
{
return r.text()
})
.then(function(t)
{
document.getElementById("log").textContent=t
})

}

</script>

</body>
</html>