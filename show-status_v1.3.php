<?php
/*** 
 * show-status.php
 * by Wolf I. Butler
 * v. 1.3, Last Updated: 11/05/2021
 * 
 * This script displays the status of my show. It is based on info-matrix.php.
 * 
 * This should be run on the FPP that is controlling the show status matrix.
 * It must have network access to the main show-runner and any other monitored FPP instances.
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
 *      /bin/php /home/fpp/media/scripts/show-status.php &
 *      ;;
 * 
 * This script uses code from "PixelOverlay-ScrollingText.php"
 * From the FPP Script Repository. The author was not attributed in that source.
 * 
 * License: CC0 1.0 Universal: This work has been marked as dedicated to the public domain.
 * 
 * This script is provided ​“AS IS”. Developer makes no warranties, express or implied, 
 * and hereby disclaims all implied warranties, including any warranty of merchantability 
 * and warranty of fitness for a particular purpose.
 * 
*/

//*** You must edit the following sections... ***

define ( 'GAP', ', ');

//The following is used to display the text in FPP:
$host  = "10.0.0.5";        # Host/ip of the show-runner FPP instance. This can be localhost, an IP address, or a resolvable host name.
$name  = "LED+Panels";      # Pixel Overlay Model Name. Verify name in FPP! Use "+" for any spaces. URL Encode any other special chars.
$color = "RAND";            # Color code (#00FF00), value (Green), or "RAND" for random.
$font  = "DejaVuSans";      # Font Name. RAND will randomize fonts.
$size  = 14;                # Font size
$pos   = "R2L";             # Position/Scroll: 'Center', 'L2R', 'R2L', 'T2B', 'B2T'
$pps   = 48;                # Pixels Per Second to Scroll
$antiAlias = TRUE;          # Anti-Alias the text

//Block mode. You should leave this at 1 unless you need to enable advanced functionality.
//Set to 2 for Transparent mode, or 3 for Transparent RGB
$blockMode = 1;

//Sleep time. This is a loop delay in seconds to prevent this script from overloading FPP.
//It should always be at least 1 second, although it should be set higher if you notice performance
//or display issues (including the wrong information for a sequence). Default is 10 seconds.
$sleepTime = 10; //Seconds

//Show Name to display:
$showName = "Oak Hills Lights";

//Power controller and Overlays used for GPIOs. Set $power to FALSE if not using.
$power = "10.0.0.33";
$powerOverlays = array ( "Power_Left", "Power_Right" ); //Pixel overlay(s) to test.
$powerNames = array ( "Power-L", "Power-R" );   //Display names.

//FM Transmitter power. Set to FALSE if not using.
$FM = "10.0.0.5";
$fmOverlay = "FM-Power";    //Pixel overlay to test

//Projector power. Set to FALSE if not using.
$proj = "10.0.0.5";
$projOverlay = "Projector-Power";   //Pixel overlay to test.

//Info-array. (Tune-To, Song Info.) Set to FALSE if not using.
$info = "10.0.0.20";
$infoOverlay = "LED+Panels";    //Pixel overlay to test.

/*
 ************************************************************************************
 * That's it. Do not edit anything below unless you REALLY know what you are doing. *
 ************************************************************************************
*/

# Function to post the data to the REST API
function do_put ( $url, $data ) {

    $data = json_encode ( $data );
    //Initiate cURL.
    $ch = curl_init($url);
    //Tell cURL that we want to send a PUT request.
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    
    //Attach our encoded JSON string to the PUT fields.
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    //Set the content type to application/json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //Set timeouts to reasonable values:
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    //Execute the request
    $curlResult = curl_exec($ch);
    
    curl_close($ch);

    return $curlResult;
}

//Get data from API
function do_get ( $url ) {
    //Initiate cURL.
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    
    //Set timeouts to reasonable values:
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    //Execute the request
    if ( $curlResult = curl_exec($ch) ) {
        $arrReturn = json_decode ( $curlResult, TRUE );
        return $arrReturn;
    }
    else return FALSE;
}

//Check to see if the model is currently active.
function is_active ( $host, $name ) {
    $arrStatus = do_get ( "http://$host/api/overlays/model/$name" );
    if ( $arrStatus['isActive'] ) return TRUE;
    else return FALSE;
}

//Play text to matrix
function play_text ( $name, $outText, $blockMode, $arrDispConfig, $resetDisplay ) {
    global $fontList;

    if ( $resetDisplay ) {
        do_get ( "http://localhost/api/overlays/model/$name/clear" );
        do_put ( "http://localhost/api/overlays/model/$name/state", array( 'state' => 0 ) );
    }
    else {
        //Don't do anything if there is an active effect.
        $arrStatus = do_get ( "http://localhost/api/overlays/model/$name" );
        if ( $arrStatus['effectRunning'] ) return FALSE;
    }

    //Initialize display.
    $data = array( 'State' => $blockMode );
    do_put("http://localhost/api/overlays/model/$name/state", $data ); 

    if ( $arrDispConfig['Font'] == 'RAND' ) {
        //Pick a random font and display it on the console (Temporary)
        $x = array_rand( $fontList );
        $font = $fontList[$x];
        $arrDispConfig['Font'] = $font;
        echo "\nRandom font: $font";
    }
    
    if ( $arrDispConfig['Color'] == 'RAND' ) {
        
        $total = 0;
        while ( $total < 400 ) {    //Setting for minimum brightness.
            //Set these for a dimmer or brighter display.
            $rd = rand ( 0, 150 );  //Never want all red, since that indicates an error.
            $gr = rand ( 0, 255 ); 
            $bl = rand ( 0, 255 );
            $total = $rd + $gr + $bl;
        }

        $rd = strval ( dechex ( $rd ) );
        $gr = strval ( dechex ( $gr ) ); 
        $bl = strval ( dechex ( $bl ) );

        //Pad for color codes
        if ( strlen ( $rd ) < 2 ) $rd = '0' . $rd;
        if ( strlen ( $gr ) < 2 ) $gr = '0' . $gr;
        if ( strlen ( $bl ) < 2 ) $bl = '0' . $bl;
        $arrDispConfig['Color'] = '#' . $rd . $gr . $bl;
    }

    $arrDispConfig['Message'] = $outText;
    return do_put("http://localhost/api/overlays/model/$name/text",  $arrDispConfig ); 
}

//Init:
$online = null;
$lastStatus = null;
$lastSequence = null;
$lastText = null;
$infoOffCount = 0;

$fontList = do_get ( "http://localhost/api/overlays/fonts" );

$arrDispConfig = array(
    'Color' => $color,
    'Font' => $font,
    'FontSize' => $size,
    'AntiAlias' => $antiAlias,
    'Position' => $pos,
    'PixelsPerSecond' => $pps
);

//Loop continuously
while (TRUE) {

    $fppStatus = do_get( "http://$host/api/fppd/status" );

    if ( $fppStatus['fppd'] ) {
        //fpp is running.
        $online = TRUE;
        $arrDispConfig['Color'] = $color;    //Just in case it was overridden (below).

        $outText = "$showName is Online" . GAP;
        
        if ( $power ) {
            $commaFlag = FALSE;
            foreach ( $powerOverlays as $pwrIndex => $overlay ) {
                if ( $commaFlag ) $outText .= ', ';
                else $commaFlag = TRUE;
                if ( is_active ( $power, $overlay ) ) $outText .= $powerNames[$pwrIndex] . ": ON";
                else $outText .= $powerNames[$pwrIndex] . ": OFF";
            }
            $outText .= GAP;
        }

        if ( $FM ) {
            if ( is_active ( $FM, $fmOverlay ) ) $outText .= "FM: ON" . GAP;
            else $outText .= "FM: OFF" . GAP;
        }

        if ( $proj ) {
            if ( is_active ( $proj, $projOverlay ) ) $outText .= "Projector: ON" . GAP;
            else $outText .= "Projector: OFF" . GAP;
        }

        //Using infoOffCount for this because the overlay may be off for a few seconds at a time
        //between text cycles. This introduces a delay in the "Off" indicator, but reduces false positives.
        if ( $info ) {
            if ( is_active( $info, $infoOverlay ) ) {
                $infoOffCount = 0;
                $outText .= "Info: ON" . GAP;
            }    
            else {
                if ( $infoOffCount > 2 ) $outText .= "Info: OFF" . GAP;
                else {
                    $infoOffCount++;
                    $outText .= "Info: ON" . GAP;            
                }
            }
        }

        $playlist = $fppStatus['current_playlist']['playlist'];
        if ( $playlist ) {
            $outText .= "Playlist: \"$playlist\",";

            if ( $sequence = $fppStatus['current_sequence'] ) {
                $outText .= " Sequence: $sequence";
                $outText .= ", Song: " . $fppStatus['current_song'];
            }
        }
        elseif ( $fppStatus['next_playlist']['start_time'] ) {
            $outText .= "Scheduled Playlist: \"" . $fppStatus['next_playlist']['playlist'];;
            $outText .= "\" will run: " . $fppStatus['next_playlist']['start_time'];
        }
        else $outText .= "Nothing playing or scheduled";
    }
    else
    {
        //FPP is not running!
        $online = FALSE;

        $arrDispConfig['Color'] = '#990000';    //Override- Red
        $outText = "$showName is OFFLINE!!!";
    }

    //Reset the display any time there is a change...
    $resetDisplay = FALSE;
    if ( $lastStatus != $online ) $resetDisplay = TRUE;
    if ( $lastText != $outText ) $resetDisplay = TRUE;
    $lastStatus = $online;
    $lastText = $outText;

    play_text ( $name, $outText, $blockMode, $arrDispConfig, $resetDisplay );
    sleep ($sleepTime);
}
?>