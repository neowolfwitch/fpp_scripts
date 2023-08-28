<?php
/*** 
 * sync-playlist.php
 * by Wolf I. Butler
 * v. 1.1, Last Updated: 08/31/2022
 * 
 * Accepts playlist data from the FPP Master.
 * 
 * This runs on the Web server and is used to sync up the playlist display.
 * Data is also used for the Info Matrix and Show Status displays.
 * 
*/

//You must set this...
//The following should be a long random key or phrase.
//This must match the "KEY" in the FPP server's push-playlist.php script.
define ( 'KEY', 'BigLongKeyGoesHere' );   //Base access key. 

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

if ( $json = file_get_contents( 'php://input' ) ) file_put_contents ( "playlist.json", $json );
else exit_header( 'No sync data received!' );

?>