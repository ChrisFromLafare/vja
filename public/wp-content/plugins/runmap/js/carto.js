// JavaScript Document
// Classe Point avec calcul de la distance entre 2 points
// Est une extension de la classe google.maps.LatLng


function Point(lat, lng){
	google.maps.LatLng.call(this, lat, lng);
	this.distanceFS = 0; // Distance depuis début (From Start)
	this.altitude = 0;
}

Point.prototype = new google.maps.LatLng();

Point.prototype.distance = function(p) {
		var latrad = this.latRad();
		var platrad = p.latRad();
		return Math.acos(Math.sin(platrad)*Math.sin(latrad)+ Math.cos(platrad)*Math.cos(latrad)*Math.cos(this.lngRad() - p.lngRad()))*6378137;
	};
	
Point.prototype.denivelle = function(p) {
		return(this.altitude - p.altitude);
	};

Point.prototype.latRad = function() {
	return this.lat()*3.14159265358979/180;
};

Point.prototype.lngRad = function() {
	return this.lng()*3.14159265358979/180;
};

// Classe Parcours
function Parcours() {
	this.name = "";
	this.heureDepart = new Date();
	this.heureArrivee = new Date();
	this.depart = new Point(0,0);
	this.arrivee = new Point(0,0);
	this.distance = 0;
	this.denivellePositif = 0;
	this.denivelleNegatif = 0;
	this.points = [];
	this.milestones = [];
	this.bounds = new google.maps.LatLngBounds();; // limites du parcours
}

Parcours.prototype = {
	loadGPX: function(url,callback) {
		$.ajax({
			context: this,
			type: "GET",
			url: url,
			dataType: "xml",
			success: function(xml) {
				// Get the name
				this.name = $(xml).find("name").text();
				var coordsXML = $(xml).find("trkpt");
				// Extract de depart point and depart date from the first point
				var f = coordsXML.first();
				this.depart = new Point(parseFloat(f.attr("lat")), parseFloat(f.attr("lon")));
				this.depart.altitude = parseFloat(f.find("ele").text());
				/^(\d+)-(\d+)-(\d+)T(\d+):(\d+):(\d+)Z$/.exec(f.find("time").text());
				this.heureDepart = new Date(parseInt(RegExp.$1),
																		parseInt(RegExp.$2)-1, 
																		parseInt(RegExp.$3),
																		parseInt(RegExp.$4),
																		parseInt(RegExp.$5),
																		parseInt(RegExp.$6));
				// Extract the arrival point and arrival time  from the la point
				var l = coordsXML.last();
				this.arrivee = new Point(parseFloat(l.attr("lat")), parseFloat(l.attr("lon")));
				/^(\d+)-(\d+)-(\d+)T(\d+):(\d+):(\d+)Z$/.exec(l.find("time").text());
				this.heureArrivee = new Date(parseInt(RegExp.$1),
																		parseInt(RegExp.$2)-1, 
																		parseInt(RegExp.$3),
																		parseInt(RegExp.$4),
																		parseInt(RegExp.$5),
																		parseInt(RegExp.$6));
																	
				// Save each point
				var savedThis = this;
				var i = -1;
				var nextML = 1000.0;
				coordsXML.each(function() {
					// Extraction des coordonnées et altitude de chaque point
					var lat = parseFloat($(this).attr("lat"));
					var lng = parseFloat($(this).attr("lon"));
					var p = new Point(lat, lng);
					p.altitude = parseFloat($(this).find("ele").text());
					// Calcul de la distance depuis le départ
					if (i >= 0) {
						savedThis.distance += p.distance(savedThis.points[i]);
						p.distanceFS = savedThis.distance;
						if (savedThis.distance >= nextML) {
							savedThis.milestones.push(p);
							nextML+=1000.0;
						}
						// Calcul du dénivelle
						var denivelle = p.denivelle(savedThis.points[i]);
						if (denivelle >= 0) 
							savedThis.denivellePositif += denivelle;
						else
							savedThis.denivelleNegatif += denivelle;
					}
					savedThis.points.push(p);
					i++;
					savedThis.bounds.extend(p);
				});
				// Call a callback function on completion
				if (callback && typeof(callback) === "function") {
  				callback(this);
				}			
			},
			error: function (xhr, status, error){
				alert("Erreur de chargement du fichier: "+xhr.responseText+" ("+status+" - "+error+")");
			}
		});
	},
/*
** Display the map in the specified container with options
** @param el: html container for the map
** @param mapOptions: google.map.MapOptions options used to display the map
*/
	display: function(el, mapOptions) {
		var map = new google.maps.Map(el, mapOptions);
		map.fitBounds(this.bounds);
	}
}

function runMap(gpxURL, map) {
	this.gpxURL = gpxURL;
	this.parcours = new Parcours();
	this.maMap = map;
	this.strokeColor = "#FF00AA";
	this.strokeWeight = 2;
	this.markersKm = []; // milestones markers
	this.markerA; // Arrival marker
	this.markerD; // Departure marker
	
	this.display = function(callback) {
		if (this.maMap === undefined) return;
		// Get a copy of this, it will be used
		var savedThis = this;
		this.parcours.loadGPX(gpxURL,function(parcours) {
			with (savedThis) {
				if (maMap === undefined) return;
				maMap.fitBounds(parcours.bounds);
				var poly = new google.maps.Polyline({
					path: parcours.points,
					strokeColor: strokeColor,
					strokeOpacity: .7,
					strokeWeight: strokeWeight
				});
				poly.setMap(maMap);					
				// Call a callback function on completion
				if (callback && typeof(callback) === "function") {
					callback(savedThis);
				}
			}
		});
	}

	this.setMarker = function() {
		// Add markers to the map
    // Marker sizes are expressed as a Size of X,Y
    // where the origin of the image (0,0) is located
    // in the top left of the image.

    // Origins, anchor positions and coordinates of the marker
    // increase in the X direction to the right and in
    // the Y direction down.
		var imageD = {
			// wp_params is generated by the wp plugin - plugin_url contains the plugin's base url.
			url: wp_params.plugin_url+'/images/pinD.png',
			// This marker is 15 pixels wide by 25 pixels tall.
			size: new google.maps.Size(15, 25),
			// The origin for this image is 0,0.
			origin: new google.maps.Point(0,0),
			// The anchor for this image is the base of the pin at 7,25.
			anchor: new google.maps.Point(7, 25)
		};
		var imageA = {
			url: wp_params.plugin_url+'/images/pinA.png',
			// This marker is 15 pixels wide by 25 pixels tall.
			size: new google.maps.Size(15, 25),
			// The origin for this image is 0,0.
			origin: new google.maps.Point(0,0),
			// The anchor for this image is the base of the pin at 7,25.
			anchor: new google.maps.Point(7, 25)
		};
		// Shapes define the clickable region of the icon.
		// The type defines an HTML are element 'poly' which
		// traces out a polygon as a series of X,Y points. The final
		// coordinate closes the poly by connecting to the first
		// coordinate.
		var shape = {
				coord: [7, 25, 5, 14, 1, 11, 0 , 8, 2, 2, 0, 7, 12, 2, 15, 8, 13, 13, 10, 15],
				type: 'poly'
		};
		// Create Depart Marker
		var markerD = new google.maps.Marker({
					position: this.parcours.depart,
					map: this.maMap,
					icon: imageD,
					shape: shape,
					title: 'D\351part',
					zIndex: 0
		});
		// Create Arrival Marker
		var markerA = new google.maps.Marker({
					position: this.parcours.arrivee,
					map: this.maMap,
					icon: imageA,
					shape: shape,
					title: 'Arriv\351e',
					zIndex: 0
		});
		// Create Marquers for each milestone
		var markerKm=[];
		for (i=0; i < this.parcours.milestones.length; i++) {
			var imageKm = {
				// this url create a sprite composed of one image for each milestone with the distance inside
				url: wp_params.plugin_url+'/pinKm.php?nb='+this.parcours.milestones.length,
				// This marker is 15 pixels wide by 25 pixels tall.
				size: new google.maps.Size(15, 25),
				// The origin for this image is 0,0.
				origin: new google.maps.Point(15*i,0),
				// The anchor for this image is the base of the flagpole at 0,32.
				anchor: new google.maps.Point(7, 25)
			};
			markerKm[i] = new google.maps.Marker({
						position: this.parcours.milestones[i],
						map: this.maMap,
						icon: imageKm,
						shape: shape,
						title: '',
						zIndex: i
			});	
		}
  }
}
