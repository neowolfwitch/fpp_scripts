<?php
/*** 
 * projectors-on.php
 * by Wolf I. Butler
 * v. 1.0, Last Updated: 11/21/2023
 * 
 * This simply turns on remote projectors, using the Projector Control Plug-In 
 * Upload and run on the Show Runner FPP.
 * 
 * License: GPLv3 
 * See the LICENSE document attached to this distribution.
 * 
 * This script is provided ​“AS IS”. Developer makes no warranties, express or implied, 
 * and hereby disclaims all implied warranties, including any warranty of merchantability 
 * and warranty of fitness for a particular purpose.
 * 
*/

$projectors = array (
     '10.0.0.15',
     '10.0.0.16'
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

foreach ( $projectors as $ip ) {
     echo "\nStarting up projector on $ip...";
     do_get ( "http://$ip/runEventScript.php?scriptName=PROJECTOR-ON.sh" );
     echo " done.";
}
echo "\nOperation complete!";
?>