<?php
/*** 
 * push-playlist.php
 * by Wolf I. Butler
 * v. 2.3, Last Updated: 09/12/2022
 * 
 * Pushes the current playlist to the Web server.
 * 
 * This should be run on the Show Runner (Master) FPP that runs the scheduler.
 * 
 * This script should be run any time there is a playlist change.
 * It is best to put it in the Lead In section of any regularly-used playlists.
 * 
 * It uses a caching system to reduce the chances of causing performance issues while a show is running.
 * See $arrPeakHours below for details.
 * 
*/

//Just set these...
define ( 'PATH', '/home/fpp/media/upload/' );       //Path to .txt info files for MP3s without meta data.
define ( 'WEB_SERVER', 'https://oakhillslights.com' );    //Web server URL or IP.
//The following should be a long random key or phrase.
//This must match the "KEY" in the Web server's sync-playlist.php script.
define ( 'KEY', 'eijeiGh8ohgeevoofee7thae3Ucoop7o' );   //Base access key.
define ( 'DEFAULT_PLAYLIST', 'Shuffled Xmas' );     //Default playlist. Used if nothing is running when this script runs.
define ( 'DEBUG_MODE', TRUE );      //Set to TRUE to print out progress info.

//Peak Hours (24-hour format) when we may be running our show.
//If the script is run during any of these hours, the cache file will be used, instead of requesting MP3 metadata for all
//songs from FPP. This is to insure the highest performance of FPP. During non-peak hours- the MP3 metadata is queried
//and the cache is re-built.
$arrPeakHours = array ( 16,17,18,19,20,21,22,23 );

//Leave the rest alone...

//Get data from API
function do_get ( $url ) {

    //Initiate cURL.
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);

    //Set timeouts to reasonable values:
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ( $curlResult = curl_exec($ch) ) {
        $curlJSON = json_decode ( $curlResult, TRUE );
        return $curlJSON;
    }
    return FALSE;
}

# Function to post the data to the REST API
function do_post ( $url, $data ) {

    // Convert the PHP array into a JSON format
    $payload = json_encode( $data, JSON_PRETTY_PRINT );
    
    // Initialise new cURL session
    $ch = curl_init ( $url );

    // Return result of POST request
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );

    // Get information about last transfer
    curl_setopt ( $ch, CURLINFO_HEADER_OUT, true );

    // Use POST request
    curl_setopt ( $ch, CURLOPT_POST, true );

    // Set payload for POST request
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $payload );
    
    // Set HTTP Header for POST request 
    curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
    'Content-Type: application/json',
    'Content-Length: ' . strlen ( $payload ) )
    );
    
    // Execute a cURL session
    $result = curl_exec($ch);
    
    // Close cURL session
    curl_close($ch);

return $result;
}

function process_info ( $item, $cache = FALSE ) {
    $arrPlaylist['RunSecs'] = null;
    $arrPlaylist['RunTime'] = null;
    $arrPlaylist['MediaName'] = null;
    $arrPlaylist['Title'] = null;
    $arrPlaylist['Artist'] = null;
    $arrPlaylist['Album'] = null;
    
    $timing = intval ( ceil ( $item['duration'] ) );
    $arrPlaylist['RunSecs'] = $timing;
    //Avoid divide by 0 errors
    if ( $timing < 1 ) {
        $minutes = "00";
        $seconds = "01";    //Just to show something, round up.
    }
    else {
        $minutes = strval ( intval ( $timing / 60 ) );
        $seconds = strval ( intval ( $timing % 60 ) );
        if ( $seconds < 10 ) $seconds = "0". $seconds;
    }

    $strTime = "$minutes:$seconds";

    $mp3URL = rawurlencode ( $item['mediaName'] );
    $songTitle = FALSE;
    $songArtist = FALSE;
    $songAlbum = FALSE;
    
    $arrPlaylist['MediaName'] = $item['mediaName'];

    if ( is_array ( $cache ) ) {
        foreach ( $cache as $value ) {
            if ( $value['MediaName'] == $item['mediaName'] ) {
                $arrMediaInfo['title'] = $value['Title'];
                $arrMediaInfo['artist'] = $value['Artist'];
                $arrMediaInfo['album'] = $value['Album'];
                if ( DEBUG_MODE ) echo "Cached Item : " . $item['mediaName'];
                break;
            }
        }
    }

     //Only get metadata from FPP if it wasn't cached...
    if ( ! isset ( $arrMediaInfo['title'] ) ) {
        $arrMeta = do_get ( "http://localhost/api/media/$mp3URL/meta" );
        $arrMediaInfo = $arrMeta['format']['tags'];   
	if ( DEBUG_MODE ) echo "NOT Cached - " . $item['mediaName'];
    }
 
    if ( isset ( $arrMediaInfo['title'] ) ) {
        $songTitle = $arrMediaInfo['title'];

        if ( isset ( $arrMediaInfo['artist'] ) ) $songArtist = $arrMediaInfo['artist'];
        elseif ( isset ( $arrMediaInfo['album_artist'] ) ) $songArtist = $arrMediaInfo['album_artist'];

        if ( isset ( $arrMediaInfo['album'] ) ) $songAlbum = $arrMediaInfo['album'];
    }
 
    //"Fix" Oak Hills Lights Intros.
    if ( substr ( $item['mediaName'], 0, 4 ) == 'OHL-' ) {
        $arrMP3 = explode ( ".", $item['mediaName'] );
        $songTitle = $arrMP3[0];    //Default to MP3 name.
        $songTitle = substr ( $songTitle, 4 );
        $songTitle = str_replace ( '-', ' ', $songTitle );
        $songTitle = 'Oak Hills Lights: ' . $songTitle;
        $songArtist = '(Intro/Info)';
        $songAlbum = 'OHL Intros';
    }

    if ( ! $songTitle ) {
        $arrMP3 = explode ( ".", $item['mediaName'] );
        $songTitle = $arrMP3[0];    //Default to MP3 name.

        //Assuming no MP3 tags found. Try to retrieve song information text file.
        //This is used/done by the info-matrix.php script. See that script for details.
        $info = PATH . "$songTitle.txt";
        if ( is_file ( $info ) ) {
            $arrInfo = file ( $info );
            foreach ( $arrInfo as $index => $value ) {
                //Doing this way to limit errors if the file isn't formatted correctly.
                if ($index == 0) $songTitle = trim ( $value );
                if ($index == 1) $songArtist = trim ( $value );
                if ($index == 2) $songAlbum = trim ( $value );
                if ($index == 3) break;
            }
        }
    }
    if ( DEBUG_MODE ) echo " => $songTitle\n";
    $arrPlaylist['RunTime'] = $strTime;
    $arrPlaylist['Title'] = $songTitle;
    if ( $songArtist ) $arrPlaylist['Artist'] = $songArtist;
    if ( $songAlbum ) $arrPlaylist['Album'] = $songAlbum;

    return $arrPlaylist;
}
 
if ( DEBUG_MODE ) echo "\nStarting playlist processing...\n";   //Debugging
//Get currently-running playlist from FPP...
//$status = do_get ( "http://localhost/api/system/status" );
//if ( $status['current_playlist']['playlist'] ) $listName = $status['current_playlist']['playlist'];
//else $listName = DEFAULT_PLAYLIST;
$listName = DEFAULT_PLAYLIST;

//Get playlist data from FPP...
$listName = rawurlencode ( $listName );
$json = do_get ( "http://localhost/api/playlist/$listName" );

//Use playlist cache during peak show hours...
if ( in_array ( date ( 'G', time() ), $arrPeakHours ) ) {
    echo "\nRunning in Cached Mode due to peak time settings.\n";
    $cache = json_decode ( file_get_contents ( PATH . 'playlist.cache' ), TRUE );
}
else {
    echo "\nNot running in cached mode. Meta data being queried.\n";
    $cache = FALSE;
}

foreach ( $json['mainPlaylist'] as $item ) {
    if ( $item['type'] == 'playlist' ) {
        //Need to pull separate playlist info...
        $sublistJson = do_get ( "http://localhost/api/playlist/" . rawurlencode ( $item['name']  ) );
        foreach ( $sublistJson['mainPlaylist'] as $subItem ) {
            $arrPlaylist[] = process_info ( $subItem, $cache );
        }
    }
    else $arrPlaylist[] = process_info( $item, $cache );
}

//Save cache file...
if ( DEBUG_MODE ) echo "Saving cache file...\n";
file_put_contents ( PATH . 'playlist.cache', json_encode( $arrPlaylist, JSON_PRETTY_PRINT ) );
//Push playlist to Web server...
if ( DEBUG_MODE ) echo "Pushing playlist to Web server...\n";
$key = md5 ( KEY . time() );    //Passkey for Web server.
do_post ( WEB_SERVER . "/sync-playlist.php?key=$key", $arrPlaylist );
echo "\nDone.\n";
?>

