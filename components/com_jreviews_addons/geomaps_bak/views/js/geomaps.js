/**
* GeoMaps Addon for JReviews
* Copyright (C) 2010-2012 ClickFWD LLC
* This is not free software, do not distribute it.
* For licencing information visit http://www.reviewsforjoomla.com
* or contact sales@reviewsforjoomla.com
**/

(function($,undefined) {

	var page = $('div.jr-page'),
		mapCanvas,
		advSearchModuleForm,
		advSearchPageForm,
		listingForm,
		proximityFieldModule,
		proximityFieldPage,
		__api_loading = false;

	jreviews.geomaps = jreviews.geomaps || {};

	jreviews.geomaps.apiLoadedCallback = null;

	jreviews.geomaps.api_callback = function() {

		if(typeof jreviews.geomaps.apiLoadedCallback == "function") {

			jreviews.geomaps.apiLoadedCallback();
		}

		jreviews.geomaps.apiLoadedCallback = null;
	};

	jreviews.geomaps.loadAPI = function(callback) {

		if(typeof google == 'undefined' || typeof google.maps == 'undefined') {

			if(__api_loading) {

				setTimeout(function() {

					jreviews.geomaps.loadAPI(callback);

				}, 50);

				return false;
			}

			__api_loading = true;

			jreviews.geomaps.apiLoadedCallback = callback;

			$.getScript(jreviews.geomaps.google_api_url+'&callback=jreviews.geomaps.api_callback');

			return false;
		}

		if(typeof callback == 'function') callback();

		return true;
	};

	jreviews.geomaps.startup = function() {

		page = page.length > 0 ? page : $('div.jr-page');

		mapCanvas = $('.jr-map-canvas') || [];

		advSearchModuleForm = page.find('form.jr-form-adv-search-module') || [];

		advSearchPageForm = page.find('#jr-form-adv-search') || [];

		proximityFieldModule = jreviews.geomaps.fields.proximity !== '' &&

								advSearchModuleForm.length ? advSearchModuleForm.find('.'+jreviews.geomaps.fields.proximity) : [];

		// Add coordinates as hidden inputs to adv. search module if they are passed as url parameters
		if(advSearchModuleForm.length) {

			var params = {},
				lat_field = jreviews.geomaps.fields.lat,
				lon_field = jreviews.geomaps.fields.lon;

			window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
				params[key] = value;
			});

			if(!(lat_field in params) || !(lon_field in params)) {

				window.location.href.replace(/([^\/:]+):([^\/]*)/gi, function(m,key,value) {
					params[key] = value;
				});
			}

			if((lat_field in params) && (lon_field in params)) {

				var lat = params[lat_field],
					lon = params[lon_field],
					latInput = advSearchModuleForm.find('[name="data[Field][Listing]['+lat_field+']"]'),
					lonInput = advSearchModuleForm.find('[name="data[Field][Listing]['+lon_field+']"]');

				if(!latInput.length && !lonInput.length) {

					advSearchModuleForm
						.append('<input type="hidden" name="data[Field][Listing]['+lat_field+']" value="'+lat+'" />')
						.append('<input type="hidden" name="data[Field][Listing]['+lon_field+']" value="'+lon+'" />');
				}
			}
		}

		// Add listing form map it button
		if(jreviews.geomaps.fields.mapit) {

			page.on('jrListingFieldsLoaded', '#jr-form-listing', function() {

				var el = $(this),
					mapitField = el.find('.'+jreviews.geomaps.fields.mapit);

				if(mapitField.length) {

					jreviews.geomaps.loadAPI(function() {

						el.jrMapIt({fields: jreviews.geomaps.fields});
					});
				}
			});
		}

		if(mapCanvas.length > 0 || proximityFieldModule.length > 0 || advSearchPageForm.length > 0) {

			jreviews.geomaps.loadAPI(jreviews.geomaps.init);
		}
	};

	jreviews.geomaps.init = function() {

		// Render all map elements on the page
		mapCanvas.each(function() {

			var el = $(this),
				referrer = el.data('referrer');

			if(referrer in jreviews.geomaps.mapData) {

				el.jrMaps(jreviews.geomaps.mapData[referrer]);
			}
		});

		if(jreviews.geomaps.fields.proximity) {

			// Add proximity search autocomplete to adv. search module
			if(proximityFieldModule && typeof google !== 'undefined') {

				var autocomplete = jreviews.geomaps.autocomplete && typeof google.maps.places != 'undefined';

				advSearchModuleForm.jrProximitySearch({fields: jreviews.geomaps.fields, autocomplete: autocomplete});
			}

			// Add proximity search autocomplete to adv. search page
			page.on('jrSearchFormLoaded','#jr-form-adv-search', function() {

				var el = $(this),
					proximityFieldPage = el.find('.'+jreviews.geomaps.fields.proximity);

				if(proximityFieldPage.length) {

					jreviews.geomaps.loadAPI(function() {

						var autocomplete = jreviews.geomaps.autocomplete && typeof google.maps.places != 'undefined';

						el.jrProximitySearch({fields: jreviews.geomaps.fields, autocomplete: autocomplete});
					});
				}
			});
		}

	};

	jreviews.addOnload('geomaps-startup',	jreviews.geomaps.startup);

})(jQuery);

/**
 * Adds the MapIt dialog capability to the listing form
 */
(function($) {

	$.widget("jreviews.jrMapIt", {

		options: {
			width: '700px',
			zoom: 15,
			lang: {
				'mapit': jreviews.__t('GEOMAPS_MAPIT'),
				'clear_coord': jreviews.__t('GEOMAPS_CLEAR_COORDINATES'),
				'geocode_fail': jreviews.__t('GEOMAPS_CANNOT_GEOCODE'),
				'drag_marker': jreviews.__t('GEOMAPS_DRAG_MARKER')
			},
			fields: {}
		},
		page: null,
		buttons : {},
		inputs: {},

		_create: function(options) {

			var self = this;

			self.page = self.element;

			$.extend(self.options, options);

			self.buttons = {
				'map': $('<button class="jrButton">').html('<span class="jrIconPin"></span>' + self.options.lang.mapit),
				'clearVal': $('<button class="jrButton">').html('<span class="jrIconRemove"></span>' + self.options.lang.clear_coord)
			};

			self.inputs = {
				'lat' : $('<input type="hidden" name="data[Field][Listing][' + self.options.fields.lat + ']" >'),
				'lon' : $('<input type="hidden" name="data[Field][Listing][' + self.options.fields.lon + ']" >')
			};
		},

		_init: function() {

			var self = this,
				buttons = self.buttons,
				inputs = self.inputs;

			buttons.map.on('click',function(e) {

					e.preventDefault();

					self.open();
				});

			buttons.clearVal.on('click',function(e) {

				e.preventDefault();

				if (self.page.find('.' + self.options.fields.lat).length) {

					inputs.lat = self.page.find('.' + self.options.fields.lat);

					inputs.lon = self.page.find('.' + self.options.fields.lon);
				}

				inputs.lat.val('');

				inputs.lon.val('');
			});

			self.page.find('.' + self.options.fields.mapit).after(buttons.map, '&nbsp;', buttons.clearVal);
		},

		open: function() {

			var self = this,
				inputs = self.inputs;

			// Add coordinate fields if not already in the form
			if (self.page.find('.' + self.options.fields.lat).length === 0) {

				self.page.append(inputs.lat, inputs.lon);
			}
			else {

				inputs.lat = self.page.find('.' + self.options.fields.lat);

				inputs.lon = self.page.find('.' + self.options.fields.lon);
			}

			var coordinates = [inputs.lat.val(), inputs.lon.val()],
				address = self.getAddress(self.page);

			if(address === '') return;

			var settings = {
				'width': self.options.width,
				'height': 'auto',
				'open': function() {

					var dialog = $(this);

					if (inputs.lat.val() === '' && inputs.lat.val() === '') {

						if(address !== '') {

							// Run geocoder
							var geocoder = new google.maps.Geocoder();

							geocoder.geocode({address: address}, function (results, status) {

								if (google.maps.GeocoderStatus.OK != status) {

									$.jrAlert(self.options.lang.geocode_fail);
								}
								else {

									var point = results[0].geometry.location;

									center = point.toUrlValue(); // round the lat/lng values to 6 decimal places by default

									coordinates = center.split(',');

									inputs.lat.val(coordinates[0]);

									inputs.lon.val(coordinates[1]);

									self._createMap(self.page, dialog, coordinates);
								}
							});
						}
					}
					else {

						self._createMap(self.page, dialog, coordinates);
					}
				}
			};

			var html = '<div class="jrInfo">' + self.options.lang.drag_marker + '</div><div class="jr-geomaps-map jrMapItCanvas jrMapLoading"></div>';

			$.jrDialog(html, settings);
		},

		getAddress: function (page) {

			var self = this,
				type,
				address = [],
				inputVal = '';

			$.each(self.options.fields.address, function (index, item) {

				var field = page.find('.' + item),
					option;

				type = field.prop('type');

				if (type == 'select-one' || type == 'select-multiple') {

					option = field.find('option:selected').first();

					inputVal = option.val() !== '' ? option.text() : null;
				}
				else {

					inputVal = field.val();
				}

				if (null !== inputVal && undefined !== inputVal && inputVal !== '' && inputVal != "undefined") {

					address.push(inputVal);
				}
			});

			if (page.find('.' + self.options.fields.address.country).length === 0 && undefined !== self.options.fields.default_country && self.options.fields.default_country !== '') {

				address.push(self.options.fields.default_country);
			}

			return address.join(' ');
		},

		_createMap: function (page, dialog, coordinates) {

			var self = this,
				inputs = self.inputs;

			var options = {
				zoom: self.options.zoom,
				center: new google.maps.LatLng(coordinates[0], coordinates[1]),
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};

			var mapCanvas = dialog.find('.jr-geomaps-map');

			map = new google.maps.Map(mapCanvas[0], options);

			marker = new google.maps.Marker({
				position: new google.maps.LatLng(coordinates[0], coordinates[1]),
				draggable: true
			});

			google.maps.event.addListener(map, "idle", function () {

				mapCanvas.removeClass('jrMapLoading');
			});

			google.maps.event.addListener(marker, "dragend", function () {

				inputs.lat.val(marker.getPosition().lat());

				inputs.lon.val(marker.getPosition().lng());
			});

			marker.setMap(map);
		}
	});

})(jQuery);

/**
 * Adds the proximity autocomplete feature to the search forms and injects the coordinates as hidden inputs
 * */
(function($) {

	$.widget("jreviews.jrProximitySearch", {

		options: {
			eventTrigger: 'jrBeforeSearch',
			fields: {},
			autocomplete: false
		},
		proximityField: null,
		page: null,
		ac_option_selected: false,

		_create: function(options) {

			var self = this;

			$.extend(self.options, options);

			self.page = self.element;

			self.searchButton = self.page.find('.jr-search');
		},

		_init:function() {

			var self = this;

			var o = {
				types: ['geocode']
			};

			if(jreviews.geomaps.autocomplete_country !== '') {
				o.componentRestrictions = {country: jreviews.geomaps.autocomplete_country};
			}

			if(self.options.autocomplete === true) {

				self.proximityField = self.page.find('.' + self.options.fields.proximity);

				// Initialize autocomplete only for text inputs
				if(self.proximityField.prop('type') == 'text') {

					var	autocomplete = new google.maps.places.Autocomplete(self.proximityField[0], o);

					google.maps.event.addListener(autocomplete, 'place_changed', function() {

						var place = autocomplete.getPlace(),
							lat = place.geometry.location.lat(),
							lon = place.geometry.location.lng();

						self._addCoordinateInputs(lat,lon);

						self.ac_option_selected = true;
					});

					// Remove placeholder value in IE
					var test = document.createElement('input');

					self.proximityField
						.attr('placeholder', 'placeholder' in test ? jreviews.__t('GEOMAPS_ENTER_LOCATION') : '')
						.keydown(function (e) {
							if (e.keyCode == 13) {
								e.preventDefault();
								return false;
							}});
				}

			}

			var eventTrigger = self.proximityField.prop('type') == 'text' ? 'blur' : 'change';

			self.page.on(eventTrigger,'.' + self.options.fields.proximity, function() {

				setTimeout(function() {

					if(!self.ac_option_selected) self._mapSearchAddress();

					self.ac_option_selected = false;

				}, 250);

			});

			self.proximityField.on('focus',function () {

				self.ac_option_selected = false;

				self.searchButton.attr('disabled','disabled');
			});
		},

		_getInputVal: function(name) {

			var self = this,
				input = self.page.find('.'+name),
				type = input.prop('type'),
				option;

			if(type == 'select-one') {

				option = input.find('option:selected');

				inputVal = input.val() !== '' ? option.text() : null;
			}
			else {

				inputVal = input.val();
			}

			return inputVal;
		},

		_mapSearchAddress: function () {

			var self = this,
				address = [],
				inputVal = '';

			if (undefined !== self.options.fields.proximity) {

				inputVal = self._getInputVal(self.options.fields.proximity);

				if (null !== inputVal && undefined !== inputVal && inputVal !== '' && inputVal != "undefined") {

					address.push(inputVal);
				}
				else {

					self._clearCoordinates();
				}
			}

			/* Add country bias */
			if (address.length > 0) {

				if (self.page.find('.' + self.options.fields.address.country).length === 0) {

					if (undefined !== self.options.fields.default_country && self.options.fields.default_country !== '') {

						address.push(self.options.fields.default_country);
					}
				}
				else {

					inputVal = self._getInputVal(self.options.fields.address.country);

					if (null !== inputVal && undefined !== inputVal && inputVal !== '' && inputVal != "undefined") {

						address.push(inputVal);
					}
				}

				address = address.join(' ');

				var geocoder = new google.maps.Geocoder();

				geocoder.geocode({'address': address}, function (results, status) {

					if (google.maps.GeocoderStatus.OK == status) {

						var point = results[0].geometry.location;

						self._addCoordinateInputs(point.lat(), point.lng());
					}

				});
			}
			else {

				self.searchButton.removeAttr('disabled');
			}
		},

		_addCoordinateInputs: function(lat, lon) {

			var self = this,
				latid = self.options.fields.lat,
				lonid = self.options.fields.lon,
				lat_selector = 'input[name="data[Field][Listing]['+latid+']"]',
				lon_selector = 'input[name="data[Field][Listing]['+lonid+']"]',
				latInput = $('<input type="hidden" name="data[Field][Listing][' + latid + ']" />'),
				lonInput = $('<input type="hidden" name="data[Field][Listing][' + lonid + ']" />');

			// If coordinate inputs not already in the form, add them
			if(self.page.find(lat_selector).length === 0) {

				latInput.appendTo(self.page);

				lonInput.appendTo(self.page);
			}
			else {

				latInput = self.page.find(lat_selector);

				lonInput = self.page.find(lon_selector);
			}

			latInput.val(lat);

			lonInput.val(lon);

			self.searchButton.removeAttr('disabled');
		},

		_clearCoordinates: function() {

			var self = this,
				latid = self.options.fields.lat,
				lonid = self.options.fields.lon,
				lat_selector = 'input[name="data[Field][Listing]['+latid+']"]',
				lon_selector = 'input[name="data[Field][Listing]['+lonid+']"]';

			self.page.find(lat_selector+','+lon_selector).val('');
		}

	});

})(jQuery);

/**
 * JReviews implemenation of the Google Maps API V3
 */
(function($) {

	$.widget("jreviews.jrMaps",{

		options: {
			zoom: 15,
			container_class: 'jr-page',
			external_trigger_id: 'jr-listing-title-',
			resize_class: 'jr-map-resize',
			streetview_canvas: 'jr-streetview-canvas',
			directions_canvas: 'jr-directions-canvas',
			scroller: false,
			cluster: {gridSize: 50, maxZoom: 15},
			iwMaxWidth: 250
		},
		init: true,
		page: null,
		referrer: null,
		tabs: null,
		map: null,
		kmlLayers: {},
		d: null, // directionsService
		directions: null,
		directionsCanvas: null,
		streetview: null,
		sv: null, // streetviewService
		streetviewCanvas: null,
		streetviewStatus: false,
		markerClusterer: null,
		bounds: null,
		center: null,
		infowindow: null,
		default_width: null,
		_gicons: {},
		_markers: {},
		_mapData: {},
		_payload: {},
		_icons: {
			'default' : {
				'type': 'custom',
				'url': 'http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|FE766A|12|_|',
				'size': [23, 41]
			},
			'default_hover' : {
				'type': 'custom',
				'url': 'http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|FDFF0F|12|_|',
				'size': [23, 41]
			},
			'default_featured' : {
				'type': 'custom',
				'url': 'http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|5F8AFF|12|_|',
				'size': [23, 41]
			},
			'numbered' : {
				'type': 'numbered',
				'url': 'http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|FE766A|12|_|{index}',
				'size': [23, 41]
			},
			'numbered_hover' : {
				'type': 'numbered',
				'url': 'http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|FDFF0F|12|_|{index}',
				'size': [23, 41]
			},
			'numbered_featured' : {
				'type': 'numbered',
				'url': 'http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|5F8AFF|12|_|{index}',
				'size': [23, 41]
			},
			'numbered_featured_hover' : {
				'type': 'numbered',
				'url': 'http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|FDFF0F|12|_|{index}',
				'size': [23, 41]
			}
		},

		_create: function(options) {

			var self = this,
				el = self.element;

			self._markers = {};

			self._gicons = {};

			self._mapData = {};

			self._payload = {};

			$.extend(self.options, options);

			self.referrer = el.data('referrer');

			self.page = el.closest('.'+self.options.container_class);

			// Add custom icons to array
			for (var i in self.options.icons) {

				self._icons[i] = self.options.icons[i];
			}

			self.streetviewCanvas = self.page.find('.'+self.options.streetview_canvas);

			self.directionsCanvas = self.page.find('.'+self.options.directions_canvas);

			self.defaultWidth = self.element.width();
		},

		_init: function() {

			var self = this,
				el = self.element;

			var tab = self.element.closest('.jr-tabs');

			// If the map is inside a jQuery tab we delay the initialization until the tab is shown
			if(tab.length && self.tabs === null) {

				self.tabs = true;

				tab.bind('tabsshow', function(event, ui) {

					if (ui.panel.id == self.element.closest('.ui-tabs-panel').attr('id') /* tab id */) {

						if(el.data('initialized') === undefined) {

							self._init();

							el.data('initialized',true);
						}
					}
				});

				return;
			}

			self.bounds = new google.maps.LatLngBounds();

			self.map = new google.maps.Map(el[0], {zoom: self.options.zoom});

			// KML support
			var attachments = self.page.find('.jr-attachments [data-file-type="kml"]');

			if(attachments.length) {

				attachments.each(function(i,row) {

					var kml_path = $(this).data('file-path');

					self.kmlLayers[i] = new google.maps.KmlLayer({
						url: kml_path
					});

					self.kmlLayers[i].setMap(self.map);
				});
			}

			self._setMapOptions();

			if(self.options.count > 0) {

				self._addMarkers();
			}

			if(self.options.count == 1) {

				self.map.setCenter(self.getCenter());
			}
			else if(self.options.center) {

				var latlng = new google.maps.LatLng(self.options.center.lat, self.options.center.lon);

				self.map.setCenter(latlng);
			}
			else {

				self.map.fitBounds(self.bounds);
			}

			// Listeners
			google.maps.event.addListener(this.map, "idle", function () {

				el.removeClass('jrMapLoading').css({'overflow-x':'visible','overflow-y':'visible'}); // for callout and custom info windows
			});

			google.maps.event.addListener(self.map, "dragstart", function () {

				if(self.options.infowindow != 'google') self._closeInfowindow();
			});

			google.maps.event.addListener(self.map, "dblclick", function () {

				if(self.options.infowindow != 'google') self._closeInfowindow();
			});

			google.maps.event.addListener(self.map, "zoom_changed", function () {

				if(self.options.infowindow != 'google') self._closeInfowindow();
			});

			google.maps.event.addListener(self.map, "visible_changed", function () {

				if(self.options.infowindow != 'google') self._closeInfowindow();
			});

			google.maps.event.addListener(self.map, "resize", function () {

				if(self.options.infowindow != 'google') self._closeInfowindow();
			});

			// Extended features
			self._createAddressBar();

			self._bindListingTitles();

			self._bindMapResize();

			self._bindMapScroller();

			self._bindStreetview(true);

			self._bindDirections();
		},

		_bindListingTitles: function() {

			var self = this;

			self.page
				.on('mouseover','[id^="'+self.options.external_trigger_id+'"]',function() {

					var id = this.id.replace(self.options.external_trigger_id,''),
						marker = self._markers[id];

					if(self.options.infowindow != 'google') self._closeInfowindow();

					if(marker) {

						self.map.setCenter(marker.payload.latlng);

						self._toggleIcon(marker,'_hover');

						self._loadInfoWindow(marker);
					}
				})
				.on('mouseout','[id^="'+self.options.external_trigger_id+'"]', function() {

					var id = this.id.replace(self.options.external_trigger_id,''),
						marker = self._markers[id];

					if(marker) {

						self._toggleIcon(marker,'');
					}
				});
		},

		_bindMapResize: function() {

			var self = this;

			// Map resize
			self.page.on('click','.'+self.options.resize_class+' a.jr-map-large, .'+self.options.resize_class + ' a.jr-map-small',function() {

				var newWidth,
					newHeight;
					mapLinks = self.page.find('.'+self.options.resize_class + ' a');

				mapLinks.toggle();

				var isSmall = self.element.width() <= self.defaultWidth;

				newWidth = isSmall ? 600 : self.defaultWidth;

				newHeight = isSmall ? 500 : self.defaultWidth;

				self.element.closest('#jr-map-column').animate({width: newWidth}, "fast");

				self.element.siblings().animate({width: newWidth}, "fast");

				self.element.animate({width: newWidth, height: newHeight}, "fast", function () {

					self.resizeMap();
				});
			});
		},

		_bindMapScroller: function() {

			var self = this;

			if(!self.options.scroller) return;

			var mapWrapper = self.page.find('div#jr-map-results-wrapper'),
				mapColumn = self.page.find('div#jr-map-column'),
				listingsColumn = self.page.find('div#jr-listing-column');

			// Map scroller
			mapColumn.width(self.defaultWidth);

			var currentWidth = listingsColumn.width();

			listingsColumn.width(listingsColumn.parent().width() - mapColumn.width() - 12);

			var topPosition = mapWrapper.offset().top,
				bottomPosition = listingsColumn.height()+topPosition-mapWrapper.height()-5;

			$(window)
				.scroll(function() {

					var y = $(this).scrollTop();

					if (y >= topPosition) {

						mapWrapper.addClass('fixed').removeClass('top bottom');

						var rightPosition = $(this).width() - (mapColumn.offset().left + mapWrapper.outerWidth());

						mapWrapper.css('right', rightPosition);
					}
					else {

						mapWrapper.addClass('top').removeClass('fixed bottom');
					}

					if (y >= bottomPosition) {

						mapWrapper.addClass('bottom').removeClass('top fixed');
					}
				})
				.resize(function() {

					var rightPosition = $(this).width() - (mapColumn.offset().left + mapWrapper.outerWidth());

					mapWrapper.css('right', rightPosition);

					listingsColumn.width(listingsColumn.parent().width() - mapColumn.width() - 12);
				});
		},

		_bindStreetview: function() {

			var self = this;

			if(self.map.get('streetViewControl') === false) return;

			if(self.streetviewCanvas.length) {

				self.streetview = self.streetview || new google.maps.StreetViewPanorama(self.streetviewCanvas[0]);

				// Display streetview when streetview icon is dropped on the map
				google.maps.event.addListener(self.streetview, 'visible_changed', function() {

					if (self.streetview.getVisible() && !self.init) {

						self.element.css('width','49%');

						self.resizeMap();

						self.streetviewCanvas.show();
					}

					self.init = false;
				});

				self.map.setStreetView(self.streetview);

				self._checkStreetviewStatus(self.map.getCenter());

				// Streetview "person" in map follows the panorama position
				google.maps.event.addListener(self.streetview, 'position_changed', function() {

					self.map.setCenter(self.streetview.getPosition());
				});

				// Enables streetview changes on map clicks, not just marker clicks
				google.maps.event.addListener(self.map, 'click', function (e) {

					self._checkStreetviewStatus(e.latLng);
				});

			}
			else {

				var panorama = self.map.getStreetView();

				google.maps.event.addListener(panorama, 'visible_changed', function() {

					if(self.options.infowindow != 'google') self._closeInfowindow();

					self.element.css('overflow','visible');
				});
			}
		},

		_checkStreetviewStatus: function(latLng) {

			var self = this;

			if(self.streetview === null) return;

			self.sv = self.sv || new google.maps.StreetViewService();

			self.sv.getPanoramaByLocation(latLng, 50, function(data, status) {

				if(status == google.maps.StreetViewStatus.OK) {

					self.element.css('width','49%');

					self.resizeMap();

					self.streetview.setPosition(data.location.latLng);

					self.streetviewCanvas.show();
				}
			});
		},

		_bindDirections: function() {

			var self = this,
				directionsAddress = self.page.find('.jr-directions-address'),
				validation = directionsAddress.find('.jr-validation');

			if(!directionsAddress.length || !self.options.directions) return;

			var origin = directionsAddress.find('.jr-directions-origin'),
				destination = directionsAddress.find('.jr-directions-destination');

			directionsAddress.show().on('click','.jr-directions-submit',function(e) {

				var dest;

				if(destination.val() == self.directionsCanvas.data('destAddr')) {
					dest = self.directionsCanvas.data('destCoord');
				}
				else {

					dest = destination.val();
				}

				e.preventDefault();

				self.directionsCanvas.hide();

				self.directions = self.directions || new google.maps.DirectionsRenderer({map: self.map, panel: self.directionsCanvas[0]});

				self.d = self.d || new google.maps.DirectionsService();

				var request = {
					origin : origin.val(),
					destination : dest,
					travelMode : google.maps.TravelMode[directionsAddress.find('.jr-directions-travelmode').val()],
					region : self.directionsCanvas.data('locale') || 'en'
				// ,unitSystem: google.maps.UnitSystem.METRIC | IMPERIAL
				};

				self.d.route(request, function(result, status) {

					if(status == google.maps.DirectionsStatus.OK) {

						self.directionsCanvas.removeClass('jrError').fadeIn();

						self.directions.setDirections(result);
					}
					else {

						self.directionsCanvas.addClass('jrError').html(jreviews.__t('PROCESS_REQUEST_ERROR')).fadeIn();
					}
				});
			});

			directionsAddress.on('click','.jr-directions-swap',function() {

				var origin_val = origin.val();

				origin.val(destination.val());

				destination.val(origin_val);
			});


		},
		_setMapOptions: function() {

			var self = this;

			self.options.mapUI.zoom = self.options.mapUI.zoom === 0 ? self.options.zoom : self.options.mapUI.zoom;

			self.map.setOptions(self.options.mapUI);
		},

		_addMarkers: function() {

			var self = this,
				_markersCluster = [];

			$.each(self.options.payload,function(i,row) {

				var marker = self._createMarker(row);

				self._markers[row.id] = marker;
				if(!self.options.clustering) {

					marker.setMap(self.map);
				}
				else {

					_markersCluster.push(marker);
				}
			});

			if(self.options.clustering) {

				self.markerClusterer = new MarkerClusterer(self.map, _markersCluster, {maxZoom: self.options.cluster.maxZoom, gridSize: self.options.cluster.gridSize});
			}
		},

		_createMarker: function(row) {

			var self = this,
				latlng = new google.maps.LatLng(row.lat, row.lon),
				icon = self._createIcon(row);

			var marker = new google.maps.Marker({
				position: latlng,
				icon: icon,
				title: row.title
			});

			// Bring featured markers to the top
			if(row.featured == 1) marker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);

			self.bounds.extend(latlng);

			if(icon.data && icon.data.shadow) marker.setShadow(icon.data.shadow);

			marker.icon_name = row.icon;

			marker.id = row.id;

			marker.payload = row;

			marker.payload.latlng = latlng;

			google.maps.event.addListener(marker, "click", function (e) {

				self._loadInfoWindow(marker,e.latLng);

				self._checkStreetviewStatus(marker.position);
			});

			google.maps.event.addListener(marker, "mouseover", function () {

				self._toggleIcon(marker, '_hover');

				return false;
			});

			google.maps.event.addListener(marker, "mouseout", function () {

				self._toggleIcon(marker, '');
			});

			return marker;
		},

		_toggleIcon: function(marker, status) {

			var self = this,
				name = marker.icon_name;

			if(marker.payload.featured === 1 && name + '_featured' + status in self._icons) {

				name = name + '_featured';
			}

			if (undefined !== marker && name + status in self._icons) {

				marker.setIcon(self._icons[name + status].url.replace('{index}', marker.payload.index));
			}
		},

		_createIcon: function(row) {

			var self = this,
				name = row.icon,
				index = row.index,
				icon;

			if (self._gicons.length && undefined !== self._gicons[name] && name !== 'numbered') {

				return self._gicons[name];
			}

			if (undefined === self._icons[name]) name = 'default';

			if(row.featured === 1 && name + '_featured' in self._icons) {

				name = name + '_featured';
			}

			switch (self._icons[name].type) {

				case 'custom':

					icon = self._createCustomIcon(self._icons[name]);

					break;

				case 'numbered':

				case 'numbered_featured':

					icon = self._createNumberedIcon(self._icons[name], index);

					break;

				case 'default':

					icon = self._createDefaultIcon(self._icons[name]);

					break;

				default:

					icon = self._createDefaultIcon(self._icons[name]);

					break;
			}

			self._gicons[name] = icon;

			return icon;
		},

		_createCustomIcon: function(iconData) {

			var icon = new google.maps.MarkerImage(iconData.url);

			icon.size = new google.maps.Size(iconData.size[0], iconData.size[1]);

			return icon;
		},

		_createDefaultIcon: function(iconData) {

			var defaultIcon = new google.maps.MarkerImage(iconData.url);

			defaultIcon.size = new google.maps.Size(23, 41);

			defaultIcon.data = {shadow: new google.maps.MarkerImage("http://www.google.com/mapfiles/shadow50.png", new google.maps.Size(37, 34), null, new google.maps.Point(9, 37))};

			var icon = new google.maps.MarkerImage(iconData.url, defaultIcon.size);

			icon.data = defaultIcon.data;

			return icon;

		},

		_createNumberedIcon: function (iconData, index) {

			var defaultIcon = new google.maps.MarkerImage(iconData.url);

			defaultIcon.size = new google.maps.Size(23, 41);

			defaultIcon.data = {shadow: new google.maps.MarkerImage("http://www.google.com/mapfiles/shadow50.png", new google.maps.Size(37, 34), null, new google.maps.Point(9, 37))};

			var icon;

			if (index !== '') {

				icon = new google.maps.MarkerImage(iconData.url.replace('{index}', (0 + index)), defaultIcon.size);

			}
			else {

				icon = new google.maps.MarkerImage(iconData.url.replace('{index}', ''), defaultIcon.size);
			}

			icon.data = defaultIcon.data;

			return icon;
		},

		_createAddressBar: function() {

			var self = this,
				mapGeocoder = new google.maps.Geocoder(),
				searchBar = $('<div class="jrRoundedPanel jrMapAddressBar">' +
								'<input type="text" size="50" name="jr-map-address-search" placeholder="'+jreviews.__t('GEOMAPS_ENTER_LOCATION')+'" />' +
								'<button class="jrButton jrSmall"><span class="jrIconSearch"></span></button>' +
								'</div>');

			if(!self.options.search) return;

			self.map.controls[google.maps.ControlPosition.TOP_LEFT].push(searchBar[0]);

			searchBar.css({'z-index':1,'-moz-user-select':''}).on('keyup','input',function(e) {

				if(e.keyCode == 13) {
					searchBar.find('.jrButton').trigger('click');
				}
			});

			searchBar.on('click','.jrButton',function(e) {

				e.preventDefault();

				var address = searchBar.find('input').val();

				if(address === '') return false;

				mapGeocoder.geocode({address: address}, function(results, status) {

					if (google.maps.GeocoderStatus.OK == status) {

						self.map.setZoom(11);

						self.map.setCenter(results[0].geometry.location);
					}
				});
			});
		},

		_loadInfoWindow: function(marker, latlng) {

			var self = this,
				infowindowElement = self._renderInfoWindowContent(marker.payload);

			if(self.options.infowindow == 'google') {

				self.infowindow = self.infowindow || new google.maps.InfoWindow({

					maxWidth: self.options.iwMaxWidth
				});

				self.infowindow.setContent(infowindowElement[0]);

				self.infowindow.open(self.map, marker);
			}
			else {

				infowindowElement.css('display','none');

				self._closeInfowindow();

				setTimeout(function() {

					self._positionInfowindow(infowindowElement, marker, latlng);

				},10);
			}

			marker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);

		},

		_renderInfoWindowContent: function(data) {

			var self = this,
				roundDecimals = 1,
				template = $(self.options.iwTheme);

			if (self.options.mapUI.title.trim === true && self.options.mapUI.title.trimchars > 0) {

				data.title = data.title.substring(0, self.options.mapUI.title.trimchars);
			}

			template.find('.jr-map-title').html(data.title);

			template.find('.jr-map-title').attr('href', data.url);

			if (false !== data.image) {

				template.find('.jr-map-image').html('<img src="' + data.image + '" />');
			}

			// Process ratings
			var user_rating = template.find('.jrOverallUser');

			var editor_rating = template.find('.jrOverallEditor');

			var rating_div = template.find('.jrOverallRatings');

			if(data.rating_scale === undefined) {

				rating_div.hide();
			}
			else {

				rating_div.show();

				if(data.user_rating === undefined)
				{
					user_rating.hide();
				}
				else {
					user_rating.show();

					template.find('.jr-map-user-rating').css('width', (data.user_rating / data.rating_scale) * 100 + '%');

					template.find('.jr-map-user-rating-val').html(Math.round(0 + (data.user_rating) * Math.pow(10, roundDecimals)) / Math.pow(10, roundDecimals));

					template.find('.jr-map-user-rating-count').html(Number(0 + data.user_rating_count));
				}

				if(data.editor_rating === undefined)
				{
					editor_rating.css('display','none');
				}
				else {
					editor_rating.show();

					template.find('.jr-map-editor-rating').css('width', (data.editor_rating / data.rating_scale) * 100 + '%');

					template.find('.jr-map-editor-rating-val').html(Math.round(0 + (data.editor_rating) * Math.pow(10, roundDecimals)) / Math.pow(10, roundDecimals));
				}

			}

			for (var i in data.field) {

				template.find('.jr-map-' + i).html(data.field[i]);

			}

			// Attach close onclick event
			template.on('click','.jr-close',function() {

				self._closeInfowindow();
			});

			return template;
		},

		_positionInfowindow: function(infowindow, marker, latlng) {

			var self = this,
				mapBounds = self.map.getBounds(),
				position = latlng || marker.getPosition(),
				overlay = new google.maps.OverlayView();

			overlay.draw = function() {};

			overlay.setMap(self.map);

			// Delay required to allow enough time to get a projection
			setTimeout(function() {

				var proj = overlay.getProjection(),
					point = proj.fromLatLngToContainerPixel(position),
					x,
					y;

				if(!mapBounds.contains(position)) {

					self.map.setCenter(position);
				}

				infowindow.appendTo(self.element);

				if (infowindow.hasClass('jrMapCustom')) {

					x = point.x + parseInt(infowindow.css('left'),10) + 4;
					y = point.y + parseInt(infowindow.css('top'),10) + 15;

				} else {

					x = point.x + parseInt(infowindow.css('left'),10),
					y = point.y + parseInt(infowindow.css('top'),10);

				}

				infowindow.css({'left': x + 'px', 'top': y + 'px', 'position': 'absolute'}).show();

			}, 100);
		},

		_closeInfowindow: function() {

			this.element.find('.jrInfowindow').remove();
		},

		getMap: function() {

			return this.map;
		},

		getMarkers: function() {

			return this._markers;
		},

		getCenter: function() {

			var self = this;

			var center_lat = (self.bounds.getNorthEast().lat() + self.bounds.getSouthWest().lat()) / 2.0;

			var center_lng = (self.bounds.getNorthEast().lng() + self.bounds.getSouthWest().lng()) / 2.0;

			if (self.bounds.getNorthEast().lng() < self.bounds.getSouthWest().lng()) {

				center_lng += 180;
			}

			return new google.maps.LatLng(center_lat, center_lng);
		},

		resizeMap: function() {

			google.maps.event.trigger(this.map, 'resize');
		},

		destroy: function () {

			$.Widget.prototype.destroy.call(this);
		}
	});

})(jQuery);
