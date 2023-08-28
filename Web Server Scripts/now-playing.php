<?php
/*** 
 * now-playing.php
 * by Wolf I. Butler
 * v. 1.0, Last Updated: 08/31/2022
 * 
 * "Quick" script to display the current show status for the Web site.
 * Should be embedded in an iFrame under the playlist display, or wherever else you want.
 * 
 * This just reads the playlist.sync file which should be uploaded periodically by the
 * Show Runner FPP computer. This file has the output of the api/status command, so you can
 * use anything in it. 
 * 
*/

//Just set these...
//By default the playlist and update time files are rsynced from the Show Runner FPP server.
define ( 'SYNCFILE', './playlist.sync' );  //Status data from Show Runner
define ( 'PLAYLISTFILE', './playlist.json' );  //Playlist data from Show Runner

//Leave the rest alone...

$syncData = json_decode ( file_get_contents ( SYNCFILE ), TRUE );
$playData = json_decode ( file_get_contents ( PLAYLISTFILE ), TRUE );

if ( isset ( $syncData['status'] ) ) {
    $updateTime = $syncData['time'];
    if ( $syncData['status'] == 'playing' ) {
        //FPP is running a playlist
        if ( $syncData['song'] ) $mp3 = $syncData['song'];
        else $mp3 = FALSE;
        
        $songName = FALSE;
        $songArtist = FALSE;
        if ( $mp3 ) {
            foreach ( $playData as $value ) {
                if ( $value['MediaName'] == $mp3 ) {
                    $songName = htmlentities ( $value['Title'] );
                    $songArtist = htmlentities ( $value['Artist'] );
                    break;
                }
            }
        }
        if ( $songName ) {
            $msg = "<font size=\"+1\"><b>Now Playing:<br>$songName</b></font>\n";
            if ( $songArtist ) $msg .= "<br>by $songArtist\n";
            //$msg .= "<br><font size=\"-1\"><em>(As of $updateTime. May be delayed.)</></>\n";
        }
        else {
            //Unable to match song with playlist!
            $msg = "<font size=\"+1\"><b>Show status is currently unavailable.</b><br</font><br>\n";
            //if ( $updateTime ) $msg .= "<font size=\"-1\"><em>(As of $updateTime.)</em></font>\n";
        }
    }
    else {
        //FPP is idle
        $msg = "<font size=\"+1\"><b>Nothing is playing right now.</b><br</font><br>\n";
        //if ( $updateTime ) $msg .= "<font size=\"-1\"><em>(As of $updateTime.)</em></font>\n";
    }
}

?>
<HTML>
<HEAD>
    <meta http-equiv="refresh" content="10">
</HEAD>
<BODY>
<?php
echo "<div align=\"center\">$msg</div>\n";
?>
</BODY>
</HTML>
