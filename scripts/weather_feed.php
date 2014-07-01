<?php
/**
 * Retrieve weather information from Open Weather Map, passes the current lat/long to the API
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
require_once 'lib/util/settings.class.php';

$url = sprintf(
    Settings::CURRENT_WEATHER_URL,
    $_GET['lat'],
    $_GET['lon']
);

$adb = curl_init();
$options = array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true
);

curl_setopt_array($adb, $options);
$result = curl_exec($adb);
print $result;
curl_close($adb);