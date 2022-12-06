<?php
/*** 
 * action.php
 *
 * Responds to action button presses...
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
?>
<script src="jquery-3.6.1.min.js"></script>

<script>

async function runGet($url){
    $.ajax({
        type: "GET",
        url: $url
    } );
    return;
}

async function fppGet($url){
    //This is necessary to wait for the runGet function to finish.
    await runGet($url);
    $('#playing').load('playing.php');
    $('#status').load('status.php');
}

</script>

<?php
require_once ( 'config.php' );
require_once ( 'library.php' );

echo "<table>\n";
$i = 0;

foreach ( $arrActions as $action ) {
    $i++;
    if ( $i == 1 ) echo "<tr>\n";
    echo "<td align=\"center\">";

    $label = $action['label'];
    $url = $action['url'];
    echo "<button class=\"button-blue\" onclick=\"fppGet('$url')\">$label</button>\n";
    echo "</td>";
    if ( $i == $actionCols ) {
        echo "</tr>\n";
        $i = 0;
    }
}
echo "</table>\n";
?>