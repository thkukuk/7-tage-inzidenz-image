<?php
include('src/RKI_Key_Data.php');

# Find your AdmUnitID for the comma separated REGIONS list here:
# https://www.arcgis.com/apps/mapviewer/index.html?layers=c093fe0ef8fd4707beeb3dc0c02c3381

### Main ###

$regions=getenv("REGIONS");
if (!$regions) {
  # if no region is specified, use Germany
  $regions="0";
}
$reg_arr = explode(',', $regions);

$past_days=getenv("PAST_DAYS");
if (!$past_days) {
  $past_days="7";
}

echo "<!DOCTYPE html>
      <html>
      <head>
      <title>7 Tage Inzidenzen</title>
      </head>
      <body>";
echo "<table>";
echo "  <tr>";

foreach($reg_arr as $reg) {
    echo "    <td>";
    drawWideget($reg, $past_days);
    echo "    </td>";
}
unset ($reg);

echo "  </tr>";
echo "</table>";
echo "</body>";

### Functions ###

function drawWideget($id, $past_days)
{
    $cache_dir = '/data';
    $threshold_green = 50;
    $threshold_yellow = 100;
    $threshold_red = 200;
    # if we don't have data for today, start with yesterdays data as current
    # ones.
    $start_past = 1;

    $incidence = new RKI_Key_Data($id, $cache_dir);

    $today = $incidence->getDaily(0);
    if (!$today) {
        $today = $incidence->getDaily(1);
	if (!$today) {
	    # No data at all, quit
	    return;
	}
	$start_past = 2;
    }

    echo "<div class='widget'>";

    echo "<h3>Inzidenz für " . $today['GEN'] . "</h3>";
    echo "<h6>(Fälle pro 100.000 Einwohner in 7 Tagen)</h6>";

    drawStoplight($today['Inz7T'], $threshold_green, $threshold_yellow, $threshold_red);

    echo "<table id='tbl_incidence'>";
    echo drawLine($today, $threshold_green, $threshold_yellow, $threshold_red);
    for ($i = $start_past; $i < ($start_past + $past_days); $i++) {
        $day = $incidence->getDaily($i);
        echo drawLine($day, $threshold_green, $threshold_yellow, $threshold_red);
    }
    echo "</table>";
    echo "<h6>Quelle: <a href='https://www.rki.de/DE/Home/homepage_node.html'>RKI</a></h6>";
    echo "</div>";
}

function drawLine($data, $threshold_green, $threshold_yellow, $threshold_red)
{
    if ($data) {

        $inc = round($data['Inz7T'], 2);
        if ($inc < $threshold_green) {
            $co = "value_ok";
	} else if ($inc < $threshold_yellow) {
	    $co = "value_risk";
	} else if ($inc < $threshold_red) {
	    $co = "value_stop";
        } else {
            $co = "value_fullstop";
        }

        echo "<tr>
                <td>" . germanDay($data['ts']) . ", " . date("d.m.Y", $data['ts']) . "</td>
                <td class='" . $co . "'>" . number_format($data['Inz7T'], 2, ",", ".") . "</td>
              </tr>
    	      <tr>
	        <td id='tbl_incidence_fzt'>Fälle insgesamt:</td>
		<td id='tbl_incidence_fzn'>" . number_format($data['AnzFall'], 0, ",", ".") . " (";
	$new = $data['AnzFallNeu'];
        if ($new > 0) {
	    echo "+";
	} else if ($new < 0) {
	    echo "-";
        }
        echo number_format($new, 0, ",", ".") . ")</td>";
	echo "<tr>
	        <td id='tbl_incidence_fzt'>Tote:</td>
		<td id='tbl_incidence_fzn'>"
                    . number_format($data['AnzTodesfall'], 0, ",", ".")
		    . " (+" . number_format($data['AnzTodesfallNeu'], 0, ",", ".") . ")</td>";
        echo "</tr>";
    }
}

function drawStoplight($cases7_per_100k, $threshold_green, $threshold_yellow, $threshold_red)
{
    if ($cases7_per_100k < $threshold_green) {
        $color = "stoplight_ok";
        $text = "Geöffnet";
    } else if ($cases7_per_100k < $threshold_yellow) {
        $color = "stoplight_risk";
        $text = "Click&Meet";
    } else if ($cases7_per_100k < $threshold_red) {
        $color = "stoplight_stop";
        $text = "Mit Schnelltest";
    } else {
        $color = "stoplight_fullstop";
        $text = "Geschlossen";
    }
    echo "<div id='div_stoplight' class='" . $color . "'>";
    echo $text;
    echo "</div>";
}

function germanDay($ts)
{
    $d = [
        1 => "Montag",
        2 => "Dienstag",
        3 => "Mittwoch",
        4 => "Donnerstag",
        5 => "Freitag",
        6 => "Samstag",
        7 => "Sonntag"
    ];
    return $d[date("N", $ts)];
}

?>
<style>
    body,
    html {
        font-family: Arial, Helvetica, sans-serif;
    }

    table td, table td * {
        vertical-align: top;
    }

    h3 {
        text-align: center;
        margin: 1%;
    }

    h6 {
        text-align: center;
        margin: 1%;
        font-size: 0.5em;
    }

    .widget {
        width: 270px;
        border: thin solid #ccc;
        min-height: 200px;
    }

    #tbl_incidence {
        width: 100%;
        text-align: center;
    }

    #tbl_incidence td {
        width: 50%;
        border-bottom: thin solid #ccc;
    }

    #tbl_incidence_fzt {
        width: 100%;
        font-size: 0.9em;
	text-align: left;
    }

    #tbl_incidence_fzn {
        width: 100%;
        font-size: 0.9em;
	text-align: right;
    }

    #div_stoplight {
        margin-top: 5%;
        margin-bottom: 5%;
        padding-top: 5%;
        width: 100%;
        height: 40px;
        text-align: center;
        vertical-align: middle;
        font-size: 1.5em;
        color: #ccc;
    }

    .stoplight_fullstop {
        background-color: darkred;
    }

    .stoplight_stop {
        background-color: red;
    }

    .stoplight_risk {
        background-color: yellow;
    }

    .stoplight_ok {
        background-color: green;
    }

    .value_fullstop {
        color: darkred;
    }

    .value_stop {
        color: red;
    }

    .value_risk {
        color: #CCCC00;
    }

    .value_ok {
        color: green;
    }
</style>
</html>
