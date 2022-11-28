<?php
/***
 * info-config.php
 * v.1.2, Last Updated: 11/28/2022
 * 
 * Configuration file for tune-to.php
 * 
 * This file is read on each loop, so you can make changes (such as overrides or info changes) live.
 * It only sets variables, so it's pointless to actually run it outside of info-matrix.php. 
 *
 */

//Log activity TRUE/FALSE:
$logFlag = FALSE;
$logFile = '/home/fpp/media/logs/tune-to.log';

//Displayed in order:
$tune = array ( 'Tune To:', '99.9 FM' );
$hold = 3;     //Number of seconds to hold each message

//The following is used to display the text in FPP:
$overlayName  = "LED+Panels";      # Pixel Overlay Model Name. Verify name in FPP! Use "+" for any spaces. URL Encode any other special chars.
$color = "RAND";            # Text Color (#FF0000) (also names like 'red', 'blue', etc.). Set to RAND for random color for each message.
$font  = "Helvetica";       # Font Name
$size  = 20;                # Font size
$pos   = "Center";          # Position/Scroll: 'Center', 'L2R', 'R2L', 'T2B', 'B2T'
$pps   = 32;                # Pixels Per Second to Scroll
$antiAlias = true;          # Anti-Alias the text

//Random color range values:
//Modify these to adjust the random colors generated if RAND is selected above...
$randValues = array(
'red_low' => 100,
'red_high' => 255,
'green_low' => 100,
'green_high' => 255,
'blue_low' => 0,
'blue_high' => 100 
);

//Block mode. You should leave this at 1 unless you need to enable advanced functionality.
//Set to 2 for Transparent mode, or 3 for Transparent RGB
$blockMode = 1;
?>