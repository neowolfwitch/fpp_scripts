<?php
/*** 
 * playlist_display.php
 * by Wolf I. Butler
 * v. 1.0, Last Updated: 10/15/2021
 * 
 * "Quick" script to display a saved playslist file.
 * This works with the playlist-dump script, which generates the playlist as a JSON file.
 * Use to display the playlist on a Web site.
 * 
 * This can run on any maching (Web Server) as it uses the Dataplicity VPN connection
 * to the show-runner FPP.
 * 
*/

//Just set this...
define ( 'URL', 'https://fungible-gar-5837.dataplicity.io/api/file/uploads/playlist.json' );
define ( 'DTE', 'https://fungible-gar-5837.dataplicity.io.io/api/file/uploads/playlist.time' );
$playlistName = "Main Music";

//Leave the rest alone...

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

?>
<HTML>
<HEAD>
<style>
    tr:nth-child(even) {background-color: #ddffdd;}
    tr:nth-child(odd) {background-color: #ffdddd;}
</style>
</HEAD>
<BODY>
<table>
    <thead>
    <tr><th>Run Time</th><th>Title</th><th>Artist</th><th>Album</th></tr>
    </thead>
    <tbody>
<?php

$arrPlaylist = do_get ( URL );
$arrUpdateTime = do_get ( DTE );

$i = 0; //Init array index.
foreach ( $arrPlaylist as $item ) {
    echo "<tr>\n";
    echo "<td>".$item['Runtime']."</td>\n";
    echo "<td>".$item['Title']."</td>\n";
    echo "<td>".$item['Artist']."</td>\n";
    echo "<td>".$item['Album']."</td>\n";
    echo "</tr>\n";
}

?>
</tbody>
</table>
<?php
echo "<div align=\"center\">(Last updated: " . $arrUpdateTime['updated'] . ") </div>\n";
?>
</BODY>
</HTML>