<?php
/*** 
 * safe-shutdown.php
 * by Wolf I. Butler
 * v. 1.0, Last Updated: 11/21/2023
 * 
 * This script performs a "Safe" shutdown of the whole system.
 * It first sends shutdown commands to any configured projectors so they are allowed
 * to cool off before the power gets cut. Then it sends the Shutdown command to all
 * FPP instances so they can shut down properly. Then, it shuts down the power.
 * 
 * Note that as of this release, the Info and Tune-To signs and Misc. decorations
 * are controlled by Alexa-enabled smart-plugs. They are not included in this.
 * 
 * License: GPLv3 
 * See the LICENSE document attached to this distribution.
 * 
 * This script is provided ​“AS IS”. Developer makes no warranties, express or implied, 
 * and hereby disclaims all implied warranties, including any warranty of merchantability 
 * and warranty of fitness for a particular purpose.
 * 
*/

//Projectors
$projectors = array (
     '10.0.0.15',
     '10.0.0.16'
);

//FPP Controllers
$controllers = array (
     'K16A-B-Tree' => '10.0.0.10',
     'K40D-PB-1' => '10.0.0.11',
     'K40D-PB-2' => '10.0.0.12',
     'Projector-L' => '10.0.0.15',
     'Projector-R' => '10.0.0.16',
     'Column-Left' => '10.0.0.21',
     'Column-Center' => '10.0.0.22',
     'Column-Right' => '10.0.0.23'
);

//Main power controller(s)
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

echo "\nPlease wait while the system shuts down. This will take less than a minute...\n";

foreach ( $projectors as $ip ) {
     echo "\nShutting down projector on $ip...";
     do_get ( "http://$ip/runEventScript.php?scriptName=PROJECTOR-OFF.sh" );
     echo " done.";
}
echo "\nWaiting for lamps to cool off...\n";
sleep (30);

foreach ( $controllers as $host => $ip ) {
     echo "\nShutting down $host...";
     do_get ( 'http://$ip/api/system/shutdown' );
     sleep ( 2 );
     echo " done.";
}
echo "\nWaiting for shutdown commands to finish...\n";
sleep (15);

foreach ( $power as $ip ) {
     echo "\nShutting down power on $ip...";
     do_get ( "http://$ip/runEventScript.php?scriptName=Power-Off.sh" );
     echo " done.";
}
sleep(1);
echo "\nSafe system shutdown completed!\n\n";

?>