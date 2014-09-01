<?php
/**
 * Defines the GPS writer class
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

require_once 'util/settings.class.php';
require_once 'util/utilities.class.php';
require_once 'util/db.pdo.class.php';
require_once 'location.class.php';

/**
 * GPSWriter
 *
 * @category   GPSMap
 * @package    GPSMap
 * @author     Mike Quinn <gpsmap@velochallenge.org>
 * @copyright  2014 Mike Quinn
 */
abstract class GPSWriter
{
    protected $dbConn;
    protected $location;

    /**
     * constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->dbConn = PdoDatabaseConnection::getInstance(Settings::TRACKING_DSN)->getConnection();
        $this->location = new Location();
    }

    /**
     * Store the GPS point in the databas
     *
     * @abstract
     * @access public
     * @return boolean false if device ID does not match Settings
     */
    abstract public function storePoint($device, $trackId, $timestamp, $latitude, $longitude, $altitude);
}

/**
 * Open GPS Tracker writer class (https://code.google.com/p/open-gpstracker/)
 *
 * @category   GPSMap
 * @package    GPSMap
 * @author     Mike Quinn <gpsmap@velochallenge.org>
 * @copyright  2014 Mike Quinn
 */
class OpenGPSTrackerWriter extends GPSWriter
{
    /**
     * Store the GPS point in the database
     *
     * @param string  $deviceid  Pass in an ID that matches the deviceid in the Settings class
     * @param integer $trackId   A unique id identifying the point belongs to a certain track
     * @param integer $timestamp Timestamp of the GPS point in milliseconds
     * @param float   $latitude  GPS Point latitude (points to the South should be negative)
     * @param float   $longitude GPS Point longitude (points to the West should be negative)
     * @param integer $altitude  Altitude of the GPS point in meters
     *
     * @access public
     * @return boolean false if device ID does not match Settings
     */
    public function storePoint($device, $trackId, $timestamp, $latitude, $longitude, $altitude)
    {
        if ($device !== Settings::DEVICEID) {
            return false;
        }

        $theDate = new DateTime(null, new DateTimeZone(Settings::TIMEZONE));
        $theDate->setTimestamp($timestamp/1000);

        try {
            $lastPoint = $this->location->getLatestLocation($theDate->format('Y-m-d H:i:s'));

            if ($lastPoint !== false) {
                $newDistance = Utilities::haversine($lastPoint['lat'], $lastPoint['lng'], $latitude, $longitude);
                $distance = $lastPoint['distance'] + ($newDistance * Settings::KM_TO_MILES);

                $previousDataTime = new DateTime($lastPoint['datatime'], new DateTimeZone(Settings::TIMEZONE));
                $interval = $theDate->diff($previousDataTime);
                $secondsPassed = $interval->format('%s');

                $speedMPS = ($newDistance * 1000) / $secondsPassed;
                $speed = $speedMPS * Settings::MPS_TO_MPH;
                $heading = Utilities::getRhumbLineBearing($lastPoint['lat'], $lastPoint['lng'], $latitude, $longitude);
            } else {
                $speed = 0;
                $heading = 0;
                $distance = 0;
            }

            $insert = "REPLACE INTO gpsdata (devicekey, tracktag, coord, altitude, datatime, speed, heading, distance)
                VALUES
                (:device, :trackid, GeomFromText('POINT($latitude $longitude)'), :altitude, :datatime, :speed, :heading, :distance)";

            $stmt = $this->dbConn->prepare($insert);

            $stmt->execute(
                array(
                    ':device' => $device,
                    ':trackid' => $trackId,
                    ':altitude' => $altitude,
                    ':datatime' => $theDate->format('Y-m-d H:i:s'),
                    ':speed' => $speed,
                    ':heading' => $heading,
                    ':distance' => $distance
                )
            );
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

}

/**
 * GPS Tracker writer class (https://www.websmithing.com/gps-tracker/)
 *
 * @category   GPSMap
 * @package    GPSMap
 * @author     Mike Quinn <gpsmap@velochallenge.org>
 * @copyright  2014 Mike Quinn
 */
class GPSTrackerWriter extends GPSWriter
{
    /**
     * Store the GPS point in the database
     *
     * @param string  $deviceid  Pass in an ID that matches the deviceid in the Settings class
     * @param integer $trackId   A unique id identifying the point belongs to a certain track
     * @param integer $timestamp Timestamp of the GPS point in milliseconds
     * @param float   $latitude  GPS Point latitude (points to the South should be negative)
     * @param float   $longitude GPS Point longitude (points to the West should be negative)
     * @param integer $altitude  Altitude of the GPS point in meters
     *
     * @access public
     * @return boolean false if device ID does not match Settings
     */
    public function storePoint($device, $trackId, $timestamp, $latitude, $longitude, $altitude)
    {
        if ($device !== Settings::DEVICEID) {
            return false;
        }

        $theDate = new DateTime(urldecode($timestamp), new DateTimeZone(Settings::TIMEZONE));
        error_log("The date is " . $theDate->format('Y-m-d H:i:s'));
        try {
            $lastPoint = $this->location->getLatestLocation($theDate->format('Y-m-d H:i:s'));

            if ($lastPoint !== false) {
                $newDistance = Utilities::haversine($lastPoint['lat'], $lastPoint['lng'], $latitude, $longitude);
                $distance = $lastPoint['distance'] + ($newDistance * Settings::KM_TO_MILES);

                $previousDataTime = new DateTime($lastPoint['datatime'], new DateTimeZone(Settings::TIMEZONE));
                $interval = $theDate->diff($previousDataTime);
                $secondsPassed = $interval->format('%s');

                $speedMPS = ($newDistance * 1000) / $secondsPassed;
                $speed = $speedMPS * Settings::MPS_TO_MPH;
                $heading = Utilities::getRhumbLineBearing($lastPoint['lat'], $lastPoint['lng'], $latitude, $longitude);
            } else {
                $speed = 0;
                $heading = 0;
                $distance = 0;
            }

            $insert = "REPLACE INTO gpsdata (devicekey, tracktag, coord, altitude, datatime, speed, heading, distance)
                VALUES
                (:device, :trackid, GeomFromText('POINT($latitude $longitude)'), :altitude, :datatime, :speed, :heading, :distance)";

            $stmt = $this->dbConn->prepare($insert);

            $stmt->execute(
                array(
                    ':device' => $device,
                    ':trackid' => $trackId,
                    ':altitude' => $altitude,
                    ':datatime' => $theDate->format('Y-m-d H:i:s'),
                    ':speed' => $speed,
                    ':heading' => $heading,
                    ':distance' => $distance
                )
            );
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

}
