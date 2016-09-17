<?php
#################################################
# fileCheckUtil.php
#
# Functions for checking files.
#################################################

function errorOut(&$retHash, $num, $msg) {
    $retHash["status"] = $num;
    $retHash["status_msg"] = $msg;
    echo json_encode($retHash);
    exit($retHash["status"]);
}


function check_file_is_empty(&$retHash, $filename) {
    if (filesize($filename) == 0) {
        errorOut($retHash, 1, "Empty file.");
    }

    return true;

}


function check_file_name(&$retHash, $filename) {
    if (preg_match('/\.(php|exe|cgi|pl|sh|py|jsp)/i', $filename)) {
        errorOut($retHash, 1, "Scripts not allowed. [$filename]");
    }
    elseif (preg_match('/(\+|\$|\@|\#|\^|\&|\*|\!)/', $filename)) {
        errorOut($retHash, 1, "[+ \$ @ # ^ & * !] not allowed in filenames. [$filename]");
    }
    elseif (mb_strlen($filename, "UTF-8") > 225 ) {
        errorOut($retHash, 1, "Filename cannot be bigger than 250 characters.");
    }

    return true;
}
?>
