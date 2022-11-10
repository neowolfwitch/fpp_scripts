<?php

/*** 
 * config.php
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

//Put all of the controller IPs and names you want to monitor here, like: '192.168.3.3' => 'FPP-Master'.
//You should put them in the order you want them to be displayed in.
$arrControllers = array(
     '10.0.0.5' => 'FPP-Master',
     '10.0.0.33' => 'Power',
     '10.0.0.10' => 'K16A-B-Tree',
     '10.0.0.11' => 'K40D-PB-1',
     '10.0.0.12' => 'K40D-PB-2',
     '10.0.0.21' => 'Column-Left',
     '10.0.0.22' => 'Column-Center',
     '10.0.0.23' => 'Column-Right',
     '10.0.0.16' => 'Optoma-Projector',
     '10.0.0.42' => 'Audio-Monitor',
     '10.0.0.20' => 'Info-Matrix'
);

/** Action button definitions. Format is:
 * array ( 
 *      'label' => 'PAUSE', 
 *      'url' => "http://10.0.0.5/api/playlists/pause" 
 * ),
 * 
 * A toggle button can also be set up by separating options with a ^ and adding "url2":
 * array(
 *        'label' => 'PAUSE^RESUME',
 *        'url' => "http://10.0.0.5/api/playlists/pause",
 *        'url2' => "http://10.0.0.5/api/playlists/resume"
 * ),
 *
**/

//Be sure to use the IP address of the Show Runner.
$arrActions = array(
     array(
          'label' => '&lt;PREV',
          'url' => "http://10.0.0.5/api/command/Prev+Playlist+Item"
     ),
     array(
          'label' => 'NEXT&gt;',
          'url' => "http://10.0.0.5/api/command/Next+Playlist+Item"
     ),
     array(
          'label' => 'PAUSE',
          'url' => "http://10.0.0.5/api/playlists/pause",
     ),
     array(
          'label' => 'RESUME',
          'url' => "http://10.0.0.5/api/playlists/resume",
     ),
     array(
          'label' => 'STOP',
          'url' => "http://10.0.0.5/api/playlists/stop"
     ),
     array(
          'label' => 'Reset FPP/Scheduler',
          'url' => "http://10.0.0.5/api/system/fppd/restart"
     ),
     array(
          'label' => 'Reboot(!)',
          'url' => "http://10.0.0.5/api/system/reboot"
     ),
     array(
          'label' => 'Play: Shuffled Xmas',
          'url' => "http://10.0.0.5/api/playlist/Shuffled+Xmas/start"
     ),
     array(
          'label' => 'Play: DRL',
          'url' => "http://10.0.0.5/api/playlist/DRL/start"
     ),
     array(
          'label' => 'Power ON',
          'url' => "http://10.0.0.33/api/scripts/Power-On.sh/run"
     ),
     array(
          'label' => 'Power OFF',
          'url' => "http://10.0.0.33/api/scripts/Power-Off.sh/run"
     ),

);

//Refresh seconds. 10 or greater recommended. Lower may cause resource issues!
$refresh = 10;

//Table Columns. Adjust for display size. Used for status and action buttons.
$statusCols = 5;
$actionCols = 6;

//IP address of Show Runner (Master) FPP:
$masterIP = '10.0.0.5';

//IP address of Power manager FPP:
$powerIP = '10.0.0.33';

//IP address of server. Needed if it is accessed remotely.
//If just using localhost, set to 127.0.0.1
$webIP = '10.0.0.107';
