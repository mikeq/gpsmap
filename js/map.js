/**
 * Defines the map javascript for creating/displaying and updating the map
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
var gpsMap = {
    teamIcon: null,
    map: null,
    proj: null,
    curPoint: null,
    startIcon: null,
    startPoint: null,
    endPoint: null,
    finishIcon: null,
    timed: null,
    markerLayer: null,
    routeLayer: null,
    projection: "EPSG:4326",
    eleArray: [],
    plot: null,
    g: null,
    m: null,
    t: null,
    lastPositionUpdate: 0,
    maxMiles: 0
};

$(document).ready(function() {
    initialiseIcons();
    init();
    gpsMap.timed = setInterval(refreshMap, 1000);
});

/**
 * Initialise any OpenLayers icons to be used on the map
 */
function initialiseIcons() {
    var size = new OpenLayers.Size(32,37);
    var offset = new OpenLayers.Pixel(-(size.w/2), 0);
    gpsMap.teamIcon = new OpenLayers.Icon('images/pointupbike.png', size, offset);

    offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
    startIcon = new OpenLayers.Icon('images/start.png', size, offset);
    gpsMap.finishIcon = new OpenLayers.Icon('images/finish.png', size, offset);
}

/**
 * Initialise the open layers map, add layers, load route information for the day
 */
function init() {
    // initialise the map
    gpsMap.map = new OpenLayers.Map({
        div: "map",
        allOverlays: true
    });

    // basic Open Street Maps map layer
    var osm = new OpenLayers.Layer.OSM();

    // Open Street Maps cycle map layer, not visible
    var cyclemap = new OpenLayers.Layer.OSM("OpenCycleMap",
        ["http://a.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png",
        "http://b.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png",
        "http://c.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png"],
        {visibility: false}
    );

    // Markers layer, this is the layer that shows the current position
    gpsMap.markerLayer = new OpenLayers.Layer.Markers("Markers");

    // Start/Finish markers layer
    gpsMap.routeLayer = new OpenLayers.Layer.Markers("Start/Finish");

    // note that first layer must be visible
    gpsMap.map.addLayers([osm, cyclemap, gpsMap.markerLayer, gpsMap.routeLayer]);
    gpsMap.map.addControl(new OpenLayers.Control.LayerSwitcher());
    gpsMap.proj = new OpenLayers.Projection(gpsMap.projection);

    // initialise the gauges
    if (typeof JustGage !== 'undefined') {
        gpsMap.g = new JustGage({
            id: "gaugeSpeed",
            value: 0,
            min: 0,
            max: 50,
            title: "Speed",
            titleFontColor: "#222",
            label: "(mph)",
            labelFontColor: "#222",
            hideMinMax: true,
            valueFontColor: "#222",
            gaugeColor: "#222",
            gaugeWidthScale: 0.5
        });
        gpsMap.m = new JustGage({
            id: "gaugeMiles",
            value: 0,
            min: 0,
            max: 100,
            title: "Daily Miles to Go",
            titleFontColor: "#222",
            label: "(miles)",
            labelFontColor: "#222",
            hideMinMax: true,
            valueFontColor: "#222",
            gaugeColor: "#222",
            gaugeWidthScale: 0.5
        });
        gpsMap.t = new JustGage({
            id: "gaugeTotalMiles",
            value: 0,
            min: 0,
            max: 1300,
            title: "Total Miles",
            titleFontColor: "#222",
            label: "(miles)",
            labelFontColor: "#222",
            hideMinMax: false,
            valueFontColor: "#222",
            gaugeColor: "#222",
            gaugeWidthScale: 0.5
        });
    }

    // initialise start/end points, load gpx and elevation data
    setupRouteInformation();
    gpsMap.map.zoomTo(11);

}

/**
 * add marker to a layer on the map
 */
function addPoint(thePoint, theLayer, theIcon) {
    thePoint.transform(gpsMap.proj, gpsMap.map.getProjectionObject());
    theLayer.addMarker(new OpenLayers.Marker(thePoint, theIcon));
}

/**
 * refresh the map with the latest location
 */
function refreshMap(override) {
    if (override === 'undefined') {
        override = false;
    }

    if (override || $('#refresh').is(':checked')) {
        $.getJSON('scripts/ajax.location.php', {datatime: gpsMap.lastPositionUpdate}, function(data) {
            if (data !== false) {
                if (gpsMap.lastPositionUpdate !== data.datatime) {
                    getWeather({lat: data.lat, lon: data.lng});
                    updatePosition(data, $('#centrebike').is(':checked'));
                }

                gpsMap.lastPositionUpdate = data.datatime;
            }
        });
    } else {
        return false;
    }
}

/**
 * Update the current point on the map and centre if option selected
 */
function updatePosition(locale, setCenter) {
    if (locale.lng !== 0 || locale.lat !== 0) {
        gpsMap.markerLayer.clearMarkers();
        gpsMap.curPoint = null;
        gpsMap.curPoint = new OpenLayers.LonLat(locale.lng, locale.lat);
        gpsMap.curPoint.transform(gpsMap.proj, gpsMap.map.getProjectionObject());
        gpsMap.markerLayer.addMarker(new OpenLayers.Marker(gpsMap.curPoint, gpsMap.teamIcon));
        if (setCenter) {
            gpsMap.map.setCenter(gpsMap.curPoint);
        }
        updateGauges(locale);
        updateElevation(locale);
        getTotalMiles();
    }
}

/**
 * Add start/end markers, title, load elevation data. weather and daily mileage
 */
function setupRouteInformation() {
    $.getJSON('scripts/ajax.startfinish.php', function(data) {
        if (data !== false) {
            gpsMap.startPoint = new OpenLayers.LonLat(data.start_lng, data.start_lat);
            gpsMap.curPoint = new OpenLayers.LonLat(data.start_lng, data.start_lat);
            gpsMap.endPoint = new OpenLayers.LonLat(data.end_lng, data.end_lat);
            addPoint(gpsMap.startPoint, gpsMap.routeLayer, startIcon);
            addPoint(gpsMap.endPoint, gpsMap.routeLayer, gpsMap.finishIcon);
            addPoint(gpsMap.curPoint, gpsMap.markerLayer, gpsMap.teamIcon);
            gpsMap.map.setCenter(gpsMap.startPoint);
            $('#route-title').text(data.title + " (Day " + (parseInt(data.dayid, 10)) + ")");
            loadElevation(data.dayid);
            getWeather({lat: data.start_lat, lon: data.start_lng});
            getTotalMiles();
        }
    });
}

/**
 * Update gauges
 */
function updateGauges(locale) {
    // compass
    $('#compass').css({'transform' : 'rotate(' + locale.heading + 'deg)'});

    // speed
    if (typeof JustGage !== 'undefined') {
        gpsMap.g.refresh(parseInt(locale.speed, 10));
    }

    //miles to go
    var maxMiles = parseInt(gpsMap.eleArray[gpsMap.eleArray.length - 1][0], 10);
    updateMilesToGo(locale.distance, maxMiles);
}

/**
 * Update daily miles to go gauge
 */
function updateMilesToGo(distanceDone, totalDistance) {
    if (typeof JustGage !== 'undefined') {
        var milesDone = parseInt(distanceDone, 10);
        var toGo = totalDistance - milesDone;
        gpsMap.m.config.max = totalDistance;
        gpsMap.m.refresh(toGo);
    }
}

/**
 * Update total miles done gauge
 */
function updateTotalMiles(distanceDone) {
    if (typeof JustGage !== 'undefined') {
        var milesDone = parseInt(distanceDone, 10);
        gpsMap.t.refresh(milesDone);
    }
}

/**
 * Load the elevation graph
 */
function loadElevation(dayId) {
    $.getJSON(
        'scripts/readgpx.php',
        {dayid: dayId},
        function (data) {
            if (data !== false) {
                gpsMap.eleArray = data;
                gpsMap.maxMiles = parseInt(gpsMap.eleArray[gpsMap.eleArray.length - 1][0], 10);
                updateMilesToGo(0, gpsMap.maxMiles);
                updateElevation();
                loadGPX(dayId);
           }
        }
    );
}

/**
 * Upate the elevation graph with team location
 */
function updateElevation(locale) {
    var teamPoint;
    var options = {
        xaxis: {
            label: "miles",
            labelPos: "low"
        },
        yaxis: {
            label: "feet",
            labelPos: "low"
        },
        colors: ["#7fafe3", "#ffae00"],
        series: {
            shadowSize: 0
        },
        grid: {
            backgroundColor: "#222"
        }
    };

    // this point is just here to make sure the height axis doesn't fall below a certain value
    var hiddenPoint = [[0,300]];
    if (locale === undefined) {
        teamPoint = [[0,0],[0,0]];
    } else {
        teamPoint = [[locale.distance, 0],[locale.distance, (gpsMap.plot.getAxes().yaxis.max - 50)]];
    }

    var result = new Array();
    result.push({data: gpsMap.eleArray, lines: {show: true, fill: true, fillColor: "rgba(127, 175, 227, 0.3)"}, points: {show: false}});
    result.push({data: teamPoint, lines: {show: true}, points: {show: true, radius: 2}});
    result.push({data: hiddenPoint, lines: {show: false}, points: {show: false}});
    gpsMap.plot = $.plot('#ele-graph', result, options);
}

/**
 * Read the GPX Track file and load onto layer on the map
 */
function loadGPX(dayId) {
    var style = {
        strokeColor: '#336600',
        strokeWidth: 4,
        strokeOpacity: 0.8,
        strokeDashstyle: 'solid'
    };

    // create the route layer
    var gpx = new OpenLayers.Layer.Vector(
        "Day " + (parseInt(dayId, 10) + 1) + " Route",
        {
            styleMap: new OpenLayers.StyleMap(new OpenLayers.Style(style)),
            strategies: [new OpenLayers.Strategy.Fixed()],
            protocol: new OpenLayers.Protocol.HTTP(
                {
                    url: "data/day" + dayId + ".gpx",
                    format: new OpenLayers.Format.GPX()
                }
            )
        }
    );

    gpsMap.map.addLayer(gpx);
    gpsMap.map.setLayerIndex(gpx, 2);
    gpsMap.map.setLayerIndex(gpsMap.markerLayer, 3);
    refreshMap(true);
}

/**
 * Get current weather in current location
 */
function getWeather(location, lastTime) {
    $.getJSON(
        'scripts/weather_feed.php',
        {lat: location.lat, lon: location.lon},
        function (data) {
            if (data !== false) {
                $('#wind').css({'transform' : 'rotate(' + (data.wind.deg - 180) + 'deg)'});
                $('#windspeed').text(Math.round(data.wind.speed * 2.23693629));
                $('#condition').css({'backgroundImage' : 'url(images/weather_flat/' + data.weather[0].icon + '.png)'});
                $('#temptext').text(Math.round((data.main.temp - 32) / 1.8000));
            }
        }
    );
}

/**
 * Get total miles done from the database
 */
function getTotalMiles() {
    $.getJSON(
        'scripts/ajax.totalmileage.php',
        function (data) {
            if (data !== false) {
                if (data.totalmiles === null) {
                    updateTotalMiles(0);
                } else {
                    updateTotalMiles(data.totalmiles);
                }
            }
        }
    );
}