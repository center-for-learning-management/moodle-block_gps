define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'core/modal_factory', 'block_gps/modal_reachedlocation', 'block_gps/leaflet'], function($, AJAX, NOTIFICATION, STR, URL, ModalFactory, ModalReachedLocation) {
    return {
        debug: false,
        honeypots: [],
        honeypotmodal: undefined,
        honeypotshown: [],
        lasttrackedposition: { 'altitude': 0, 'latitude': 0, 'longitude': 0},
        locateinterval: null,
        locateintervalrunning: false,
        deg2rad: function(degrees) {
            return degrees * (Math.PI/180);
        },
        distance: function(position1, position2, decimals) {
            if (typeof(decimals) === 'undefined') {
                decimals = 0;
            }

            var lat1 = this.deg2rad(position1.latitude);
            var lon1 = this.deg2rad(position1.longitude);
            var lat2 = this.deg2rad(position2.latitude);
            var lon2 = this.deg2rad(position2.longitude);

            latDelta = lat2 - lat1;
            lonDelta = lon2 - lon1;
            angle = 2*Math.asin(Math.sqrt(Math.pow(Math.sin(latDelta / 2), 2) + Math.cos(lat1) * Math.cos(lat2) * Math.pow(Math.sin(lonDelta / 2), 2)));
            return Math.round(angle * 6378.388 * 1000, decimals);
        },
        /**
         * Start an interval for requesting the location.
         * @param ms milliseconds between requests
         */
        interval: function(ms) {
            var GEOASSIST = this;
            if (this.debug) console.log('block_gps/geoassist::interval(ms)', ms);
            if (ms > 0) {
                $('#block_gps_interval_toggler').find('i.fa').removeClass('fa-toggle-off').addClass('fa-toggle-on');
                clearInterval(this.locateinterval);
                this.locateinterval = setInterval(function() { GEOASSIST.locate(); }, ms);
                this.locateintervalrunning = true;
                // We ask for permission immediately.
                navigator.geolocation.getCurrentPosition(function() {});
            } else if (typeof(this.locateinterval) !== 'undefined') {
                $('#block_gps_interval_toggler').find('i.fa').removeClass('fa-toggle-on').addClass('fa-toggle-off');
                clearInterval(this.locateinterval);
                this.locateinterval = null;
                this.locateintervalrunning = false;
            }
            AJAX.call([{
                methodname: 'block_gps_setinterval',
                args: { ms: ms },
                done: function(result){},
                fail: NOTIFICATION.exception,
            }]);
        },
        intervalToggle: function() {
            if (this.debug) console.log('block_gps/geoassist::intervalToggle()');
            if (this.locateintervalrunning) {
                this.interval(0);
            } else {
                this.interval(5000);
            }
        },
        /**
         * Let Javascript now the last position Moodle knew.
         * @param altitude
         * @param latitude
         * @param longitude
         */
        locateInit: function(altitude, latitude, longitude) {
            if (this.debug) console.log('block_gps/geoassist::locateInit(altitude, latitude, longitude)', altitude, latitude, longitude);
            if (typeof(altitude) !== 'undefined') { this.lasttrackedposition.altitude = altitude; }
            if (typeof(latitude) !== 'undefined') { this.lasttrackedposition.latitude = latitude; }
            if (typeof(longitude) !== 'undefined') { this.lasttrackedposition.longitude = longitude; }
        },
        /**
         * Ask the browser for the current location.
         * @param once indicate if request is done once (true) or regularly (false)
         */
        locate: function(once){
            if (typeof(once) === 'undefined') { once = false; }
            if (this.debug) console.log('block_gps/geoassist::locate(once)', once);
            var GEOASSIST = this;
            if (navigator.geolocation) {
                if (GEOASSIST.debug) console.log('navigator.geolocation exists');
                navigator.geolocation.getCurrentPosition(
                    function(position){
                        position = position.coords;

                        if (typeof(position.latitude) !== 'undefined' && position.latitude != null) {
                            $('.block_gps_current_latitude').html(position.latitude);
                        }
                        if (typeof(position.longitude) !== 'undefined' && position.longitude != null) {
                            $('.block_gps_current_longitude').html(position.longitude);
                        }
                        if (typeof(position.altitude) !== 'undefined' && position.altitude != null) {
                            $('.block_gps_current_altitude').html(position.altitude);
                        }

                        if (GEOASSIST.debug) console.log('retrieved position', position);
                        $('.availability_gps_condition_button').each(function() {
                            var span = this;
                            var lat = $(span).find('.latitude').html();
                            var lon = $(span).find('.longitude').html();
                            var pos2 = { 'latitude': lat, 'longitude': lon};

                            var distance = GEOASSIST.distance(position, pos2);
                            if (GEOASSIST.debug) console.log('Update UI Position of ', pos2, ' with distance ', distance, ' in span ', span);
                            STR.get_strings([
                                    {'key' : 'meters', component: 'block_gps' },
                                    {'key' : 'kilometers', component: 'block_gps' },
                                ]).done(function(s) {
                                    $(span).find('.distanceerror').addClass('hidden');
                                    $(span).find('.distanceok').removeClass('hidden');
                                    if (distance > 1000) {
                                        $(span).find('.distance').html(Math.round(distance / 1000, 1));
                                        $(span).find('.distancelabel').html(s[1]);
                                    } else {
                                        $(span).find('.distance').html(distance);
                                        $(span).find('.distancelabel').html(s[0]);
                                    }

                                }
                            ).fail(NOTIFICATION.exception);
                        });

                        var found_honeypot = false;
                        GEOASSIST.honeypots.forEach(function(honeypot) {
                            var distance = GEOASSIST.distance(position, honeypot);
                            if (GEOASSIST.debug) console.log('Our distance to honeypot ', honeypot, ' is ', distance);
                            if (honeypot.accessible && distance < honeypot.accuracy) {
                                if (GEOASSIST.debug) console.log('JEHAAA, you reached it!');
                                found_honeypot = true;
                                var alreadyshown = false;
                                GEOASSIST.honeypotshown.forEach(function(url) {
                                    if (url == honeypot.url) {
                                        alreadyshown = true;
                                    }
                                });
                                if (!alreadyshown) {
                                    GEOASSIST.honeypotshown.push(honeypot.url);
                                    if (GEOASSIST.debug) console.log('Now showing a success modal!');
                                    if (typeof(GEOASSIST.honeypotmodal) === 'undefined') {
                                        ModalFactory.create({
                                            type: ModalReachedLocation.TYPE,
                                        }).then(function(modal) {
                                            GEOASSIST.honeypotmodal = modal;
                                            $(GEOASSIST.honeypotmodal.getRoot()).find('.modal-footer a[data-action="goto"]').attr('href', honeypot.url);
                                            $(GEOASSIST.honeypotmodal.getRoot()).find('.modal-body span.name').html('"' + honeypot.name + '"');
                                            modal.show();
                                        });
                                    } else {
                                        $(GEOASSIST.honeypotmodal.getRoot()).find('.modal-footer a[data-action="goto"]').attr('href', honeypot.url);
                                        $(GEOASSIST.honeypotmodal.getRoot()).find('.modal-body span.name').html('"' + honeypot.name + '"');
                                        GEOASSIST.honeypotmodal.show();
                                    }
                                } else {
                                    if (GEOASSIST.debug) console.log('Our last modal was for this honeypot - suppress!');
                                }

                            }
                        });

                        if (once || found_honeypot) {
                            if (GEOASSIST.debug) console.log('Let us tell Moodle about our new position!');
                            var posdata = { lat: position.latitude, lon: position.longitude, alt: position.altitude };
                            AJAX.call([{
                                methodname: 'block_gps_locate',
                                args: posdata,
                                done: function(result){
                                    if (GEOASSIST.debug) console.log('moodle informed about position', posdata, ' replied with', result);
                                    if (result == 'coordinates_set') {
                                        if (GEOASSIST.debug) console.log('Reloading page');
                                        //location.reload();
                                    }
                                },
                                fail: NOTIFICATION.exception,
                            }]);
                        }

                        var distance = GEOASSIST.distance(GEOASSIST.lasttrackedposition, position);
                        if (GEOASSIST.debug) console.log('distance since last tracked position', distance);
                        if (once && distance < 5) {
                            STR.get_strings([
                                    {'key' : 'location_not_changed:title', component: 'block_gps' },
                                    {'key' : 'location_not_changed:body', component: 'block_gps' },
                                ]).done(function(s) {
                                    ModalFactory.create({
                                        type: ModalFactory.types.OK,
                                        title: s[0],
                                        body: s[1],
                                    }).then(function(modal) {
                                        modal.show();
                                        setTimeout(function() { modal.hide(); }, 2000);
                                    });
                                }
                            ).fail(NOTIFICATION.exception);
                        }

                        GEOASSIST.lasttrackedposition = position;
                    }
                );
            } else {
                alert('geolocation_not_supported');
            }
        },
        /**
         * Push a location that the user may achieve.
         */
        pushHoneypot: function(location) {
            if (this.debug) console.log('block_gps/geoassist::pushHoneypot(location)', location);
            location.accessible = (typeof(location.visible) !== 'undefined' && location.visible == 1);
            this.honeypots.push(location);
        },
    };
});
