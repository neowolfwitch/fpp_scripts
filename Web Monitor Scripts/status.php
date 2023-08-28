<?php
/*** 
 * status.php
 *
 * Shows the status of all FPP devices configured.
 * 
 * by Wolf I. Butler
 * v. 1.1, Last Updated: 12/06/2022
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
?><link rel="stylesheet" type="text/css" href="status.css"><?php
require_once ( 'config.php' );
require_once ( 'library.php' );

foreach ( $arrControllers as $ip => $name ) {
    if ( ping ( $ip ) ) {
        $arrStatus[$name] = do_get ( "http://$ip/api/fppd/status" );
    }
    else $arrStatus[$name] = FALSE;
}

//Get the power relay model names and status from the power controller.
//Used to display their on/off status after the rest of the controllers below.
$arrPower = do_get( "http://$powerIP/api/overlays/models");

//System Status...
echo "<hr><table>\n";
$i = 0;
foreach ( $arrStatus as $name => $arrData ) {
    $i++;
    if ( $i == 1 ) echo "<tr>\n";
    echo "<td>";
    
    if ( is_array ( $arrData ) ) {
        if ( $arrData['status_name'] == 'playing' ) echo "<div class=\"button-green\">";
        else echo "<div class=\"button-yellow\">";
    }
    else {
        echo "<div class=\"button-red\">";
    } 
    echo $name;
    echo "</div>\n";
    echo "</td>";
    if ( $i == $statusCols ) {
        echo "</tr>\n";
        $i = 0;
    }
}

//Power Status
foreach ( $arrPower as $index => $arrData ) {
    $i++;
    if ( $i == 1 ) echo "<tr>\n";
    echo "<td>";
    
    if ( is_array ( $arrData ) ) {
        if ( $arrData['isActive'] == 1 ) echo "<div class=\"button-green\">";
        else echo "<div class=\"button-red\">";
    }
    echo $arrData['Name'];
    echo "</div>\n";
    echo "</td>";
    if ( $i == $statusCols ) {
        echo "</tr>\n";
        $i = 0;
    }

}

echo "</table><p>\n";
echo "<div align=\"center\"><strong>LEGEND: ";
echo "<span class=\"button-green\">&nbsp;&nbsp;&nbsp;</span> &nbsp;= Online, Playing &nbsp; ";
echo "<span class=\"button-yellow\">&nbsp;&nbsp;&nbsp;</span> &nbsp;= Online, Idle &nbsp; ";
echo "<span class=\"button-red\">&nbsp;&nbsp;&nbsp;</span> &nbsp;= Offline &nbsp;";
echo "</strong></div><hr>";

?>