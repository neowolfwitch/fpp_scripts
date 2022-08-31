<?php
/***
 * info-config.php
 * 
 * Configuration file for info-matrix.php
 * 
 * This file is only ready on startup, so you need to reboot if you make any changes here.
 * It only sets variables, so it's pointless to actually run it outside of info-matrix.php. 
 *
 */

//Configuration .txt files will go here. FPP likes putting misc. files in the upload folder...
$uploadPath = "/home/fpp/media/upload/";

//Web server address. This will have the playlist.json and playlist.sync files used to
//display the current system status.
$host = 'https://example.com';

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
$playlistFile = "playlist.json";        # Full JSON playlist file on the Web server.
$syncFile = "playlist.sync";            # JSON Sync file ("Now Playing" info) on the Web server.
$overlayName  = "LED+Panels";      # Pixel Overlay Model Name. Verify name in FPP! Use "+" for any spaces. URL Encode any other special chars.
$color = "RAND";            # Text Color (#FF0000) (also names like 'red', 'blue', etc.). Set to RAND for random color for each message.
$font  = "Helvetica";       # Font Name
$size  = 14;                # Font size
$pos   = "R2L";             # Position/Scroll: 'Center', 'L2R', 'R2L', 'T2B', 'B2T'
$pps   = 55;                # Pixels Per Second to Scroll
$antiAlias = true;          # Anti-Alias the text

//Block mode. You should leave this at 1 unless you need to enable advanced functionality.
//Set to 2 for Transparent mode, or 3 for Transparent RGB
$blockMode = 1;

//Idle file. This file contains show information played when a sequence isn't running.
//For example, it can contain show schedule information.
//Separate lines are split using GAP specified above.
//***This file needs to be uploaded to the "Uploads" folder in FPP's file manager.***
//It is loaded on every non-sequence loop iteration, so you can change it on-the-fly if you need to.
$idleFile = "info-idle.txt";

//We have some "Static" light displays before and after our actual musical show. These emulate "old school"
//christmas lights, or just include patterns that we don't need to display any other information about.
//In these cases- we want to display the show information in the "idleFile" (above).
//In order to do this- prefix those sequence file names with the text below.
$staticPrefix = "Static_";

//This is for "Intro" displays with messages displayed DURING a show. We only want to display limited
//information during these as they are short and won't have any music to pull data from.
$introPrefix = "Intro_";

//Logfile. Use this for debugging. Remark-out to stop logging...
# $logFile = '/home/fpp/media/logs/info-matrix.log';

?>