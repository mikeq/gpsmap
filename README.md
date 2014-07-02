gpsmap
======

Browser mapping solution for Open GPS Tracker Android application (https://code.google.com/p/open-gpstracker/)

![Alt text](images/screenshot/map.png?raw=true "Live Tracking")

Prerequisites
-------------

Developed on the following
- PHP 5.5.3
- MySQL 5.5.34
- Apache 2

for installation the following are preferred
- git (http://git-scm.com/)
- node/npm (http://nodejs.org/)
- bower (http://bower.io/)


Installation
------------

clone or fork this repository

cd into your local directory and run 

    bower install

If you do not have bower installed then you will need to install the dependencies manually
The following are used in the project (view bower.json for dependencies)
- jquery ~2.1.1
- openlayers 2.13.1
- flot ~0.8.3
- raphael ~2.1.2
- respond  ~1.4.2
- bootstrap ~3.2.0
- justgage *

### Database

The database install script can be found under `scripts/install/`
Change the username and password to something more appropriate then run the script on your database

### Settings
The following in the Settings class (`scripts/lib/util/settings.class.php`) should be changed

    const TRACKING_DSN = 'localhost|username|password|velo'; //username/password should be set to the user/pass you defined when setting up the database in the db.sql script
    const START_DATE = '2014-07-01'; // Date of the first day to start tracking from
    const DEVICEID = 'TOPSECRETINFO'; // Change to something appropriate. This will be passed into the server script from the phone application

Background
----------
This has been developed to support the live tracking of a charity event I am taking part in at the end of August.
This event takes place over 17 days and sees us cycling from Edinburgh to Monte Carlo for Prostate Cancer UK.

We will run the open source Android application Open GPS Tracker (https://code.google.com/p/open-gpstracker/), this application
will send gps data to the script `scripts/store_loc.php` at regular intervals, this data is stored in the database (`gpsdata`) and then
displayed on a map in the browser as and when users visit the map site.

In addition to this the mapping site will also display that days route and elevation profile, the routes are read in from GPX Track files stored under `data/`.  These files
must be GPX Track and not GPX Route format.  The filename of each daily track should be of the format day*X*.gpx, so 1st day is *day1.gpx*, 2nd day is *day2.gpx* etc

The code works out which day it is based on `Settings::START_DATE`, so make sure this is set to the date that corresponds to day 1.  

2 sample GPX files are under `data/`

An additional table `gpsdaypoints` holds the latitude and longitude of each days start and end points and route title, 2 sample records are entered during
the running of the database script.  The `dayid` in this table corresponds to day **1** day **2** etc

The site will also display compass heading, daily distance to go, total miles done and speed.

The current weather conditions will also be displayed, this utilises the http://openweathermap.org/ APIs by sending the current GPS coordinates

### Setting up the Android application
There is a streaming option (more|settings|sharing settings), check the "Stream to custom web server" option, enter the following
for the "Custom web server URL"

    http://*yourserver*/scripts/store_loc.php?d=*DEVICEID*&lat=@LAT@&lon=@LON@&id=@ID@&alt=@ALT@&t=@TIME@

This sends 
- device ID (replace *DEVICEID* with whatever you setup in the Settings class)
- Latitude (@LAT@)
- Longitude (@LON@)
- Track ID (@ID@ just a unique integer assigned by the application for each track)
- Altitude (@ALT@)
- Time (@TIME@)

there is additional information that can be passed but I wanted to minimise network traffic, other settings are
- Speed (@SPEED@)
- Bearing (@BEAR@)
- Accuracy (@ACC@)

I calculate the bearing and speed in the code so I had no need for these.

If you find this code useful please consider donating to our charity event
visit http://monte.bike
