<?php
/*** 
 * info-matrix.php
 * by Wolf I. Butler
 * v. 1.4, Last Updated: 10/05/2021
 * (Added Static display code.)
 * 
 * This script extracts and displays song information from a running FPP sequence.
 * It also displays in-show information (such as a welcome message and tune-to info.)
 * If there isn't anything playing, it displays show schedule and any other information
 * from a file.
 *  
 * This must be run on an FPP install that will be playing sequences with media
 * as it needs to know what media files are playing. The display host needs to have a matrix
 * configured with a pixel overlay model, but otherwise does not need to be running any
 * sequences. It can be in Player or Remote mode, as long as FPPD is running.
 * 
 * This script should be run in the background using UserCallbackHook.sh which can be found
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
 * If you need to kill it for any reason, the PID will be in: /tmp/show-status.lock
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
define ( "POSTROLL", '*** Please do not block driveways or traffic. ***' );
define ( "GAP", "  -  " );

//The following is used to display the text in FPP:
$host  = "10.0.0.20";       # Host/ip of the FPP instance with the matrix. This can be localhost, an IP address, or a resolvable host name.
$name  = "LED+Panels";      # Pixel Overlay Model Name. Verify name in FPP! Use "+" for any spaces. URL Encode any other special chars.
$color = "RAND";            # Text Color (#FF0000) (also names like 'red', 'blue', etc.). Set to RAND for random color for each message.
$font  = "Helvetica";       # Font Name
$size  = 14;                # Font size
$pos   = "R2L";             # Position/Scroll: 'Center', 'L2R', 'R2L', 'T2B', 'B2T'
$pps   = 32;                # Pixels Per Second to Scroll
$antiAlias = true;          # Anti-Alias the text

//Block mode. You should leave this at 1 unless you need to enable advanced functionality.
//Set to 2 for Transparent mode, or 3 for Transparent RGB
$blockMode = 1;

//Sleep time. This is a loop delay in seconds to prevent this script from overloading FPP.
//It should always be at least 1 second, although it should be set higher if you notice performance
//or display issues (including the wrong information for a sequence). Default is 5 seconds.
$sleepTime = 5; //Seconds

//Idle file. This file contains show information played when a sequence isn't running.
//For example, it can contain show schedule information.
//Separate lines are split using GAP specified above.
//***This file needs to be uploaded to the "Uploads" folder in FPP's file manager.***
//It is loaded on every non-sequence loop iteration, so you can change it on-the-fly if you need to.
$idleFile = "info-matrix.txt";

//We have some "Static" light displays before and after our actual musical show. These emulate "old school"
//christmas lights, or just include patterns that we don't need to display any other information about.
//In these cases- we want to display the show information in the "idleFile" (above).
//In order to do this- I am prefixing them with the text below.
$staticPrefix = "Static_";

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

//Check to see if this script is already running in another process. Exit if it is.
$lockFile = sys_get_temp_dir() . '/info-matrix.lock';
if ( is_file ( $lockFile ) ) {
    $pid = file_get_contents($lockFile);
    if (posix_getsid($pid) === false) {
        file_put_contents($lockFile, getmypid()); // create lockfile
    } 
    else {
        echo "\nAnother instance of this script is already running!\nAborting this process...\n";
        exit;
    }
}
else file_put_contents($lockFile, getmypid()); // create lockfile

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
function play_text ( $host, $name, $outText, $blockMode, $arrDispConfig, $resetDisplay ) {
    global $timer, $displayTime;

    if ( $resetDisplay ) {
        //Attempt to clear the display.
        do_get ( "http://$host/api/overlays/model/$name/clear" );
        do_put ( "http://$host/api/overlays/model/$name/state", array( 'state' => 0 ) );
    }
    else {
        //Don't do anything if there is an active effect.
        $arrStatus = do_get ( "http://$host/api/overlays/model/$name" );
        if ( $arrStatus['effectRunning'] ) return FALSE;
    }

    $data = array( 'State' => $blockMode );
    do_put ( "http://$host/api/overlays/model/$name/state", $data );

    //Randomize colors if RAND was used.
    if ( $arrDispConfig['Color'] == 'RAND' ) {
        
        $total = 0;
        while ( $total < 255 ) {
            //Setting minimum color brightness (~33%)
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
    if ( $timer ) $displayTime = time() - $timer;
    $timer = time();    //Start a new timer.
    return do_put ( "http://$host/api/overlays/model/$name/text", $arrDispConfig );
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

    $resetDisplay = FALSE;
    $fppStatus = do_get( "http://localhost/api/fppd/status" );

    if ( $fppStatus['fppd'] ) {
        //fpp is running and playing a sequence.
        
        //Bypass sequence information if a "Static" sequence is playing.
        if ( isset ( $staticPrefix ) ) {
            $staticLen = strlen ( $staticPrefix );
            $seq = $fppStatus['current_sequence'];
            if ( substr ( $seq, 0, $staticLen ) == $staticPrefix ) {
                $outText = idle_text();
                play_text ( $host, $name, $outText, $blockMode, $arrDispConfig, $resetDisplay );
                sleep ($sleepTime);
                continue;
            }
        };

        //Display song information if possible.
        $mp3 = $fppStatus['current_song'];
        $mediaFile = '/home/fpp/media/music/' . $mp3;
        $timeRemaining = intval ( $fppStatus['seconds_remaining'] );

        if ( $displayTime ) {
            if ( $timeRemaining < $displayTime ) {
                if ( $timeRemaining <= $sleepTime ) {
                    //Don't do anything. Song will be done before sleepTime is over.
                    sleep (1);
                    continue;
                }
                //Not enough time to complete full text display. Just display Preroll and Tune.
                $outText = PREROLL . GAP . TUNE;
                play_text ( $host, $name, $outText, $blockMode, $arrDispConfig, $resetDisplay );
                sleep ($sleepTime);
                continue;
            }
        }

        if ( $lastFile != $mediaFile ) {
            //File change. Try to get new meta data.
            $songTitle = FALSE;
            $songArtist = FALSE;
            $songAlbum = FALSE;
            $lastFile = $mediaFile;
            
            $mp3URL = rawurlencode ( $mp3 );
            $arrMeta = do_get ( "http://localhost/api/media/$mp3URL/meta" );
            $arrMediaInfo = $arrMeta['format']['tags'];

            if ( $songTitle = $arrMediaInfo['title'] ) {
                if ( $songArtist = $arrMediaInfo['artist'] ) {
                    $songAlbum = $arrMediaInfo['album'];
                }
                elseif ( $songArtist = $arrMediaInfo['album_artist'] ) {
                    $songAlbum = $arrMediaInfo['album'];
                }
            }

            if ( ! $songTitle ) {
                $arrMP3 = explode ( ".", $mp3 );
                $songTitle = $arrMP3[0];    //Default to MP3 name.

                //Assuming no MP3 tags found. Try to retrieve song information text file.
                $info = "/home/fpp/media/upload/$songTitle.txt";
                if ( is_file ( $info ) ) {
                    $arrInfo = file ( $info );
                    foreach ( $arrInfo as $index => $value ) {
                        //Doing this way to limit errors if the file isn't formatted correctly.
                        if ($index == 0) $songTitle = trim ( $value );
                        if ($index == 1) $songArtist = trim ( $value );
                        if ($index == 2) $songAlbum = trim ( $value );
                        if ($index == 3) break;
                    }
                }
            }

            $outText = PREROLL . GAP;
            $outText .= TUNE . GAP;
            if ( $songTitle ) {
                $outText .= 'Now Playing: ' . $songTitle ;
                if ( $songArtist ) $outText .= ", By: " . $songArtist ;
                if ( $songAlbum ) $outText .= ", Album: " . $songAlbum ;
                $outText .= GAP;
            }
            $outText .= POSTROLL;
            $resetDisplay = TRUE;   //Display new media information immediately when available.
        }

    }
    else {
        //fpp is not playing a sequnce. Display general information from file.
        $outText = idle_text();
    }

    //Display outText:
    if ( $outText ) play_text ( $host, $name, $outText, $blockMode, $arrDispConfig, $resetDisplay );
    sleep ($sleepTime);
}
?>