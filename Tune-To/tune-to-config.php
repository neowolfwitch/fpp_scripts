<?php
/***
 * tune-to-config.php
 * 
 * Configuration file for tune-to-matrix.php
 * 
 * This file is read on each loop, so you can make changes (such as overrides or info changes) live.
 * It only sets variables, so it's pointless to actually run it outside of tune-to-matrix.php. 
 *
 * Version 4.2, Updated 09/05/2023
 *
 */

//Log activity TRUE/FALSE:
$logFlag = FALSE;
$logFile = '/home/fpp/media/logs/tune-to-matrix.log';

//Configuration .txt files will go here. FPP likes putting misc. files in the /media/upload folder.
//Generally you shouldn't change this unless you have modified FPP's file structure.
$uploadPath = "/home/fpp/media/upload/";

//Show Runner (Master) FPP IP Address or hostname (if resolvable).
//The primary FPP controller that runs the scheduler and playlists.
$master = '10.0.0.5';

//Displayed first in all messages:
$preroll = 'Welcome to my cool light show!';

//Tune-to information. Displayed after the above:
$tune = 'Tune to 99.9 FM';

//Displayed after song information or the above depending on system status.
$postroll = 'Please do not block driveways!';

//Gap displayed between elements. Like ' - ' or ' ... ' or even just ' '
$gap = ' - ';

//Overrides. Uncomment just one of these to override normal displays.
# $override = 'blank';       //Don't display anything on the matrix.
# $override = 'info';        //Only display show info from info-idle.txt 
# $override = 'emergency';   //Only display information in info-emergency.txt

//The following is used to display the text in FPP:
$playlistFile = "playlist.json";   # Full JSON playlist file on the Web server.
$overlayName  = "LED+Panels";      # Pixel Overlay Model Name. Verify name in FPP! Use "+" for any spaces. URL Encode any other special chars.
$color = "RAND";            # Text Color (#FF0000) (also names like 'red', 'blue', etc.). Set to RAND for random color for each message.
$font  = "Helvetica";       # Font Name
$size  = 14;                # Font size
$pos   = "R2L";          # Position/Scroll: 'Center', 'L2R', 'R2L', 'T2B', 'B2T'
$pps   = 64;                # Pixels Per Second to Scroll
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

//Block mode.
//Set to 1 if you are ONLY displaying information on the sign.
//Set to 2 for Transparent mode if you want the text overlaid onto effects from your show.
$blockMode = 3;

//Idle file. This file contains show information played when a sequence isn't running.
//For example, it can contain show schedule information.
//Separate lines are split using GAP specified above.
//***This file needs to be uploaded to the "Uploads" folder in FPP's file manager.***
//It is loaded on every non-sequence loop iteration, so you can change it on-the-fly if you need to.
$idleFile = "info-idle.txt";

//We have some "Static" light sequences before and after our actual musical show. These emulate "old school"
//christmas lights, or just include patterns that we don't need to display any other information about.
//In these cases- we want to display the show information in the "idleFile" (above).
//In order to do this- prefix those sequence file names with the text below.
$staticPrefix = "Static_";

//This is for "Intro" sequences with messages displayed DURING a show. We only want to display limited
//information during these as they are short and won't have any music to pull data from.
$introPrefix = "Intro_";

?>