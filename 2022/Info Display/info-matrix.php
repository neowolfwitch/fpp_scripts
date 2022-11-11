<?php
/*** 
 * info-matrix.php
 * by Wolf I. Butler
 * v. 3.2, Last Updated: 08/31/2022
 * 
 * This script uses playlist data from the Web server to display the currently running
 * sequence information on an attached matrix using Overlay Models.
 *
 * It specifically needs playlist.json which should be automatically
 * created on the Web server by the sync-playlist.php script.
 *  
 * It also displays in-show information (such as a welcome message and tune-to info.)
 * If there isn't anything playing, it displays show schedule and any other information
 * from a file.
 * 
 * This is designed to run on and FPP instance that is also controlling a matrix display
 * capable of displaying the text using a pixel overlay model. The simplest setup would be
 * a Raspberry Pi with a P10 Pi Hat connected to a set of configured P10 panels.
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
 * License: GPLv3 
 * See the LICENSE document attached to this distribution.
 * 
 * This script is provided ​“AS IS”. Developer makes no warranties, express or implied, 
 * and hereby disclaims all implied warranties, including any warranty of merchantability 
 * and warranty of fitness for a particular purpose.
 * 
*/

//You must edit the info-config.php configuration script, which should live in the same scripts folder
//as this file. The default path follows. Change if necessary.
//This file is read on every loop- so changes can be made "live" without restarting the server.
$configFile = '/home/fpp/media/scripts/info-config.php';

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
    global $idleFile, $preroll, $postroll, $gap;
    $info = "/home/fpp/media/upload/$idleFile";
    if ( is_file ( $info ) ) {
        $arrInfo = file ( $info );
        $outText = null;
        $gapFlag = FALSE;
        foreach ( $arrInfo as $value ) {
            if ( $gapFlag ) $outText .= $gap;
            else $gapFlag = TRUE;
            $outText .= trim ( $value );
        }
    }
    else $outText = $preroll . $gap . $postroll;   //If no file, display what we can.
    return $outText;
}

//Simple logging function. Resets file at each run. Accepts array or text.
function logger ( $logData ) {
    global $logFile, $logFlag;
    if ( ! $logFlag ) return FALSE;
    if ( ! is_string ( $logData ) ) $logData = var_export ( $logData, TRUE );
    $logData = "$logData\n";
    if ( $logFlag ) file_put_contents ( $logFile, $logData, FILE_APPEND );
    else {
        $logFlag = TRUE;
        file_put_contents ( $logFile, $logData );
    }
}

//Holds the loop until the current display output is finished.
//Returns the approx. number of seconds it took for the display to finish.
function display_wait ( $overlay ) {
    $timer = time();
    $loop = TRUE;
    while ( $loop ) {
        logger ( "Waiting for display to be idle...");
        sleep ( 2 );    //no reason to beat up fpp over this.
        $arrStatus = do_get ( "http:/localhost/api/overlays/model/$overlay" );
        if ( isset ( $arrStatus['effectRunning'] ) && $arrStatus['effectRunning'] > 0 ) continue;
        else $loop = FALSE;
    }
    return time() - $timer;
}

//Play text to matrix
function play_text ( $overlayName, $outText, $blockMode, $arrDispConfig ) {
    global $randValues;
    
    //Don't do anything if there is an active effect.
    $arrStatus = do_get ( "http:/localhost/api/overlays/model/$overlayName" );
    if ( isset ( $arrStatus['effectRunning'] ) && $arrStatus['effectRunning'] > 0 ) return FALSE;

    $data = array( 'State' => $blockMode );
    do_put ( "http://localhost/api/overlays/model/$overlayName/state", $data );

    //Randomize colors if RAND was used.
    if ( $arrDispConfig['Color'] == 'RAND' ) {
        
        $total = 0;
        while ( $total < 255 ) {
            //Adjust these to get the color range you want. The above sets a minimum overall brightness.
            $rd = rand ( $randValues['red_low'], $randValues['red_high'] );
            $gr = rand ( $randValues['green_low'], $randValues['green_high'] );
            $bl = rand ( $randValues['blue_low'], $randValues['blue_high'] );
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

    logger ( $arrDispConfig );

    return do_put ( "http://localhost/api/overlays/model/$overlayName/text", $arrDispConfig );
}

//Init:
$displayTime = null;
$arrPlaylist = FALSE;

//Loop continuously
while (TRUE) {

    //Read this on every loop for "live" configuration changes.
    include ( $configFile );

    if ( ! $arrPlaylist ) $arrPlaylist = do_get ( $host . '/' . $playlistFile );    //Only pull once

    $arrDispConfig = array(
        'Color' => $color,
        'Font' => $font,
        'FontSize' => $size,
        'AntiAlias' => $antiAlias,
        'Position' => $pos,
        'PixelsPerSecond' => $pps
    );
    
    //Wait for the display to finish before looping again and return
    //the number of seconds it took to finish.
    $displayTime = display_wait( $overlayName );
    
    //Minimum display/loop time. Used to insure the wrong song info isn't displayed near the end of a song.
    if ( $displayTime < 15 ) $displayTime = 15;

    $arrStatus = do_get ( $master . '/api/system/status' );

    logger ( $arrPlaylist );
    logger ( $arrStatus );

    //Attempt to match currently playing song with the current playlist...
    if ( isset ( $arrStatus['current_song'] ) ) {
        $songTitle = FALSE;
        $songArtist = FALSE;
        $songAlbum = FALSE;
        foreach ( $arrPlaylist as $value ) {
            if ( $value['MediaName'] == $arrStatus['current_song'] ) {
                if ( $value['Title'] ) $songTitle = html_entity_decode ( $value['Title'] );
                if ( $value['Artist'] ) $songArtist = html_entity_decode ( $value['Artist'] );
                if ( $value['Album'] ) $songAlbum = html_entity_decode ( $value['Album']);
                break;
            }
        }
    }
 
    if ( $songTitle ) {
        $outText = null;
        
        //Display overrides...

        if ( isset ( $override ) ) {
            switch ( $override ) {
                case ('blank') : 
                    logger ( 'Not displaying anything. Blank override is in effect.' );
                    sleep (30);
                    break;
                case ('info') :
                    $outText = idle_text();
                    logger ( 'Only displaying show info. Info override is in effect.' );
                    play_text ( $overlayName, $outText, $blockMode, $arrDispConfig );
                    break;
                case ('emergency') :
                    $outText = file_get_contents ( '/home/fpp/media/upload/info-emergency.txt' );
                    logger ( 'Displaying emergency text info. Emergency override is in effect.' );
                    play_text ( $overlayName, $outText, $blockMode, $arrDispConfig );
                    break;
                default :
                    logger ( 'Invalid override specified in config file! Ignoring.' );
                    unset ( $override );
            }
            continue;
        }           

        //Bypass sequence information if a "Static" sequence is playing.
        //Play the show info instead.
        if ( isset ( $staticPrefix ) ) {
            $staticLen = strlen ( $staticPrefix );
            $seq = $arrStatus['current_sequence'];
            if ( substr ( $seq, 0, $staticLen ) == $staticPrefix ) {
                $outText = idle_text();
                play_text ( $overlayName, $outText, $blockMode, $arrDispConfig );
                continue;
                }
        };

        //Bypass sequence information if a "Intro" sequence is playing.
        if ( isset ( $introPrefix ) ) {
            $introLen = strlen ( $introPrefix );
            $seq = $arrStatus['current_sequence'];
            if ( substr ( $seq, 0, $introLen ) == $introPrefix ) {
                $outText = $preroll . $gap . $tune . $gap . $postroll;
                play_text ( $overlayName, $outText, $blockMode, $arrDispConfig );
                continue;
            }
        };

        //Display song information.        
        $timeRemaining = intval ( $arrStatus['seconds_remaining'] );
        if ( $timeRemaining < 0 ) $timeRemaining = 0;   //sanity check.
        logger ( "Song Time Remaining: $timeRemaining" );
        logger ( "Display Time: $displayTime" );

        if ( $timeRemaining < $displayTime ) {
            //Not enough time to complete full text display. Just display $preroll, $tune, and $postroll.
            $outText = $preroll . $gap . $tune . $gap . $postroll;
            play_text ( $overlayName, $outText, $blockMode, $arrDispConfig );
            continue;
        }

        $outText = $preroll . $gap;
        $outText .= $tune . $gap;

        if ( $songTitle ) {
            $outText .= 'Now Playing: ' . $songTitle ;
            if ( $songArtist ) {
                $outText .= ", By: " . $songArtist ;
            }
            //Removing Album from display.
            //if ( $songAlbum ) {
            //    $outText .= ", Album: " . $songAlbum ;
            //}
            $outText .= $gap;
        }
        $outText .= $postroll;
    }
    else {
        //Unable to determine if a sequence is playing. Display general information from file.
        $outText = idle_text();
    }

    //Display outText:
    if ( $outText ) play_text ( $overlayName, $outText, $blockMode, $arrDispConfig );
}
?>