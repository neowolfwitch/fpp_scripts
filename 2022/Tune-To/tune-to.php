<?php
/*** 
 * tune-to.php
 * by Wolf I. Butler
 * v.1.2, Last Updated: 11/28/2022
 * 
 * This script just displays text on our smaller "Tune-To" sign.
 * 
 * This is designed to run on an FPP instance that is also controlling a matrix display
 * capable of displaying the text using a pixel overlay model. The simplest setup would be
 * a Raspberry Pi with a P10 Pi Hat connected to a set of configured P10 panels.
 *  
 * This script should be run ONCE in the background using UserCallbackHook.sh which can be found
 * in the FPP Script Repository. Put it in the boot section so it only runs once on startup.
 * 
 * THIS SCRIPT RUNS A CONTINUIOUS LOOP! 
 * YOU MUST END IT WITH ' &' SO IT RUNS IN THE BACKGROUND, OR IT WILL PREVENT FPP FROM BOOTING!!!
 * 
 * Like this (in userCallbackHook.sh):
 *    boot)		
 *      # put your commands here
 *      /bin/php /home/fpp/media/scripts/info-matrix.php &
 *      ;;
 * 
 * This script runs independently of anything else in FPP. The FPP instance should be set to "Player"
 * mode. It does not need to be a remote and does not need to have any sequences or be synced to the
 * show runner/master in any way. You should exclude it from xLights' FPP Connect as-well.
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

//You must edit the tune-config.php configuration script, which should live in the same scripts folder
//as this file. The default path follows. Change if necessary.
//This file is read on every loop- so changes can be made "live" without restarting the server.
$configFile = '/home/fpp/media/scripts/tune-config.php';

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
        sleep ( 1 );    //no reason to beat up fpp over this.
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

//Loop continuously
while (TRUE) {

    //Read this on every loop for "live" configuration changes.
    include ( $configFile );

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

    foreach ( $tune as $outText )
    {
        //Display outText:
        play_text ( $overlayName, $outText, $blockMode, $arrDispConfig );
        sleep ( $hold );
    }
}
?>