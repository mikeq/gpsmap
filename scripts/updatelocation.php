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
require_once 'lib/location.class.php';
error_log(print_r($_POST, true));
/**
 * Values sent by https://www.websmithing.com/gps-tracker/
 * sent as POST variables by the application
 *
 * longitude
 * latitude
 * extrainfo - Supposed to be altitude but 0.0 is being sent
 * distance
 * date - format YYYY-MM-DD+HH:MM:SS
 * direction - presumably the compass bearing but 0.0 is being passed
 * accuracy - GPS accuracy
 * phonenumber - just a string that the user can set in the application
 * eventype - Android
 * sessionid - unique identifier
 * speed - seems to pass 0.0 rather than the actual speed
 * locationmethod - fused, android GPS location method used
 */

require_once 'lib/gpswriter.class.php';

try {
    // as a simple layer of security pass the string set in lib/util/settings.class.php (Settings::DEVICEID) from your device
    if ($_POST['phonenumber'] !== Settings::DEVICEID) {
        return false;
    }

    $writer = new GPSTrackerWriter();
    /**
     * Not passing all values possible from Open GPS Tracker application, you will need to amend the
     * Location::storePoint method if you want to store other parameters and possibly the gpsdata table
     */
    $writer->storePoint($_POST['phonenumber'], $_POST['sessionid'], $_POST['date'], $_POST['latitude'], $_POST['longitude'], $_POST['extrainfo']);
} catch (Exception $e) {
    error_log($e->getMessage());
}
