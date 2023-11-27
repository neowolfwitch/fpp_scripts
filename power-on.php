<?php
/*** 
 * power-on.php
 * by Wolf I. Butler
 * v. 1.1, Last Updated: 11/21/2023
 * 
 * This simply turns on Main Power on the power management computer.
 * I've found doing this in scripts works better than running remote
 * scripts via a playlist. Upload and run on the Show Runner FPP.
 *
 * Because some pixels inevitably turn on when power is applied, I created a 1-second
 * long sequence that just sends OFF to the All GRP. I added the URL to launch this
 * after a 30-second delay to give the controllers time to boot. This should turn off
 * any "pilot light" pixels that aren't actually failures.
 *
 * Run the script "non-blocking" in the scheduler so it will run in the background.
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
     echo "\nTurning on Main Power on $ip...";
     do_get ( "http://$ip/runEventScript.php?scriptName=Power-On.sh" );
     echo " done.";
}
sleep(30);   //Give controllers some time to boot.
echo "\nTurning off any pixels that may have turned on when power was applied...";
do_get ( "http://localhost/api/playlist/All-Off.fseq/start" );
echo " done.";
echo "\nOperation complete!";
?>