<?php
/*** 
 * library.php
 * by Wolf I. Butler
 * 
 * Part of the Oak Hills Lights Web status system.
 * 
 * This is designed to be run on the same network as the rest of the show as it needs
 * access to all of the players and controllers.
 * 
 * License: GPLv3 
 * See the LICENSE document attached to this distribution.
 * 
 * This script is provided ​“AS IS”. Developer makes no warranties, express or implied, 
 * and hereby disclaims all implied warranties, including any warranty of merchantability 
 * and warranty of fitness for a particular purpose.
 * 
*/

//Get data from API
function do_get ( $url ) {
    //Initiate cURL.
    $ch = curl_init($url);

    //Capture returned html:
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );

    //Disable caching:
    curl_setopt( $ch, CURLOPT_FRESH_CONNECT, TRUE );
    
    //Set timeouts to reasonable values:
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt( $ch, CURLOPT_TIMEOUT, 10);

    //Execute the request
    if ( $curlResult = curl_exec($ch) ) {
        $arrReturn = json_decode ( $curlResult, TRUE );
        return $arrReturn;
    }
    return FALSE;
}

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

function ping ( $host ) {
    //Since running ping through exec is SLOW, using cURL instead...
    $ch = curl_init($host);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ( $httpcode >= 200 && $httpcode < 300) return TRUE;
    else return FALSE;
}

?>