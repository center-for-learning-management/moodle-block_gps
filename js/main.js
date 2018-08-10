function block_gps_locate() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position){
                var url = new URL(top.location.href);
                url.searchParams.append('longitude', position.coords.longitude);
                url.searchParams.append('latitude', position.coords.latitude);
                top.location.href = url;
            }
        );
    } else {
        alert('geolocation_not_supported');
    }
}
