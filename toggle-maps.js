jQuery(document).ready( function($) {

    var tm = {
        map: null,
        mapContainer: 'toggle-map',
        toggleContainer: 'toggles',
        toggle: 'toggle',
        openWindow: null,
        visibleMarkers: [],
        allMarkers: [],
        host: 'http://' + window.location.hostname,
        id: toggleMaps.id,
        single: toggleMaps.single,
        terms: toggleMaps.terms,
        termNames: toggleMaps.termNames,
        termSlugs: toggleMaps.termSlugs,
        listView: toggleMaps.listView
    }

    if(toggleMaps.devPort)
        tm.host += toggleMaps.devPort;

    initializeMap();

    /*
     *
     * PROPERTY GETTERS
     *
     */

    // Get map properties
    function getMapProps(mapJSON){
        var zoom = parseInt(mapJSON['toggle_maps_zoom']);
        if(!zoom) zoom = 14;

        var mapProps = {
            center:{
                lat:  parseFloat(mapJSON['toggle_maps_latitude']),
                lng: parseFloat(mapJSON['toggle_maps_longitude'])
            },
            zoom: zoom,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        return mapProps;
    }

    // Get properties for a location marker
    function getMarkerProps(locationJSON){
        var markerProps = {
            position: {
                lat: parseFloat(locationJSON['toggle_maps_latitude']),
                lng: parseFloat(locationJSON['toggle_maps_longitude'])
            },
            map: null,
            title: locationJSON['title']['rendered']
        }
        return markerProps;
    }

    // Get properties for the info window
    function getWindowProps(locationJSON){
        var contentString = '<div class="toggle-maps info-window">'
        var title = locationJSON['title']['rendered'];
        var address = locationJSON['toggle_maps_address'];
        var phone = locationJSON['toggle_maps_phone'];
        var link = locationJSON['link'];
        var website = locationJSON['toggle_maps_website_link'];

        contentString = contentString + '<h1 class="marker-title">' + title + '</h1>';
        if(address){
            contentString = contentString + '<div class="marker-address"><label>Address:</label> ' + address + '</div>';
        }
        if(phone){
            contentString = contentString + '<div class="marker-phone"><label>Phone:</label> ' + phone + '</div>';
        }
        if(website){
            contentString = contentString + '<div class="marker-website"><label>Website:</label> <a class="website" href="' + website + '">' + title + ' Website</a>';
        }
        if(link){
            contentString = contentString + '<div class="marker-more-info"><a href="' + link + '">More Info</a></div>';
        }
        contentString = contentString + '</div>';

        // Return windo properties
        return {content: contentString}
    }

    /*
     *
     * INITIALIZERS
     *
     */

    // Inititalize a single marker
    function initializeMarker(locationJSON){

        // Make the marker
        var markerProps = getMarkerProps(locationJSON);
        var marker = new google.maps.Marker(markerProps);

        // Make the info window
        var windowProps = getWindowProps(locationJSON);
        var window = new google.maps.InfoWindow(windowProps);

        marker.addListener('click', function() {
            if(tm.openWindow)
                tm.openWindow.close();

            window.open(tm.map, marker);
            tm.openWindow = window;
        });

        return marker;
    }

    // Initialize all markers for all categories
    function initializeAllMarkers(){
        for(var n = 0; n < tm.terms.length; n++){
            initializeMarkers(tm.terms[n]['slug']);
        }
    }

    // Initialize all markers from specified category
    function initializeMarkers(term){
        var locations = $.ajax({
            url: tm.host + '/wp-json/wp/v2/toggle_maps_location?filter[posts_per_page]=2000&filter[toggle_maps_category]="' + term + '"',
            type: 'GET',
            dataType: 'json',
            success: function (locationsJSON) {
                tm.allMarkers[term] = [];
                for(var n = 0; n < locationsJSON.length; n++){
                    tm.allMarkers[term].push(initializeMarker(locationsJSON[n]));
                }
                if(term == tm.terms[0]['slug']){
                    setMarkers(term);
                }
            },
            error: function () {
                console.log("Could not fetch JSON");
            }
        });
        return locations;
    }

    // Create map
    function makeMap(mapProps){
        tm.map = new google.maps.Map(document.getElementById(tm.mapContainer), mapProps);
    }

    // Initialize the map
    function initializeMap(){
        if(tm.single){
            // Initialize a single-element map
            $.ajax({
                url: tm.host + '/wp-json/wp/v2/toggle_maps_location/' + tm.id,
                type: 'GET',
                dataType: 'json',
                success: function (locationJSON) {
                    makeMap(getMapProps(locationJSON));
                    setMarker(initializeMarker(locationJSON));
                },
                error: function () {
                    console.log("Could not fetch JSON");
                }
            });
        }
        else{
            // Initialize a toggle map
            $.ajax({
                url: tm.host + '/wp-json/wp/v2/toggle_maps_map/' + tm.id,
                type: 'GET',
                dataType: 'json',
                success: function (mapJSON) {
                    makeMap(getMapProps(mapJSON));
                    initializeAllMarkers();
                    if(tm.terms.length > 1)
                        addToggles();
                },
                error: function () {
                    console.log("Could not fetch JSON");
                }
            });
        }
    }

    /*
     *
     * SETTERS
     *
     */

    // Place a single marker on the map
    function setMarker(marker){
        tm.visibleMarkers.push(marker);
        tm.visibleMarkers[tm.visibleMarkers.length - 1].setMap(tm.map);
    }

    // Place all markers of a specified category on the map
    function setMarkers(term){
        for(var n = 0; n < tm.allMarkers[term].length; n++){
            setMarker(tm.allMarkers[term][n]);
        }
    }

    // Add toggle for a category
    function addToggle(term){
        var toggleButton = $('<input type="button" class="' + tm.toggle + ' ' + term + '" value="' + tm.termNames[term] +'"/>');
        $('.' + tm.toggleContainer).append(toggleButton);
        return $('.toggle.' + term);
    }

    function addListView(){
        if(tm.listView){
            var toggleButton = $('<a href="' + tm.host + '/list-view"><input type="button" class="' + tm.toggle + ' list-view" value="List View"/></a>');
            $('.' + tm.toggleContainer).append(toggleButton);
            return $('.toggle.list-view');
        }
    }

    // Add toggles for toggle-maps
    function addToggles(){
        var toggleGroup = $('#' + tm.mapContainer).append('<div class="' + tm.toggleContainer + '"></div>');

        for(var n = 0; n < tm.terms.length; n++){
            var toggle = addToggle(tm.terms[n]['slug']);
            if(n == 0 && tm.terms.length > 1)
                toggle.addClass('active');

            $('.toggle.' + tm.terms[n]['slug']).click(function(){
                $('.toggle.active').removeClass('active');
                $(this).addClass('active');
                toggleMarkers(tm.termSlugs[$(this).attr('value')]);
            });
        }
        addListView();
    }

    // Toggle category on or off
    function toggleMarkers(term){
        resetMarkers();
        setMarkers(term);
    }

    // Clear all visible markers
    function resetMarkers(){
        if(tm.openWindow)
            tm.openWindow.close();
        for(var n = 0; n < tm.visibleMarkers.length; n++){
            tm.visibleMarkers[n].setMap(null);
        }
        tm.visibleMarkers = [];
    }
});