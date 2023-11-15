<?php
/*** 
 * playlist_display.php
 * by Wolf I. Butler
 * v. 1.0, Last Updated: 10/15/2021
 * 
 * "Quick" script to display a saved playlist file generated by playlist_dump_v2.0.php
 * This works with the playlist-dump script, which generates the playlist as a JSON file.
 * Use to display the playlist on a Web site.
 * 
 * This can run on any machine (Web Server) as I'm using Dataplicity URLs
 * to the show-runner FPP.
 * 
*/

//Just set these...
//These URLS need to be accessible by the Web server this script is running on.
//If public, I recommend using Dataplicity for your FPP instances to protect them.
//Exposing your FPP instances to the Internet directly is BAD. Just don't do it!
//Never assume someone can't read scripts on your Web server, or that port-forwarding is somehow secure.
//For example- don't use port-forwarding and put those URLS with port numbers here and expect them to be secure.
define ( 'URL', 'https://fpp_public.example.com/api/file/uploads/playlist.json' );  //Playlist
define ( 'DTE', 'https://fpp_public.example.com/api/file/uploads/playlist.time' );  //Playlist update time

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