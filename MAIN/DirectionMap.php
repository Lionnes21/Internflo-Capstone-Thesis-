<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucclogo2.png">
    <title>UCC - Company</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;800&display=swap");
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: "Open Sans", sans-serif;
        }
        #map {
            height: 100%;
            width: 100%;
        }
        #info-container {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .btn.go-now-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            background-color: #4aa629;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: color 0.3s ease;
            overflow: hidden;
            font-family: "Open Sans", sans-serif;
            font-weight: 600;
            z-index: 1;
            margin: 0 auto;
            padding: 8px 15px 10px 15px;
        }

        .btn.go-now-btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #449e25;
            transform: rotateX(0deg);
            transform-origin: top;
            transition: transform 0.5s ease;
            z-index: -1;
        }

        .btn.go-now-btn:hover::before {
            transform: rotateX(90deg);
        }

        .btn.go-now-btn:hover {
            color: white;
        }
        .custom-label-current {
                position: absolute;
                background-color: white;
                padding: 5px 10px;
                border-radius: 5px;
                box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.3);
                font-weight: bold;
                color: #171717;
                font-size: 14px;
                text-align: center;
                border: 1px solid #d1d1d1;
            }   
            .company-card {
            width: 250px;
            padding: 20px;
            border-radius: 12px;
            background-color: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            font-family: "Open Sans", sans-serif;
            text-align: center;
            position: absolute;
            transform: translate(-50%, -100%);
            }

            .company-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 12px;
            }

            .company-name {
            color: #171717;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            }

            .company-website {
            font-size: 14px;
            font-weight: 600;
            }

            .website-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #0000ee;
            text-decoration: none;
            font-size: inherit;
            }

            .website-link:hover {
            text-decoration: underline;
            }

            .external-link-icon {
            margin-left: 2px;
            }

            .company-address,
            .company-contact {
            font-size: 14px;
            color: #2e3849;
            margin-bottom: 5px;
            }

            .button-container {
            margin: 15px 0px 5px 0px;
            display: flex; /* Align buttons in a row */
            justify-content: center; /* Center the buttons */
            gap: 8px; /* Add space between buttons */
            }

            .btn {
            font-family: "Open Sans", sans-serif;
            font-size: 14px;
            padding: 8px 15px 10px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        
            }

            .apply-btn {
            background-color: #002b7f;
            color: white;
            font-weight: 600;
            }

            .view-btn {
            background-color: #d9eafd;
            color: #1e48aa;
            font-weight: 600;
            }
            .custom-label-internship {
            position: absolute;
            background-color: white;
            padding: 5px 10px;
            border-radius: 5px;
            box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.3);
            font-weight: bold;
            color: #171717;
            font-size: 14px;
            text-align: center;
            border: 1px solid #d1d1d1;
            width: 155px;
            }
            @media (max-width: 870px) {
  .company-card {
    width: 220px;
    padding: 15px;
  }

  .company-logo {
    width: 70px;
    height: 70px;
    margin: 0 auto 10px;
  }

  .company-name {
    font-size: 16px;
    margin-bottom: 8px;
  }

  .company-website,
  .company-address,
  .company-contact {
    font-size: 13px;
  }

  .btn {
    font-size: 13px;
    padding: 6px 12px 8px 12px;
  }

  .button-container {
    margin: 12px 0px 4px 0px;
    gap: 6px;
  }


}

@media (max-width: 560px) {
  .company-card {
    width: 200px;
    padding: 12px;
  }

  .company-logo {
    width: 60px;
    height: 60px;
    margin: 0 auto 8px;
  }

  .company-name {
    font-size: 15px;
    margin-bottom: 6px;
  }

  .company-website,
  .company-address,
  .company-contact {
    font-size: 12px;
  }

  .btn {
    font-size: 12px;
    padding: 5px 10px 7px 10px;
  }

  .button-container {
    margin: 10px 0px 3px 0px;
    gap: 5px;
  }



  .btn.go-now-btn {
    font-size: 13px;
    padding: 6px 12px 8px 12px;
    gap: 6px;
  }

  #info-container {
    padding: 8px;
    top: 8px;
    left: 8px;
  }
}
    </style>
</head>
<body>
    <div id="info-container">
        <div id="distance-info"></div>
        <div id="duration-info"></div>
    </div>
    <div id="map"></div>

    <script>
    function initMap() {
        const urlParams = new URLSearchParams(window.location.search);
        const destinationLat = parseFloat(urlParams.get('lat'));
        const destinationLng = parseFloat(urlParams.get('lng'));
        // Decode the URL parameters
        const companyLogo = decodeURIComponent(urlParams.get('logo') || '');
        const companyName = decodeURIComponent(urlParams.get('name') || '');
        const companyAddress = decodeURIComponent(urlParams.get('address') || '');
        const contactDetails = decodeURIComponent(urlParams.get('contact') || '');
        const companyWebsite = decodeURIComponent(urlParams.get('website') || '');
        const companyId = urlParams.get('id');
        const internshipId = urlParams.get('internship_id'); // Get internship_id from URL

        if (isNaN(destinationLat) || isNaN(destinationLng)) {
            console.error('Invalid coordinates');
            alert('Invalid destination location');
            return;
        }

    const destinationLocation = { lat: destinationLat, lng: destinationLng };

    // Add custom styles for the location marker label
    document.head.insertAdjacentHTML('beforeend', `
        <style>
            .gm-style-cc:not(.gmnoprint) { 
                display: none !important;
            }

            .gmnoprint.gm-style-mtc-bbw {
                bottom: 0 !important;
            }
            
            /* Hide Google Maps text logo */
            a[href^="http://maps.google.com/maps"]:not(.google-maps-link), 
            a[href^="https://maps.google.com/maps"]:not(.google-maps-link) {
                display: none !important;
            }
            
            /* Hide Terms text specifically */
            .gm-style-cc > div > a[href*="terms"] {
                display: none !important;
            }
            
            /* Hide the container of Terms text if empty */
            .gm-style-cc:empty,
            .gm-style-cc > div:empty {
                display: none !important;
            }

            /* Style the Pegman (Street View control) in all states */
            .gm-svpc {
                background-color: white !important;
                border-radius: 2px !important;
            }

            /* Base Pegman color (#449e25) */
            .gm-svpc img,
            [src*="cb_scout_sprite"],
            [src*="cb_scout_sprite_2x"],
            .gm-control-active img {
                filter: hue-rotate(112deg) saturate(140%) brightness(80%) !important;
            }

            /* Hover state color (#4aa629) */
            .gm-svpc:hover img {
                filter: hue-rotate(112deg) saturate(150%) brightness(85%) !important;
            }

            /* Dragged Pegman color (#449e25) */
            .gm-style [src*="pegman"]:not([src*="pegman_dock"]),
            .gm-style [src*="cb_scout"] {
                filter: hue-rotate(112deg) saturate(140%) brightness(80%) !important;
            }

            /* Style the highlighted roads when dragging Pegman */
            .gm-style .gm-style-pbc {
                background-color: rgba(68, 158, 37, 0.25) !important;
            }
        </style>
    `);

    const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 15,
        center: destinationLocation,
        mapId: "104493fcf83dca90", // Added Map ID
        mapTypeControl: true,
        zoomControl: false,
        clickableIcons: false,
        streetViewControl: true,
        fullscreenControl: false,
        gestureHandling: 'greedy',
        mapTypeId: google.maps.MapTypeId.TERRAIN,
        mapTypeControlOptions: {
            position: google.maps.ControlPosition.RIGHT_BOTTOM,
            style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
            mapTypeIds: [
                google.maps.MapTypeId.TERRAIN,
                google.maps.MapTypeId.SATELLITE
            ]
        },
        streetViewControlOptions: {
            position: google.maps.ControlPosition.LEFT_BOTTOM
        },
        streetViewOptions: {
            addressControl: false
        },
    });

    const directionsService = new google.maps.DirectionsService();
    const directionsRenderer = new google.maps.DirectionsRenderer({
        map: map,
        suppressMarkers: false,
        preserveViewport: false
    });

    // Declare variables at top level
    let userMarker = null;
    let userOverlay = null;

    function getDirections() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };

                    // Remove previous marker if exists
                    if (userMarker) {
                        userMarker.map = null;
                    }

                    // Create a bounds object to fit both user location and destination
                    const bounds = new google.maps.LatLngBounds();
                    bounds.extend(userLocation);
                    bounds.extend(destinationLocation);

                    // Fit the map to the bounds with some padding
                    map.fitBounds(bounds, {
                        top: 100,     // Increased top padding
                        bottom: 100,  // Increased bottom padding
                        left: 100,    // Increased left padding
                        right: 100    // Increased right padding
                    });

                    // Create AdvancedMarkerElement for user location
                    const userIcon = document.createElement('img');
                    userIcon.src = "pics/gps.png";
                    userIcon.style.width = "50px";
                    userIcon.style.height = "50px";

                    // Initialize userMarker - using the outer scope variable
                    userMarker = new google.maps.marker.AdvancedMarkerElement({
                        position: userLocation,
                        map: map,
                        title: "Your Current Location",
                        content: userIcon
                    });

                    // Create custom label for user location
                    const userLabelDiv = document.createElement("div");
                    userLabelDiv.innerHTML = "Current location";
                    userLabelDiv.classList.add("custom-label-current");

                    if (userOverlay) {
                        userOverlay.setMap(null);
                    }

                    userOverlay = new google.maps.OverlayView();
                    userOverlay.onAdd = function () {
                        const panes = this.getPanes();
                        panes.floatPane.appendChild(userLabelDiv);
                    };

                    userOverlay.draw = function () {
                        const projection = this.getProjection();
                        const position = projection.fromLatLngToDivPixel(userLocation);
                        const labelOffsetX = -55;
                        const labelOffsetY = 5;
                        userLabelDiv.style.left = position.x + labelOffsetX + "px";
                        userLabelDiv.style.top = position.y + labelOffsetY + "px";
                    };

                    userOverlay.onRemove = function () {
                        userLabelDiv.parentNode.removeChild(userLabelDiv);
                    };

                    userOverlay.setMap(map);

                    // Modify the zoom change listener
                    google.maps.event.addListener(map, "zoom_changed", () => {
                        const zoom = map.getZoom();
                        // Only attempt to resize if userMarker exists and has content
                        if (userMarker && userMarker.content) {
                            try {
                                const newSize = Math.max(30, 60 - (zoom - 15) * 3);
                                userMarker.content.style.width = `${newSize}px`;
                                userMarker.content.style.height = `${newSize}px`;
                            } catch (error) {
                                console.warn('Could not update marker size:', error);
                            }
                        }
                    });

                    // Rest of your code remains the same...
                    const directionsRenderer = new google.maps.DirectionsRenderer({
                        map: map,
                        suppressMarkers: true,
                        preserveViewport: false,
                        polylineOptions: {
                            strokeOpacity: 1,
                            strokeWeight: 10,
                            geodesic: true,
                            strokeColor: '#0A11D8'  // Blue color for the route
                        }
                    });

                    directionsService.route(
                        {
                            origin: userLocation,
                            destination: destinationLocation,
                            travelMode: google.maps.TravelMode.DRIVING
                        },
                        (response, status) => {
                            if (status === google.maps.DirectionsStatus.OK) {
                                // Clear default blue route
                                directionsRenderer.setOptions({ suppressPolylines: true });

                                const route = response.routes[0];
                                const totalDistance = route.legs[0].distance.text;
                                const totalDuration = route.legs[0].duration.text;
                                document.getElementById('distance-info').textContent = `Distance: ${totalDistance}`;
                                document.getElementById('duration-info').textContent = `Estimated Duration: ${totalDuration}`;

                                // Create AdvancedMarkerElement for destination
                                const destinationIcon = document.createElement('img');
                                destinationIcon.src = "pics/company.png";
                                destinationIcon.style.width = "50px";
                                destinationIcon.style.height = "50px";

                                const destinationMarker = new google.maps.marker.AdvancedMarkerElement({
                                    position: destinationLocation,
                                    map: map,
                                    title: companyName,
                                    content: destinationIcon
                                });

                                    // Create label div
                                    const destinationLabelDiv = document.createElement("div");
                                    destinationLabelDiv.innerHTML = companyName;
                                    destinationLabelDiv.classList.add("custom-label-internship");

                                    const destinationLabelOverlay = new google.maps.OverlayView();
                                    destinationLabelOverlay.onAdd = function () {
                                        const panes = this.getPanes();
                                        panes.floatPane.appendChild(destinationLabelDiv);
                                    };

                                    destinationLabelOverlay.draw = function () {
                                        const projection = this.getProjection();
                                        const position = projection.fromLatLngToDivPixel(destinationLocation);
                                        const labelOffsetX = -85;
                                        const labelOffsetY = 5;
                                        destinationLabelDiv.style.left = position.x + labelOffsetX + "px";
                                        destinationLabelDiv.style.top = position.y + labelOffsetY + "px";
                                    };

                                    destinationLabelOverlay.onRemove = function () {
                                        if (destinationLabelDiv.parentNode) {
                                            destinationLabelDiv.parentNode.removeChild(destinationLabelDiv);
                                        }
                                    };

                                    // When showing the marker
                                    destinationMarker.map = map;
                                    destinationLabelOverlay.setMap(map);

                                    const destinationCardDiv = document.createElement("div");
destinationCardDiv.classList.add("company-card");
destinationCardDiv.style.display = 'none'; // Start hidden
destinationCardDiv.innerHTML = `
    <img src="../RECRUITER/${companyLogo}" alt="Company Logo" class="company-logo"> 
    <div class="company-name">${companyName}</div>
    <div class="company-address">${companyAddress}</div>
    <div class="company-contact">${contactDetails}</div>
    <div class="company-website">
        <a href="${companyWebsite}" target="_blank" class="website-link">
            ${companyWebsite}
            <svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#0000ee" class="external-link-icon">
                <path d="M206.78-100.78q-44.3 0-75.15-30.85-30.85-30.85-30.85-75.15v-546.44q0-44.3 30.85-75.15 30.85-30.85 75.15-30.85H480v106H206.78v546.44h546.44V-480h106v273.22q0 44.3-30.85 75.15-30.85 30.85-75.15 30.85H206.78ZM405.52-332 332-405.52l347.69-347.7H560v-106h299.22V-560h-106v-119.69L405.52-332Z"/>
            </svg>
        </a>
    </div>
    <div class="button-container">
        <button class="btn apply-btn" onclick="window.location.href='COMPANYCARDINFO-APPLY.php?id=${companyId}&internship_id=${internshipId}'">APPLY NOW</button>
        <button class="btn view-btn" onclick="window.location.href='COMPANYCARDINFO-VIEW.php?id=${companyId}&internship_id=${internshipId}'">VIEW MORE</button>
    </div>
    <button class="btn go-now-btn">DIRECTION <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#FFFFFF"><path d="M402-402 143-507q-13-5-19-15.5t-6-21.5q0-11 6.5-21.5T144-581l614-228q12-5 23-2t19 11q8 8 11 19t-2 23L581-144q-5 13-15.5 19.5T544-118q-11 0-21.5-6T507-143L402-402Z"/></svg></button>
`;

                                    // Inside the directionsService.route() callback, after creating the destinationCardDiv
                                    const goNowBtn = destinationCardDiv.querySelector('.go-now-btn');
                                                                    let navigationMode = false;
                                                                    let navigationRenderer = null;
                                                                    let navigationMarker = null;
                                                                    let currentLocationMarker = null;
                                    // First, modify the createNavigationMarker function
                                    const createNavigationMarker = (position) => {
                                        return new Promise((resolve, reject) => {
                                            try {
                                                const navigationIcon = document.createElement('img');
                                                navigationIcon.src = 'pics/location-status.png';
                                                navigationIcon.style.width = "50px";
                                                navigationIcon.style.height = "50px";
                                                
                                                navigationIcon.onload = () => {
                                                    const marker = new google.maps.marker.AdvancedMarkerElement({
                                                        position: position,
                                                        content: navigationIcon
                                                    });
                                                    
                                                    // Only set the map after marker is fully created
                                                    setTimeout(() => {
                                                        marker.map = map;
                                                    }, 100);
                                                    
                                                    resolve(marker);
                                                };
                                                
                                                navigationIcon.onerror = () => {
                                                    // Fallback to default marker if image fails to load
                                                    const marker = new google.maps.Marker({
                                                        position: position,
                                                        map: map,
                                                        icon: {
                                                            path: google.maps.SymbolPath.CIRCLE,
                                                            scale: 10,
                                                            fillColor: '#4285F4',
                                                            fillOpacity: 1,
                                                            strokeColor: '#ffffff',
                                                            strokeWeight: 2
                                                        }
                                                    });
                                                    resolve(marker);
                                                };
                                            } catch (error) {
                                                reject(error);
                                            }
                                        });
                                    };

                                    // Then modify the goNowBtn click handler
                                    goNowBtn.addEventListener('click', async () => {
                                        if (!navigationMode) {
                                            try {
                                                navigationMode = true;
                                                goNowBtn.textContent = 'STOP DIRECTION';
                                                goNowBtn.classList.add('navigation-active');

                                                // Create navigation renderer
                                                navigationRenderer = new google.maps.DirectionsRenderer({
                                                    map: map,
                                                    suppressMarkers: true,
                                                    preserveViewport: true, // Changed to true to prevent automatic zoom
                                                    polylineOptions: {
                                                        strokeColor: '#FF0000',
                                                        strokeOpacity: 0.8,
                                                        strokeWeight: 8
                                                    }
                                                });

                                                // Get initial position
                                                const position = await new Promise((resolve, reject) => {
                                                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                                                        enableHighAccuracy: true,
                                                        timeout: 5000,
                                                        maximumAge: 0
                                                    });
                                                });

                                                const userLocation = {
                                                    lat: position.coords.latitude,
                                                    lng: position.coords.longitude
                                                };

                                                // Clean up existing markers
                                                if (currentLocationMarker) {
                                                    currentLocationMarker.map = null;
                                                    currentLocationMarker = null;
                                                }
                                                if (userMarker) {
                                                    userMarker.map = null;
                                                    userMarker = null;
                                                }
                                                if (navigationMarker) {
                                                    navigationMarker.map = null;
                                                    navigationMarker = null;
                                                }

                                                // Set map center first
                                                map.setCenter(userLocation);
                                                
                                                // Create new marker
                                                try {
                                                    navigationMarker = await createNavigationMarker(userLocation);
                                                    
                                                    // Only set zoom after marker is created
                                                    setTimeout(() => {
                                                        if (navigationMarker && map) {
                                                            map.setZoom(18); // Reduced zoom level for better performance
                                                        }
                                                    }, 200);
                                                } catch (error) {
                                                    console.error('Error creating marker:', error);
                                                    // Create fallback marker
                                                    navigationMarker = new google.maps.Marker({
                                                        position: userLocation,
                                                        map: map
                                                    });
                                                }

                                                const updateNavigation = async () => {
                                                    if (!navigationMode) return;

                                                    try {
                                                        const position = await new Promise((resolve, reject) => {
                                                            navigator.geolocation.getCurrentPosition(resolve, reject, {
                                                                enableHighAccuracy: true,
                                                                timeout: 5000,
                                                                maximumAge: 0
                                                            });
                                                        });

                                                        const newLocation = {
                                                            lat: position.coords.latitude,
                                                            lng: position.coords.longitude
                                                        };

                                                        // Update marker position
                                                        if (navigationMarker) {
                                                            if (navigationMarker instanceof google.maps.marker.AdvancedMarkerElement) {
                                                                navigationMarker.position = newLocation;
                                                            } else {
                                                                navigationMarker.setPosition(newLocation);
                                                            }
                                                        }

                                                        // Update map center
                                                        map.setCenter(newLocation);

                                                        // Calculate route
                                                        const response = await new Promise((resolve, reject) => {
                                                            directionsService.route({
                                                                origin: newLocation,
                                                                destination: destinationLocation,
                                                                travelMode: google.maps.TravelMode.DRIVING
                                                            }, (result, status) => {
                                                                if (status === google.maps.DirectionsStatus.OK) {
                                                                    resolve(result);
                                                                } else {
                                                                    reject(status);
                                                                }
                                                            });
                                                        });

                                                        if (navigationRenderer) {
                                                            navigationRenderer.setDirections(response);
                                                        }
                                                    } catch (error) {
                                                        console.error('Navigation update error:', error);
                                                    }
                                                };

                                                // Start updates
                                                await updateNavigation();
                                                this.navigationInterval = setInterval(updateNavigation, 30000);

                                            } catch (error) {
                                                console.error('Navigation initialization error:', error);
                                                navigationMode = false;
                                                alert('Unable to start navigation. Please try again.');
                                            }
                                        } else {

                                            location.reload();
                                        }
                                    });

                                    // Create company card overlay
                                    const destinationCardOverlay = new google.maps.OverlayView();
                                    destinationCardOverlay.onAdd = function () {
                                        const panes = this.getPanes();
                                        panes.floatPane.appendChild(destinationCardDiv);
                                    };

                                    destinationCardOverlay.draw = function () {
                                        const projection = this.getProjection();
                                        const position = projection.fromLatLngToDivPixel(destinationLocation);
                                        destinationCardDiv.style.left = position.x + "px";
                                        destinationCardDiv.style.top = (position.y - 60) + "px";
                                    };

                                    destinationCardOverlay.onRemove = function () {
                                        if (destinationCardDiv.parentNode) {
                                            destinationCardDiv.parentNode.removeChild(destinationCardDiv);
                                        }
                                    };

                                    // Assuming you have a global variable to track the active card
                                    let activeCardDiv = null;

                                    destinationMarker.addListener('click', () => {
                                        if (destinationCardDiv.style.display === 'block') {
                                            // Hide the card
                                            destinationCardDiv.style.display = 'none';
                                            destinationCardOverlay.setMap(null);
                                            activeCardDiv = null;
                                        } else {
                                            // Hide any previously active card
                                            if (activeCardDiv) {
                                                activeCardDiv.style.display = 'none';
                                            }

                                            // Show the destination card
                                            destinationCardDiv.style.display = 'block';
                                            destinationCardOverlay.setMap(map);
                                            activeCardDiv = destinationCardDiv;

                                            // Adjust zooming based on navigation mode
                                            if (navigationMode) {
                                                // During navigation, keep the current zoom level
                                                map.setCenter(destinationLocation);
                                            } else {
                                                // Normal mode: zoom to 16
                                                map.setCenter(destinationLocation);
                                                map.setZoom(16);
                                            }

                                            // Consistent card positioning
                                            const scale = Math.pow(2, map.getZoom());
                                            const worldCoordinateCenter = map.getProjection().fromLatLngToPoint(destinationLocation);
                                            const pixelOffset = new google.maps.Point(0, -240);

                                                const worldCoordinateNewCenter = new google.maps.Point(
                                                    worldCoordinateCenter.x,
                                                    worldCoordinateCenter.y + (pixelOffset.y / scale)
                                                );

                                                const newCenter = map.getProjection().fromPointToLatLng(worldCoordinateNewCenter);
                                                map.panTo(newCenter);
                                            }
                                        });

                                        map.addListener('click', () => {
                                            if (activeCardDiv) {
                                                activeCardDiv.style.display = 'none';
                                                activeCardDiv = null;
                                            }
                                        });

                                        // Add click handlers for buttons
                                        const viewMoreBtn = destinationCardDiv.querySelector('.view-btn');
    viewMoreBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        window.location.href = `COMPANYCARDINFO-VIEW.php?id=${companyId}&internship_id=${internshipId}`;
    });

    const applyNowBtn = destinationCardDiv.querySelector('.apply-btn');
    applyNowBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        window.location.href = `COMPANYCARDINFO-APPLY.php?id=${companyId}&internship_id=${internshipId}`;
    });

                                        // Get the path
                                        const path = response.routes[0].overview_path;
                                        const totalPathLength = path.length;

                                        // Meaningful color segments
                                        const colorSegments = [
                                            {
                                                startIndex: 0,
                                                endIndex: Math.floor(totalPathLength * 0.1),
                                                color: '#4CAF50', // Green for start of journey
                                                description: 'Initial Route'
                                            },
                                            {
                                                startIndex: Math.floor(totalPathLength * 0.1),
                                                endIndex: Math.floor(totalPathLength * 0.6),
                                                color: '#2196F3', // Blue as default route color
                                                description: 'Standard Route'
                                            },
                                            {
                                                startIndex: Math.floor(totalPathLength * 0.6),
                                                endIndex: Math.floor(totalPathLength * 0.8),
                                                color: '#FFC107', // Yellow for potential traffic or slower section
                                                description: 'Potential Traffic Area'
                                            },
                                            {
                                                startIndex: Math.floor(totalPathLength * 0.8),
                                                endIndex: totalPathLength - 1,
                                                color: '#F44336', // Red for approaching destination
                                                description: 'Approaching Destination'
                                            }
                                        ];

                                        // Function to detect significant direction changes
                                        function detectDirectionChanges(pathPoints) {
                                            const directionChangePoints = [];
                                            const angleThreshold = 30; // Degrees of angle change to consider significant
                                            const minDistance = 100; // Meters to consider a meaningful change

                                            for (let i = 1; i < pathPoints.length - 1; i++) {
                                                const prevPoint = pathPoints[i-1];
                                                const currentPoint = pathPoints[i];
                                                const nextPoint = pathPoints[i+1];

                                                const prevBearing = google.maps.geometry.spherical.computeHeading(prevPoint, currentPoint);
                                                const nextBearing = google.maps.geometry.spherical.computeHeading(currentPoint, nextPoint);

                                                let angleDiff = Math.abs(prevBearing - nextBearing);
                                                angleDiff = Math.min(angleDiff, 360 - angleDiff);

                                                if (angleDiff > angleThreshold) {
                                                    directionChangePoints.push(currentPoint);
                                                }
                                            }

                                            return directionChangePoints;
                                        }

                                        // Create color-specific polylines
                                        colorSegments.forEach(segment => {
                                            const segmentPath = path.slice(segment.startIndex, segment.endIndex + 1);
                                            
                                            // Create polyline for the segment
                                            new google.maps.Polyline({
                                                path: segmentPath,
                                                geodesic: true,
                                                strokeColor: segment.color,
                                                strokeOpacity: 1.0,
                                                strokeWeight: 10,
                                                map: map,
                                                title: segment.description
                                            });

                                            // Detect and mark direction change points with smaller circles
                                            const directionChangePoints = detectDirectionChanges(segmentPath);
                                             directionChangePoints.forEach(point => {
                                                const pinElement = document.createElement('div');
                                                pinElement.style.width = '6px';
                                                pinElement.style.height = '6px';
                                                pinElement.style.borderRadius = '50%';
                                                pinElement.style.background = 'white';
                                                pinElement.style.border = '1px solid black';

                                                new google.maps.marker.AdvancedMarkerElement({
                                                    position: point,
                                                    map: map,
                                                    content: pinElement
                                                });
                                            });
                                        });

                                    } else {
                                        console.error('Directions request failed due to ' + status);
                                    }
                                }
                            );

                                },
                                (error) => {
                                    console.error('Error getting user location:', error);
                                    alert('Unable to retrieve your location. Please enable location services.');
                                },
                                {
                                    enableHighAccuracy: true,
                                    timeout: 5000,
                                    maximumAge: 0
                                }
                            );
                                } else {
                                    console.error('Geolocation is not supported by this browser.');
                                    alert('Geolocation is not supported by your browser.');
                                }
                            }

                            getDirections();

const streetView = map.getStreetView();
google.maps.event.addListener(streetView, 'visible_changed', function() {
    const isVisible = streetView.getVisible();
    const infoContainer = document.getElementById('info-container');
    if (infoContainer) {
        infoContainer.style.display = isVisible ? 'none' : 'block';
    }
});

streetView.setOptions({
    addressControl: false,
    enableCloseButton: true,    
    showRoadLabels: false,             
    fullscreenControl: false,    
    motionTracking: false,       
    motionTrackingControl: false,
    zoomControl: false
});
}

function loadGoogleMapsAPI() {
const script = document.createElement("script");
script.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyDtbgRcgki0qgsq4Kt6c0JlhhUhEUH7PXQ&libraries=geometry,marker&callback=initMap&loading=async&v=beta`;
script.async = true;
script.defer = true;
document.head.appendChild(script);
}

                        // Load the Google Maps API and initialize the map
                        loadGoogleMapsAPI();
    </script>
</body>
</html>


