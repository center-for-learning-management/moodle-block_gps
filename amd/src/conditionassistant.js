define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'core/modal_factory', 'block_gps/leaflet'], function($, AJAX, NOTIFICATION, STR, URL, ModalFactory) {
    return {
        debug: false,

        coord: function(lat, lon) {
            M.availability_gps.node.one('[name=longitude]').set('value', lon);
            M.availability_gps.node.one('[name=latitude]').set('value', lat);
            M.core_availability.form.update();
            this.init();
        },
        current: function(src) {
            var CA = this;
            if (navigator.geolocation) {
                M.availability_gps.locatebtn = src;
                M.availability_gps.locatebtn.value = M.str.availability_gps.loading + '...';
                navigator.geolocation.getCurrentPosition(
                    function(position){
                        M.availability_gps.locatebtn.value = M.str.availability_gps.current_location;
                        CA.coord(position.coords.latitude, position.coords.longitude);
                    }
                )
            } else {
                M.availability_gps.locatebtn.value = M.str.availability_gps.geolocation_not_supported;
            }
        },
        init: function(toggle) {
            var CA = this;
            var lat = $('[name=latitude]').val();
            var lon = $('[name=longitude]').val();
            var accuracy = $('[name=accuracy]').val();

            if (typeof(toggle) !== 'undefined') {
                $('#availability_gps_map').toggleClass('hidden');
                if (lat == 0 && lon == 0 && navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position){
                            CA.coord(position.coords.latitude, position.coords.longitude);
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
                        CA.coord(coord.lat, coord.lng);
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
    };
});
