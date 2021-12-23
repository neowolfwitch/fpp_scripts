<?php
/*** 
 * shutdown.php
 * by Wolf I. Butler
 * v. 1.0, Last Updated: 11/14/2021
 * 
 * The shutdown scripts available in the script repository and plugins all use additional
 * companion scripts run on the remote FPPs. That's really unnecessary since the API has
 * the functionality built in.
 * 
 * So, all this does is step through a provided list of IPs and issue the shutdown command
 * through the API to those FPPs.
 * From the FPP Script Repository. The author was not attributed in that source.
 * 
 * License: CC0 1.0 Universal: This work has been marked as dedicated to the public domain.
 * 
 * This script is provided ​“AS IS”. Developer makes no warranties, express or implied, 
 * and hereby disclaims all implied warranties, including any warranty of merchantability 
 * and warranty of fitness for a particular purpose.
 * 
*/

//*** You must edit the following sections... ***

//Enter all of the FPP IP addresses you want to shut down...
$hosts = array ( 
    '10.0.0.10',
    '10.0.0.11',
    '10.0.0.15',
    '10.0.0.21',
    '10.0.0.22',
    '10.0.0.23'
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    //Execute the request
    if ( $curlResult = curl_exec($ch) ) {
        $arrReturn = json_decode ( $curlResult, TRUE );
        return $arrReturn;
    }
    return FALSE;
}

//Main Loop continuously
foreach ( $hosts as $value )
{
    $url = "http://$value/api/system/shutdown";
    do_get ( $url );
    echo "\nShutting down $value";
}
echo "\nDone.";

?>