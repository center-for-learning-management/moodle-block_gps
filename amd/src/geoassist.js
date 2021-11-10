define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'block_gps/leaflet'], function($, ajax, notification, str, url) {
    return {
        locateinterval: null,
        locate: function(){
            if (typeof(this.locateinterval) !== 'undefined') {
                if (navigator.geolocation) {
                    this.locateinterval = setInterval(function() {
                        navigator.geolocation.getCurrentPosition(
                            function(position){
                                ajax.call([{
                                    methodname: 'block_gps_locate',
                                    args: { lat: position.coords.latitude, lon: position.coords.longitude, alt: position.coords.altitude },
                                    done: function(result){
                                        if (result == 'coordinates_set') {
                                            console.log('User moved more than 5m since last position, reloading page');
                                            top.location.href = top.location.href;
                                        } else if (result == 'moved_less_than_5m') {
                                            //alert('less than 5m');
                                            console.log('User moved less than 5m since last position');
                                        } else {
                                            console.log('There was an error, show notification');
                                            var resStr = str.get_string(result, 'block_gps');
                                            $.when(resStr).done(function(localizedEditString) {
                                                 notification.alert(localizedEditString, '');
                                            });
                                        }
                                    },
                                    fail: notification.exception,
                                }]);
                            }
                        );
                    }, 5000);
                } else {
                    alert('geolocation_not_supported');
                }
            }
        },
        current: function(src) {
            if (navigator.geolocation) {
                M.availability_gps.locatebtn = src;
                M.availability_gps.locatebtn.value = M.str.availability_gps.loading + '...';
                navigator.geolocation.getCurrentPosition(
                    function(position){
                        M.availability_gps.locatebtn.value = M.str.availability_gps.current_location;
                        require(['block_gps/geoassist'], function(helper){
                            helper.coord(position.coords.latitude, position.coords.longitude);
                        });
                    }
                )
            } else {
                M.availability_gps.locatebtn.value = M.str.availability_gps.geolocation_not_supported;
            }
        },

        init: function(lat, lon) {
            if (typeof M.availability_gps.marker !== 'undefined') {
                M.availability_gps.marker.setLatLng(L.latLng(lat, lon));
                M.availability_gps.map.panTo(L.latLng(lat, lon));
            } else {
                Y.one('#availability_gps_map').removeClass('closed');
                Y.one('#availability_gps_map_info').removeClass('closed');
                M.availability_gps.map = L.map( 'availability_gps_map', {
                    center: [lat, lon],
                    zoom: 13
                });
                L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    subdomains: ['a','b','c']
                }).addTo( M.availability_gps.map );
                var iconUrl = '' + url.relativeUrl('/blocks/gps/pix/google-maps-pin-blue.svg');
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
                        require(['block_gps/geoassist'], function(helper){
                            helper.coord(coord.lat, coord.lng);
                        });
                    }
                );
            }
        },
        coord: function(lat, lon) {
            require(['block_gps/geoassist'], function(helper){
                helper.init(lat, lon);
            });
            M.availability_gps.node.one('[name=longitude]').set('value', lon);
            M.availability_gps.node.one('[name=latitude]').set('value', lat);
            M.core_availability.form.update();
        }
    };
});
