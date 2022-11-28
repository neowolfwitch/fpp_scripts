<?php
/*** 
 * tune-to.php
 * by Wolf I. Butler
 * v. 1.1, Last Updated: 11/21/2022
 * 
 * This script sends the shutdown command to all configured computers/controllers.
 * It should be run before powering the system down, just to safegard the systems.
 * 
 * License: GPLv3 
 * See the LICENSE document attached to this distribution.
 * 
 * This script is provided ​“AS IS”. Developer makes no warranties, express or implied, 
 * and hereby disclaims all implied warranties, including any warranty of merchantability 
 * and warranty of fitness for a particular purpose.
 * 
*/

$controllers = array (
     'K16A-B-Tree' => '10.0.0.10',
     'K40D-PB-1' => '10.0.0.11',
     'K40D-PB-2' => '10.0.0.12',
     'Column-Left' => '10.0.0.21',
     'Column-Center' => '10.0.0.22',
     'Column-Right' => '10.0.0.23',
     'Tune-To' => '10.0.0.25'
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

foreach ( $controllers as $host => $ip ) {
     echo "\nShutting down $host...";
     do_get ( 'http://$ip/api/system/shutdown' );
     sleep ( 2 );
     echo " done.";
}
echo "\nOperation complete!";
?>