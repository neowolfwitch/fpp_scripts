<?php
/*** 
 * playing.php
 *
 * Shows the current Now Playing/Playlist status.
 * 
 * by Wolf I. Butler
 * v. 1.0, Last Updated: 09/19/2022
 * 
 * Part of the Oak Hills Lights Web status system.
 * 
 * This is designed to be run on the same network as the rest of the show as it needs
 * access to all of the players and controllers.
 * 
 * License: GPLv3 
 * See the LICENSE document attached to this distribution.
 * 
 * This script is provided ​“AS IS”. Developer makes no warranties, express or implied, 
 * and hereby disclaims all implied warranties, including any warranty of merchantability 
 * and warranty of fitness for a particular purpose.
 * 
*/

?><link rel="stylesheet" type="text/css" href="status.css">

<?php
require_once ( 'config.php' );
require_once ( 'library.php' );

$arrStatus = do_get( "http://$masterIP/api/fppd/status" );

echo "<div class=\"title\">Oak Hills Lights Control Panel</div><br><div class=\"big\">\n";
if ( $arrStatus['status_name'] == 'playing') {
    $playFlag = TRUE;
    echo "<span class=\"good\">Now playing:</span> " . 
        $arrStatus['current_sequence'] . ' (' . $arrStatus['current_song'] . ') '; 
}
elseif ( $arrStatus['status_name'] == 'paused') {
    $playFlag = TRUE;
    echo "<span class=\"bad\">PAUSED:</span> " . 
        $arrStatus['current_sequence'] . ' (' . $arrStatus['current_song'] . ') '; 
}
else {
    $playFlag = FALSE;
    echo "<span class=\"inact\">System Idle</span>";
}
echo "</div><br><div class=\"larger\">\n";

//Playlist...
if ( $playFlag ) {
    echo 'Current Playlist: ' . $arrStatus['current_playlist']['playlist'] . "<br>\n";
}
if ( isset ( $arrStatus['next_playlist'] ) ) {
echo 'Next Playlist: ' . $arrStatus['next_playlist']['playlist'] . ', Starting: ' 
    . $arrStatus['next_playlist']['start_time'];
}
else echo "<span class=\"bad\">No playlist scheduled or scheduler not running.</span>";
echo "</div>\n";

?>