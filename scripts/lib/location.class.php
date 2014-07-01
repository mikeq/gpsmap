<?php
/**
 * Defines the location class for GPSMap
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
require_once 'util/db.pdo.class.php';

/**
 * Location
 *
 * @category   GPSMap
 * @package    GPSMap
 * @author     Mike Quinn <gpsmap@velochallenge.org>
 * @copyright  2014 Mike Quinn
 */
class Location
{
    private $_device;
    private $_dbConn;

    /**
     * constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->_dbConn = PdoDatabaseConnection::getInstance(Settings::TRACKING_DSN)->getConnection();
    }

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
            $lastPoint = $this->_getLastStoredPoint($theDate);

            if ($lastPoint !== false) {
                $newDistance = $this->haversine($lastPoint['lat'], $lastPoint['lng'], $latitude, $longitude);
                $distance = $lastPoint['distance'] + ($newDistance * Settings::KM_TO_MILES);

                $previousDataTime = new DateTime($lastPoint['datatime'], new DateTimeZone(Settings::TIMEZONE));
                $interval = $theDate->diff($previousDataTime);
                $secondsPassed = $interval->format('%s');

                $speedMPS = ($newDistance * 1000) / $secondsPassed;
                $speed = $speedMPS * Settings::MPS_TO_MPH;
                $heading = $this->getRhumbLineBearing($lastPoint['lat'], $lastPoint['lng'], $latitude, $longitude);
            } else {
                $speed = 0;
                $heading = 0;
                $distance = 0;
            }

            $insert = "REPLACE INTO gpsdata (devicekey, tracktag, coord, altitude, datatime, speed, heading, distance)
                VALUES
                (:device, :trackid, GeomFromText('POINT($latitude $longitude)'), :altitude, :datatime, :speed, :heading, :distance)";

            $stmt = $this->_dbConn->prepare($insert);

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

    /**
     * Get the last (based on date) point stored in the database
     *
     * @param DateTime $dataTime Date on which to get last point
     *
     * @access private
     * @return array Array with the point information
     */
    private function _getLastStoredPoint($dataTime)
    {
        try {
            $query = "SELECT
                datatime,
                X(coord) AS lat,
                Y(coord) AS lng,
                distance
            FROM gpsdata
            WHERE date_format(datatime, '%Y-%m-%d') = :datatime
            ORDER BY datatime DESC
            LIMIT 1";

            $stmt = $this->_dbConn->prepare($query);

            $stmt->execute(
                array(
                    ':datatime' => $dataTime->format('Y-m-d')
                )
            );

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result;
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Get the last point stored in the database
     *
     * @param DateTime $lastTime Date/Time
     *
     * @access public
     * @return array Array with the point information
     */
    public function getLatestLocation($lastTime)
    {
        try {
            if ($lastTime == 0) {
                $date = new DateTime(null, new DateTimeZone(Settings::TIMEZONE));
            } else {
                $date = new DateTime($lastTime, new DateTimeZone(Settings::TIMEZONE));
            }

            $query = "SELECT datatime, X(coord) AS lat, Y(coord) AS lng, heading, distance, (altitude * :mtf) AS altitude, speed, tracktag
                FROM gpsdata
                WHERE DATE_FORMAT(datatime, '%Y-%m-%d') = :datatime
                ORDER BY datatime DESC
                LIMIT 1";

            $stmt = $this->_dbConn->prepare($query);

            $stmt->execute(
                array(
                    ':mtf' => Settings::METRE_TO_FEET,
                    ':datatime' => $date->format('Y-m-d')
                )
            );

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result;
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Get the route start/end and title for the day
     *
     * @param integer $day Day to get information for
     *
     * @access public
     * @return array Array with the daily route information
     */
    public function getStartFinish($day)
    {
        try {
            $query = "SELECT dayid, X(start_coords) AS start_lat, Y(start_coords) AS start_lng, X(end_coords) AS end_lat, Y(end_coords) AS end_lng, title
            FROM daypoints
            WHERE dayid = :dayid";

            $stmt = $this->_dbConn->prepare($query);
            $stmt->execute(
                array(":dayid" => $day)
            );

            $result =$stmt->fetch(PDO::FETCH_ASSOC);
            return $result;
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Implementation of the Haversine distance calculation
     *
     * @param float $lat1  Starting Latitude
     * @param float $long1 Starting Longitude
     * @param float $lat2  End Latitude
     * @param float $long2 End Longitude
     *
     * @access public
     * @return float Distance in km
     */
    public function haversine($lat1, $long1, $lat2, $long2)
    {
        $lat1Rad  = deg2rad($lat1);
        $lat2Rad  = deg2rad($lat2);
        $long1Rad = deg2rad($long1);
        $long2Rad = deg2rad($long2);
        $dLat     = deg2rad(($lat2 - $lat1));
        $dLon     = deg2rad(($long2 - $long1));
        $a        = (sin($dLat/2) * sin($dLat/2)) + cos($lat1Rad) * cos($lat2Rad) * (sin($dLon/2) * sin($dLon/2));
        $c        = 2 * atan2(sqrt($a), sqrt(1-$a));

        return Settings::EARTH_RADIUS * $c;
    }

    /**
     * Get the compass bearing
     *
     * @param float $lat1  Starting Latitude
     * @param float $long1 Starting Longitude
     * @param float $lat2  End Latitude
     * @param float $long2 End Longitude
     *
     * @access public
     * @return integer Angle of the bearing
     */
    public function getRhumbLineBearing($lat1, $long1, $lat2, $long2)
    {
        // difference in longitudinal coordinates
        $dLon = deg2rad($long2) - deg2rad($long1);

        // difference in the phi of latitudinal coordinates
        $dPhi = log(tan(deg2rad($lat2) / 2 + pi() / 4) / tan(deg2rad($lat1) / 2 + pi() / 4));

        // we need to recalculate $dLon if it is greater than pi
        if (abs($dLon) > pi()) {
            if ($dLon > 0) {
              $dLon = (2 * pi() - $dLon) * -1;
            }
            else {
              $dLon = 2 * pi() + $dLon;
            }
        }

        // return the angle, normalized
        return (rad2deg(atan2($dLon, $dPhi)) + 360) % 360;
    }

    /**
     * Get the compass direction
     *
     * @param integer $bearing Bearing
     *
     * @access public
     * @return integer Angle of heading
     */
    public function getCompassDirection($bearing)
    {
        static $cardinals = array( 'N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW', 'N' );
        return $cardinals[round( $bearing / 45 )];
    }


    /**
     * Get total miles done so far (sum of all distances in the gpsdata table)
     *
     * @access public
     * @return integer Total miles
     */
    public function getTotalMiles()
    {
        try {
            $query = "SELECT ROUND(SUM(maxdist.dist)) AS totalmiles
                FROM
                (SELECT MAX(distance) AS dist, DATE_FORMAT(datatime, '%Y-%m-%d')
                FROM gpsdata
                GROUP BY date_format(datatime, '%Y-%m-%d')) AS maxdist";

            $stmt = $this->_dbConn->prepare($query);

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result;
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

    }

}