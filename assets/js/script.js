/*****************************************************************
 * JQuery assignments
*****************************************************************/

// Choose files
$("#mainfiles").change(function(){ chooseFiles(); });

// When files are chosen, attempt to upload
$("#uplink").click(function(e){ uploadFile(e); });

// Submit form link
$("#submit").click(function(e){ processJob(e); });

// Options section
/*
$("#moreopts").click(function(e){
    $("#optsection").slideToggle("slow", function(){
        var stat = $("#optstat");
        if( $(this).is(":visible")) {
            stat.text("click to hide");
        }
        else {
            stat.text("click to show");
        }
    });
});
*/

// Add more samples
$("#addsr").click(function(e){
    var snum = ($("#srtab tr").length / 2) + 1;
    $("#srtab tr:last").after('<tr><td>Name:</td><td><input name="sample' +
                               snum+'" type="text" placeholder="e.g. ecoli"' +
                               '></td></tr>');
    $("#srtab tr:last").after('<tr><td>Replicate:</td><td><input ' +
                              'name="replicate'+snum+'" type="text" ' +
                              'placeholder="e.g. rep1"></td></tr>');
    $("#numsamples").val(snum);
});


/*****************************************************************
 * Callback functions
 *****************************************************************/

/***********************************
 * Reset fields after choosing files
 ***********************************/
function chooseFiles() {
    //$("#uplink").removeClass("not-active");
    $("#uplink").prop("disabled", false);
    $("#uploadstatus").html("");
    $("#status").html("");
}

/***********************************
 * File upload function
 ***********************************/
function uploadFile(e) {
    e.preventDefault();
    var myform = $("#upload")[0];
    var jid = new Date().valueOf();
    jid += Math.floor((Math.random() * 10) + 1);
    $("#jid").val(jid);

    $.ajax({
        url: "assets/php/upload.php",
        type: "POST",
        data: new FormData(myform),
        cache: false,
        contentType: false,
        processData: false
    })
    .done(function(data){
        data = JSON.parse(data);
        clearInterval($analysisInt);
        // Check the upload status
        if (data["status"] == 0) {
            //$("#upbutton").prop("disabled", true)
            $("#uploadstatus").html("OK");
            // Show submit button
            $("#submit").removeClass("not-active");
            $("#uplink").prop("disabled", true);
            //$("#uplink").addClass("not-active");
        }
        else{
            $("#uploadstatus").html(data["status_msg"]);
        }
    })
    .fail(function(data){
        data = JSON.parse(data);
        clearInterval($analysisInt);
        if (data["status"] != 0){
            $("#uploadstatus").html(data["status_msg"]);
        }
    });
    $("#uploadstatus").html("Checking files");
    $timecnt = 0;
    $analysisInt = window.setInterval(function(){
        switch( $timecnt % 3 ) {
        case 0:
            $("#uploadstatus").html("Checking files.");
            break;
        case 1:
            $("#uploadstatus").html("Checking files..");
            break;
        default:
            $("#uploadstatus").html("Checking files...");
        }
    $timecnt++;
    }, 1500);
}

/***********************************
 * Job process function
 ***********************************/
function processJob(e) {
    e.preventDefault();
    var myform = $("#upload")[0];
    $.ajax({
        url: "assets/php/processJob.php",
        type: "POST",
        data: new FormData(myform),
        cache: false,
        contentType: false,
        processData: false
    })
    .done(function(data){
        data = JSON.parse(data);
        clearInterval($analysisInt);
        if (data["status"] == 0) {
            var info = "Done";
            $("#status").html(info);
            $("#uploadstatus").html("");
            showResults(data);
            //$("#uplink").removeClass("not-active");
            $("#uplink").prop("disabled", false);
        }
        else{
            $("#status").html(data["status_msg"]);
            showError("Please see log file");
        }
    })
    .fail(function(data){
        data = JSON.parse(data);
        clearInterval($analysisInt);
        if (data["status"] != 0){
            $("#status").html(data["status_msg"]);
        }
    });
    $("#submit").addClass("not-active");
    $("#status").html("Analyzing data.......");
    $timecnt = 0;
    $analysisInt = window.setInterval(function(){
        switch( $timecnt % 6 ) {
        case 0:
            $("#status").html(".Analyzing data......");
            break;
        case 1:
            $("#status").html("..Analyzing data.....");
            break;
        case 2:
            $("#status").html("...Analyzing data....");
            break;
        case 3:
            $("#status").html("....Analyzing data...");
            break;
        case 4:
            $("#status").html(".....Analyzing data..");
            break;
        default:
            $("#status").html("......Analyzing data.");
        }
    $timecnt++;
    }, 1500);

    var jid = $("#jid").val();
    var resdir = "uploads/"+jid+"/results/";
    var html = '<h2 style="text-align:center">Results</h2>';
    html += '<div style="text-align:center;font-size:10px;font-style:italic;">(will appear below)</div><br/>';
    html += '<div id="jobid">Job ID '+
        jid + ' &#8212; <a target="_blank" href="'+resdir+'errLog.txt"><i>View log</i></a></div><br/>';
    $("#results").html(html);

}

/*************************************************
 * Display results after completion. First, build
 * HTML table for file links. Second, build HTML
 * image links.
 *************************************************/
function showResults(data) {
    // Build results HTML

    //****** Display file links section ******//
    var resdir = data["info"]["resultsdir"];
    var html = '<h2 style="text-align:center">Results</h2>';
    var jid = $("#jid").val();
    html += '<div id="jobid">Job ID '+
        jid + ' &#8212; <a target="_blank" href="'+resdir+'errLog.txt"><i>View log</i></a></div><br/>';
    html += '<table id="resultsfiles">';
    html += '<tr><th style="min-width:400px;">PMAnalyzer files</th><th></th><th></th></tr>';
    for (i in data["txt"]) {
        var fn = data["txt"][i];
        $.each(fn, function(name, loc) {
            html += '<tr><td>'+name+'</td>';
            html += '<td><a class="filelink" target="_blank" href="'+resdir+loc+'">View</a></td>';
            html += '<td><a class="filelink" target="_blank" href="'+resdir+loc+'" download>Download</a></td></tr>';
        });
    }
    // Include zip file
    html += '<tr><td>All files (zip)</td><td></td>';
    html += '<td><a class="filelink" target="_blank" href="'+resdir+'myfiles.zip" download>Download</a></td></tr>';
    html += '</table><br/>';
    $("#results").html(html);

    //****** Display result images ******//
    if ($("input[name=figs]").prop("checked") == true) {
        html += '<div id="imgs">';
        // Present summary figures
        html += '<div class="resheader"><hr><h1>All Data</h1></div>';
        html += '<div id="img">';
        html += '<a class="imga" target="_blank" href="'+resdir+'growthlevels.png"><img src="'+resdir+'growthlevels.png" alt="Growth Levels"></a>';
        html += '<a class="imga" target="_blank" href="'+resdir+'all_median.png"><img src="'+resdir+'all_median.png" alt="All Median Growth Curves"></a>';
        html += '<a class="imga" target="_blank" href="'+resdir+'density_plots_all_samples.png"><img src="'+resdir+'density_plots_all_samples.png" alt="Density Plots"></a>';
        html += '<a class="imga" target="_blank" href="'+resdir+'box_plots_all_samples.png"><img src="'+resdir+'box_plots_all_samples.png" alt="Box Plots"></a>';
        html += '</div><br/>';
        for (i in data["samplenames"]) {
            var sn = data["samplenames"][i];
            html +=  '<div class="resheader"><hr><h1>' + sn + '</h1></div>';
            html += '<div id="img">';
            html += '<a class="imga" target="_blank" href="'+resdir+'raw_curves_'+sn+'.png"><img src="'+resdir+'raw_curves_'+sn+'" alt="Raw Growth Curves"></a>';
            //html += '</div><br/>';
            //html += '<div id="img">';
            html += '<a class="imga" target="_blank" href="'+resdir+'avg_'+sn+'.png"><img src="'+resdir+'avg_'+sn+'" alt="Average Growth Curves"></a>';
            //html += '</div><br/>';
            //html += '<div id="img">';
            html += '<a class="imga" target="_blank" href="'+resdir+'log_'+sn+'.png"><img src="'+resdir+'log_'+sn+'" alt="Logistic Growth Curves"></a>';
            html += '<a class="imga" target="_blank" href="'+resdir+'density_plots_'+sn+'.png"><img src="'+resdir+'density_plots_'+sn+'" alt="Density Plots"></a>';
            html += '<a class="imga" target="_blank" href="'+resdir+'box_plots_'+sn+'.png"><img src="'+resdir+'box_plots_'+sn+'" alt="Box Plots"></a>';
            html += '</div><br/>';
        }
        html += '</div>';
        /*
        html += '<div id="imgs">';
        // Raw growth curves
        for (var fn in data["imgs"]["rawgrowthcurves"]) {
            var src = resdir+data["imgs"]["rawgrowthcurves"][fn];
            html += '<div class="img">';
            //html += '<h2 style="text-align:center;">'+fn+'</h2>';
            html += '<a class="imga" target="_blank" href="'+src+'"><img src="'+src+'" alt="Raw Growth Curves"></a>';
            html += '</div><br/>';
        }
        // Mean growth curves
        /* Not producing this anymore
        var src = resdir+data["imgs"]["meangrowthcurves"];
        html += '<div class="img">';
        html += '<h2 style="text-align:center;">Mean Growth Curves</h2>';
        html += '<a class="imga" target="_blank" href="'+src+'"><img src="'+src+'" alt="Mean Growth Curves"></a> ';
        html += '</div><br/>';
        */
        /*
        // Growth levels
        src = resdir+data["imgs"]["growthlevels"];
        html += '<div class="img">';
        html += '<h2 style="text-align:center;">Growth Levels</h2>';
        html += '<a class="imga" target="_blank" href="'+src+'"><img src="'+src+'" alt="Growth Levels">';
        html += '</div>';
        */
        $("#results").html(html);
    }
}

function showError(msg) {
    var htmlmsg = '<h2 class="errormsg">' + msg + '</h2>';
    var old = $("#results").html();
    $("#results").html(old + htmlmsg)
}

/*************************************************
 * Test function to display results
 *************************************************/
function showResultsTest(data) {
    // Build results HTML
    $("#results").html(data);
}
