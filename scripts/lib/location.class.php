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