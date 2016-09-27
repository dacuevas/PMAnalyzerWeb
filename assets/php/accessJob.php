<?php
#################################################
# accessJob.php
#
# Obtain PMAnalyzer results
#################################################

require "fileCheckUtil.php";

function getFileLinks(&$retHash, $jid) {
    $resdir = $retHash["info"]["fullresultsdir"];
    $retHash["imgs"] =
        array("meangrowthcurves"    => "mean_growthcurves.png",
              "mediangrowthcurves"  => "median_growthcurves.png",
              "rawgrowthcurves"     => array(),
              "densityplots"        => array(),
              "boxplots"            => array(),
              "growthlevels"        => "growthlevels.png");

    $files = scandir($resdir);
    # Find raw growth curves
    # Remove parent and current directory
    $files = array_diff($files, array('..', '.'));
    foreach (glob($resdir."raw_curves*.png") as $filename) {
        # Don't add previous images
        $filename = basename($filename);
        if (in_array($filename, array_values($retHash["imgs"]))) {
            continue;
        }
        $name = preg_replace('/.png/','',$filename);
        $retHash["imgs"]["rawgrowthcurves"][$name] = $filename;
    }
    # Find density plots
    foreach (glob($resdir."density*.png") as $filename) {
        # Don't add previous images
        $filename = basename($filename);
        if (in_array($filename, array_values($retHash["imgs"]))) {
            continue;
        }
        $name = preg_replace('/.png/','',$filename);
        $retHash["imgs"]["densityplots"][$name] = $filename;
    }
    # Find box plots
    foreach (glob($resdir."box*.png") as $filename) {
        # Don't add previous images
        $filename = basename($filename);
        if (in_array($filename, array_values($retHash["imgs"]))) {
            continue;
        }
        $name = preg_replace('/.png/','',$filename);
        $retHash["imgs"]["boxplots"][$name] = $filename;
    }

    # Obtain result text files
    $retHash["txt"] = array(
        0 => array("Raw Growth Curves"                => "raw_curves_".$jid.".txt"),
        1 => array("Growth Parameters (per sample)"   => "logistic_params_sample_".$jid.".txt"),
        2 => array("Logistic Curves (per sample)"     => "logistic_curves_sample_".$jid.".txt"),
        3 => array("Growth Parameters (averaged)"     => "logistic_params_mean_".$jid.".txt"),
        4 => array("Logistic Curves (averaged)"       => "logistic_curves_mean_".$jid.".txt"));
}

#################################################
# Begin
#################################################
# retHash holds information sent back to primary webpage
$retHash = array("status" => 0, "status_msg" => "ok");
# Set up directory paths
$pmdir = $_SERVER["SCRIPT_FILENAME"];
$pmdir = str_replace("/assets/php/accessJob.php", "", $pmdir);
$jid = $_POST["jid"];  # Job ID
$jdir = $pmdir . "/uploads/".$jid."/";
$data = $jdir."data/";
$results = $jdir."results/";

# Check to see if directory exists
if (! file_exists($jdir)) {
    errorOut($retHash, 1, "Job not found");
} elseif (! file_exists($results)) {
    errorOut($retHash, 1, "Job found but no results found");
}

# Set return JSON object
$retHash["info"] = array("fullresultsdir" => $results,
                         "jobid" => $jid,
                         "resultsdir" => "uploads/" . $jid . "/results/");

# Get sample names from statistics file
$statsfile = glob($results."sample_statistics_*.txt")[0];
$snames;
exec("tail -n +2 ".$statsfile." | cut -f 1 | uniq | sort",
    $snames);
$retHash["samplenames"] = $snames;

# Fill in file links for webpage display
getFileLinks($retHash, $jid);

echo json_encode($retHash);
//*/
?>
