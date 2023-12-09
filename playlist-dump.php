<?php
/*** 
 * 
 * DEPRECATED. Does not work anyore and not used.
 * 
 * playlist_dump.php
 * by Wolf I. Butler
 * v. 2.0, Last Updated: 11/30/2021
 * Updates:
 *  Pull sub-playlist data in.
 *  Cleaned up code.
 * 
 * "Quick" script to output a specified playlist to a JSON file.
 * Use to display the playlist on a Web site.
 * 
 * This should be run on the master/show-runner FPP intall.
 * It pulls all information from SERVER value. 
 * 
 * This should be manually run when the playlist changes.
 * It should not be set up to run automatically.
 * 
 * It may take several minutes to run, depending on the size of the playlist.
 * It intentionally runs slowly, to avoid overtaxing the FPP REST API.
 * 
*/

//Just set this...
define ( 'SERVER', '10.0.0.5' );
define ( 'PATH', '/home/fpp/media/upload/' );
$playlistName = "Shuffled Xmas";

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

function get_json ( $listName ) {
    $listName = rawurlencode ( $listName );
    $json = do_get ( "http://".SERVER."/api/playlist/$listName" );
    return $json;
}

function process_info ( $item ) {

    $arrPlaylist['Runtime'] = null;
    $arrPlaylist['Title'] = null;
    $arrPlaylist['Artist'] = null;
    $arrPlaylist['Album'] = null;
    
    $timing = intval ( ceil ( $item['duration'] ) );
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
    
    $arrMeta = do_get ( "http://".SERVER."/api/media/$mp3URL/meta" );
    
    $arrMediaInfo = $arrMeta['format']['tags'];

    if ( $songTitle = $arrMediaInfo['title'] ) {
        if ( $songArtist = $arrMediaInfo['artist'] ) {
            $songAlbum = $arrMediaInfo['album'];
        }
        elseif ( $songArtist = $arrMediaInfo['album_artist'] ) {
            $songAlbum = $arrMediaInfo['album'];
        }
    }
    else{
        //Assuming no MP3 tags found. Try to retrieve song information text file.
        //This is used/done by the info-matrix.php script. See that script for details.
        $info = PATH . $songTitle . ".info";
        if ( is_file ( $info ) ) {
            $arrInfo = file ( $info );
            foreach ( $arrInfo as $index => $value ) {
                //Doing this way to limit errors if the file isn't formatted correctly.
                if ($index == 0) $songTitle = trim ( $value );
                if ($index == 1) $songArtist = trim ( $value );
                if ($index == 2) $songAlbum = trim ( $value );
                if ($index > 2) break;
            }
        }
    }

    if ( ! $songTitle ) {
        $arrMP3 = explode ( ".", $item['mediaName'] );
        $songTitle = $arrMP3[0];    //Default to MP3 name.

        if ( substr ( $songTitle, 0, 4 ) == 'OHL-' ) {
            $songTitle = substr ( $songTitle, 4 );
            $songTitle = 'Oak Hills Lights: ' . $songTitle;
            $songArtist = '(Intro/Info)';
        }


    }

    $arrPlaylist['Runtime'] = $strTime;
    $arrPlaylist['Title'] = $songTitle;
    if ( $songArtist ) $arrPlaylist['Artist'] = $songArtist;
    if ( $songAlbum ) $arrPlaylist['Album'] = $songAlbum;

    return $arrPlaylist;

}

$json = get_json ( $playlistName );

//Check to see if we've already updated the file with this playlist. If so, exit.
$md5 = md5 ( $json );
if ( $checkMD5 = file_get_contents ( PATH . 'playlist.md5' ) ) {
    if ( $md5 == $checkMD5 ) {
        //exit;   //No reason to continue.
    }
    else {
        file_put_contents ( PATH . 'playlist.md5', $md5 );
    }
}

foreach ( $json['mainPlaylist'] as $item ) {
    sleep(1);   //Slowing the script down so it doesn't take too many API resources

    if ( $item['type'] == 'playlist' ) {
        //Need to pull separate playlist info...
        $sublistJson = get_json ( $item['name'] );
        foreach ( $sublistJson['mainPlaylist'] as $subItem ) {
            $arrPlaylist[] = process_info ( $subItem );
        }
    }
    else $arrPlaylist[] = process_info( $item );
}

$outJSON = json_encode ( $arrPlaylist, JSON_PRETTY_PRINT );
file_put_contents ( "/home/fpp/media/upload/playlist.json", $outJSON );
$arrNow['updated'] = date ( "F j, Y, g:i a" );
$nowJSON = json_encode ( $arrNow, JSON_PRETTY_PRINT );
file_put_contents ( "/home/fpp/media/upload/playlist.time", $nowJSON );
?>