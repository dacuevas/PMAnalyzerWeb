<?php
#################################################
# upload.php
#
# Preprocess files to be sure they are safe.
#################################################

require "fileCheckUtil.php";

################################################
# Begin processing
################################################
# retHash holds information sent back to primary webpage
$retHash = array("status" => 0, "status_msg" => "ok");

# Begin checking files
foreach ($_FILES["datafiles"]["error"] as $idx => $error) {
    # First check if upload was okay
    if ($error != UPLOAD_ERR_OK) {
        errorOut($retHash,
            1,
            "Bad upload for ".$_FILES["datafiles"]["name"][$idx]);
    }
    $filepath = $_FILES["datafiles"]["tmp_name"][$idx];
    $filename = $_FILES["datafiles"]["name"][$idx];
    # Check file is empty
    check_file_is_empty($retHash, $filepath);

    # Check for php, exe, or any other characters
    check_file_name($retHash, $filename);
}

# Move files into their appropriate directory
# Use the job id to name the directory
$jid = $_POST["jid"];  # Job ID
$pmdir = $_SERVER["DOCUMENT_ROOT"] . "/PMAnalyzerWeb";
$job_dir = $pmdir . "/uploads/".$jid."/";
$data_dir = $job_dir."/data/";

# Create the directory
if (!mkdir($job_dir, 0775, true)) {
    errorOut($retHash, 1, "Failed to create folders");
}
if (!mkdir($data_dir, 0775, true)) {
    errorOut($retHash, 1, "Failed to create folders");
}
# Make sure permissions are set correctly. mkdir may not have worked
# correctly due to umask
chmod($job_dir, 0775);
chmod($data_dir, 0775);

foreach ($_FILES["datafiles"]["tmp_name"] as $idx => $filename) {
    $name = $_FILES["datafiles"]["name"][$idx];
    move_uploaded_file($filename, "$data_dir/$name");
}

echo json_encode($retHash);
?>
