<?php
/*** 
 * index.php
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

?>
<html><head>
<script src="jquery-3.6.1.min.js"></script>
<script src="library.js"></script>
<meta charset="UTF-8">
<link rel="stylesheet" type="text/css" href="status.css">
</head>
<body>
<?php
//Format the display screen. Note Javascript and CSS Grids control the following.

echo "<div class=\"home\">\n";
echo "<div id=\"playing\" class=\"playing\"><img src=\"spinner.gif\"></div>\n";
echo "<div id=\"status\" class=\"status\"><img src=\"spinner.gif\"></div>\n";
echo "<div id=\"actions\" class=\"actions\"><img src=\"spinner.gif\"></div>\n";
echo "</div>\n";
?>
</body></html>