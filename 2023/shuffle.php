<?php
/*** 
 * scramble.php
 * by Wolf I. Butler
 * v. 1.1, Last Updated: 08/19/2022
 * 
 * This script shuffles the $inList playlist, adds reminder messages, and outputs
 * the scrambled playlist for use.
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
$reminderInt = 7;       //Number of songs between reminder messages.
//reminderList should be a playlist that contains your reminder sequence(s).
//These would include announcements and reminder messages you want played regularly during your show.

//Note on song announcements:
//If you announce certain non-Christmas or other songs, like "This is a Disney song..." the best way to
//handle them is to put them in their own individual playlists, just containing your announcment and
//the special song's sequence, and then link those into your $inList playlist by just adding them as
//a playlist. This script will treat them appropriately.

//***** Don't edit anything after here. *****/

//Get data from API
function do_get ( $url ) {
    //Initiate cURL.
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    
    //Set timeouts to reasonable values:
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ( $curlResult = curl_exec($ch) ) {
        $curlJSON = json_decode ( $curlResult, TRUE );
        return $curlJSON;
    }
    return FALSE;
}

//Load playlist data into memory:
$listName = rawurlencode ( $inList );
$json = do_get ( "http://localhost/api/playlist/$listName" );

//Randomize main playlist array...
$arrNew = $json['mainPlaylist'];
shuffle ( $arrNew );

//Build new playlist...
//Header
$outArray['name'] = "$outList";
$outArray['version'] = $json['version'];
$outArray['repeat'] = $json['repeat'];
$outArray['loopCount'] = $json['loopCount'];
$outArray['empty'] = $json['empty'];
$outArray['desc'] = "Shuffled Playlist from shuffle.php script.";
$outArray['random'] = $json['random'];

//Lead In
$outArray['leadIn'] = $json['leadIn'];

//Main Playlist
$c = -1;
$i = 0;
foreach ( $arrNew as $item ) {
    if ( $reminderList ) $c++;  //Don't increment if FALSE.
    if ( $c == $reminderInt ) {
        //Insert "reminder" playlist...
        $c = 0;
        $outArray['mainPlaylist'][$i]['type'] = "playlist";
        $outArray['mainPlaylist'][$i]['enabled'] = "1";
        $outArray['mainPlaylist'][$i]['playOnce'] = "0";
        $outArray['mainPlaylist'][$i]['name'] = $reminderList;
        $outArray['mainPlaylist'][$i]['duration'] = null;
        $i++;
    }
    $outArray['mainPlaylist'][$i] = $item;
    $i++;
} 

//Lead Out
$outArray['leadOut'] = $json['leadOut'];

//Footer
$outArray['playlistInfo']['total_duration'] = null;
$outArray['playlistInfo']['total_items'] = $i;

//Convert back to JSON and output to file
$playlistOut = json_encode ( $outArray, JSON_PRETTY_PRINT );
file_put_contents ( "/home/fpp/media/playlists/$outList.json", $playlistOut );

echo "\nDone shuffling! $inList -> $outList \n";

?>