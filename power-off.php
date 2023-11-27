<?php
/*** 
 * power-off.php
 * by Wolf I. Butler
 * v. 1.0, Last Updated: 11/21/2023
 * 
 * This simply turns off Main Power on the power management computer.
 * I've found doing this in scripts works better than running remote
 * scripts via a playlist. Upload and run on the Show Runner FPP.
 * 
 * License: GPLv3 
 * See the LICENSE document attached to this distribution.
 * 
 * This script is provided ​“AS IS”. Developer makes no warranties, express or implied, 
 * and hereby disclaims all implied warranties, including any warranty of merchantability 
 * and warranty of fitness for a particular purpose.
 * 
*/

$power = array (
     '10.0.0.33'
);

/*
 ************************************************************************************
 * That's it. Do not edit anything below unless you REALLY know what you are doing. *
 ************************************************************************************
*/

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

foreach ( $power as $ip ) {
     echo "\nShutting down power on $ip...";
     do_get ( "http://$ip/runEventScript.php?scriptName=Power-Off.sh" );
     echo " done.";
}
echo "\nOperation complete!";
?>