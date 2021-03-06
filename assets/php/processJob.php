<?php
#################################################
# processJob.php
#
# Run PMAnalyzer on data
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
        4 => array("Logistic Curves (averaged)"       => "logistic_curves_mean_".$jid.".txt"),
        5 => array("Metric statistics (per sample)"   => "sample_statistics_".$jid.".txt"),
        6 => array("Metric statistics (per well)"     => "well_statistics_".$jid.".txt"));
}

#################################################
# Begin processing
#################################################
# retHash holds information sent back to primary webpage
$retHash = array("status" => 0, "status_msg" => "ok");

# Add /usr/local/bin to PATH
$pathenv = getenv("PATH");
$pathenv .= ":/usr/local/bin";
putenv("PATH=".$pathenv);

# Get parser type
$parser = $_POST["parser"];

# Check if figures is set
$figs = "";
if (isset($_POST["figs"])) {
    $figs = "-m";
}


# Set up directory paths
$pmdir = $_SERVER["SCRIPT_FILENAME"];
$pmdir = str_replace("/assets/php/processJob.php", "", $pmdir);
$jid = $_POST["jid"];  # Job ID
$jdir = $pmdir . "/uploads/".$jid."/";
$data = $jdir."data/";
$results = $jdir."results/";
$fileCheckScript = "python3 " . $pmdir . "/PMAnalyzer/py/Parsers/FileCheck.py";
$script = $pmdir . "/PMAnalyzer/runPM";
$errLog = $results."errLog.txt";

if (!mkdir($results, 0775, true)) {
    errorOut($retHash, 1, "Failed to create result folder");
}

# Make sure permissions are set correctly. mkdir may not have worked
# correctly due to umask
chmod($results, 0775);

$pflag = "";
# Check if a plate file needs to be used
# Check if a pre-set plate was selected
if ($_POST["plateselect"] != "0") {
    $pfile = $pmdir . "/PMAnalyzer/plates/".$_POST["plateselect"];
    $pflag = "-p ".$pfile;
} else if ($_FILES["platefile"]["error"] != UPLOAD_ERR_NO_FILE) {
    # Check if a plate file was uploaded
    $pf = $_FILES["platefile"];
    # First check if upload was okay
    if ($pf["error"] != UPLOAD_ERR_OK) {
        errorOut($retHash, 1, "Bad upload for ".$pf["name"]);
    }
    $filepath = $pf["tmp_name"];
    $filename = $pf["name"];
    # Check file is empty
    check_file_is_empty($retHash, $filepath);

    # Check for php, exe, or any other characters
    check_file_name($retHash, $filename);

    # Move the file to the directory
    $pfile = $jdir.$filename;
    move_uploaded_file($filepath, $pfile);
    $pflag = "-p ".$pfile;
}

$sflag = "";
# Check if a sample name file needs to be used
if ($_FILES["samplefile"]["error"] != UPLOAD_ERR_NO_FILE) {
    $sf = $_FILES["samplefile"];
    # First check if upload was okay
    if ($sf["error"] != UPLOAD_ERR_OK) {
        errorOut($retHash, 1, "Bad upload for ".$sf["name"]);
    }
    $filepath = $sf["tmp_name"];
    $filename = $sf["name"];
    # Check file is empty
    check_file_is_empty($retHash, $filepath);

    # Check for php, exe, or any other characters
    check_file_name($retHash, $filename);

    # Move the file to the directory
    $sfile = $jdir.$filename;
    move_uploaded_file($filepath, $sfile);
    $sflag = "-s ".$sfile;
}

# Run file format checks
$result;  # Capture result of analysis
exec($fileCheckScript." ".$data." ".$parser. " 2>&1",
     $output,
     $result);

# Check if an error occurred
if ($result) {
    errorOut($retHash, 1, $output);
}

# Run analysis
$result;
exec($script." -i ".$data." -o ".$jid." -d ".$results.
    " -t ".$parser." ".$sflag." ".$figs." -v ".$pflag." > ".$errLog." 2>&1",
     $output,
     $result);

# Check if an error occcurred
if ($result) {
    errorOut($retHash, 1, "An error occurred in the analysis pipeline");
}

/***************************************
 * All files are now put in a zip file
 * *************************************
# Tarball all files
$wd = getcwd()."/";
$tardir = $jdir.$jid."/";
$tarname = "myfiles.tar.gz";
if (!mkdir($tardir, 0775, true)) {
    errorOut($retHash, 1, "Failed to create download folder");
}

# (1) Copy results text files to download folder
# (2) Change to JobID folder
# (3) Create tar.gz file from items in download folder
# (4) Change back to home directory
exec("(cp ".$results."*".$jid.".txt ".$tardir." && cd ".$jdir.
    " && tar -czf results/".$tarname." ".$jid."/ && cd ".$wd.")");
exec("rm -r ".$tardir);
 */

# Zip all files
$wd = getcwd()."/";
$zipdir = $jdir.$jid."/";
$zipname = "myfiles.zip";
if (!mkdir($zipdir, 0775, true)) {
    errorOut($retHash, 1, "Failed to create download folder");
}

# (1) Copy results text files to download folder
# (2) Change to JobID folder
# (3) Create zip file from items in download folder
# (4) Change back to home directory
exec("(cp ".$results."* ".$zipdir." && cd ".$jdir.
    " && zip -r results/".$zipname." ".$jid."/ && cd ".$wd.")");
exec("rm -r ".$zipdir);

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
