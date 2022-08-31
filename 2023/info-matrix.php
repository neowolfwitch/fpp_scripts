<?php
/*** 
 * info-matrix.php
 * by Wolf I. Butler
 * v. 3.0, Last Updated: 08/30/2022
 * 
 * Changes:
 *  Now uses playlist and sync data from the Web server, instead of the FPP show runner/master.
 *  This is to limit the load on the FPP Master, since the needed data is already being
 *  pushed to the Web server to display the playlist and Now Playing. This also greatly
 *  simplifies processing for display on the matrix.
 *  Most of the script has been re-written to simplify it.
 *  
 * This script uses playlist and sync data from the Web server to display the currently running
 * sequence information on an attached matrix using Overlay Models.
 *
 * It specifically needs playlist.json and playlist.sync, which should be automatically
 * created on the Web server by the sync-playlist.php and sync-status.php scripts.
 * The data for these files is pushed by the FPP Showrunner/Master using push-playlist.php 
 * and push-status.php
 *  
 * It also displays in-show information (such as a welcome message and tune-to info.)
 * If there isn't anything playing, it displays show schedule and any other information
 * from a file.
 * 
 * This is designed to run on and FPP instance that is also controlling a matrix display
 * capable of displaying the text using a pixel overlay model. The simplest setup would be
 * a Raspberry Pi with a P10 Pi Hat connected to a set of configured P10 panels.
 * 
 * Previous versions interacted directly with the Show Runner and required network access to the
 * show network. This version (3.x+) only requires Internet access to reach the Web server
 * used to display the playlist and Now Playing to the public.
 * 
 * If you are not running a Web server, you should use an older 2.x version of this script. 
 * 
 * This script should be run ONCE in the background using UserCallbackHook.sh which can be found
 * in the FPP Script Repository. Put it in the boot section so it only runs once on startup.
 * 
 * THIS SCRIPT RUNS A CONTINUIOUS LOOP! 
 * YOU MUST END IT WITH ' &' SO IT RUNS IN THE BACKGROUND, OR IT WILL PREVENT FPP FROM BOOTING!!!
 * 
 * Like this:
 *    boot)		
 *      # put your commands here
 *      /bin/php /home/fpp/media/scripts/info-matrix.php &
 *      ;;
 * 
 * This script runs independently of anything else in FPP. The FPP instance should be set to "Player"
 * mode. It does not need to be a remote and does not need to have any sequences or be synced to the
 * show runner/master in any way.
 * 
 * This script uses code from "PixelOverlay-ScrollingText.php"
 * From the FPP Script Repository. The author was not attributed in that source.
 * 
 * License: CC0 1.0 Universal: This work has been marked as dedicated to the public domain.
 * 
 * This script is provided ​“AS IS”. Developer makes no warranties, express or implied, 
 * and hereby disclaims all implied warranties, including any warranty of merchantability 
 * and warranty of fitness for a particular purpose.
 * 
*/

//*** You must edit the following sections... ***

//PREROLL plays first, and might contain a welcome message.
//TUNE should be your tune-to information. It plays after PREROLL.
//Song information, if available, plays after TUNE.
//POSTROLL plays after the above. I use this for reminder/courtesy info.
//GAP is what to put between each element. It can just be spaces, or something like "... " or " - " or " * * * ".
define ( "PREROLL", 'Welcome to Oak Hills Lights!' );
define ( "TUNE", 'Tune To: 103.7 FM' );
define ( "POSTROLL", 'Please do not block driveways or traffic.' );
define ( "GAP", "  -  " );

//The following is used to display the text in FPP:
$host  = "https://oakhillslights.com";  # Host/ip of the Web server with playlist.json and playlist.sync files.
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

//Sleep time. This is a update delay in seconds.
//It should always be at least 1 second, although it should be set higher if you notice performance
//or display issues (including the wrong information for a sequence). Default is 5 seconds.
$sleepMin = 5; //Seconds

//Idle file. This file contains show information played when a sequence isn't running.
//For example, it can contain show schedule information.
//Separate lines are split using GAP specified above.
//***This file needs to be uploaded to the "Uploads" folder in FPP's file manager.***
//It is loaded on every non-sequence loop iteration, so you can change it on-the-fly if you need to.
$idleFile = "info-matrix.txt";

//We have some "Static" light displays before and after our actual musical show. These emulate "old school"
//christmas lights, or just include patterns that we don't need to display any other information about.
//In these cases- we want to display the show information in the "idleFile" (above).
//In order to do this- I am prefixing those file names with the text below.
$staticPrefix = "Static_";

//This is for "Intro" displays with messages displayed DURING a show. We only want to display limited
//information during these as they are short and won't have any music to pull data from.
$introPrefix = "Intro_";

/*
* If a song has no MP3 tags, the script looks in the Uploads folder for a TXT
* file with the same name as the MP3. This file should contain three lines of text:
* Title
* Artist
* Album
* 
* For example: Little Drummer Boy Live.txt :
* Little Drummer Boy
* for King & Country
* Live Performance
* 
* If you don't have specific information, leave that line blank (with a CR/LF).
* If there is no .TXT file, the name of the MP3 is displayed as the Title.
*/

/*
 ************************************************************************************
 * That's it. Do not edit anything below unless you REALLY know what you are doing. *
 ************************************************************************************
*/

# Function to post the data to the REST API
function do_put ( $url, $data ) {

    $data = json_encode($data);     //The API uses JSON.
    //Initiate cURL.
    $ch = curl_init($url);
    //Tell cURL that we want to send a PUT request.
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    
    //Attach our encoded JSON string to the PUT fields.
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    //Set the content type to application/json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //Set timeouts to reasonable values:
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    //Execute the request
    if ( $curlResult = curl_exec($ch) ) {
        $arrReturn = json_decode ( $curlResult, TRUE );
        return $arrReturn;
    }
    return FALSE;
}

//Get data from API
function do_get ( $url ) {
    //Initiate cURL.
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    
    //Set timeouts to reasonable values:
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    //Execute the request
    if ( $curlResult = curl_exec($ch) ) {
        $arrReturn = json_decode ( $curlResult, TRUE );
        return $arrReturn;
    }
    return FALSE;
}

//Load idle text display from file...
function idle_text () {
    global $idleFile;
    $info = "/home/fpp/media/upload/$idleFile";
    if ( is_file ( $info ) ) {
        $arrInfo = file ( $info );
        $outText = null;
        $gapFlag = FALSE;
        foreach ( $arrInfo as $value ) {
            if ( $gapFlag ) $outText .= GAP;
            else $gapFlag = TRUE;
            $outText .= trim ( $value );
        }
    }
    else $outText = PREROLL . GAP . POSTROLL;   //If no file, display what we can.
    return $outText;
}

//Play text to matrix
function play_text ( $overlayName, $outText, $blockMode, $arrDispConfig, $resetDisplay ) {
    global $timer, $sleepTime, $displayTime;

    if ( $resetDisplay ) {
        //Attempt to clear the display.
        do_get ( "http://localhost/api/overlays/model/$overlayName/clear" );
        do_put ( "http://localhost/api/overlays/model/$overlayName/state", array( 'state' => 0 ) );
    }
    else {
        //Don't do anything if there is an active effect.
        $arrStatus = do_get ( "http:/localhost/api/overlays/model/$overlayName" );
        if ( isset ( $arrStatus['effectRunning'] ) && $arrStatus['effectRunning'] > 0 ) return FALSE;
    }

    $data = array( 'State' => $blockMode );
    do_put ( "http://localhost/api/overlays/model/$overlayName/state", $data );

    //Randomize colors if RAND was used.
    if ( $arrDispConfig['Color'] == 'RAND' ) {
        
        $total = 0;
        while ( $total < 255 ) {
            //Setting minimum color brightness. Roughly 1/3 total.
            $rd = rand ( 0, 255 );
            $gr = rand ( 0, 255 ); 
            $bl = rand ( 0, 255 );
            $total = $rd + $gr + $bl;
        }

        $rd = strval ( dechex ( $rd ) );
        $gr = strval ( dechex ( $gr ) ); 
        $bl = strval ( dechex ( $bl ) );

        //Pad for color codes
        if ( strlen ( $rd ) < 2 ) $rd = '0' . $rd;
        if ( strlen ( $gr ) < 2 ) $gr = '0' . $gr;
        if ( strlen ( $bl ) < 2 ) $bl = '0' . $bl;
        $arrDispConfig['Color'] = '#' . $rd . $gr . $bl;
    }
    
    $arrDispConfig['Message'] = $outText;

    if ( $timer ) {
        $displayTime = time() - $timer;
        $timer = time();
    }
    else $timer = time();    //Start a new timer.

    if ( $displayTime > $sleepTime ) $sleepTime = $displayTime;
    echo "\nDisplay time: $displayTime";
    echo "\nSleep Time: $sleepTime\n";
    print_r ( $arrDispConfig );     //Debugging.
    return do_put ( "http://localhost/api/overlays/model/$overlayName/text", $arrDispConfig );
}

//Init:
$lastFile = null;
$timer = FALSE;
$displayTime = FALSE;

$arrDispConfig = array(
    'Color' => $color,
    'Font' => $font,
    'FontSize' => $size,
    'AntiAlias' => $antiAlias,
    'Position' => $pos,
    'PixelsPerSecond' => $pps
);

//Loop continuously
while (TRUE) {

    $arrPlaylist = do_get ( $host . '/' . $playlistFile );
    $arrStatus = do_get ( $host . '/' . $syncFile );
    $sleepTime = $sleepMin; //Default

    //Attempt to match currently playing song with the current playlist...
    if ( isset ( $arrStatus['song'] ) ) {
        $songTitle = FALSE;
        $songArtist = FALSE;
        $songAlbum = FALSE;
        foreach ( $arrPlaylist as $value ) {
            if ( $value['MediaName'] == $arrStatus['song'] ) {
                if ( $value['Title'] ) $songTitle = html_entity_decode ( $value['Title'] );
                if ( $value['Artist'] ) $songArtist = html_entity_decode ( $value['Artist'] );
                if ( $value['Album'] ) $songAlbum = html_entity_decode ( $value['Album']);
                break;
            }
        }
    }

    $resetDisplay = FALSE;
 
    if ( $songTitle ) {
        $outText = null;
        
        //Display overrides...

        //Don't display anything if "display_blank.txt" exists.
        //Use this if you don't want anything displayed at all.
        if ( is_file ( '/home/fpp/media/upload/display_blank.txt') ) {
            sleep (30);
            continue;
        };

        //Only display show info if "display_info.txt" file is found.
        //This prevents the display of sequence information for testing.
        if ( is_file ( '/home/fpp/media/upload/display_info.txt') ) {
            $outText = idle_text();
            play_text ( $overlayName, $outText, $blockMode, $arrDispConfig, $resetDisplay );
            sleep (30);
            continue;
        };

        //Bypass sequence information if a "Static" sequence is playing.
        //Play the show info instead.
        if ( isset ( $staticPrefix ) ) {
            $staticLen = strlen ( $staticPrefix );
            $seq = $arrStatus['seq'];
            if ( substr ( $seq, 0, $staticLen ) == $staticPrefix ) {
                $outText = idle_text();
                play_text ( $overlayName, $outText, $blockMode, $arrDispConfig, $resetDisplay );
                sleep (30);
                continue;
                }
        };

        //Bypass sequence information if a "Intro" sequence is playing.
        if ( isset ( $introPrefix ) ) {
            $introLen = strlen ( $introPrefix );
            $seq = $arrStatus['seq'];
            if ( substr ( $seq, 0, $introLen ) == $introPrefix ) {
                $outText = PREROLL . GAP . TUNE;
                play_text ( $overlayName, $outText, $blockMode, $arrDispConfig, $resetDisplay );
                sleep ($sleepTime);
                continue;
            }
        };

        //Display song information.        
        $timeRemaining = intval ( ( $arrStatus['timestamp'] + $arrStatus['left'] ) - time() );
        if ( $timeRemaining < 0 ) $timeRemaining = 0;   //sanity check.
        echo "\nSong Time Remaining: $timeRemaining\n";

        if ( $displayTime ) {
            if ( $timeRemaining < $displayTime ) {
                if ( $timeRemaining <= $sleepTime ) {
                    //Don't do anything. Song will be done before sleepTime is over.
                    sleep (1);
                    continue;
                }
                //Not enough time to complete full text display. Just display Preroll and Tune.
                $outText = PREROLL . GAP . TUNE;
                play_text ( $overlayName, $outText, $blockMode, $arrDispConfig, $resetDisplay );
                sleep ($sleepTime);
                continue;
            }
        }

        $outText = PREROLL . GAP;
        $outText .= TUNE . GAP;

        if ( $songTitle ) {
            $outText .= 'Now Playing: ' . $songTitle ;
            if ( $songArtist ) {
                $outText .= ", By: " . $songArtist ;
            }
            if ( $songAlbum ) {
                $outText .= ", Album: " . $songAlbum ;
            }
            $outText .= GAP;
        }
        $outText .= POSTROLL;
        $resetDisplay = TRUE;   //Display new media information immediately when available.
    }
    else {
        //fpp is not playing a sequnce. Display general information from file.
        $outText = idle_text();
    }

    //Display outText:
    if ( $outText ) play_text ( $overlayName, $outText, $blockMode, $arrDispConfig, $resetDisplay );
    sleep ($sleepTime);
}
?>