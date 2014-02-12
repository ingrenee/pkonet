jreviews.geomaps=jreviews.geomaps||{};(function(e){var t,n,r,i=!1;jreviews.geomaps.apiLoadedCallback=null;jreviews.geomaps.api_callback=function(){typeof jreviews.geomaps.apiLoadedCallback=="function"&&jreviews.geomaps.apiLoadedCallback();jreviews.geomaps.apiLoadedCallback=null};jreviews.geomaps.loadAPI=function(t){if(typeof google=="undefined"){if(i){setTimeout(function(){jreviews.geomaps.loadAPI(t)},50);return!1}i=!0;jreviews.geomaps.apiLoadedCallback=t;e.getScript(jreviews.geomaps.google_api_url+"&callback=jreviews.geomaps.api_callback");return!1}typeof t=="function"&&t();return!0};jreviews.geomaps.init=function(){var t=jrPage;t.on("click","#jr-geocode-all",function(t){t.preventDefault();var n=e(this),r=jreviews.geomaps.loadAPI(function(){jreviews.geomaps.geocodeAll(n)})});t.on("click","#jr-geocode-debug-hide",function(e){e.preventDefault();t.find("#jr-geocode-debug").slideUp()});t.on("click",".jr-geocode-pin",function(t){t.preventDefault();var n=e(this),r=jreviews.geomaps.loadAPI(function(){jreviews.geomaps.geocodeOne(n)})});t.on("mousemove",'.jr-geomaps-config form:not(".jr-ready-field-suggest")',function(){jreviews.geomaps.fieldSuggest(e(this));e(this).addClass("jr-ready-field-suggest")});t.on("click",".jr-geomaps-markers-list #jr-assign-marker",function(n){n.preventDefault();jreviews.geomaps.markerDialog(e(this),t)});jreviews.geomaps.fields.mapit&&t.on("jrListingFieldsLoaded","#jr-form-listing",function(){var t=e(this),n=t.find("."+jreviews.geomaps.fields.mapit);n.length&&jreviews.geomaps.loadAPI(function(){t.jrMapIt({fields:jreviews.geomaps.fields})})})};jreviews.geomaps.geocodeAll=function(t){var n=t.html(),r=jreviews.dispatch({method:"get",type:"html",controller:"admin/admin_geomaps",action:"_geocodeAll",data:{task:"all"}});r.done(function(t){var r={},i=function(){e("body").data("Geocode.abort",1)},s=e.jrDialog(t,{position:"top",title:n,close:i,buttons:r,width:"800px"});s.on("click","button#jr-geocode-all-start",function(t){t.preventDefault();jreviews.geomaps.geocodeAllStart(e(this))});s.on("click","button#jr-geocode-all-stop",function(t){t.preventDefault();e("#statusUpdate").html("Aborting...please wait.");e("body").data("Geocode.abort",1);e("button#jr-geocode-all-start").removeAttr("disabled")})})};jreviews.geomaps.geocodeAllStart=function(t,n,r){e("body").data("Geocode.abort",0);var i=e("input[name=debug_info]:checked").val(),s={task:"start",debug_info:i,process_increment:e("input#jr-geocode-increment").val(),offset:r||0,total:n||0},o=jreviews.dispatch({method:"get",type:"json",controller:"admin/admin_geomaps",action:"_geocodeAll",data:s});t.attr("disabled","disabled");o.done(function(n){var r=e("#jr-geocode-remaining"),s=e("#jr-geocode-successful"),o=e("#jr-geocode-error"),u=Number(s.html())+Number(n.success),a=Number(o.html())+Number(n.skipped)+Number(n.errors),f=n.total;r.html(f);s.html(u);o.html(a);if(f>0&&e("body").data("Geocode.abort")===0){if(i==="1"){var l=e("#jr-geocode-debug"),c=l.find("div.jr-geocode-debug-results");l.show();e.each(n.debug,function(e,t){var n;switch(t.api_response.status){case 0:n='<span class="jrStatusLabel jrOrange">'+jreviews.__t("GEOMAPS_ADDRESS_EMPTY")+"</span>";break;case 200:n='<span class="jrStatusLabel jrGreen">OK ('+t.api_response.lat+","+t.api_response.lon+")</span>";break;case 620:n='<span class="jrStatusLabel jrOrange">'+jreviews.__t("GEOMAPS_ADDRESS_SKIPPED")+"</span>"}c.before('<div class="jrGrid"><div class="jrCol1">'+t.id+"</div>"+'<div class="jrCol4">'+t.title+"&nbsp;</div>"+'<div class="jrCol4">'+t.address+"&nbsp;</div>"+'<div class="jrCol3">'+n+"</div>"+"</div>")})}jreviews.geomaps.geocodeAllStart(t,n.total,n.offset)}})};jreviews.geomaps.geocodeOne=function(t){var n=t,r=t.data("id"),i=t.data("title"),s=t.data("lat"),o=t.data("lon"),u={task:"single",listing_id:r},a=jreviews.dispatch({method:"get",type:"html",controller:"admin/admin_geomaps",action:"_geocodePopup",data:u});a.done(function(t){var r=e(t),u={};u[jreviews.__t("GEOMAPS_GEOCODE_ADDRESS")]=function(){jreviews.geomaps.geocodeInMap(e(this))};u[jreviews.__t("APPLY")]=function(){var t=e(this).find("form"),r=e(".ui-dialog-buttonpane"),i=e('<div class="jr-status jrSuccess jrHidden">'),s=e('<div class="jr-validation jrError jrHidden">');r.find(".jr-validation, .jr-status").fadeOut().remove();var o=t.find("."+jreviews.geomaps.fields.lat).val(),u=t.find("."+jreviews.geomaps.fields.lon).val();n.data("lat",o);n.data("lon",u);var a=jreviews.dispatch({type:"html",form:t});a.done(function(e){if(e==="1"){i.html(jreviews.__t("APPLY_SUCCESS"));r.prepend(i);i.fadeIn()}else{s.html(jreviews.__t("PROCESS_REQUEST_ERROR"));r.prepend(s);s.fadeIn()}})};var a=function(){var t=e(this),n=t.jreviewsFields({entry_id:t.find("#listing_id").val(),page_setup:!0,recallValues:!0});n.done(function(){s!==""&&o!==""&&s!==0&&o!==0?jreviews.geomaps.renderMapitMap(t,t,[s,o]):jreviews.geomaps.geocodeInMap(t)})};e.jrDialog(r,{title:i,open:a,buttons:u,width:"800px",height:"600"})})};jreviews.geomaps.geocodeInMap=function(r){var i=jreviews.geomaps.getAddress(r),s=new google.maps.Geocoder,o=r.find(".jr-validation").hide();s.geocode({address:i},function(s,o){if(google.maps.GeocoderStatus.OK!=o){var u=e(".ui-dialog-buttonpane"),a=e('<div class="jr-validation jrSuccess jrHidden">');u.find(".jr-validation, .jr-status").fadeOut().remove();a.html(jreviews.__t("GEOMAPS_CANNOT_GEOCODE"));u.prepend(a);a.fadeIn()}else{var f=s[0].geometry.location,l=[f.lat(),f.lng()];jreviews.geomaps.renderMapitMap(r,r,l);t.setCenter(f);t.setZoom(15);n.setPosition(f);var c=new google.maps.InfoWindow;c.setContent(i);c.open(t,n);var h=n.getPosition().lat(),p=n.getPosition().lng();r.find("."+jreviews.geomaps.fields.lat).val(h);r.find("."+jreviews.geomaps.fields.lon).val(p)}})};jreviews.geomaps.renderMapitMap=function(e,r,i){var s={zoom:15,center:new google.maps.LatLng(i[0],i[1]),mapTypeId:google.maps.MapTypeId.ROADMAP},o=r.find(".jr-geomaps-map");t=new google.maps.Map(o[0],s);n=new google.maps.Marker({position:new google.maps.LatLng(i[0],i[1]),draggable:!0});google.maps.event.addListener(t,"idle",function(){o.removeClass("jrMapLoading")});google.maps.event.addListener(n,"dragend",function(){e.find("."+jreviews.geomaps.fields.lat).val(n.getPosition().lat());e.find("."+jreviews.geomaps.fields.lon).val(n.getPosition().lng())});n.setMap(t)};jreviews.geomaps.getAddress=function(t){var n,r=[],i="",s=jreviews.geomaps.fields.default_country;e.each(jreviews.geomaps.fields.address,function(e,s){var o=t.find("."+s);n=o.prop("type");if(n=="select-one"){var u=t.find("."+s+" option:selected");i=u.val()!==""?u.text():null}else i=t.find("."+s).val();null!==i&&undefined!==i&&i!==""&&i!="undefined"&&r.push(i)});t.find("."+jreviews.geomaps.fields.address.country).length===0&&undefined!==s&&s!==""&&r.push(s);return r.join(" ")};jreviews.geomaps.fieldSuggest=function(t){var n=t.find("input.jr-field-suggest"),r=!1,i="";n.after("<span>").focus("focus",function(){e(this).next("span").html("")}).on("blur",function(){var t=e(this);if(!r){var n={field:t.val()};if(t.val()!==""){var i=jreviews.dispatch({method:"get",type:"text",controller:"admin/admin_geomaps",action:"_validateField",data:n});i.done(function(e){e==1?t.next("span").html('<span class="jrIconYes"></span>'):t.val("").next("span").html('<span class="jrStatusLabel jrRed">'+jreviews.__t("GEOMAPS_INVALID_FIELD")+"</span>")})}else t.next("span").attr("class","")}}).autocomplete({minLength:1,open:function(e,t){r=!0},close:function(t,n){r=!1;e(this).focus()},create:function(t,n){i=e(this).data("field-types")},source:function(e,n){if(t.data("cache."+e.term))n(t.data("cache."+e.term));else{data={limit:10,value:e.term};data.field_types=i;var r=jreviews.dispatch({method:"get",type:"json",controller:"admin/admin_geomaps",action:"_fieldList",data:data});r.done(function(r){t.data("cache."+e.term,r);n(r)})}},select:function(t,n){n.item.value!==""&&e(this).val(n.item.value)}})};jreviews.geomaps.markerDialog=function(t,n){var r=t.html(),i=t.data("lang"),s=n.find("input#jr-marker-field"),o=n.find("input#jr-marker-cat"),u=n.find("input#jr-marker-src");if(n.find("input.jr-row-cb:checked").length===0){e.jrAlert(i.select_validation);return!1}var a=e(e.trim(e("script#jr-marker-template").html())),f=a.find("div.jr-marker-icon");f.on("click","img",function(){var t=e(this);f.find("img").removeClass("jrSelected");e(this).addClass("jrSelected")});jreviews.geomaps.fieldSuggest(a);var l={};l[jreviews.__t("SAVE")]=function(){var t=e(this),r=f.find("img.jrSelected").first(),i={"data[marker_icon]":[],"data[cat_ids]":[]},s,o,u,a=n.find("input.jr-row-cb:checked"),l=a.closest("div.jr-layout-outer");a.each(function(){i["data[cat_ids]"].push(e(this).val())});s=i["data[marker_icon][field]"]=t.find("input.jr-field-suggest").val();i["data[marker_icon][cat]"]=r.data("filename")||"";o=r.attr("src");var c=jreviews.dispatch({method:"post",controller:"admin/admin_geomaps",action:"_saveMarkers",data:i});c.done(function(e){t.dialog("close");o&&l.find("span.jr-marker").html('<img src="'+o+'" />');l.find("span.jr-field").html(s);a.removeAttr("checked");jreviews.tools.flashRow(l)})};l[jreviews.__t("CANCEL")]=function(){e(this).dialog("close")};dialog=e.jrDialog(a,{title:r,buttons:l,width:"800px"});var c=e(".ui-dialog-buttonpane");c.find("button:contains("+jreviews.__t("SAVE")+")").addClass("jrButton jrGreen").prepend('<span class="jrIconSave"></span>');c.find("button:contains("+jreviews.__t("CANCEL")+")").addClass("jrButton").prepend('<span class="jrIconCancel"></span>')};jreviews.addOnload("geomaps-ini",jreviews.geomaps.init)})(jQuery);(function(e){e.widget("jreviews.jrMapIt",{options:{width:"700px",zoom:15,lang:{mapit:jreviews.__t("GEOMAPS_MAPIT"),clear_coord:jreviews.__t("GEOMAPS_CLEAR_COORDINATES"),geocode_fail:jreviews.__t("GEOMAPS_CANNOT_GEOCODE"),drag_marker:jreviews.__t("GEOMAPS_DRAG_MARKER")},fields:{}},page:null,buttons:{},inputs:{},_create:function(t){var n=this;n.page=n.element;e.extend(n.options,t);n.buttons={map:e('<button class="jrButton">').html('<span class="jrIconPin"></span>'+n.options.lang.mapit),clearVal:e('<button class="jrButton">').html('<span class="jrIconRemove"></span>'+n.options.lang.clear_coord)};n.inputs={lat:e('<input type="hidden" name="data[Field][Listing]['+n.options.fields.lat+']" >'),lon:e('<input type="hidden" name="data[Field][Listing]['+n.options.fields.lon+']" >')}},_init:function(){var e=this,t=e.buttons,n=e.inputs;t.map.on("click",function(t){t.preventDefault();e.open()});t.clearVal.on("click",function(t){t.preventDefault();if(e.page.find("."+e.options.fields.lat).length){n.lat=e.page.find("."+e.options.fields.lat);n.lon=e.page.find("."+e.options.fields.lon)}n.lat.val("");n.lon.val("")});e.page.find("."+e.options.fields.mapit).after(t.map,"&nbsp;",t.clearVal)},open:function(){var t=this,n=t.inputs;if(t.page.find("."+t.options.fields.lat).length===0)t.page.append(n.lat,n.lon);else{n.lat=t.page.find("."+t.options.fields.lat);n.lon=t.page.find("."+t.options.fields.lon)}var r=[n.lat.val(),n.lon.val()],i=t.getAddress(t.page);if(i==="")return;var s={width:t.options.width,height:"auto",open:function(){var s=e(this);if(n.lat.val()===""&&n.lat.val()===""){if(i!==""){var o=new google.maps.Geocoder;o.geocode({address:i},function(i,o){if(google.maps.GeocoderStatus.OK!=o)e.jrAlert(t.options.lang.geocode_fail);else{var u=i[0].geometry.location;center=u.toUrlValue();r=center.split(",");n.lat.val(r[0]);n.lon.val(r[1]);t._createMap(t.page,s,r)}})}}else t._createMap(t.page,s,r)}},o='<div class="jrInfo">'+t.options.lang.drag_marker+'</div><div class="jr-geomaps-map jrMapItCanvas jrMapLoading"></div>';e.jrDialog(o,s)},getAddress:function(t){var n=this,r,i=[],s="";e.each(n.options.fields.address,function(e,n){var o=t.find("."+n);r=o.prop("type");if(r=="select-one"){var u=o.find("option:selected");s=u.val()!==""?u.text():null}else s=o.val();null!==s&&undefined!==s&&s!==""&&s!="undefined"&&i.push(s)});t.find("."+n.options.fields.address.country).length===0&&undefined!==n.options.fields.default_country&&n.options.fields.default_country!==""&&i.push(n.options.fields.default_country);return i.join(" ")},_createMap:function(e,t,n){var r=this,i=r.inputs,s={zoom:r.options.zoom,center:new google.maps.LatLng(n[0],n[1]),mapTypeId:google.maps.MapTypeId.ROADMAP},o=t.find(".jr-geomaps-map");map=new google.maps.Map(o[0],s);marker=new google.maps.Marker({position:new google.maps.LatLng(n[0],n[1]),draggable:!0});google.maps.event.addListener(map,"idle",function(){o.removeClass("jrMapLoading")});google.maps.event.addListener(marker,"dragend",function(){i.lat.val(marker.getPosition().lat());i.lon.val(marker.getPosition().lng())});marker.setMap(map)}})})(jQuery);