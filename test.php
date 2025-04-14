<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippines DA-PCC Centers Map</title>
    <style>
        /* Base styles */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        /* Modified map container to span full width */
        #map-container {
            display: flex;
            gap: 20px;
            width: 100%; 
            max-width: none; 
        }

        
        #map {
            width: 60%;
            height: 80vh;
            max-width: none;
            border-radius: 10px;
            overflow: hidden;
            -webkit-border-radius: 10px;
            -webkit-mask-image: -webkit-radial-gradient(circle, black 100%, black 0);
            -webkit-transform: translate3d(0px, 0px, 0px);
            z-index: 1;
        }

        
        #info-panel {
            width: 40%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }

        #info-panel.visible {
            opacity: 1;
            pointer-events: auto;
        }

        .panel-header {
            font-size: 1.2em;
            margin-bottom: 10px;
            color: #333;
        }

        .panel-details {
            font-size: 0.9em;
            line-height: 1.5;
            color: #666;
        }

        .panel-location {
            margin-top: 5px;
            font-style: italic;
        }

        /* Contact information styling */
        .contact-info {
            margin-top: 10px;
            padding-left: 15px;
        }

        .contact-item {
            margin-bottom: 5px;
        }

        /* Marker hover effects */
        .GMAMP-maps-pin-view {
            transition: all 0.25s linear;
        }

        .GMAMP-maps-pin-view:hover {
            transform: scale(1.2);
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            #map-container {
                width: 100%;
            }
            #map {
                width: 75%;
            }
        }

        @media (max-width: 900px) {
            #map-container {
                width: 100%;
            }
            #map {
                width: 80%;
            }
        }

        @media (max-width: 600px) {
            #map-container {
                flex-direction: column;
                gap: 10px;
            }
            #map {
                width: 100%;
                height: 60vh;
            }
            #info-panel {
                width: 90%;
                max-width: 400px;
            }
        }
    </style>
</head>
<body>
    <div id="map-container">
        <div id="map"></div>
        <div id="info-panel">
            <div class="panel-header">Center Details</div>
            <div class="panel-details">
                <div id="center-name"></div>
                <div id="center-location" class="panel-location"></div>
                <div id="center-contact" class="contact-info"></div>
            </div>
        </div>
    </div>

    <!-- Load Google Maps API -->
    <script>
    (g => {
        var h, a, k, p = "The Google Maps JavaScript API", c = "google", l = "importLibrary", q = "__ib__";
        var m = document, b = window;
        b = b[c] || (b[c] = {});
        var d = b.maps || (b.maps = {}), r = new Set, e = new URLSearchParams, u = () => h || (
            h = new Promise(async(f, n) => {
                await (a = m.createElement("script"));
                e.set("libraries", [...r] + "");
                for(k in g)e.set(k.replace(/[A-Z]/g, t => "_" + t[0].toLowerCase()), g[k]);
                e.set("callback", c + ".maps." + q);
                a.src = `https://maps.${c}apis.com/maps/api/js?` + e;
                d[q] = f;
                a.onerror = () => h = n(Error(p + " could not load."));
                a.nonce = m.querySelector("script[nonce]")?.nonce || "";
                m.head.append(a);
            })
        );
        d[l] ? console.warn(p + " only loads once. Ignoring:", g) :
               d[l] = (f, ...n) => r.add(f) && u().then(() => d[l](f, ...n));
    })({
        key: "AIzaSyC-76TlP4VSPEjMYYUOTNvXFhoDRpZqa54",
        v: "beta"
    });
    </script>
    <script>
    async function initMap() {
        // Import required libraries
        const { Map } = await google.maps.importLibrary("maps");
        const { AdvancedMarkerElement, PinElement } = await google.maps.importLibrary("marker");

        // Create map instance with water removed
        const map = new Map(document.getElementById('map'), {
            center: { lat: 12.8797, lng: 121.7740 },
            zoom: 6,
            mapId: 'philippines_dapcc_map',
            disableDefaultUI: true,
            zoomControl: false,
            zoomControlOptions: {
                position: google.maps.ControlPosition.LEFT_CENTER
            },
            styles: [
                {
                    featureType: "water",
                    elementType: "geometry",
                    stylers: [{ color: "#c0c0c0" }]
                },
                {
                    featureType: "water",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }]
                }
            ]
        });

        // Regional centers data
        const regionalCenters = [
            {
                name: "DA-PCC at Mariano Marcos State University",
                shortName: "DA-PCC at MMSU",
                lat: 18.0479061,
                lng: 120.5525777,
                location: "Batac City, Ilocos Norte",
                mobile: "+63 919.224.9062",
                email: "pcc-mmsu@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Cagayan State University",
                shortName: "DA-PCC at CSU",
                lat: 17.5534463,
                lng: 121.782553,
                location: "Tuguegarao City, Cagayan",
                phone: "+63 (078) 377.9315",
                mobile: "+63 977.806.4930",
                email: "pcc-csu@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Don Mariano Marcos Memorial State University",
                shortName: "DA-PCC at DMMMSU",
                lat: 16.2378308,
                lng: 120.4157506,
                location: "Rosario, La Union",
                mobile: "+63 920.982.9666",
                email: "pcc-dmmmsu@pcc.gov.ph"
            },
            {
                name: "DA-PCC National Headquarters and Gene Pool",
                shortName: "DA-PCC NHGP",
                lat: 15.744035,
                lng: 120.942936,
                location: "Science City of Muñoz, Nueva Ecija",
                phone: "+63 (044) 456.0731",
                email: "oed@pcc.gov.ph",
                color: "blue"
            },
            {
                name: "DA-PCC at Central Luzon State University",
                shortName: "DA-PCC at CLSU",
                lat: 15.739369,
                lng: 120.9312026,
                location: "Science City of Muñoz, Nueva Ecija",
                phone: "+63 (044) 940 3061",
                mobile: "+63 968.853.5754",
                email: "pcc-clsu@pcc.gov.ph"
            },
            {
                name: "DA-PCC at University Of The Philipines - Los Baños",
                shortName: "DA-PCC at UPLB",
                lat: 14.1587114,
                lng: 121.2440314,
                location: "Los Baños, Laguna",
                phone: "(049) 536.2729",
                email: "pcc-uplb@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Western Visayas State University",
                shortName: "DA-PCC at WVSU",
                lat: 11.1152003,
                lng: 122.5360161,
                location: "Calinog, Iloilo",
                phone: "+63 (033) 323.4781",
                mobile: "+63 999.991.6115",
                email: "pcc-wvsu@pcc.gov.ph"
            },
            {
                name: "DA-PCC at La Carlota Stock Farm",
                shortName: "DA-PCC at LCSF",
                lat: 10.4039666,
                lng: 122.9991764,
                location: "La Granja, La Carlota City, Negros Occidental",
                mobile: "+63 919.006.8392",
                email: "pcc-lcsf@pcc.gov.ph"
            },
            {
                name: "DA-PCC at VIsayas State University",
                shortName: "DA-PCC at VSU",
                lat: 10.7413785,
                lng: 124.7940473,
                location: "Baybay City, Leyte",
                mobile: "+63 908.895.2461",
                email: "pcc-vsu@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Ubay Stock Farm",
                shortName: "DA-PCC at USF",
                lat: 9.9928517,
                lng: 124.4482512,
                location: "Ubay, Bohol",
                mobile: "+63 992 161 5798",
                email: "pcc-usf@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Mindanao Livestock Production Center",
                shortName: "DA-PCC at MLPC",
                lat: 7.9253361,
                lng: 122.5321893,
                location: "Kalawit, Zamboanga del Norte",
                mobile: "+63 912.784.4668",
                email: "pcc-mlpc@pcc.gov.ph"
            },
            {
                name: "DA-PCC at University of Southern Mindanao",
                shortName: "DA-PCC at USM",
                lat: 7.1108477,
                lng: 124.839300,
                location: "Kabacan, North Cotabato",
                mobile: "+63 999.229.5948",
                email: "pcc-usm@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Central Mindanao University",
                shortName: "DA-PCC at CMU",
                lat: 7.8802248,
                lng: 125.0619829,
                location: "Maramag, Bukidnon",
                mobile: "+63 939.916.9719",
                email: "pcc-cmu@pcc.gov.ph"
            }
        ];

        // DOM elements for info panel
        const infoPanel = document.getElementById('info-panel');
        const centerName = document.getElementById('center-name');
        const centerLocation = document.getElementById('center-location');
        const centerContact = document.getElementById('center-contact');

        // Function to update info panel
        function updateInfoPanel(center) {
            // Clear previous contact information
            centerContact.innerHTML = '';
            
            // Set center name and location
            centerName.textContent = center.name;
            centerLocation.textContent = center.location;
            
            // Add contact information
            let contactHtml = '';
            if (center.phone) {
                contactHtml += `<div class="contact-item">Phone: ${center.phone}</div>`;
            }
            if (center.mobile) {
                contactHtml += `<div class="contact-item">Mobile: ${center.mobile}</div>`;
            }
            contactHtml += `<div class="contact-item">Email: <a href="mailto:${center.email}" style="color: inherit;">${center.email}</a></div>`;
            
            centerContact.innerHTML = contactHtml;
            infoPanel.classList.add('visible');
        }

        // Function to hide info panel
        function hideInfoPanel() {
            infoPanel.classList.remove('visible');
        }

        // Add markers with hover and click functionality
        regionalCenters.forEach(center => {
            const pinOptions = {
                background: center.color === 'blue' ? '#FFD700' : '#4169E1',
                borderColor: center.color === 'blue' ? '#FFD700' : '#4169E1',
                glyphColor: 'white',
                scale: center.color === 'blue' ? 2.0 : 1.5
            };

            const pin = new PinElement(pinOptions);
            const marker = new AdvancedMarkerElement({
                map,
                position: { lat: center.lat, lng: center.lng },
                content: pin.element,
                title: center.shortName
            });

            // Add hover event listeners to the marker content
            marker.content.addEventListener('mouseenter', () => {
                updateInfoPanel(center);
            });

            marker.content.addEventListener('mouseleave', () => {
                hideInfoPanel();
            });

            // Add click listener to show detailed information
            marker.addListener('click', ({ domEvent }) => {
                // Close any open info windows first
                infoPanel.classList.remove('visible');
                
                // Update the info panel with all contact details
                updateInfoPanel(center);
                
                // Prevent the default click behavior on the marker
                domEvent.stopPropagation();
            });
        });
    }

    // Initialize the map when the window loads
    window.addEventListener('load', initMap);
    </script>
</body>
</html>