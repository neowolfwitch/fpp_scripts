<?php
/*** 
 * fpp-sync.php
 * by Wolf I. Butler
 * v. 1.0, Last Updated: 08/25/2022
 * 
 * Accepts sync data from the FPP Master.
 * 
 * This runs on the Web server and is used to sync up the playlist display for "Now Playing"
 * 
*/

//You must set this...
define ( 'KEY', 'ahWo6yohkoh2Foec4caex0eeh9iebohC' );   //Base access key. Must match key on FPP Master push-status.php script. 

//Leave the rest alone...

//Returns a 500 error if something isn't right.
function exit_header ( $error ) {
     header('HTTP/1.1 500 Internal Server Error');
     echo ( "\n\t$error" );
     file_put_contents ( "playlist.error", $error );
     //foreach ( $_POST as $index => $value ) file_put_contents ( "playlist.error", "\n$index => $value", FILE_APPEND );     //Debugging
     exit;
}

//Simple time-based authentication.
//The auth key sent consists of an MD5 of the $authKey with the unix (epoch) timestamp appended to it.
//Both systems must use NTP! Using a 10 second window to be a bit forgiving...
function fpp_auth ( $key ) {
     $x = time() - 5;
     $end = $x + 10;
     while ( $x <= $end ) {
          if ( $key == md5 ( KEY . $x ) ) return TRUE;
          $x++;
     }
     exit_header( "Authentication Failed! ($key)" );
}

if ( ! isset ( $_GET['key'] ) ) exit_header( 'Invalid authentication key! (' . $_GET['key'] . ')' );
fpp_auth ( $_GET['key'] );

if ( ! isset ( $_POST['playlist'] ) ) exit_header( 'No sync data received!' );

$json = json_encode ( $_POST, JSON_PRETTY_PRINT );
file_put_contents ( "playlist.sync", $json );

?>