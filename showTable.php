<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8"/>
    <title>PMAnalyzer</title>

    <!-- Google web fonts -->
    <link href="https://fonts.googleapis.com/css?family=PT+Sans+Narrow:400,700" rel='stylesheet' />
    <link href='https://fonts.googleapis.com/css?family=Special+Elite' rel='stylesheet' type='text/css'>

    <!-- The main CSS file -->
    <link href="assets/css/style.css" rel="stylesheet" />

    <!-- Internal CSS -->
    <style>
        table{
            margin:auto;
            table-layout:fixed;
        }
        td,th{
            border: 1px solid #ccc;
            text-align: center;
        }
        th{
            background: #1496c1;
            border-color: white;
            color: white;
            padding:10px;
        }
        td{
            font-size: 12px;
            padding: 5px 5px;
        }

    </style>
    <!-- JavaScript Includes -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico" />

</head>

<body>
    <div class="wrapper">
    <h1 class="title">PMAnalyzer</h1>
    <div id="info">
        A web tool to process and analyze bacterial growth curve experimental data from 96-well Multi-phenotype Assay Plates (MAPs) or phenotype microarrays. <br/>
        Upload your data to obtain predicted growth curve parameters and high resolution plots.
    </div>
    <div style="width:100%;text-align:center">
    <br/>
<?php

require "assets/php/fileCheckUtil.php";

#################################################
# Begin
#################################################
# retHash holds information sent back to primary webpage
$retHash = array("status" => 0, "status_msg" => "ok");
# Set up directory paths
$pmdir = $_SERVER["SCRIPT_FILENAME"];
$pmdir = str_replace("showTable.php", "", $pmdir);
$jid = $_GET["jid"];  # Job ID
$fname = $_GET["fname"];  # File name
$title = $_GET["title"];  # Title
$jdir = $pmdir . "/uploads/".$jid."/";
$data = $jdir."data/";
$results = $jdir."results/";
$fpath = $results . $fname;

# Check to see if directory exists
if (! file_exists($jdir)) {
    errorOut($retHash, 1, "Job not found");
} elseif (! file_exists($results)) {
    errorOut($retHash, 1, "Job found but no results found");
} elseif (! file_exists($fpath)) {
    errorOut($retHash, 1, "File does not exist");
}

# Read in file and return array of arrays
echo '<h2>' . $title . '</h2><br/>';
echo '<table>';
$f = fopen($fpath, "r");
$header = true;
while (($line = fgetcsv($f, 0, "\t")) !== false) {
    echo '<tr>';
    foreach ($line as $cell) {
        if ($header) {
            echo '<th>' . htmlspecialchars($cell) . '</th>';
        }
        else {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
    }
    $header = false;
    echo '</tr>';
}
fclose($f);
echo '</table>';
?>

    </div>
    <div class="push"></div>
    </div> <!-- End wrapper -->

    <footer>
        <span id="edwardslab">
            <a class="downloadsection" href="https://edwards.sdsu.edu/research/" title="Edwards Bioinformatics Lab Site" target="_blank">Visit Rob Edwards Lab</a>
        </span>
        <span id="footersection">
            <a class="downloadsection" href="https://github.com/dacuevas/PMAnalyzer" title="Download from GitHub" target="_blank">PMAnalyzer @ GitHub</a>
        </span>
    </footer>



    <script src="assets/js/script.js" type="text/javascript"></script>

</body>
</html>
