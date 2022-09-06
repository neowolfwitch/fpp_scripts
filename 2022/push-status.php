<?php
/*** 
 * push-status.php
 * by Wolf I. Butler
 * v. 2.1, Last Updated: 08/31/2022
 * 
 * Pushes the current show status to the Web server.
 * 
 * This should be run on the Show Runner (Master) FPP that is running the show,
 * 
 * This script should be run ONCE in the background using UserCallbackHook.sh which can be found
 * in the FPP Script Repository. Put it in the boot section so it only runs once on startup.
 *
 * THIS SCRIPT RUNS A CONTINUIOUS LOOP! 
 * YOU MUST END IT WITH '&' SO IT RUNS IN THE BACKGROUND, OR IT WILL PREVENT FPP FROM BOOTING!!!
 * 
 * Like this:
 *    boot)		
 *      # put your commands here
 *      /bin/php /home/fpp/media/scripts/push-status.php &
 *      ;;
 * 
 * 
*/

//Just set these...
define ( 'WEB_SERVER', 'https://example.com' );    //Web server URL or IP.
//The following should be a long random key or phrase.
//This must match the "KEY" in the Web server's sync-status.php script.
define ( 'KEY', 'BigLongKeyGoesHere' );   //Base access key.

//Leave the rest alone...

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

# Function to post the data to the REST API
function do_put ( $url, $data ) {

    //Initiate cURL.
    $ch = curl_init($url);

    //Tell cURL that we want to send a PUT request.
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data') );
    
    //Attach our encoded JSON string to the POST fields.
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    //Set timeouts to reasonable values:
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    //Execute the request
    $curlResult = curl_exec($ch);
    
    curl_close($ch);

    return $curlResult;
}

$loop = TRUE;
$checkVal = null;
$minSleep = 15;   //Minimum Seconds
while ( $loop ) {
     $arrStatus = do_get ( "http://localhost/api/system/status" );
     $arrPost['status'] = $arrStatus['status_name'];
     $arrPost['time'] = $arrStatus['time'];
     $arrPost['timestamp'] = time();
     $arrPost['playlist'] = $arrStatus['current_playlist']['playlist'];
     $arrPost['seq'] = $arrStatus['current_sequence'];
     $arrPost['song'] = $arrStatus['current_song'];
     $arrPost['left'] = $arrStatus['seconds_remaining'];
     //Only update the Web server if there has been a change...
     if ( $checkVal != $arrPost['seq'] . $arrPost['song'] ) {
        $checkVal = $arrPost['seq'] . $arrPost['song'];
        $key = md5 ( KEY . time() );    //Passkey for Web server.
        do_put ( WEB_SERVER . "/sync-status.php?key=$key", $arrPost );   
     }
     //Update faster if at the end of a song...
     $left = intval ( $arrPost['left'] );
     if ( $left < $minSleep ) $sleep = $left + 1;   //+1 to give next song a chance to start.
     else $sleep = $minSleep; 
     sleep ( $sleep );
}
?>