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
 * For command/playlist functions, the format is:
 * array(
 *     'label' => 'Play: NRL',
 *     'url' => "http://10.0.0.5/api/command/Start+Playlist/Playlist+Name/true/false"
 * ),
 * The entry after the Playlist Name is for REPEAT (true/false)
 * The second entry is for "If not already playing." (true/false)
 * 
**/

//Be sure to use the IP address of the Show Runner. Use the remote IP address for remote scripts.
$arrActions = array(
     array(
          'label' => '&lt; PREV',
          'url' => "http://10.0.0.5/api/command/Prev+Playlist+Item"
     ),
     array(
          'label' => 'NEXT &gt;',
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
          'label' => 'Reset System/Schedule',
          'url' => "http://10.0.0.5/api/system/fppd/restart"
     ),
     array(
          'label' => 'Reboot(!)',
          'url' => "http://10.0.0.5/api/system/reboot"
     ),
     array(
          'label' => 'Play: Shuffled Xmas',
          'url' => "http://10.0.0.5/api/command/Start+Playlist/Shuffled+Xmas/true/false"
     ),
     array(
          'label' => 'Play: DRL',
          'url' => "http://10.0.0.5/api/command/Start+Playlist/DRL/true/false"
     ),
     array(
          'label' => 'Play: NRL',
          'url' => "http://10.0.0.5/api/command/Start+Playlist/NRL/true/false"
     ),
     array(
          'label' => 'Power ON',
          'url' => "http://10.0.0.5/api/command/Start+Playlist/Main+Power+On/false/false"
     ),
     array(
          'label' => 'Shutdown',
          'url' => "http://10.0.0.5/api/command/Start+Playlist/Safe+Shutdown/false/false"
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
