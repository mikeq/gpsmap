<?php
/**
 * Defines the Utilities class
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

require_once 'settings.class.php';

/**
 * Utilities
 *
 * @category   GPSMap
 * @package    GPSMap
 * @author     Mike Quinn <gpsmap@velochallenge.org>
 * @copyright  2014 Mike Quinn
 */
class Utilities
{
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
    static public function haversine($lat1, $long1, $lat2, $long2)
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
    static public function getRhumbLineBearing($lat1, $long1, $lat2, $long2)
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
    static public function getCompassDirection($bearing)
    {
        static $cardinals = array( 'N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW', 'N' );
        return $cardinals[round( $bearing / 45 )];
    }

}