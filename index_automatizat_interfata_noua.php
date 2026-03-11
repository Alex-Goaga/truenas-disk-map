<!DOCTYPE html>
<html>
<head>
    <title>Generare Automata SES (cu/fara SMART)</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .btn-large {
            height: 150px;
            font-size: 1.5rem;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="container mt-5">
    <h3 class="mb-4 text-center">🔧 Generare completă fisiere SES</h3>

    <?php
    if (isset($_GET['mod'])) {
        $mod = $_GET['mod'];
        echo "<div class='alert alert-info text-center'>Executare mod: <strong>$mod</strong></div><hr>";

        ob_start();  // capturam tot output-ul

        echo "<h5>Pasul 1: Curatare fisiere anterioare</h5>";
        include("clean_hdd_files.php");

        echo "<h5>Pasul 2: Detectare controllere</h5>";
        include("detect_controllers.php");

        echo "<h5>Pasul 3: Generare fisiere HDD</h5>";
        include("generate_hdd_files.php");

        echo "<h5>Pasul 4: Asociere Serial ↔ Device</h5>";
        include("associate_devices.php");

        echo "<h5>Pasul 5: Generare SES</h5>";
        include("generate_ses_smart_files.php");
		
		echo "<h5>Pasul 6: Generare Lista Discuri nefolosite</h5>";
        include("gen_disk_unused_api.php");

		echo "<h5>Pasul 7: Generare Lista per pool</h5>";
        include("gen_disk_per_pool_api.php");



        $output = ob_get_clean(); // luam tot outputul
        echo "<hr><pre>$output</pre>";

        echo "<div class='text-center mt-4'>
                <a href='ses_mode_selector.php' class='btn btn-secondary'>⬅️ Înapoi</a>
                <a href='index_final.php' class='btn btn-success ml-3'>➡️ Mergi la Interfata Finala</a>
              </div>";
        exit;
    }
    ?>

    <div class="row">
        <div class="col-md-6 text-center mb-3">
            <a href="?mod=cu_smart" class="btn btn-outline-danger btn-block btn-large">
                Rulare COMPLETA<br> CU verificare SMART (dureaza in functie de numarul de discuri)
            </a>
        </div>
    </div>

</body>
</html>
