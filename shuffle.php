<?php

/*** 
 * shuffle.php
 * by Wolf I. Butler
 * v. 2.0, Last Updated: 12/02/2023
 * 
 * v.2 Changes:
 * Added functionality to keep embeded playlists separated by at least one regular song.
 * Embedded playlists are non-Christmas music, like Disney and Other. We don't want one
 * right after another.
 * 
 * This script shuffles the $inList playlist, adds reminder messages, and outputs
 * the shuffled playlist for use.
 * 
 * This script should be run on the show-runner FPP as it creates a playlist for it to use.
 * Just schedule the script to run sometime before the playlist is used, and then play the
 * designated $outList playlist.
 * 
 * License: CC0 1.0 Universal: This work has been marked as dedicated to the public domain.
 * 
 * This script is provided ​“AS IS”. Developer makes no warranties, express or implied, 
 * and hereby disclaims all implied warranties, including any warranty of merchantability 
 * and warranty of fitness for a particular purpose.
 * 
 */

//*** You must edit the following sections... ***
//Since this script uses existing playlists and sequences, double-check the names!
//Note that this script directly edits output playlist. If the playlist format changes in
//future versions of FPP, it may need to be edited to output the correct format.

$inList = "Main Music";             //Input playlist name. Must match a valid FPP playlist!
//Note that lead-in and lead-out from this playlist will be used as-is in the new one.
//Do not put reminder elemments in this playlist, or they will get multiplied. (See below.)
//Also don't set this to to be a random playlist!

$outList = 'Shuffled Xmas';              //This is the output playlist name.
//Note: $outList is overwritten every time this is run. Don't run this script while outList
//may be playing. It may cause unpredictable results.

$reminderList = "Reminder";        //Reminder/Welcome playlist. Set to FALSE if not using.
$reminderInt = 5;       //Number of songs between reminder messages.
//reminderList should be a playlist that contains your reminder sequence(s).
//These would include announcements and reminder messages you want played regularly during your show.

//Note on song announcements:
//If you announce certain non-Christmas or other songs, like "This is a Disney song..." the best way to
//handle them is to put them in their own individual playlists, just containing your announcment and
//the special song's sequence, and then link those into your $inList playlist by just adding them as
//a playlist. This script will treat them appropriately.

//***** Don't edit anything after here. *****/

//Get data from API
function do_get($url)
{
     //Initiate cURL.
     $ch = curl_init($url);

     curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
     curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);

     //Set timeouts to reasonable values:
     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
     curl_setopt($ch, CURLOPT_TIMEOUT, 10);

     if ($curlResult = curl_exec($ch)) {
          $curlJSON = json_decode($curlResult, TRUE);
          return $curlJSON;
     }
     return FALSE;
}

//Load playlist data into memory:
$listName = rawurlencode($inList);
$json = do_get("http://localhost/api/playlist/$listName");

//Randomize main playlist array...
$arrIn = $json['mainPlaylist'];
shuffle($arrIn); //Initial s

//Build new playlist...
//Header
$arrOut['name'] = "$outList";
$arrOut['version'] = $json['version'];
$arrOut['repeat'] = $json['repeat'];
$arrOut['loopCount'] = $json['loopCount'];
$arrOut['empty'] = $json['empty'];
$arrOut['desc'] = "Shuffled Playlist from shuffle.php script.";
$arrOut['random'] = $json['random'];

//Lead In
$arrOut['leadIn'] = $json['leadIn'];

//Main Playlist
$i = 0;        //Playlist index.

//Setting this to TRUE initially to prevent an embedded playlist from STARTING the show...
$plFlag = TRUE;

$itemCount = count($arrIn);
$itemCountSave = $itemCount;
$arrInSave = $arrIn;
$arrOutSave = $arrOut;
$reshuffleCount = 0;

while ( TRUE ) {
     if ($itemCount == 0) break;

     if ($reshuffleCount == 10) {
          //Deadlock condition. Reset and try again...
          echo "\nOnly embedded playlists remaining. Deadlock condition. Restarting...\n";
          unset ( $arrIn, $arrOut );
          $arrIn = $arrInSave;
          $arrOut = $arrOutSave;
          $itemCount = $itemCountSave;
          $i = 0;
          $reshuffleCount = 0;
          continue;
     }

     $arrItem = $arrIn[0];

     if ($arrItem['type'] == 'playlist') {
          if ($plFlag == TRUE) {
               //We don't want two playlists in a row, so need to reshuffle...
               shuffle($arrIn);
               if ($itemCount == $itemCountSave) echo "\nEmbedded playlist at start. Reshuffling...";
               else echo "\nTwo embedded playlists in a row. Reshuffling...";
               $reshuffleCount++;
               continue;
          } else {
               $plFlag = TRUE;
          }
     } else $plFlag = FALSE;

     $reshuffleCount = 0;
     $arrOut['mainPlaylist'][$i] = array_shift($arrIn);

     $i++;
     $itemCount--;

     //+1 to avoid divide by zero and evenly separate reminders.
     if (  ($i+1) % ($reminderInt+1) == 0 ) {
          //Insert "reminder" playlist...
          $c = 0;
          $arrOut['mainPlaylist'][$i]['type'] = "playlist";
          $arrOut['mainPlaylist'][$i]['enabled'] = "1";
          $arrOut['mainPlaylist'][$i]['playOnce'] = "0";
          $arrOut['mainPlaylist'][$i]['name'] = $reminderList;
          $arrOut['mainPlaylist'][$i]['duration'] = null;
          $i++;
          $plFlag = FALSE;      //The Reminder doesn't count.
     }
}

//Lead Out
$arrOut['leadOut'] = $json['leadOut'];

//Footer
$arrOut['playlistInfo']['total_duration'] = null;
$arrOut['playlistInfo']['total_items'] = $i;

//Convert back to JSON and output to file
$playlistOut = json_encode($arrOut, JSON_PRETTY_PRINT);
file_put_contents("/home/fpp/media/playlists/$outList.json", $playlistOut);

echo "\n\nDone shuffling! $inList -> $outList \n";
