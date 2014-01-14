/**
* GeoMaps Addon for JReviews
* Copyright (C) 2010-2012 ClickFWD LLC
* This is not free software, do not distribute it.
* For licencing information visit http://www.reviewsforjoomla.com
* or contact sales@reviewsforjoomla.com
**/(function(e,t){var n=e("div.jr-page"),r,i,s,o,u,a,f=!1;jreviews.geomaps=jreviews.geomaps||{};jreviews.geomaps.apiLoadedCallback=null;jreviews.geomaps.api_callback=function(){typeof jreviews.geomaps.apiLoadedCallback=="function"&&jreviews.geomaps.apiLoadedCallback();jreviews.geomaps.apiLoadedCallback=null};jreviews.geomaps.loadAPI=function(t){if(typeof google=="undefined"||typeof google.maps=="undefined"){if(f){setTimeout(function(){jreviews.geomaps.loadAPI(t)},50);return!1}f=!0;jreviews.geomaps.apiLoadedCallback=t;e.getScript(jreviews.geomaps.google_api_url+"&callback=jreviews.geomaps.api_callback");return!1}typeof t=="function"&&t();return!0};jreviews.geomaps.startup=function(){n=n.length>0?n:e("div.jr-page");r=e(".jr-map-canvas")||[];i=n.find("form.jr-form-adv-search-module")||[];s=n.find("#jr-form-adv-search")||[];u=jreviews.geomaps.fields.proximity!==""&&i.length?i.find("."+jreviews.geomaps.fields.proximity):[];if(i.length){var t={},o=jreviews.geomaps.fields.lat,a=jreviews.geomaps.fields.lon;window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(e,n,r){t[n]=r});(!(o in t)||!(a in t))&&window.location.href.replace(/([^\/:]+):([^\/]*)/gi,function(e,n,r){t[n]=r});if(o in t&&a in t){var f=t[o],l=t[a],c=i.find('[name="data[Field][Listing]['+o+']"]'),h=i.find('[name="data[Field][Listing]['+a+']"]');!c.length&&!h.length&&i.append('<input type="hidden" name="data[Field][Listing]['+o+']" value="'+f+'" />').append('<input type="hidden" name="data[Field][Listing]['+a+']" value="'+l+'" />')}}jreviews.geomaps.fields.mapit&&n.on("jrListingFieldsLoaded","#jr-form-listing",function(){var t=e(this),n=t.find("."+jreviews.geomaps.fields.mapit);n.length&&jreviews.geomaps.loadAPI(function(){t.jrMapIt({fields:jreviews.geomaps.fields})})});(r.length>0||u.length>0||s.length>0)&&jreviews.geomaps.loadAPI(jreviews.geomaps.init)};jreviews.geomaps.init=function(){r.each(function(){var t=e(this),n=t.data("referrer");n in jreviews.geomaps.mapData&&t.jrMaps(jreviews.geomaps.mapData[n])});if(jreviews.geomaps.fields.proximity){if(u&&typeof google!="undefined"){var t=jreviews.geomaps.autocomplete&&typeof google.maps.places!="undefined";i.jrProximitySearch({fields:jreviews.geomaps.fields,autocomplete:t})}n.on("jrSearchFormLoaded","#jr-form-adv-search",function(){var t=e(this),n=t.find("."+jreviews.geomaps.fields.proximity);n.length&&jreviews.geomaps.loadAPI(function(){var e=jreviews.geomaps.autocomplete&&typeof google.maps.places!="undefined";t.jrProximitySearch({fields:jreviews.geomaps.fields,autocomplete:e})})})}};jreviews.addOnload("geomaps-startup",jreviews.geomaps.startup)})(jQuery);(function(e){e.widget("jreviews.jrMapIt",{options:{width:"700px",zoom:15,lang:{mapit:jreviews.__t("GEOMAPS_MAPIT"),clear_coord:jreviews.__t("GEOMAPS_CLEAR_COORDINATES"),geocode_fail:jreviews.__t("GEOMAPS_CANNOT_GEOCODE"),drag_marker:jreviews.__t("GEOMAPS_DRAG_MARKER")},fields:{}},page:null,buttons:{},inputs:{},_create:function(t){var n=this;n.page=n.element;e.extend(n.options,t);n.buttons={map:e('<button class="jrButton">').html('<span class="jrIconPin"></span>'+n.options.lang.mapit),clearVal:e('<button class="jrButton">').html('<span class="jrIconRemove"></span>'+n.options.lang.clear_coord)};n.inputs={lat:e('<input type="hidden" name="data[Field][Listing]['+n.options.fields.lat+']" >'),lon:e('<input type="hidden" name="data[Field][Listing]['+n.options.fields.lon+']" >')}},_init:function(){var e=this,t=e.buttons,n=e.inputs;t.map.on("click",function(t){t.preventDefault();e.open()});t.clearVal.on("click",function(t){t.preventDefault();if(e.page.find("."+e.options.fields.lat).length){n.lat=e.page.find("."+e.options.fields.lat);n.lon=e.page.find("."+e.options.fields.lon)}n.lat.val("");n.lon.val("")});e.page.find("."+e.options.fields.mapit).after(t.map,"&nbsp;",t.clearVal)},open:function(){var t=this,n=t.inputs;if(t.page.find("."+t.options.fields.lat).length===0)t.page.append(n.lat,n.lon);else{n.lat=t.page.find("."+t.options.fields.lat);n.lon=t.page.find("."+t.options.fields.lon)}var r=[n.lat.val(),n.lon.val()],i=t.getAddress(t.page);if(i==="")return;var s={width:t.options.width,height:"auto",open:function(){var s=e(this);if(n.lat.val()===""&&n.lat.val()===""){if(i!==""){var o=new google.maps.Geocoder;o.geocode({address:i},function(i,o){if(google.maps.GeocoderStatus.OK!=o)e.jrAlert(t.options.lang.geocode_fail);else{var u=i[0].geometry.location;center=u.toUrlValue();r=center.split(",");n.lat.val(r[0]);n.lon.val(r[1]);t._createMap(t.page,s,r)}})}}else t._createMap(t.page,s,r)}},o='<div class="jrInfo">'+t.options.lang.drag_marker+'</div><div class="jr-geomaps-map jrMapItCanvas jrMapLoading"></div>';e.jrDialog(o,s)},getAddress:function(t){var n=this,r,i=[],s="";e.each(n.options.fields.address,function(e,n){var o=t.find("."+n),u;r=o.prop("type");if(r=="select-one"||r=="select-multiple"){u=o.find("option:selected").first();s=u.val()!==""?u.text():null}else s=o.val();null!==s&&undefined!==s&&s!==""&&s!="undefined"&&i.push(s)});t.find("."+n.options.fields.address.country).length===0&&undefined!==n.options.fields.default_country&&n.options.fields.default_country!==""&&i.push(n.options.fields.default_country);return i.join(" ")},_createMap:function(e,t,n){var r=this,i=r.inputs,s={zoom:r.options.zoom,center:new google.maps.LatLng(n[0],n[1]),mapTypeId:google.maps.MapTypeId.ROADMAP},o=t.find(".jr-geomaps-map");map=new google.maps.Map(o[0],s);marker=new google.maps.Marker({position:new google.maps.LatLng(n[0],n[1]),draggable:!0});google.maps.event.addListener(map,"idle",function(){o.removeClass("jrMapLoading")});google.maps.event.addListener(marker,"dragend",function(){i.lat.val(marker.getPosition().lat());i.lon.val(marker.getPosition().lng())});marker.setMap(map)}})})(jQuery);(function(e){e.widget("jreviews.jrProximitySearch",{options:{eventTrigger:"jrBeforeSearch",fields:{},autocomplete:!1},proximityField:null,page:null,ac_option_selected:!1,_create:function(t){var n=this;e.extend(n.options,t);n.page=n.element;n.searchButton=n.page.find(".jr-search")},_init:function(){var e=this,t={types:["geocode"]};jreviews.geomaps.autocomplete_country!==""&&(t.componentRestrictions={country:jreviews.geomaps.autocomplete_country});if(e.options.autocomplete===!0){e.proximityField=e.page.find("."+e.options.fields.proximity);if(e.proximityField.prop("type")=="text"){var n=new google.maps.places.Autocomplete(e.proximityField[0],t);google.maps.event.addListener(n,"place_changed",function(){var t=n.getPlace(),r=t.geometry.location.lat(),i=t.geometry.location.lng();e._addCoordinateInputs(r,i);e.ac_option_selected=!0});var r=document.createElement("input");e.proximityField.attr("placeholder","placeholder"in r?jreviews.__t("GEOMAPS_ENTER_LOCATION"):"").keydown(function(e){if(e.keyCode==13){e.preventDefault();return!1}})}}var i=e.proximityField.prop("type")=="text"?"blur":"change";e.page.on(i,"."+e.options.fields.proximity,function(){setTimeout(function(){e.ac_option_selected||e._mapSearchAddress();e.ac_option_selected=!1},250)});e.proximityField.on("focus",function(){e.ac_option_selected=!1;e.searchButton.attr("disabled","disabled")})},_getInputVal:function(e){var t=this,n=t.page.find("."+e),r=n.prop("type"),i;if(r=="select-one"){i=n.find("option:selected");inputVal=n.val()!==""?i.text():null}else inputVal=n.val();return inputVal},_mapSearchAddress:function(){var e=this,t=[],n="";if(undefined!==e.options.fields.proximity){n=e._getInputVal(e.options.fields.proximity);null!==n&&undefined!==n&&n!==""&&n!="undefined"?t.push(n):e._clearCoordinates()}if(t.length>0){if(e.page.find("."+e.options.fields.address.country).length===0)undefined!==e.options.fields.default_country&&e.options.fields.default_country!==""&&t.push(e.options.fields.default_country);else{n=e._getInputVal(e.options.fields.address.country);null!==n&&undefined!==n&&n!==""&&n!="undefined"&&t.push(n)}t=t.join(" ");var r=new google.maps.Geocoder;r.geocode({address:t},function(t,n){if(google.maps.GeocoderStatus.OK==n){var r=t[0].geometry.location;e._addCoordinateInputs(r.lat(),r.lng())}})}else e.searchButton.removeAttr("disabled")},_addCoordinateInputs:function(t,n){var r=this,i=r.options.fields.lat,s=r.options.fields.lon,o='input[name="data[Field][Listing]['+i+']"]',u='input[name="data[Field][Listing]['+s+']"]',a=e('<input type="hidden" name="data[Field][Listing]['+i+']" />'),f=e('<input type="hidden" name="data[Field][Listing]['+s+']" />');if(r.page.find(o).length===0){a.appendTo(r.page);f.appendTo(r.page)}else{a=r.page.find(o);f=r.page.find(u)}a.val(t);f.val(n);r.searchButton.removeAttr("disabled")},_clearCoordinates:function(){var e=this,t=e.options.fields.lat,n=e.options.fields.lon,r='input[name="data[Field][Listing]['+t+']"]',i='input[name="data[Field][Listing]['+n+']"]';e.page.find(r+","+i).val("")}})})(jQuery);(function(e){e.widget("jreviews.jrMaps",{options:{zoom:15,container_class:"jr-page",external_trigger_id:"jr-listing-title-",resize_class:"jr-map-resize",streetview_canvas:"jr-streetview-canvas",directions_canvas:"jr-directions-canvas",scroller:!1,cluster:{gridSize:50,maxZoom:15},iwMaxWidth:250},init:!0,page:null,referrer:null,tabs:null,map:null,kmlLayers:{},d:null,directions:null,directionsCanvas:null,streetview:null,sv:null,streetviewCanvas:null,streetviewStatus:!1,markerClusterer:null,bounds:null,center:null,infowindow:null,default_width:null,_gicons:{},_markers:{},_mapData:{},_payload:{},_icons:{"default":{type:"custom",url:"http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|FE766A|12|_|",size:[23,41]},default_hover:{type:"custom",url:"http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|FDFF0F|12|_|",size:[23,41]},default_featured:{type:"custom",url:"http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|5F8AFF|12|_|",size:[23,41]},numbered:{type:"numbered",url:"http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|FE766A|12|_|{index}",size:[23,41]},numbered_hover:{type:"numbered",url:"http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|FDFF0F|12|_|{index}",size:[23,41]},numbered_featured:{type:"numbered",url:"http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|5F8AFF|12|_|{index}",size:[23,41]},numbered_featured_hover:{type:"numbered",url:"http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.6|0|FDFF0F|12|_|{index}",size:[23,41]}},_create:function(t){var n=this,r=n.element;n._markers={};n._gicons={};n._mapData={};n._payload={};e.extend(n.options,t);n.referrer=r.data("referrer");n.page=r.closest("."+n.options.container_class);for(var i in n.options.icons)n._icons[i]=n.options.icons[i];n.streetviewCanvas=n.page.find("."+n.options.streetview_canvas);n.directionsCanvas=n.page.find("."+n.options.directions_canvas);n.defaultWidth=n.element.width()},_init:function(){var t=this,n=t.element,r=t.element.closest(".jr-tabs");if(r.length&&t.tabs===null){t.tabs=!0;r.bind("tabsshow",function(e,r){if(r.panel.id==t.element.closest(".ui-tabs-panel").attr("id")&&n.data("initialized")===undefined){t._init();n.data("initialized",!0)}});return}t.bounds=new google.maps.LatLngBounds;t.map=new google.maps.Map(n[0],{zoom:t.options.zoom});var i=t.page.find('.jr-attachments [data-file-type="kml"]');i.length&&i.each(function(n,r){var i=e(this).data("file-path");t.kmlLayers[n]=new google.maps.KmlLayer({url:i});t.kmlLayers[n].setMap(t.map)});t._setMapOptions();t.options.count>0&&t._addMarkers();if(t.options.count==1)t.map.setCenter(t.getCenter());else if(t.options.center){var s=new google.maps.LatLng(t.options.center.lat,t.options.center.lon);t.map.setCenter(s)}else t.map.fitBounds(t.bounds);google.maps.event.addListener(this.map,"idle",function(){n.removeClass("jrMapLoading").css({"overflow-x":"visible","overflow-y":"visible"})});google.maps.event.addListener(t.map,"dragstart",function(){t.options.infowindow!="google"&&t._closeInfowindow()});google.maps.event.addListener(t.map,"dblclick",function(){t.options.infowindow!="google"&&t._closeInfowindow()});google.maps.event.addListener(t.map,"zoom_changed",function(){t.options.infowindow!="google"&&t._closeInfowindow()});google.maps.event.addListener(t.map,"visible_changed",function(){t.options.infowindow!="google"&&t._closeInfowindow()});google.maps.event.addListener(t.map,"resize",function(){t.options.infowindow!="google"&&t._closeInfowindow()});t._createAddressBar();t._bindListingTitles();t._bindMapResize();t._bindMapScroller();t._bindStreetview(!0);t._bindDirections()},_bindListingTitles:function(){var e=this;e.page.on("mouseover",'[id^="'+e.options.external_trigger_id+'"]',function(){var t=this.id.replace(e.options.external_trigger_id,""),n=e._markers[t];e.options.infowindow!="google"&&e._closeInfowindow();if(n){e.map.setCenter(n.payload.latlng);e._toggleIcon(n,"_hover");e._loadInfoWindow(n)}}).on("mouseout",'[id^="'+e.options.external_trigger_id+'"]',function(){var t=this.id.replace(e.options.external_trigger_id,""),n=e._markers[t];n&&e._toggleIcon(n,"")})},_bindMapResize:function(){var e=this;e.page.on("click","."+e.options.resize_class+" a.jr-map-large, ."+e.options.resize_class+" a.jr-map-small",function(){var t,n;mapLinks=e.page.find("."+e.options.resize_class+" a");mapLinks.toggle();var r=e.element.width()<=e.defaultWidth;t=r?600:e.defaultWidth;n=r?500:e.defaultWidth;e.element.closest("#jr-map-column").animate({width:t},"fast");e.element.siblings().animate({width:t},"fast");e.element.animate({width:t,height:n},"fast",function(){e.resizeMap()})})},_bindMapScroller:function(){var t=this;if(!t.options.scroller)return;var n=t.page.find("div#jr-map-results-wrapper"),r=t.page.find("div#jr-map-column"),i=t.page.find("div#jr-listing-column");r.width(t.defaultWidth);var s=i.width();i.width(i.parent().width()-r.width()-12);var o=n.offset().top,u=i.height()+o-n.height()-5;e(window).scroll(function(){var t=e(this).scrollTop();if(t>=o){n.addClass("fixed").removeClass("top bottom");var i=e(this).width()-(r.offset().left+n.outerWidth());n.css("right",i)}else n.addClass("top").removeClass("fixed bottom");t>=u&&n.addClass("bottom").removeClass("top fixed")}).resize(function(){var t=e(this).width()-(r.offset().left+n.outerWidth());n.css("right",t);i.width(i.parent().width()-r.width()-12)})},_bindStreetview:function(){var e=this;if(e.map.get("streetViewControl")===!1)return;if(e.streetviewCanvas.length){e.streetview=e.streetview||new google.maps.StreetViewPanorama(e.streetviewCanvas[0]);google.maps.event.addListener(e.streetview,"visible_changed",function(){if(e.streetview.getVisible()&&!e.init){e.element.css("width","49%");e.resizeMap();e.streetviewCanvas.show()}e.init=!1});e.map.setStreetView(e.streetview);e._checkStreetviewStatus(e.map.getCenter());google.maps.event.addListener(e.streetview,"position_changed",function(){e.map.setCenter(e.streetview.getPosition())});google.maps.event.addListener(e.map,"click",function(t){e._checkStreetviewStatus(t.latLng)})}else{var t=e.map.getStreetView();google.maps.event.addListener(t,"visible_changed",function(){e.options.infowindow!="google"&&e._closeInfowindow();e.element.css("overflow","visible")})}},_checkStreetviewStatus:function(e){var t=this;if(t.streetview===null)return;t.sv=t.sv||new google.maps.StreetViewService;t.sv.getPanoramaByLocation(e,50,function(e,n){if(n==google.maps.StreetViewStatus.OK){t.element.css("width","49%");t.resizeMap();t.streetview.setPosition(e.location.latLng);t.streetviewCanvas.show()}})},_bindDirections:function(){var e=this,t=e.page.find(".jr-directions-address"),n=t.find(".jr-validation");if(!t.length||!e.options.directions)return;var r=t.find(".jr-directions-origin"),i=t.find(".jr-directions-destination");t.show().on("click",".jr-directions-submit",function(n){var s;i.val()==e.directionsCanvas.data("destAddr")?s=e.directionsCanvas.data("destCoord"):s=i.val();n.preventDefault();e.directionsCanvas.hide();e.directions=e.directions||new google.maps.DirectionsRenderer({map:e.map,panel:e.directionsCanvas[0]});e.d=e.d||new google.maps.DirectionsService;var o={origin:r.val(),destination:s,travelMode:google.maps.TravelMode[t.find(".jr-directions-travelmode").val()],region:e.directionsCanvas.data("locale")||"en"};e.d.route(o,function(t,n){if(n==google.maps.DirectionsStatus.OK){e.directionsCanvas.removeClass("jrError").fadeIn();e.directions.setDirections(t)}else e.directionsCanvas.addClass("jrError").html(jreviews.__t("PROCESS_REQUEST_ERROR")).fadeIn()})});t.on("click",".jr-directions-swap",function(){var e=r.val();r.val(i.val());i.val(e)})},_setMapOptions:function(){var e=this;e.options.mapUI.zoom=e.options.mapUI.zoom===0?e.options.zoom:e.options.mapUI.zoom;e.map.setOptions(e.options.mapUI)},_addMarkers:function(){var t=this,n=[];e.each(t.options.payload,function(e,r){var i=t._createMarker(r);t._markers[r.id]=i;t.options.clustering?n.push(i):i.setMap(t.map)});t.options.clustering&&(t.markerClusterer=new MarkerClusterer(t.map,n,{maxZoom:t.options.cluster.maxZoom,gridSize:t.options.cluster.gridSize}))},_createMarker:function(e){var t=this,n=new google.maps.LatLng(e.lat,e.lon),r=t._createIcon(e),i=new google.maps.Marker({position:n,icon:r,title:e.title});e.featured==1&&i.setZIndex(google.maps.Marker.MAX_ZINDEX+1);t.bounds.extend(n);r.data&&r.data.shadow&&i.setShadow(r.data.shadow);i.icon_name=e.icon;i.id=e.id;i.payload=e;i.payload.latlng=n;google.maps.event.addListener(i,"click",function(e){t._loadInfoWindow(i,e.latLng);t._checkStreetviewStatus(i.position)});google.maps.event.addListener(i,"mouseover",function(){t._toggleIcon(i,"_hover");return!1});google.maps.event.addListener(i,"mouseout",function(){t._toggleIcon(i,"")});return i},_toggleIcon:function(e,t){var n=this,r=e.icon_name;e.payload.featured===1&&r+"_featured"+t in n._icons&&(r+="_featured");undefined!==e&&r+t in n._icons&&e.setIcon(n._icons[r+t].url.replace("{index}",e.payload.index))},_createIcon:function(e){var t=this,n=e.icon,r=e.index,i;if(t._gicons.length&&undefined!==t._gicons[n]&&n!=="numbered")return t._gicons[n];undefined===t._icons[n]&&(n="default");e.featured===1&&n+"_featured"in t._icons&&(n+="_featured");switch(t._icons[n].type){case"custom":i=t._createCustomIcon(t._icons[n]);break;case"numbered":case"numbered_featured":i=t._createNumberedIcon(t._icons[n],r);break;case"default":i=t._createDefaultIcon(t._icons[n]);break;default:i=t._createDefaultIcon(t._icons[n])}t._gicons[n]=i;return i},_createCustomIcon:function(e){var t=new google.maps.MarkerImage(e.url);t.size=new google.maps.Size(e.size[0],e.size[1]);return t},_createDefaultIcon:function(e){var t=new google.maps.MarkerImage(e.url);t.size=new google.maps.Size(23,41);t.data={shadow:new google.maps.MarkerImage("http://www.google.com/mapfiles/shadow50.png",new google.maps.Size(37,34),null,new google.maps.Point(9,37))};var n=new google.maps.MarkerImage(e.url,t.size);n.data=t.data;return n},_createNumberedIcon:function(e,t){var n=new google.maps.MarkerImage(e.url);n.size=new google.maps.Size(23,41);n.data={shadow:new google.maps.MarkerImage("http://www.google.com/mapfiles/shadow50.png",new google.maps.Size(37,34),null,new google.maps.Point(9,37))};var r;t!==""?r=new google.maps.MarkerImage(e.url.replace("{index}",0+t),n.size):r=new google.maps.MarkerImage(e.url.replace("{index}",""),n.size);r.data=n.data;return r},_createAddressBar:function(){var t=this,n=new google.maps.Geocoder,r=e('<div class="jrRoundedPanel jrMapAddressBar"><input type="text" size="50" name="jr-map-address-search" placeholder="'+jreviews.__t("GEOMAPS_ENTER_LOCATION")+'" />'+'<button class="jrButton jrSmall"><span class="jrIconSearch"></span></button>'+"</div>");if(!t.options.search)return;t.map.controls[google.maps.ControlPosition.TOP_LEFT].push(r[0]);r.css({"z-index":1,"-moz-user-select":""}).on("keyup","input",function(e){e.keyCode==13&&r.find(".jrButton").trigger("click")});r.on("click",".jrButton",function(e){e.preventDefault();var i=r.find("input").val();if(i==="")return!1;n.geocode({address:i},function(e,n){if(google.maps.GeocoderStatus.OK==n){t.map.setZoom(11);t.map.setCenter(e[0].geometry.location)}})})},_loadInfoWindow:function(e,t){var n=this,r=n._renderInfoWindowContent(e.payload);if(n.options.infowindow=="google"){n.infowindow=n.infowindow||new google.maps.InfoWindow({maxWidth:n.options.iwMaxWidth});n.infowindow.setContent(r[0]);n.infowindow.open(n.map,e)}else{r.css("display","none");n._closeInfowindow();setTimeout(function(){n._positionInfowindow(r,e,t)},10)}e.setZIndex(google.maps.Marker.MAX_ZINDEX+1)},_renderInfoWindowContent:function(t){var n=this,r=1,i=e(n.options.iwTheme);n.options.mapUI.title.trim===!0&&n.options.mapUI.title.trimchars>0&&(t.title=t.title.substring(0,n.options.mapUI.title.trimchars));i.find(".jr-map-title").html(t.title);i.find(".jr-map-title").attr("href",t.url);!1!==t.image&&i.find(".jr-map-image").html('<img src="'+t.image+'" />');var s=i.find(".jrOverallUser"),o=i.find(".jrOverallEditor"),u=i.find(".jrOverallRatings");if(t.rating_scale===undefined)u.hide();else{u.show();if(t.user_rating===undefined)s.hide();else{s.show();i.find(".jr-map-user-rating").css("width",t.user_rating/t.rating_scale*100+"%");i.find(".jr-map-user-rating-val").html(Math.round(0+t.user_rating*Math.pow(10,r))/Math.pow(10,r));i.find(".jr-map-user-rating-count").html(Number(0+t.user_rating_count))}if(t.editor_rating===undefined)o.css("display","none");else{o.show();i.find(".jr-map-editor-rating").css("width",t.editor_rating/t.rating_scale*100+"%");i.find(".jr-map-editor-rating-val").html(Math.round(0+t.editor_rating*Math.pow(10,r))/Math.pow(10,r))}}for(var a in t.field)i.find(".jr-map-"+a).html(t.field[a]);i.on("click",".jr-close",function(){n._closeInfowindow()});return i},_positionInfowindow:function(e,t,n){var r=this,i=r.map.getBounds(),s=n||t.getPosition(),o=new google.maps.OverlayView;o.draw=function(){};o.setMap(r.map);setTimeout(function(){var t=o.getProjection(),n=t.fromLatLngToContainerPixel(s),u,a;i.contains(s)||r.map.setCenter(s);e.appendTo(r.element);if(e.hasClass("jrMapCustom")){u=n.x+parseInt(e.css("left"),10)+4;a=n.y+parseInt(e.css("top"),10)+15}else u=n.x+parseInt(e.css("left"),10),a=n.y+parseInt(e.css("top"),10);e.css({left:u+"px",top:a+"px",position:"absolute"}).show()},100)},_closeInfowindow:function(){this.element.find(".jrInfowindow").remove()},getMap:function(){return this.map},getMarkers:function(){return this._markers},getCenter:function(){var e=this,t=(e.bounds.getNorthEast().lat()+e.bounds.getSouthWest().lat())/2,n=(e.bounds.getNorthEast().lng()+e.bounds.getSouthWest().lng())/2;e.bounds.getNorthEast().lng()<e.bounds.getSouthWest().lng()&&(n+=180);return new google.maps.LatLng(t,n)},resizeMap:function(){google.maps.event.trigger(this.map,"resize")},destroy:function(){e.Widget.prototype.destroy.call(this)}})})(jQuery);