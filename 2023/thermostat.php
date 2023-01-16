<?php
/****
 * thermostat.php
 * by Wolf I Butler
 * v. 1.01 (First Production)
 * 
 * This script is designed to run in the background and monitor a DS18B20 digital temperature
 * sensor installed on a Raspberry Pi.
 * 
 * For right now this just echos text with the temperature and heater/fan status.
 * It will eventually use pixel overlays tied to GPIO relays to control a heater
 * and two sets of fans (1 top vent and 2-3 side vents).
 * 
 * I wrote this to help manage the temperature in my projector enclosures.
 * 
 * Basic DS18B20 wiring information can be found here:
 * https://www.circuitbasics.com/raspberry-pi-ds18b20-temperature-sensor-tutorial/
 * 
 * Edit /boot/config.txt and add the following to the bottom:
 * dtoverlay=w1-gpio
 * 
 * Edit /etc/modules and add the following to the bottom:
 * w1-gpio
 * w1-therm
 * 
 * I just wire up the sensor by soldering leads to the sensor with a 4.7k resistor 
 * between Data and +5v. I wrap the bare leads/solder joints with Kapton tape and
 * then heat-shrink. This gives me a temperature probe that I can connect several inches
 * away from the RPi. It can be mounted just using 1/2" low-voltage cable loops or
 * with zip-tie mounting squares. 
 *
 * The 1Wire interface uses unique device serial numbers, so once set up you will
 * need to determine the device's serial number in /sys/bus/w1/devices and configure
 * it below.
 * 
 * This script should be run ONCE in the background using UserCallbackHook.sh which can be found
 * in the FPP Script Repository. Put it in the boot section so it only runs once on startup.
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
***/

//You must configure the following:

//The temperature sensor's 1Wire address will include a unique serial number:
$devAddress = "/sys/bus/w1/devices/28-3c43e38183cb/";

//This is the file that stores the raw Celcius temperature value:
$tempFile = "temperature";    //(You shouldn't need to change this.)

$loopTime = 30;     //Number of seconds between updates.

//Cooling:
$fan1_overlay = 'fan1';     //Side fan(s) FPP Pixel Overlay name (first stage cooling)
$fan1Temp = 30;         //Temp in °C to turn fan1 on

$fan2_overlay = 'fan2';     //Top fan(s) FPP Pixel Overlay name (Second stage cooling)
$fan2Temp = 35;         //Temp in °C to turn fan2 on

$warnTemp = 40;         //Adds a temp warning to the log file.

//Heating:
$heat_overlay = 'heater';    //Heater FPP Pixel Overlay name (Heater Fan)
$heatTemp = 0;           //Temp in °C to turn heater on

//Leave the following alone unless you really know what you are doing...

unlink ( '/home/fpp/media/logs/thermostat.log' );       //Clear the log file.

while ( TRUE ) {                                                                                                                                                        
        $rawTemp = file ( "/sys/bus/w1/devices/28-3c43e38183cb/temperature" );
        $cTemp = round ( intval ( $rawTemp[0] )  / 1000 );
        $fTemp = round ( $cTemp * 1.8 + 32 );
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