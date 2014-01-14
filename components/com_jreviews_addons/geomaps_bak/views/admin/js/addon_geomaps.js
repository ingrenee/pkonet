jreviews.geomaps = jreviews.geomaps || {};

(function($) {

	var map,
		marker,
		infoWindow,
		__api_loading = false;

	jreviews.geomaps.apiLoadedCallback = null;

	jreviews.geomaps.api_callback =  function() {

		if(typeof jreviews.geomaps.apiLoadedCallback == "function") {

			jreviews.geomaps.apiLoadedCallback();
		}

		jreviews.geomaps.apiLoadedCallback = null;
	};

	jreviews.geomaps.loadAPI = function(callback) {

		if(typeof google == 'undefined') {

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

	jreviews.geomaps.init = function() {

		var page = jrPage;

		page.on('click','#jr-geocode-all',function(e) {

			e.preventDefault();

			var el = $(this);

			var api_loaded = jreviews.geomaps.loadAPI(function() {

				jreviews.geomaps.geocodeAll(el);
			});
		});

		page.on('click','#jr-geocode-debug-hide',function(e){

			e.preventDefault();

			page.find('#jr-geocode-debug').slideUp();
		});

		page.on('click','.jr-geocode-pin',function(e){

			e.preventDefault();

			var el = $(this);

			var api_loaded = jreviews.geomaps.loadAPI(function() {

				jreviews.geomaps.geocodeOne(el);
			});

		});

		// Bind field auto-suggest event
		page.on('mousemove','.jr-geomaps-config form:not(".jr-ready-field-suggest")',function() {

			jreviews.geomaps.fieldSuggest($(this));

			$(this).addClass('jr-ready-field-suggest');
		});

		page.on('click','.jr-geomaps-markers-list #jr-assign-marker',function(e){

			e.preventDefault();

			jreviews.geomaps.markerDialog($(this),page);
		});

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
	};

	// Load batch geocoder dialog
	jreviews.geomaps.geocodeAll = function(el) {

		var title = el.html();

		var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_geomaps',action:'_geocodeAll',data:{task:'all'}});

		loadingForm.done(function(html) {

			// Call dialog
			var buttons = {};

			var close = function() {

				$('body').data('Geocode.abort',1);
			};

			var dialog = $.jrDialog(html, {position:'top', title: title, close: close, buttons:buttons, width:'800px'});

			dialog.on('click','button#jr-geocode-all-start',function(e) {

				e.preventDefault();

				jreviews.geomaps.geocodeAllStart($(this));
			});

			dialog.on('click','button#jr-geocode-all-stop',function(e) {

				e.preventDefault();

				$('#statusUpdate').html('Aborting...please wait.');

				$('body').data('Geocode.abort',1);

				$('button#jr-geocode-all-start').removeAttr('disabled');
			});
		});

	};

	// Begin batch geocoding
	jreviews.geomaps.geocodeAllStart = function(el, total, offset) {

		$('body').data('Geocode.abort',0);

		var debug_info = $('input[name=debug_info]:checked').val();

		var data = {
			task:'start',
			debug_info: debug_info,
			process_increment: $('input#jr-geocode-increment').val(),
			offset: offset || 0,
			total: total || 0
		};

		var geocoding = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_geomaps',action:'_geocodeAll',data:data});

		el.attr('disabled','disabled');

		geocoding.done(function(res){

			var remaining = $('#jr-geocode-remaining'),
				success = $('#jr-geocode-successful'),
				errors = $('#jr-geocode-error');

			var success_count = Number(success.html()) + Number(res.success),
				error_count = Number(errors.html()) + Number(res.skipped) + Number(res.errors),
				remaining_count = res.total;

			remaining.html(remaining_count);

			success.html(success_count);

			errors.html(error_count);

			if(remaining_count > 0 && $('body').data('Geocode.abort') === 0) {

				if(debug_info === '1') {

					var debugDiv = $('#jr-geocode-debug'),
						debugRow = debugDiv.find('div.jr-geocode-debug-results');

					debugDiv.show();

					$.each(res.debug,function(i,val){

						var label;

						switch(val.api_response.status) {

							case 0:
								label = '<span class="jrStatusLabel jrOrange">'+jreviews.__t('GEOMAPS_ADDRESS_EMPTY')+'</span>';
							break;
							case 200:
								label = '<span class="jrStatusLabel jrGreen">OK ('+val.api_response.lat+','+val.api_response.lon+')</span>';
							break;
							case 620:
								label = '<span class="jrStatusLabel jrOrange">'+jreviews.__t('GEOMAPS_ADDRESS_SKIPPED')+'</span>';
							break;
						}

						debugRow.before('<div class="jrGrid">'+
							'<div class="jrCol1">'+val.id+'</div>'+
							'<div class="jrCol4">'+val.title+'&nbsp;</div>'+
							'<div class="jrCol4">'+val.address+'&nbsp;</div>'+
							'<div class="jrCol3">'+label+'</div>'+
							'</div>');
					});
				}

				jreviews.geomaps.geocodeAllStart(el, res.total, res.offset);
			}
		});
	};

		// Individial address geocode via pin button
	jreviews.geomaps.geocodeOne = function(el) {

		var mapItPin = el,
			listing_id = el.data('id'),
			title = el.data('title'),
			lat = el.data('lat'),
			lon = el.data('lon'),
			data = {
				task:'single',
				listing_id:listing_id
			};

		var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_geomaps',action:'_geocodePopup',data:data});

		loadingForm.done(function(html) {

			var dialogContent = $(html);

			// Call dialog
			var buttons = {};

			buttons[jreviews.__t('GEOMAPS_GEOCODE_ADDRESS')] = function() {

				jreviews.geomaps.geocodeInMap($(this));
			};

			buttons[jreviews.__t('APPLY')] = function() {

				var form = $(this).find('form'),
					buttonPane = $('.ui-dialog-buttonpane'),
					statusUpdate = $('<div class="jr-status jrSuccess jrHidden">'),
					validation = $('<div class="jr-validation jrError jrHidden">');

				buttonPane.find('.jr-validation, .jr-status').fadeOut().remove();

				var lat = form.find('.' + jreviews.geomaps.fields.lat).val(),
					lon = form.find('.' + jreviews.geomaps.fields.lon).val();

				mapItPin.data('lat', lat);

				mapItPin.data('lon', lon);

				var submittingForm = jreviews.dispatch({type:'html',form:form});

				submittingForm.done(function(res){

					if(res === '1'){

						statusUpdate.html(jreviews.__t('APPLY_SUCCESS'));

						buttonPane.prepend(statusUpdate);

						statusUpdate.fadeIn();
					}
					else {

						validation.html(jreviews.__t('PROCESS_REQUEST_ERROR'));

						buttonPane.prepend(validation);

						validation.fadeIn();
					}

				});
			};

			var open = function() {

				var dialog = $(this);

				var loadingFields = dialog.jreviewsFields({'entry_id':dialog.find('#listing_id').val(),'page_setup':true,'recallValues':true});

				loadingFields.done(function() {

					if (lat !== '' && lon !== '' && lat !== 0 && lon !== 0) {

						jreviews.geomaps.renderMapitMap(dialog, dialog, [lat, lon]);

					} else {

						jreviews.geomaps.geocodeInMap(dialog);
					}
				});
			};

			$.jrDialog(dialogContent, {title: title, open: open, buttons: buttons, width:'800px', height: '600'});

		});
	};

	jreviews.geomaps.geocodeInMap = function (dialog) {

		var address = jreviews.geomaps.getAddress(dialog);

		var geocoder = new google.maps.Geocoder();

		var validation = dialog.find('.jr-validation').hide();

		geocoder.geocode({address: address}, function (results, status) {

			if (google.maps.GeocoderStatus.OK != status) {

				var buttonPane = $('.ui-dialog-buttonpane'),
					validation = $('<div class="jr-validation jrSuccess jrHidden">');

				buttonPane.find('.jr-validation, .jr-status').fadeOut().remove();

				validation.html(jreviews.__t('GEOMAPS_CANNOT_GEOCODE'));

				buttonPane.prepend(validation);

				validation.fadeIn();
			}
			else {

				var point = results[0].geometry.location;

				var coordinates = [point.lat(), point.lng()];

				jreviews.geomaps.renderMapitMap(dialog, dialog, coordinates);

				map.setCenter(point);

				map.setZoom(15);

				marker.setPosition(point);

				var infoWindow = new google.maps.InfoWindow();

				infoWindow.setContent(address);

				infoWindow.open(map, marker);

				var lat = marker.getPosition().lat(),
					lon = marker.getPosition().lng();

				dialog.find('.' + jreviews.geomaps.fields.lat).val(lat);

				dialog.find('.' + jreviews.geomaps.fields.lon).val(lon);
			}
		});
	};

	jreviews.geomaps.renderMapitMap = function (page, dialog, coordinates) {

		var options = {
			zoom: 15,
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

			page.find('.' + jreviews.geomaps.fields.lat).val(marker.getPosition().lat());

			page.find('.' + jreviews.geomaps.fields.lon).val(marker.getPosition().lng());

		});

		marker.setMap(map);
	};

	jreviews.geomaps.getAddress = function (page) {

		var type,
			address = [],
			inputVal = '',
			default_country = jreviews.geomaps.fields.default_country;

		$.each(jreviews.geomaps.fields.address, function (index, item) {

			var field = page.find('.' + item);

			type = field.prop('type');

			if (type == 'select-one') {

				var option = page.find('.' + item + ' option:selected');

				inputVal = option.val() !== '' ? option.text() : null;
			}
			else {

				inputVal = page.find('.' + item).val();
			}

			if (null !== inputVal && undefined !== inputVal && inputVal !== '' && inputVal != "undefined") {

				address.push(inputVal);
			}
		});

		if (page.find('.' + jreviews.geomaps.fields.address.country).length === 0 && undefined !== default_country && default_country !== '') {

			address.push(default_country);
		}

		return address.join(' ');
	};

	jreviews.geomaps.fieldSuggest = function(form){

		var suggestFields = form.find('input.jr-field-suggest'),
			disable_blur = false,
			field_types = '';

		suggestFields
			.after('<span>')
			.focus('focus',function() {
				$(this).next('span').html('');
			})
			.on('blur',function() {

				var el = $(this);

				if(!disable_blur) {

					var data = {field:el.val()};

					if(el.val() !== '') {

						var submittingAction = jreviews.dispatch({method:'get',type:'text',controller:'admin/admin_geomaps',action:'_validateField',data:data});

						submittingAction.done(function(res){

							if(res == 1) {

								el.next('span').html('<span class="jrIconYes"></span>');
							}
							else {

								el.val('').next('span').html('<span class="jrStatusLabel jrRed">'+jreviews.__t('GEOMAPS_INVALID_FIELD')+'</span>');
							}
						});
					}
					else {
						el.next('span').attr('class','');
					}
				}
			})
			.autocomplete({
				minLength: 1,
				open: function(event, ui) {
					disable_blur = true;
				},
				close: function(event, ui) {
					disable_blur = false;
					$(this).focus();
				},
				create: function(event, ui) {

					field_types = $(this).data('field-types');
				},
				source: function( request, response ) {

					if(form.data('cache.'+request.term)) {

						response(form.data('cache.'+request.term));
					}
					else {

						data = {limit: 10,value: request.term};

						data.field_types = field_types;

						var submittingAction = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_geomaps',action:'_fieldList',data:data});

						submittingAction.done(function(res){

							form.data('cache.'+request.term, res);

							response(res);
						});
					}
				},
				select: function( event, ui )  {

					if(ui.item.value !== '') {

						$(this).val(ui.item.value);
					}
				}
			});
	};

	jreviews.geomaps.markerDialog = function(el,page) {

			var title = el.html(),
				lang = el.data('lang'),
				marker_field = page.find('input#jr-marker-field'),
				marker_cat = page.find('input#jr-marker-cat'),
				marker_src = page.find('input#jr-marker-src');

			if(page.find("input.jr-row-cb:checked").length === 0) {

				$.jrAlert(lang.select_validation);

				return false;
			}

			var template = $($.trim($('script#jr-marker-template').html()));

			// Add marker icon selection event
			var markers = template.find('div.jr-marker-icon');

			markers.on('click','img',function() {

				var el = $(this);

				markers.find('img').removeClass('jrSelected');

				$(this).addClass('jrSelected');
			});

			// Add field autosuggest event
			jreviews.geomaps.fieldSuggest(template);

			// Call dialog
			var buttons = {};

			buttons[jreviews.__t('SAVE')] = function() {

				var dialog = $(this),
					selectedMarker = markers.find('img.jrSelected').first(),
					data = {'data[marker_icon]':[],'data[cat_ids]':[]},
					field,
					src,
					row;

				var checkedCats = page.find('input.jr-row-cb:checked'),
					checkedRows = checkedCats.closest('div.jr-layout-outer');

				checkedCats.each(function(){
					data['data[cat_ids]'].push($(this).val());
				});

				field = data['data[marker_icon][field]'] = dialog.find('input.jr-field-suggest').val();

				data['data[marker_icon][cat]'] = selectedMarker.data('filename') || '';

				src = selectedMarker.attr('src');

				var submittingForm = jreviews.dispatch({method:'post',controller:'admin/admin_geomaps',action:'_saveMarkers',data:data});

				submittingForm.done(function(res) {

					dialog.dialog('close');

					if(src) {

						checkedRows.find('span.jr-marker').html('<img src="' + src + '" />');
					}

					checkedRows.find('span.jr-field').html(field);

					checkedCats.removeAttr('checked');

					jreviews.tools.flashRow(checkedRows);
				});
			};

			buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

			dialog = $.jrDialog(template, {title: title, buttons:buttons, width:'800px'});

			var buttonPane = $('.ui-dialog-buttonpane');

			buttonPane.find('button:contains('+jreviews.__t('SAVE')+')').addClass('jrButton jrGreen').prepend('<span class="jrIconSave"></span>');

		buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');
	};

	jreviews.addOnload('geomaps-ini', jreviews.geomaps.init);

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

				var field = page.find('.' + item);

				type = field.prop('type');

				if (type == 'select-one') {

					var option = field.find('option:selected');

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
