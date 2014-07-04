<?php
/**
 * Used to feed GPX coordinates to the database for testing purposes
 *
 * PHP version 5
 *
 * This file is part of GPSMap.
 *
 * GPSMap is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GPSMap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GPSMap.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   GPSMap
 * @package    GPSMap
 * @author     Mike Quinn <gpsmap@velochallenge.org>
 * @copyright  2014 Mike Quinn
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 */
require_once 'lib/location.class.php';

if ($argc < 2) {
    print "php gps_feed.php dayId [date] [sleep]" . PHP_EOL;
    die("not enough parameters passed" . PHP_EOL);
}

$dayId = $argv[1];

if (empty($argv[2])) {
    $now = new DateTime(null, new DateTimeZone(Settings::TIMEZONE));
} else {
    $now = new DateTime($argv[2], new DateTimeZone(Settings::TIMEZONE));
}

$timestamp = $now->format('U');

$sleep = empty($argv[3]) ? 0 : $argv[3];

$points = array();
$pointTemplate = '{"LAT":"%s","LON":"%s","DEVICE":"%s","TRACKID":"5","ALTITUDE":"%s","TIME":"%s"}';

$location = new Location();
$xml = simplexml_load_file('../data/day' . $dayId . '.gpx');
$i = 0;

foreach ($xml->trk->trkseg->trkpt as $value) {
    $att = $value->attributes();
    $i++;
    $dataTime = ($timestamp + $i) . '000';
    $points[] = sprintf($pointTemplate, $att->lat, $att->lon, Settings::DEVICEID, $value->ele, $dataTime);
}

$url = Settings::TESTTRACKING_URL;
$adb = curl_init();

foreach ($points as $point) {
    $pointObj = json_decode($point);
    $send = sprintf(
        $url,
        $pointObj->DEVICE,
        $pointObj->LAT,
        $pointObj->LON,
        $pointObj->TRACKID,
        $pointObj->ALTITUDE,
        $pointObj->TIME
    );

    print "sending: " . $send . PHP_EOL;
    $options = array(CURLOPT_URL => $send);
    curl_setopt_array($adb, $options);
    curl_exec($adb);
    sleep($sleep);
}

curl_close($adb);