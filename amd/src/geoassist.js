define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'block_gps/leaflet'], function($, ajax, notification, str, url) {
    return {
        locate: function(){
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position){
                        ajax.call([{
                            methodname: 'block_gps_locate',
                            args: { lat: position.coords.latitude, lon: position.coords.longitude },
                            done: function(result){
                                if (result == 'coordinates_set') {
                                    top.location.href = top.location.href;
                                } else {
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
            } else {
                alert('geolocation_not_supported');
            }
        },
        map: undefined,
        marker: undefined,
        current: function() {
            if (navigator.geolocation) {
                M.availability_gps.locatebtn = this;
                M.availability_gps.locatebtn.value = M.str.availability_gps.loading + '...';
                navigator.geolocation.getCurrentPosition(
                    function(position){
                        M.availability_gps.locatebtn.value = M.str.availability_gps.current_location;
                        console.log('Position:' , position.coords);
                        this.coord(position.coords.latitude, position.coords.longitude);
                    }
                )
            } else {
                M.availability_gps.locatebtn.value = M.str.availability_gps.geolocation_not_supported;
            }
        },

        init: function(lat, lon) {
            if (typeof this.marker !== 'undefined') {
                this.marker.setLatLng(L.latLng(lat, lon));
                this.map.panTo(L.latLng(lat, lon));
            } else {
                Y.one('#availability_gps_map').removeClass('closed');
                Y.one('#availability_gps_map_info').removeClass('closed');
                this.map = L.map( 'availability_gps_map', {
                    center: [lat, lon],
                    zoom: 13
                });
                L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    subdomains: ['a','b','c']
                }).addTo( this.map );
                var iconUrl = '' + url.relativeUrl('/blocks/gps/pix/google-maps-pin-blue.svg');
                var icon = L.icon({
                    iconUrl: iconUrl,
                    iconRetinaUrl: iconUrl,
                    iconSize: [29, 24],
                    iconAnchor: [9, 21],
                    popupAnchor: [0, -14]
                });
                this.marker = L.marker([lat, lon], { title: '', draggable: true, icon: icon})
                    .addTo(this.map)
                    .on('dragend', function(){
                        var coord = this.marker.getLatLng();
                        this.coord(coord.lat, coord.lng);
                    }
                );
            }
        },
        coord: function(lat, lon) {
            this.init(lat, lon);
            M.availability_gps.node.one('[name=longitude]').set('value', lon);
            M.availability_gps.node.one('[name=latitude]').set('value', lat);
            M.core_availability.form.update();
        }
    };
});
/*
function block_gps_locate() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position){
                require(['core/ajax', 'core/notification', 'core/str'], function(ajax, notification, str) {
                    ajax.call([{
                        methodname: 'block_gps_locate',
                        args: { lat: position.coords.latitude, lon: position.coords.longitude },
                        done: function(result){
                            if (result == 'coordinates_set') {
                                top.location.href = top.location.href;
                            } else {
                                var resStr = str.get_string(result, 'block_gps');
                                $.when(resStr).done(function(localizedEditString) {
                                     notification.alert(localizedEditString, '');
                                });
                            }
                        },
                        fail: notification.exception,
                    }]);
                });
            }
        );
    } else {
        alert('geolocation_not_supported');
    }
}

var availability_gps_helper = {
    map: undefined,
    marker: undefined,
    current: function() {
        if (navigator.geolocation) {
            M.availability_gps.locatebtn = this;
            M.availability_gps.locatebtn.value = M.str.availability_gps.loading + '...';
            navigator.geolocation.getCurrentPosition(
                function(position){
                    M.availability_gps.locatebtn.value = M.str.availability_gps.current_location;
                    console.log('Position:' , position.coords);
                    availability_gps_helper.coord(position.coords.latitude, position.coords.longitude);
                }
            )
        } else {
            M.availability_gps.locatebtn.value = M.str.availability_gps.geolocation_not_supported;
        }
    },

    init: function(lat, lon) {
        require(['block_gps/leaflet'], function(){
            if (typeof availability_gps_helper.marker !== 'undefined') {
                availability_gps_helper.marker.setLatLng(L.latLng(lat, lon));
                availability_gps_helper.map.panTo(L.latLng(lat, lon));
            } else {
                Y.one('#availability_gps_map').removeClass('closed');
                Y.one('#availability_gps_map_info').removeClass('closed');
                require(['core/url'], function(url) {
                    availability_gps_helper.map = L.map( 'availability_gps_map', {
                        center: [lat, lon],
                        zoom: 13
                    });
                    L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        subdomains: ['a','b','c']
                    }).addTo( availability_gps_helper.map );
                    var iconUrl = '' + url.relativeUrl('/blocks/gps/pix/google-maps-pin-blue.svg');
                    var icon = L.icon({
                        iconUrl: iconUrl,
                        iconRetinaUrl: iconUrl,
                        iconSize: [29, 24],
                        iconAnchor: [9, 21],
                        popupAnchor: [0, -14]
                    });
                    availability_gps_helper.marker = L.marker([lat, lon], { title: '', draggable: true, icon: icon})
                        .addTo(availability_gps_helper.map)
                        .on('dragend', function(){
                            var coord = availability_gps_helper.marker.getLatLng();
                            availability_gps_helper.coord(coord.lat, coord.lng);
                        }
                    );
                });
            }
        });
    },
    coord: function(lat, lon) {
        availability_gps_helper.init(lat, lon);
        M.availability_gps.node.one('[name=longitude]').set('value', lon);
        M.availability_gps.node.one('[name=latitude]').set('value', lat);
        M.core_availability.form.update();
    }
}
*/
