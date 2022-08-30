<?php
/*** 
 * push-status.php
 * by Wolf I. Butler
 * v. 2.0, Last Updated: 08/23/2022
 * 
 * Pushes the current show status to the Web server.
 * 
 * This should be run on the Show Runner (Master) FPP that is running the show,
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
 *      /bin/php /home/fpp/media/scripts/push-status.php &
 *      ;;
 * 
 * 
*/

//Just set these...
define ( 'WEB_SERVER', 'https://oakhillslights.com' );    //Web server URL or IP.
define ( 'KEY', 'ahWo6yohkoh2Foec4caex0eeh9iebohC' );   //Base access key. Must match key on Web server.

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
$sleep = 10;   //Minimum Seconds
while ( $loop ) {
     sleep ( $sleep );
     $arrStatus = do_get ( "http://localhost/api/system/status" );
     $arrPost['status'] = $arrStatus['status_name'];
     $arrPost['time'] = $arrStatus['time'];
     $arrPost['playlist'] = $arrStatus['current_playlist']['playlist'];
     $arrPost['seq'] = $arrStatus['current_sequence'];
     $arrPost['song'] = $arrStatus['current_song'];
     $arrPost['left'] = $arrStatus['seconds_remaining'];
     $key = md5 ( KEY . time() );    //Passkey for Web server.
     do_put ( WEB_SERVER . "/sync-status.php?key=$key", $arrPost );
}
?>