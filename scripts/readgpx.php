<?php
/**
 * Reads a GPX Track file to get the elevation data
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
require_once 'lib/util/utilities.class.php';

$xml = simplexml_load_file('../data/day' . $_GET['dayid'] . '.gpx');
$preLat = 0;
$preLon = 0;
$totDistance = 0;
$elevationArray = array();

foreach ($xml->trk->trkseg->trkpt as $value) {
    $att = $value->attributes();

    if ($preLat !== 0 && $preLon !== 0) {
        $distance = Utilities::haversine($preLat, $preLon, floatval($att->lat), floatval($att->lon));
        $totDistance += ($distance * Settings::KM_TO_MILES);
    }

    $elevationArray[] = array((string)$totDistance, (string)($value->ele * Settings::METRE_TO_FEET));
    $preLat = floatval($att->lat);
    $preLon = floatval($att->lon);
}

print_r(json_encode($elevationArray));