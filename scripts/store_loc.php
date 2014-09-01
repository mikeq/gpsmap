<?php
/**
 * Server side script to be called by Open GPS Tracker (open source android gps tracking application)
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
/**
 * possible values that can be uploaded by open-gpstracker
 *
 * passing in "d" as device
 * @LAT@ passing in as "lat"
 * @LON@ passing in as "lon"
 * @ID@ passing in as "id"
 * @ALT@ passing in as "alt"
 * @ACC@
 * @SPEED@
 * @BEAR@
 * @TIME@ passing in as "t"
 */
require_once 'lib/gpswriter.class.php';

try {
    // as a simple layer of security pass the string set in lib/util/settings.class.php (Settings::DEVICEID) from your device
    if ($_GET['d'] !== Settings::DEVICEID) {
        return false;
    }

    $writer = new OpenGPSTrackerWriter();
    /**
     * Not passing all values possible from Open GPS Tracker application, you will need to amend the
     * Location::storePoint method if you want to store other parameters and possibly the gpsdata table
     */
    $writer->storePoint($_GET['d'], $_GET['id'], $_GET['t'], $_GET['lat'], $_GET['lon'], $_GET['alt']);
} catch (Exception $e) {
    error_log($e->getMessage());
}
