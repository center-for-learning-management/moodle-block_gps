define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'core/modal_factory', 'block_gps/leaflet'], function($, AJAX, NOTIFICATION, STR, URL, ModalFactory) {
    return {
        debug: true,
        lasttrackedposition: { 'altitude': 0, 'latitude': 0, 'longitude': 0},
        locateinterval: null,
        locateintervalrunning: false,
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
         */
        locate: function(once){
            if (this.debug) console.log('block_gps/geoassist::locate()');
            var GEOASSIST = this;
            if (navigator.geolocation) {
                if (GEOASSIST.debug) console.log('navigator.geolocation exists');
                navigator.geolocation.getCurrentPosition(
                    function(position){
                        position = position.coords;
                        if (GEOASSIST.debug) console.log('retrieved position', position);
                        var distance = GEOASSIST.distance(GEOASSIST.lasttrackedposition, position);
                        if (GEOASSIST.debug) console.log('distance since last tracked position', distance);

                        if (distance > 5) {
                            var posdata = { lat: position.latitude, lon: position.longitude, alt: position.altitude };
                            AJAX.call([{
                                methodname: 'block_gps_locate',
                                args: posdata,
                                done: function(result){
                                    if (GEOASSIST.debug) console.log('moodle informed about position', posdata, ' replied with', result);
                                    GEOASSIST.lasttrackedposition = position;
                                    if (result == 'coordinates_set') {
                                        if (GEOASSIST.debug) console.log('Reloading page');
                                        location.reload();
                                    }
                                },
                                fail: NOTIFICATION.exception,
                            }]);
                        } else if (typeof(once) !== 'undefined' && once) {
                            // Show a modal info for 3 seconds.
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
                    }
                );
            } else {
                alert('geolocation_not_supported');
            }
        },
        current: function(src) {
            var GEOASSIST = this;
            if (navigator.geolocation) {
                M.availability_gps.locatebtn = src;
                M.availability_gps.locatebtn.value = M.str.availability_gps.loading + '...';
                navigator.geolocation.getCurrentPosition(
                    function(position){
                        M.availability_gps.locatebtn.value = M.str.availability_gps.current_location;
                        GEOASSIST.coord(position.coords.latitude, position.coords.longitude);
                    }
                )
            } else {
                M.availability_gps.locatebtn.value = M.str.availability_gps.geolocation_not_supported;
            }
        },
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
        init: function(toggle) {
            var GEOASSIST = this;
            var lat = $('[name=latitude]').val();
            var lon = $('[name=longitude]').val();
            var accuracy = $('[name=accuracy]').val();

            if (typeof(toggle) !== 'undefined') {
                $('#availability_gps_map').toggleClass('hidden');
                if (lat == 0 && lon == 0 && navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position){
                            GEOASSIST.coord(position.coords.latitude, position.coords.longitude);
                        }
                    )
                }
            }

            if (typeof M.availability_gps.marker !== 'undefined') {
                M.availability_gps.marker.setLatLng(L.latLng(lat, lon));
                M.availability_gps.map.panTo(L.latLng(lat, lon));
                M.availability_gps.circle.setLatLng(L.latLng(lat, lon));
                M.availability_gps.circle.setRadius(accuracy);
            } else {
                M.availability_gps.map = L.map( 'availability_gps_map', {
                    center: [lat, lon],
                    zoom: 13
                });
                L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    subdomains: ['a','b','c']
                }).addTo( M.availability_gps.map );
                var iconUrl = '' + URL.relativeUrl('/blocks/gps/pix/google-maps-pin-blue.svg');
                var icon = L.icon({
                    iconUrl: iconUrl,
                    iconRetinaUrl: iconUrl,
                    iconSize: [29, 24],
                    iconAnchor: [9, 21],
                    popupAnchor: [0, -14]
                });
                M.availability_gps.marker = L.marker([lat, lon], { title: '', draggable: true, icon: icon})
                    .addTo(M.availability_gps.map)
                    .on('dragend', function(){
                        var coord = M.availability_gps.marker.getLatLng();
                        GEOASSIST.coord(coord.lat, coord.lng);
                    })
                    .on('move', function(){
                        var coord = M.availability_gps.marker.getLatLng();
                        M.availability_gps.circle.setLatLng(coord);
                    }
                );
                M.availability_gps.circle = L.circle([lat, lon], {
                    color: 'red',
                    fillColor: '#f03',
                    fillOpacity: 0.2,
                    radius: accuracy,
                }).addTo(M.availability_gps.map);
            }
        },
        initIfShown: function() {
            console.log('initifshown');
            if (typeof M.availability_gps.marker !== 'undefined') {
                console.log('is shown');
                this.init();
            }
        },
        coord: function(lat, lon) {
            M.availability_gps.node.one('[name=longitude]').set('value', lon);
            M.availability_gps.node.one('[name=latitude]').set('value', lat);
            M.core_availability.form.update();
            this.init();
        }
    };
});
