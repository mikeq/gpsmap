<?php
/**
 * Defines the settings class for GPSMap
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
 * Settings
 *
 * @category   GPSMap
 * @package    GPSMap
 * @author     Mike Quinn <gpsmap@velochallenge.org>
 * @copyright  2014 Mike Quinn
 */
class Settings
{
    // DSN is in the format host|username|password|database_name
    const TRACKING_DSN = 'localhost|tester|tester|velo'; //username/password should be set to the user/pass you defined when setting up the database in the db.sql script
    const DEFAULT_MYSQL_PORT = '3306';

    /**
     * Live tracking
     */
    const MPS_TO_MPH = 2.23693629; //meters per second to miles per hour
    const EARTH_RADIUS = 6371; //in km
    const KM_TO_MILES = 0.621371192;
    const METRE_TO_FEET = 3.2808399;

    const START_DATE = '2014-07-21';
    const TIMEZONE = 'Europe/London';
    const DEVICEID = 'TOPSECRETINFO'; //

    /*
     * Weather API
     */
    const CURRENT_WEATHER_URL = 'http://api.openweathermap.org/data/2.5/weather?lat=%f&lon=%f&units=imperial';

    /**
     * Test tracking
     */
    const TESTTRACKING_URL = 'http://gps.local/scripts/store_loc.php?d=%s&lat=%s&lon=%s&id=%s&alt=%s&t=%s';
    const GPSTRACKER_URL = 'http://gps.local/scripts/updatelocation.php';
    const GPSTRACKER_POST = 'phonenumber=%s&latitude=%s&longitude=%s&sessionid=%s&extrainfo=%s&date=%s';
}