<?php

/****
 * thermostat.php
 * by Wolf I Butler
 * v. 2.0.1
 *  
 * v.2 Changes:
 * Modified to call GPIOs directly instead of relying on FPP's GUI GPIO Output functions.
 * 
 * This script is designed to run in the background and monitor a DS18B20 digital temperature
 * sensor installed on a Raspberry Pi.
 * 
 * It toggles GPIOs on and off automatically to control relays for two sets of
 * cooling fans and a (12v) heater (portable automotive defroster) used in our projector
 * enclosures. The same Raspberry Pi is used to control the projector and display a virtual
 * matrix and in-show videos.
 * 
 * Basic DS18B20 wiring information can be found here:
 * https://www.circuitbasics.com/raspberry-pi-ds18b20-temperature-sensor-tutorial/
 * 
 * I just wire up the sensor by soldering leads to it with a 4.7k resistor 
 * between Data and +5v. I wrap the bare leads/solder joints with Kapton tape and
 * then heat-shrink. This gives me a temperature probe that I can connect several inches
 * away from the RPi. It can be mounted just using 1/4" low-voltage cable loops or
 * with zip-tie mounting squares. The sensor should be mounted above the center of the
 * projector to get a good "average" temperature inside the enclosure. Having it too
 * close to an air intake or the exhaust from the projector will not yield good results.
 * 
 * WARNING: The following settings, as well as the 1Wire address set below, will be 
 * deleted if you do an "FPP-OS" upgrade! This is because the operating system is completely
 * replaced, with these non-default options being removed.
 * 
 * Edit /boot/config.txt and add the following to the bottom:
 * dtoverlay=w1-gpio
 * 
 * Edit /etc/modules and add the following to the bottom:
 * w1-gpio
 * w1-therm
 *
 * The 1Wire interface uses unique device serial numbers, so once set up you will
 * need to determine the device's serial number in /sys/bus/w1/devices and configure
 * it in $devPath below. There should be a 'temperature' file that stores the Celsius
 * temperature in 1000ths in this folder. Reading this file triggers the sensor.
 * 
 * This script should be run ONCE in the background using UserCallbackHook.sh in FPP which
 * can be found in the FPP Script Repository. Put it in the boot section so it only runs
 * once on startup.
 * 
 * THIS SCRIPT RUNS A CONTINUIOUS LOOP! 
 * YOU MUST END IT WITH ' &' SO IT RUNS IN THE BACKGROUND, OR IT WILL PREVENT FPP FROM BOOTING!!!
 * 
 * Like this:
 *    boot)		
 *      # put your commands here
 *      /bin/php /home/fpp/media/scripts/thermostat.php &
 *      ;;
 * 
 * Besides triggering the GPIOs, it also writes its status to the FPP log directory as
 * "thermostat.log". This is reset on each run so it shouldn't get too out of hand, especially
 * since we power down the system during off-hours. You can check this log file to see what
 * the temperature of the enclosure is and what fans/heater should be running.
 * 
 ***/

/*** You must configure the following: ***/

//The temperature sensor's 1Wire address will include a unique serial number:
$devPath = "/sys/bus/w1/devices/28-3c10e381052d/";      //Must end with a '/'
//You must change this:         ^^^^^^^^^^^^^^^
//If you are using multiple 1Wire devices, you will need to determine which one is your
//temperature probe.

//This is the file that stores the raw Celcius temperature value:
$tempFile = "temperature";      //You shouldn't need to change this.

//Wait time before starting to monitor the temperature.
//Sometimes this script will actually run before the 1W wire module is loaded, and it will
//falsely detect a temperature sensor error. This gives the module more time to load.
//Generally 5 seconds should be enough time.
$waitTime = 5;

//Generally this should be between 15-60 seconds. More often may tie up processes
//needed for the display, and too long may not react fast enough to prevent overheating.
$loopTime = 15;         //Number of seconds between updates.

//Cooling:
//First stage cooling fan(s). I use this for the fan closest to the projector's cooling output.
$fan1_gpio = 20;       //Pixel overlay name for first stage fan(s)
$fan1Temp = 24;        //Temp in °C to turn fan1 on. Default is 24 (75F)

//Second stage cooling fan(s). I use this for the remaining enclosure cooling fans.
$fan2_gpio = 21;       //Pixel overlay name for the second stage fan(s).
$fan2Temp = 28;        //Temp in °C to turn fan2 on. Default is 28 (82F)

//Heating:
$heat_gpio = 16;       //Heater FPP Pixel Overlay name (Heater Fan)
$heatTemp = 4;         //Temp in °C to turn heater on. Default is 4 (39F)
//The heating temperature should be just below the lower operating temperature of the projector.
//Most have an operating temperature range starting at 40F.

//Overheat Protection:

//A significant portion of this script is dedicated to protecting the projecgtor from
//overheating. The projector itself will shut down if it actually overheats, but I would
//rather not rely on that to happen. Set the following carefully!

//This will put an alarm message in thermostat.log, and send a shut down signal to the projector.
$warnTemp = 38;                 //Temperature warning trigger. Default is 38 (100F)
//The above should be close to the maximum operating temperature of the projector.

$shutdown = 'http://localhost/api/scripts/PROJECTOR-OFF.sh/run'; //Projector shut-down API URL

/*** Leave the following alone unless you really know what you are doing... ***/


//Get data from API
function do_get($url)
{
        //Initiate cURL.
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);

        //Set timeouts to reasonable values:
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        //Execute the request
        if ($curlResult = curl_exec($ch)) {
                $arrReturn = json_decode($curlResult, TRUE);
                return $arrReturn;
        }
        return FALSE;
}

//Rotate the log file(s). We want to keep the last three.
if ( is_file ('/home/fpp/media/logs/thermostat.3.log') ) unlink ('/home/fpp/media/logs/thermostat.3.log');
if ( is_file ('/home/fpp/media/logs/thermostat.2.log') ) rename ('/home/fpp/media/logs/thermostat.2.log', '/home/fpp/media/logs/thermostat.3.log');
if ( is_file ('/home/fpp/media/logs/thermostat.log') ) rename ('/home/fpp/media/logs/thermostat.log', '/home/fpp/media/logs/thermostat.2.log');

sleep ($waitTime);

$shutdownFlag = FALSE;

while (TRUE) {
        
        $rawTemp = file($devPath . $tempFile);
        $cTemp = round(intval($rawTemp[0])  / 1000);         //Temp is in 1000ths
        $fTemp = round($cTemp * 1.8 + 32);                   //Used just for display/logs
        $status = "\n" . date('Y-m-d H:i:s') . "\tEncl. Temp. is: " . $cTemp . "°C (" . $fTemp . "°F)";
        
        if ($shutdownFlag) {
                //Projector was going to overheat. Make sure fans stay on full and heater is off.
                //FPP will need to be rebooted to clear the error!
                exec("/opt/fpp/src/fpp -G $fan1_gpio,Output");
                exec("/opt/fpp/src/fpp -G $fan2_gpio,Output");
                exec("/opt/fpp/src/fpp -G $heat_gpio,Output");
                exec("/opt/fpp/src/fpp -g $fan1_gpio,Output,1");
                exec("/opt/fpp/src/fpp -g $fan2_gpio,Output,1");
                exec("/opt/fpp/src/fpp -g $heat_gpio,Output,0");
                $status .= "\n\t*** TEMP CONTROL ERROR ***\n\tProjector is being shut down!";
                $status .= "\n\tYou will need to reboot FPP to clear this state.";
                do_get($shutdown);       //Keep sending shutdown command.
                file_put_contents('/home/fpp/media/logs/thermostat.log', $status, FILE_APPEND);
                sleep($loopTime);
                continue;
        }

        if ($rawTemp == 0) {
                //Failsafe in case there is no reading from the sensor.'
                //This will happen if you do an FPP OS upgrade! Remember to reconfigure the sensor!
                //Without this- the heater will run all the time and the fans will never turn on.
                $status .= "\n*** TEMP SENSOR ERROR - SYSTEM SHUTDOWN ***";
                $status .= "\nYou will need to reboot FPP to clear this state.";
                $shutdownFlag = TRUE;
                do_get($shutdown);
                file_put_contents('/home/fpp/media/logs/thermostat.log', $status, FILE_APPEND);
                sleep($loopTime);
                continue;
        }

        //High temperature warning and shutdown.
        if ($cTemp >= $warnTemp) {
                //Log high temperature and shut down projector, if $shutdown is configured.
                $status .= "\n*** OVERTEMP WARNING - SYSTEM SHUTDOWN ***";
                $status .= "\nYou will need to reboot FPP to clear this state.";
                $shutdownFlag = TRUE;
                do_get($shutdown);
                continue;
        }

        //Cooling
        if ($cTemp >= $fan1Temp) {
                //Fan Stage 1 On
                exec("/opt/fpp/src/fpp -G $fan1_gpio,Output");
                exec("/opt/fpp/src/fpp -g $fan1_gpio,Output,1");
                $status .= "\tFan 1 is ON ";
        } else {
                //Pixel Overlay: Fan Stage 1 Off
                exec("/opt/fpp/src/fpp -G $fan1_gpio,Output");
                exec("/opt/fpp/src/fpp -g $fan1_gpio,Output,0");
                $status .= "\tFan 1 is OFF ";
        }
        if ($cTemp >= $fan2Temp) {
                //Fan Stage 2 On
                exec("/opt/fpp/src/fpp -G $fan2_gpio,Output");
                exec("/opt/fpp/src/fpp -g $fan2_gpio,Output,1");
                $status .= "\tFan 2 is ON ";
        } else {
                //Fan Stage 2 Off
                exec("/opt/fpp/src/fpp -G $fan2_gpio,Output");
                exec("/opt/fpp/src/fpp -g $fan2_gpio,Output,0");
                $status .= "\tFan 2 is OFF ";
        }

        //Heater
        if ($cTemp <= $heatTemp) {
                //Heater On
                exec("/opt/fpp/src/fpp -G $heat_gpio,Output");     
                exec("/opt/fpp/src/fpp -g $heat_gpio,Output,1");
                $status .= "\tHeat is ON ";
        } else {
                //Heater Off
                exec("/opt/fpp/src/fpp -G $heat_gpio,Output");     
                exec("/opt/fpp/src/fpp -g $heat_gpio,Output,0");
                $status .= "\tHeat is OFF ";
        }
        
        file_put_contents('/home/fpp/media/logs/thermostat.log', $status, FILE_APPEND);
        sleep($loopTime);
}
