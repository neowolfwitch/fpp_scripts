<?php
/*** 
 * playlist_dump.php
 * by Wolf I. Butler
 * v. 1.0, Last Updated: 08/23/2022
 * 
 * Polls the FPP Show Runner for the current status.
 * 
 * If a playlist is running, write the following to playlist.sync 
 * * (Playlist) Name
 * * Track
 * * Seconds
 * * Timestamp
 * 
 * Using this information- we should be able to figure out where we are in the playlist at all times.
 * Another script will use this data to display the playlist with current song info.
 * 
 * 
*/

//Just set these...
define ( 'PATH', '/home/fpp/media/upload/' );       //Path to save the playlist locally
define ( 'DEFAULT_PLAYLIST', 'Shuffled Xmas' );     //Default playlist. Used if nothing is running when this script runs.
//Normally this script will automatically pick the currently-running playlist.

define ( "WEB_SERVER", '54.211.27.74' );    //Web server URL or IP.
define ( "WEB_LOGIN",  'admin' );       //Web server user ID. Must be set up to use SSL keys with this server!

//Leave the rest alone...

$arrPlaylist = null;

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

function process_info ( $item ) {
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
        $minutes = strval ( intval ( $timing/60 ) );
        $seconds = strval ( intval ( $timing % 60 ) );
        if ( $seconds < 10 ) $seconds = "0". $seconds;
    }
    $strTime = "$minutes:$seconds";

    $mp3URL = rawurlencode ( $item['mediaName'] );
    $songTitle = FALSE;
    $songArtist = FALSE;
    $songAlbum = FALSE;
    
    $arrMeta = do_get ( "http://localhost/api/media/$mp3URL/meta" );
    $arrMediaInfo = $arrMeta['format']['tags'];

    $arrPlaylist['MediaName'] = $item['mediaName'];

    if ( isset ( $arrMediaInfo['title'] ) ) {
        $songTitle = $arrMediaInfo['title'];

        if ( isset ( $arrMediaInfo['artist'] ) ) $songArtist = $arrMediaInfo['artist'];
        elseif ( isset ( $arrMediaInfo['album_artist'] ) ) $songArtist = $arrMediaInfo['album_artist'];

        if ( isset ( $arrMediaInfo['album'] ) ) $songAlbum = $arrMediaInfo['album'];
    }

    if ( ! $songTitle ) {
        $arrMP3 = explode ( ".", $item['mediaName'] );
        $songTitle = $arrMP3[0];    //Default to MP3 name.

        if ( substr ( $songTitle, 0, 4 ) == 'OHL-' ) {
            $songTitle = substr ( $songTitle, 4 );
            $songTitle = 'Oak Hills Lights: ' . $songTitle;
            $songArtist = '(Intro/Info)';
        }

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

    $arrPlaylist['RunTime'] = $strTime;
    $arrPlaylist['Title'] = $songTitle;
    if ( $songArtist ) $arrPlaylist['Artist'] = $songArtist;
    if ( $songAlbum ) $arrPlaylist['Album'] = $songAlbum;
   
    return $arrPlaylist;

}

//Get currently-running playlist from FPP...
$status = do_get ( "http://localhost/api/system/status" );
if ( $status['current_playlist']['playlist'] ) $listName = $status['current_playlist']['playlist'];
else $listName = DEFAULT_PLAYLIST;

//Get playlist data from FPP...
$listName = rawurlencode ( $listName );
$json = do_get ( "http://localhost/api/playlist/$listName" );

foreach ( $json['mainPlaylist'] as $item ) {
    //sleep(1);   //Slowing the script down so it doesn't take too many API resources

    if ( $item['type'] == 'playlist' ) {
        //Need to pull separate playlist info...
        $sublistJson = do_get ( "http://localhost/api/playlist/" . rawurlencode ( $item['name']  ) );
        foreach ( $sublistJson['mainPlaylist'] as $subItem ) {
            $arrPlaylist[] = process_info ( $subItem );
        }
    }
    else $arrPlaylist[] = process_info( $item );
}

$outJSON = json_encode ( $arrPlaylist, JSON_PRETTY_PRINT );
file_put_contents ( PATH .'playlist.json', $outJSON );
$arrNow['updated'] = date ( "F j, Y, g:i a" );
$nowJSON = json_encode ( $arrNow, JSON_PRETTY_PRINT );
file_put_contents ( PATH . 'playlist.time', $nowJSON );
$exec = "rsync " . PATH . 'playlist.* ' . WEB_LOGIN . '@' . WEB_SERVER . ':~';
exec ( $exec );
?>