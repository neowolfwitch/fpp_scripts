Web Monitor "Control Panel" implementation used by Oak Hills Lights
By Wolf Butler
v. 1.1, Last Updated: 12/06/2022

These scripts are designed to run on an nginx Web server with PHP installed. They should run on Apache if you are so-inclined, but
nginx is much faster and not nearly as bloated and is rapidly displacing Apache.

We use them on a Raspberry Pi with the official 7" touchscreen, configured as a Web kiosk that displays http://localhost/index.php.

There are a number of tutorials showing how to set up a Raspeberry Pi Kiosk online. Google is your friend.

In a nutshell, this system uses jquery to provide a simple AJAX interface for managing our show using FPP's excellent API, which is
well documented in the FPP Help system with one of the advanced UIs enabled. The config.php file is set up specifically for Oak Hills
Lights, but you can easily customize it for your own show.

I'm sure someone with much better css-fu than I have can make something that looks a lot prettier too, but even though it isn't
fancy- it works well for us.

Note that I make extensive use of playlists as an abstraction layer for more advanced functionality. For example- instead of calling
a chain of scripts and remote scripts directly, I put them in a playlist which is then called by this system. A good example is the
Safe Shutdown playlist. This runs the following sequence:
 * Send projector POWER-OFF command to the projector fpp.
 * Wait 30 seconds for the projector to shut down and cool off.
 * Send shutdown API commands to all of the other FPP controllers.
 * Wait 15 seconds to give them a chance to shut down.
 * Send a Power-Off command to our power managment system, turning off the show's power.

The script files are well-documented and should be easy enough to figure out. These are being made availalbe as-is with no warranty
or support.
