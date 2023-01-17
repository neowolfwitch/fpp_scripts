<?php
/****
 * thermostat.php
 * by Wolf I Butler
 * v. 1.02 (First Production)
 * 
 * This script is designed to run in the background and monitor a DS18B20 digital temperature
 * sensor installed on a Raspberry Pi.
 * 
 * It uses FPP pixel overlay models to toggle GPIO-attached relays that control two sets of
 * cooling fans and a (12v) heater (portable automotive defroster) used in our projector
 * enclosures. The same Raspberry Pi is used to control the projector and displahy a virtual
 * matrix and in-show videos.
 * 
 * If not run as part of an FPP instance, it can be modified to use shell script GPIO trigger
 * commands, such as the "gpio" command, or using /sys/class/gpio commands. I'm using overlay
 * models so I have the option of further controlling the system within the FPP scheduler, and
 * because this is designed to run on the projector's Raspberry Pi manager and video source.
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
$devPath = "/sys/bus/w1/devices/28-3c43e38183cb/";      //Must end with a '/'
//You must change this:         ^^^^^^^^^^^^^^^
//If you are using multiple 1Wire devices, you will need to determine which one is your
//temperature probe.

//This is the file that stores the raw Celcius temperature value:
$tempFile = "temperature";      //You shouldn't need to change this.

//Generally this should be between 30-60 seconds. More often may tie up processes
//needed for the display, and too long may not react fast enough to prevent overheating.
$loopTime = 30;                 //Number of seconds between updates.

//Cooling:
$fan1_overlay = 'fan1';         //Side fan(s) FPP Pixel Overlay name (first stage cooling)
$fan1Temp = 30;                 //Temp in °C to turn fan1 on

$fan2_overlay = 'fan2';         //Top fan(s) FPP Pixel Overlay name (Second stage cooling)
$fan2Temp = 35;                 //Temp in °C to turn fan2 on

$warnTemp = 40;                 //Adds a temp warning to the log file.
//The above should be close to the maximum operating tempearture of the projector.
//Not doing it now, but we may add a projector shutdown, dim, or some other mitigation 
//process if the warning temperature is reached/exceeded.

//Heating:
$heat_overlay = 'heater';       //Heater FPP Pixel Overlay name (Heater Fan)
$heatTemp = 4;                  //Temp in °C to turn heater on.
//The heating temperature should be just below the lower operating temperature of the projector.

/*** Leave the following alone unless you really know what you are doing... ***/

unlink ( '/home/fpp/media/logs/thermostat.log' );       //Clear the log file.

while ( TRUE ) {                                                                                                                                                        
        $rawTemp = file ( $devPath.$tempFile );
        $cTemp = round ( intval ( $rawTemp[0] )  / 1000 );      //Temp is in 1000ths
        $fTemp = round ( $cTemp * 1.8 + 32 );                   //Used just for display/logs
        $status = "\n" . date ( 'Y-m-d H:i:s' ) . "\tEncl. Temp. is: " .$cTemp. "°C (" .$fTemp. "°F)";
        //The following will be replaced with pixel overlay commands, but for
        //now I am just logging the text...
        
        //Cooling
        if ( $cTemp >= $fan1Temp ) {
                //Pixel Overlay: Fan Stage 1 On
                exec ( "fppmm -m $fan1_overlay -o on " );
                exec ( "fppmm -m $fan1_overlay -s 255 " );
                $status .= "\tFan 1 is ON ";
        }
        else {
                //Pixel Overlay: Fan Stage 1 Off
                exec ( "fppmm -m $fan1_overlay -s 0 " );
                sleep (1);      //Recommended in FPP docs.
                exec ( "fppmm -m $fan1_overlay -o off " );
                $status .= "\tFan 1 is OFF ";
        }
        if ( $cTemp >= $fan2Temp ) {
                //Pixel Overlay: Fan Stage 2 On
                exec ( "fppmm -m $fan2_overlay -o on " );
                exec ( "fppmm -m $fan2_overlay -s 255 " );
                $status .= "\tFan 2 is ON ";
        }
        else {
                //Pixel Overlay: Fan Stage 2 Off
                exec ( "fppmm -m $fan2_overlay -s 0 " );
                sleep (1);      //Recommended in FPP docs.
                exec ( "fppmm -m $fan2_overlay -o off " );
                $status .= "\tFan 2 is OFF ";
        }

        //Heater
        if ( $cTemp <= $heatTemp ) {
                //Pixel Overlay: heater On
                exec ( "fppmm -m $heat_overlay -o on " );
                exec ( "fppmm -m $heat_overlay -s 255 " );
                $status .= "\tHeater is ON ";
        }
        else {
                //Pixel Overlay: heater Off
                exec ( "fppmm -m $heat_overlay -s 0 " );
                sleep (1);      //Recommended in FPP docs.
                exec ( "fppmm -m $heat_overlay -o off " );
                $status .= "\tHeater is OFF ";
        }

        //Warning
        if ( $cTemp >= $warnTemp ) {
                //Possible to shut down projector or drop brightness?
                $status .= "\t***OVERTEMP WARNING***";
        }

        file_put_contents ( '/home/fpp/media/logs/thermostat.log', $status, FILE_APPEND );

        sleep ( $loopTime );                                                                                                                                                   
}
?>