FPP back-end scripts.

Please read the license terms carefully, as the author disclaims all liability for everything included here.

***This software is distributed AS-IS without support.*** 

I have a "day job" and just don't have time to provide technical support for this stuff. There are a lot of resources available online for xLights, FPP, Arduino, C, PHP, etc, and my code is well-commented. Please do not attempt to compile or use anything here unless you have a high level of comfort with the tools involved.

Many resources can be found here: https://lunardenlights.com/pixel-resources/

...and here: https://lunardenlights.com/terminology/

I'm happy to respond to feedback, suggestions, and simple questions as I have time, but I can't support xLights, FPP, Arduino IDE, or Visual Studio Code.

Thanks!

Wolf I. Butler

File Descriptions:

push-playlist.php runs on FPP Showrunner (Master) from the scheduler. It pushes the playlist to our Web server.

push-status.php runs on FPP Shorunner (Master). It runs continuously on boot and pushes the current system status to the Web server.

info-matrix.php runs on the Info Matrix. It automagically displays the current show status/song status on a matrix.

info-config.php is the configuration file for info-matrix.php and needs to be somewhere readable by that script.

Web Server Scripts are the API scripts on the Web server that accept the playlist and status data from the FPP Showrunner.
They also display the playlist and Now Playing data for our Web site:

sync-playlist.php accepts data from push-playlist.php and saves playlist.json to the Web root.

sync-status.php accepts data from push-status.php and saves playlist.sync to the Web root.

now-playing.php displays the current song information in an iframe-able format for the Web site.

playlist-display.php displays the current playlist, with the Now Playing entry highlighted. This is also iframe-able and used for the Web site.
