#!/usr/bin/php
<?php
/*** 
 * info-matrix.php
 * by Wolf I. Butler
 * v. 1.2, Last Updated: 9/29/2021
 * (Shortented display if there isn't enough song time left to display full message.)
 * (Bug fixes.)
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
 * This script should be run in the background from cron, and should not be run from a playlist
 * unless that is your only option. It will attempt to insure that only one insance of it is
 * running, as it never exits and multiple instances will cause problems.
 * 
 * To run from cron, run 'crontab -e' from a command-line shell and enter and save:
 * @reboot /bin/php ~/media/scripts/info-matrix.php &
 * 
 * If you need to kill it for any reason, the PID will be in: /tmp/info-matrix.lock
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
define ( "GAP", "  -  " );  //You need something here. At least a space, " ", or everything will run together.

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
function do_put($url, $data) {

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
    $curlResult = curl_exec($ch);
    
    curl_close($ch);

    return $curlResult;
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

    if ( $curlResult = curl_exec($ch) ) {
        $curlJSON = json_decode ( $curlResult, TRUE );
        return $curlJSON;
    }
    return FALSE;
}

//Get current fpp status
function fpp_status () {
    //Using localhost for this. 
    //If displaying on a remote FPP, it doesn't need to be a Remote or synced.

    $ch = curl_init ( "http://localhost/api/fppd/status" );

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    
    //Set timeouts to reasonable values:
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $curlResult = curl_exec($ch);

    $curlJSON = json_decode ( $curlResult, TRUE );

    return $curlJSON;
}

//Pull MP3 metadata from the API
function mp3_meta ( $mp3 ) {

    $mp3 = rawurlencode ( $mp3 );
    //Using localhost to pull mp3 data.
    $ch = curl_init ( "http://localhost/api/media/$mp3/meta" );

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    
    //Set timeouts to reasonable values:
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $curlResult = curl_exec($ch);

    $curlJSON = json_decode ( $curlResult, TRUE );

    //Only returning tags...
    return $curlJSON['format']['tags'];
}

//Check to see if the model is currently active.
function is_active ( $host, $name ) {
    $arrStatus = do_get ( "http://$host/api/overlays/model/$name" );
    if ( $arrStatus['isActive'] ) return TRUE;
    else return FALSE;
}

//Check to see if the model is currently running an effect (our text).
function effect_running ( $host, $name ) {
    $arrStatus = do_get ( "http://$host/api/overlays/model/$name" );
    if ( $arrStatus['effectRunning'] ) return TRUE;
    else return FALSE;
}

//Clear the display. 
function clear_display ( $host, $name ) {
    
    $ch = curl_init ( "http://$host/api/overlays/model/$name/clear" );

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    
    //Set timeouts to reasonable values:
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    curl_exec($ch); 
    curl_close($ch);
}

//Play text to matrix
function play_text ( $host, $name, $outText, $blockMode, $arrDispConfig, $resetDisplay ) {
    global $timer;

    if ( $resetDisplay ) {
        clear_display ( $host, $name );
        $data = array( 'State' => 0 );
        do_put("http://$host/api/overlays/model/$name/state", json_encode($data));  //Set state to 0/Off
    }
    else {
        //Don't do anything if there is an active effect.
        if ( is_active ( $host, $name ) ) return FALSE;
    }

    $data = array( 'State' => $blockMode );
    do_put("http://$host/api/overlays/model/$name/state", json_encode($data));

    //Put this inside the display function so new colors are picked on every iteration...
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
    $timer = time();    //Start a new timer.
    return do_put("http://$host/api/overlays/model/$name/text", json_encode($arrDispConfig));
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

    $fppStatus = fpp_status();

    if ( $fppStatus['status'] ) {
        //fpp is running and playing a sequence.
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
            
            $arrMediaInfo = mp3_meta ( $mp3 );

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
            play_text ( $host, $name, $outText, $blockMode, $arrDispConfig, $resetDisplay );
            sleep ($sleepTime);
            continue;
        }
    }
    else {
        //fpp is not playing a sequnce. Display general information from file.
        $info = "/home/fpp/media/upload/$idleFile";
        if ( is_file ( $info ) ) {
            $arrInfo = file ( $info );
            $outText = null;
            $gapFlag = FALSE;
            foreach ( $arrInfo as $value ) {
                if ( $gapFlag ) $outText .= GAP;
                $outText .= trim ( $value );
                $gapFlag = TRUE;
            }
        }
        else $outText = PREROLL . GAP . POSTROLL;

        play_text ( $host, $name, $outText, $blockMode, $arrDispConfig, $resetDisplay );
        sleep ($sleepTime);
        continue;
    }

    //Default action if nothing changed...
    if ( $outText ) play_text ( $host, $name, $outText, $blockMode, $arrDispConfig, $resetDisplay );
    sleep ($sleepTime);
}
?>