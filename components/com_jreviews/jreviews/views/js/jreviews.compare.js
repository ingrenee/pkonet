/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

(function ($) {

	jreviewsCompare = {

		numberOfListingsPerPage: 3,

		maxNumberOfListings: 15,

		compareURL: 'index.php?option=com_jreviews&url=categories/compare/id:listing_ids/',

		lang: {
			'compare_heading': jreviews.__t('COMPARE_HEADING'),
			'compare_all': jreviews.__t('COMPARE_COMPARE_ALL'),
			'remove_all': jreviews.__t('COMPARE_REMOVE_ALL'),
			'select_more': jreviews.__t('COMPARE_SELECT_MORE'),
			'select_max': jreviews.__t('COMPARE_SELECT_MAX')
		},

		listingTypeID: null,

		init: function()
		{
			jreviewsCompare.set({
				numberOfListingsPerPage: jreviews.comparison.numberOfListingsPerPage,
				maxNumberOfListings : jreviews.comparison.maxNumberOfListings,
				compareURL: jreviews.comparison.compareURL
			});

			jreviewsCompare.initComparePage();

			jreviewsCompare.initCompareDashboard();

			jreviewsCompare.initListingsSelection();
		},

		set: function(options)
		{
			if(options !== undefined) {
				$.extend(jreviewsCompare, options);
			}
		},

		initComparePage: function()
		{

			var jrCompareView = $('.jr-compareview'),
				jrCompareSlider = $('.jr-compare-slider'),
				jrCompareSliderItems = jrCompareSlider.find('.jr-compare-slider-items'),
				jrCompareSliderSecondary = $('.jr-compare-slider-secondary'),
				jrCompareSliderItemsSecondary = jrCompareSliderSecondary.find('.jr-compare-slider-items2'),
				jrCompareSliderItem = jrCompareSliderItems.find('.jr-compare-slider-item'),
				jrScrollbarArea = $('.jrScrollbarArea'),
				numberOfListings = $('.jr-compare-slider-item').length,
				itemWidth,
				itemsWidth;

			// Set compare slider width
			jrCompareView.width(jrCompareView.parent().width());

			if (numberOfListings >= jreviewsCompare.numberOfListingsPerPage) {
				itemWidth = jrCompareSlider.width() / jreviewsCompare.numberOfListingsPerPage;
			}
			else {
				itemWidth = jrCompareSlider.width() / numberOfListings;
			}

			itemsWidth = itemWidth * numberOfListings;
			jrCompareSliderItem.width(itemWidth);
			jrCompareSliderItems.width(itemsWidth);
			jrCompareSliderItemsSecondary.width(itemsWidth);

			// scroll both scrollbars at the same time
			jrCompareSlider.scroll(function(){
				jrCompareSliderSecondary.scrollLeft(jrCompareSlider.scrollLeft());
			});

			jrCompareSliderSecondary.scroll(function(){
				jrCompareSlider.scrollLeft(jrCompareSliderSecondary.scrollLeft());
			});

			if (numberOfListings > jreviewsCompare.numberOfListingsPerPage) {

				jrScrollbarArea.removeClass('jrHidden');
				jrCompareSlider.removeClass('jrSliderDisabled');
				jrCompareSliderSecondary.removeClass('jrSliderDisabled');

				if (!jrCompareView.hasClass('jrCompareViewMenu')) {
					$('.removeComparedListing').removeClass('jrHidden');
				}

				jrCompareSlider.find('.jr-compare-slider-item').hover(
					function(){
						var listing = $(this);
						var listingID = listing.data('listingid');
						var listingWidth = listing.width();

						listing.on('click', 'img.removeListing', function(){
							listing.fadeOut('slow', function() {
								listing.remove();
								var newItemWidth = itemWidth * $('div.jr-compare-slider-item').length;
								var newNumberOfListings = $('.jr-compare-slider-item').length;
								jrCompareSliderItems.width(newItemWidth);
								jrCompareSliderItemsSecondary.width(newItemWidth);

								if (newNumberOfListings <= jreviewsCompare.numberOfListingsPerPage) {
									jrScrollbarArea.addClass('jrHidden');
									jrCompareSlider.addClass('jrSliderDisabled');
									jrCompareSliderSecondary.addClass('jrSliderDisabled');
									jrCompareSlider.find('.removeComparedListing').addClass('jrHidden');
								}

								// remove the listing from comparison list
								$('span#removelisting'+listingID).trigger('click');
							});
						});
					}
				);

			}

			jreviewsCompare.fixCompareAlignment();
		},

		fixCompareAlignment: function()
		{
			function eqHeight(group) {
				tallest = 0;
				group.each(function() {
					thisHeight = $(this).height();
					if(thisHeight > tallest) {
						tallest = thisHeight;
					}
				});
				group.height(tallest);
			}

			function fixHeight(group) {
				group.each(function() {
					var firstclass = $(this).attr('class').split(' ').slice(0,1);
					eqHeight($('div.'+firstclass));
				});
			}

			var compareFields = $('div.jr-compare-slider-item div.jrCompareField');
			fixHeight(compareFields);

		},

		initCompareDashboard: function()
		{
			$('body').append('<div class="jrCompareDashboard"><div class="jrCompareHeader"><div class="jrCompareArrow"></div></div><div class="jrCompareTabs"></div></div>');
			$('.jrCompareTabs').append('<div class="jrCompareTitle">'+jreviewsCompare.lang.compare_heading+'</div><ul class="jrTabsNav"></ul><div class="jrTabsContainer"></div>');
			$('.jrCompareHeader, .jrCompareTitle, ul.jrTabsNav')
				.hover(function(){
					$(this).css('cursor', 'pointer');
				})
				.click(function(){
					$('.jrCompareTabs .jrTabsContainer').slideToggle('slow', function(){
						var headerArrow = $('.jrCompareArrow');
						if($(this).is(':visible')) {
							headerArrow.addClass('down');
						}
						else {
							headerArrow.removeClass('down');
						}
					});
				});

			var jrCompareDashboard = $('.jrCompareDashboard');

			// get listings stored for comparison in localStorage
			var storedListings = jreviewsCompare.getListingsFromStorage();

			if (!$.isEmptyObject(storedListings)) {

				jrCompareDashboard.slideDown('slow');

				$.each(storedListings, function(key, value){

					var tabCreated = false;
					var listingTypeID = key.substring(11);

					// get listings for each listing type
					$.each(storedListings[key], function(key, listing){

						if (!tabCreated) {
							jreviewsCompare.insertTab(listing);
							tabCreated = true;
						}

						jreviewsCompare.insertListingIntoComparison(listing);

						// update compare listings url
						jreviewsCompare.updateCompareAllUrl(listing);

					});

					if ($('#tabLT'+listingTypeID+' li.ltItem').length > 4) {
						$('#tabLT'+listingTypeID+' a.compareNext').css('visibility', 'visible');
					}

				});

			}

			// remove listing from comparison after icon clicked
			jrCompareDashboard.on('click', 'span.removeItem',function() {

				var item = $(this).attr('id');

				var listing = {
					id: item.substring(13),
					typeId: $(this).text()
				};

				$('input.listing'+listing.id).attr('checked', false);
				jreviewsCompare.removeListingFromComparison(listing);
			});

			// remove all listings from comparison
			jrCompareDashboard.on('click', 'a.removeListings', function(e) {

				e.preventDefault();

				var listingType = $(this).parent().attr('id');
				var listingTypeID = listingType.substring(9);

				var firstListingId = $('#tabLT'+listingTypeID+' li.ltItem:first-child span.removeItem').attr('id').substring(13);
				var listing = {
					id: firstListingId,
					typeId: listingTypeID
				};

				var checkboxes = $('.jrCheckListing[data-listingtypeid="'+listingTypeID+'"]');
				checkboxes.attr('checked', false);

				jreviewsCompare.removeTab(listing);

			});

			// compare all listings
			jrCompareDashboard.on('click', 'a.compareListings', function(e) {

				e.preventDefault();

				var listingTypeID = $(this).parent().attr('id').substring(9);
				var compareCount = $('div#tabLT'+listingTypeID+' ul.ltList li').length;

				if (compareCount < 2) {

					if ($('p.comparisonMessage.'+listingTypeID).val() !== ''){
						$('div#tabLT'+listingTypeID).append('<p class="comparisonMessage '+ listingTypeID +' jrPopup">'+jreviewsCompare.lang.select_more+'</p>');
						$('p.comparisonMessage').hide().fadeIn('slow', function(){
							setTimeout( function(){
								$('p.comparisonMessage').fadeOut('slow', function(){
									$(this).remove();
								});
							}, 3000 );
						});
					}

				} else {

					var url = $(this).attr('href');
					window.location.href = url;

				}
			});

			$('ul.jrTabsNav').on('click', 'a', function(e){
				e.stopPropagation();
				var tabsContainer = $('.jrTabsContainer');
				if (tabsContainer.is(':hidden')) {
					tabsContainer.slideDown('slow');
				}
			});

		},

		insertTab: function(listing)
		{

			var listingTypeID = listing.typeId;
			var listingTypeTitle = listing.typeTitle;
			var compareTabs = $('.jrCompareTabs');

			if (compareTabs.data("ui-tabs")) {
				compareTabs.tabs('destroy');
			}

			$('.jrCompareTabs ul.jrTabsNav').append('<li><a href="#tabLT'+ listingTypeID +'" class="listingTypeCompare jrCompare'+ listingTypeID +'">' + listingTypeTitle + ' <span class="numSelected">(0)</span></a></li>');
			$('.jrCompareTabs .jrTabsContainer').append('<div id="tabLT'+ listingTypeID +'" class="ui-helper-clearfix"><div class="jrCompareScroll"><ul class="ltList"></ul></div></div>');

			compareURL = jreviewsCompare.compareURL.replace('listing_ids',listing.id);

			var compareAllLink = '<a rel="nofollow" href="' + compareURL + '" class="compareListings jrButton jrSmall listingType' +listingTypeID+ '"><span class="jrIconCompare"></span>'+jreviewsCompare.lang.compare_all+'</a>';
			var removeAllLink = '<a href="#" class="removeListings jrButton jrSmall listingType' + listingTypeID + '"><span class="jrIconRemove"></span>'+jreviewsCompare.lang.remove_all+'</a>';
			$('.jrTabsContainer #tabLT'+listingTypeID).append('<div id="jrCompare' + listingTypeID + '" class="jrCompareButtons">'+ compareAllLink + removeAllLink +'</div>');

			var numTabs = $('.jrCompareTabs ul.jrTabsNav li').length;

			var base_url = $('base').attr('href');

			if(base_url !== undefined && base_url.length)
			{
				compareTabs.find('ul.jrTabsNav li a:not(".jrBaseUpdated")').each(function() {
					$(this).addClass('jrBaseUpdated').attr('href',$(location).attr('href')+$(this).attr('href'));
				});
			}

			compareTabs.tabs({selected: numTabs-1});
		},

		removeTab: function(listing)
		{
			var jrCompareDashboard = $('.jrCompareDashboard');
			var $tabs = $('.jrCompareTabs');
			var activeTab = $tabs.tabs('option', 'selected');
			var numTabs = $tabs.tabs('length');

			if (numTabs > 1) {

				$('ul.jrTabsNav li.ui-tabs-active').fadeOut('slow', function(){
					$tabs.tabs('remove', activeTab);
				});

				$tabs.tabs('destroy');

				if(activeTab > 0) {
					$tabs.tabs({selected: activeTab-1});
				}
				else {
					$tabs.tabs({selected: 0});
				}

			} else {
				jrCompareDashboard.slideUp('slow', function(){
					$tabs.tabs('remove', activeTab);
					$tabs.tabs('destroy');
				});
			}

			jreviewsCompare.removeListingTypeFromStorage(listing);
		},

		insertListingIntoComparison: function(listing)
		{
			if (listing.thumbSrc.length) {
				listing.thumb = '<div class="compareThumb"><a href="'+ listing.url +'"><img src="'+ listing.thumbSrc +'" /></a></div>';
			} else {
				listing.thumb = '';
			}
			listing.titleUrl = '<a href="'+ listing.url +'">' + listing.title + '</a>';
			$('div#tabLT' + listing.typeId + ' ul').append('<li id="listing' + listing.id + '" class="ltItem"><span id="removelisting' + listing.id + '" data-icon="&#xe018;" class="removeItem">' + listing.typeId + '</span>' + listing.thumb + '<span class="compareItemTitle">' + listing.titleUrl + '</span></li>');

			$('input.listing'+listing.id).attr('checked', 'checked');

			var numListings = jreviewsCompare.getNumberOfSelectedListings(listing.typeId);
			numListings++;
			$('.jrCompareTabs a.jrCompare'+listing.typeId+' span.numSelected').html('('+ numListings +')');
		},

		removeListingFromComparison: function(listing)
		{
			var $tabs = $('.jrCompareTabs');

			if($('li#listing'+listing.id).siblings().length > 0) {

				$('li#listing'+listing.id).fadeOut('slow', function(){
					$(this).remove();
				});

				jreviewsCompare.updateNumberOfSelectedListings(listing.typeId, -1);

				// remove single listing from localStorage
				jreviewsCompare.removeListingFromStorage(listing);

				// update compare listings url
				jreviewsCompare.updateCompareAllUrl(listing);

			}
			else {
				jreviewsCompare.removeTab(listing);
			}

		},

		initListingsSelection: function()
		{
			// select listing for comparison - checkbox
			$('.jrPage, .jrModuleContainer').on('click', 'input.jrCheckListing', function() {

				var listing = {
					id : $(this).attr('value'),
					title : $(this).data('listingtitle'),
					url : $(this).data('listingurl'),
					thumbSrc : $(this).data('thumburl'),
					typeId : $(this).data('listingtypeid'),
					typeTitle : $(this).data('listingtypetitle'),
					location : $(this).data('location')
				};

				if (listing.thumbSrc.length) {
					listing.thumb = '<div class="compareThumb"><a href="'+ listing.url +'"><img src="'+ listing.thumbSrc +'" /></a></div>';
				} else {
					thumb = $("img[data-listingid='"+ listing.id + "']");
					if (thumb.length) {
						thumbSrc = thumb.attr('src');
						listing.thumbSrc = thumbSrc;
						listing.thumb = '<div class="compareThumb"><a href="'+ listing.url +'"><img src="'+ thumbSrc +'" /></a></div>';
					} else {
						listing.thumb = '';
					}
				}
				listing.titleUrl = '<a href="'+ listing.url +'">' + listing.title + '</a>';
				listing.data = '<li id="listing' + listing.id + '" class="ltItem">'+ listing.thumb +'<span class="compareItemTitle">' + listing.titleUrl + '</span><span id="removelisting' + listing.id + '" data-icon="&#xe018;" class="removeItem">' + listing.typeId + '</span></li>';

				var jrCompareDashboard = $('.jrCompareDashboard');
				var compareTabs = $('.jrCompareTabs');
				var tabsContainer = $('.jrTabsContainer');
				var headerArrow = $('.jrCompareArrow');
				var duplicateCheckboxes;

				if ($(this).is(':checked')) {

					duplicateCheckboxes = $('input.listing'+listing.id+':not(:checked)');

					// check other checkbox instances of the same listing
					$.each(duplicateCheckboxes, function(){
						$(this).attr('checked', 'checked');
					});

					if ($('div#tabLT'+listing.typeId).length > 0) {
						if (tabsContainer.is(':hidden')) {
							tabsContainer.slideDown('slow');
							headerArrow.addClass('down');
						}

						var numListings = jreviewsCompare.getNumberOfSelectedListings(listing.typeId);

						if (numListings >= jreviewsCompare.maxNumberOfListings) {

							if ($('p.comparisonMessageMax.'+listing.id).val() !== ''){
								$(this).parent().append('<p class="comparisonMessageMax '+ listing.id +' jrPopup">'+jreviewsCompare.lang.select_max+'</p>');
								$('p.comparisonMessageMax').hide().fadeIn('slow', function(){
									setTimeout( function(){
										$('p.comparisonMessageMax').fadeOut('slow', function(){
											$(this).remove();
										});
									}, 3000 );
								});
							}

							return false;

						}

						$(listing.data).appendTo('div#tabLT' + listing.typeId + ' ul.ltList').hide().fadeIn('slow');

						compareTabs.tabs('select', '#tabLT'+listing.typeId);

					} else {

						if (tabsContainer.is(':hidden')) {
							tabsContainer.show();
							headerArrow.addClass('down');
						}
						jreviewsCompare.insertTab(listing);
						$(listing.data).appendTo('div#tabLT' + listing.typeId + ' ul.ltList').hide().fadeIn('slow');
						if (jrCompareDashboard.is(':hidden')) {
							jrCompareDashboard.slideDown('slow');
						}
					}
					jreviewsCompare.updateNumberOfSelectedListings(listing.typeId, 1);

					// save listing data to local storage
					jreviewsCompare.saveListingToStorage(listing);

					// update compare listings url
					jreviewsCompare.updateCompareAllUrl(listing);

				} else {

					duplicateCheckboxes = $('input.listing'+listing.id+':checked');

					// check other checkbox instances of the same listing
					$.each(duplicateCheckboxes, function(){
						$(this).attr('checked', false);
					});

					compareTabs.tabs('select', '#tabLT'+listing.typeId);
					//listingId = 'listing'+listing.id;
					jreviewsCompare.removeListingFromComparison(listing);
				}
			});
		},

		getNumberOfSelectedListings: function(listingTypeID)
		{
			var numListings = $('.jrCompareTabs a.jrCompare'+listingTypeID+' span.numSelected').html().slice(1,-1);
			return parseInt(numListings,10);
		},

		updateNumberOfSelectedListings: function(listingTypeID, update)
		{
			var numListings = jreviewsCompare.getNumberOfSelectedListings(listingTypeID);
			numListings = numListings + update;
			$('.jrCompareTabs a.jrCompare'+listingTypeID+' span.numSelected').html('('+ numListings +')');
		},

		updateCompareAllUrl: function(listing) {
			var compareAllLink = $('a.compareListings.listingType'+listing.typeId);
			var compareAllUrl = compareAllLink.attr('href');
			var listings = jreviewsCompare.getListingsFromStorage(listing.typeId);
			var listingIDs = [];

			$.each(listings, function(key, listing){
				listingIDs.push(listing.id);
			});

			listingIDs = listingIDs.toString();

			var newCompareAllUrl = jreviewsCompare.compareURL.replace('listing_ids',listingIDs);
			compareAllLink.attr('href', newCompareAllUrl);
		},

		saveListingToStorage: function(listing) {

			// create an object for current listing
			var currentListingTypeId = listing.typeId;
			var currentListingId = listing.id;
			var currentListing = {};
			currentListing['listingType'+currentListingTypeId] = {};
			currentListing['listingType'+currentListingTypeId]['listing'+currentListingId] = listing;

			// get all lisitngs stored for comparison
			var storedListings = jreviewsCompare.getListingsFromStorage();

			if (!$.isEmptyObject(storedListings)) {

				var listings = $.extend({}, storedListings);
				listings['listingType'+currentListingTypeId] = $.extend({}, listings['listingType'+currentListingTypeId], currentListing['listingType'+currentListingTypeId]);
				localStorage.setItem("jrCompare", JSON.stringify(listings));

			} else {

				localStorage.setItem("jrCompare", JSON.stringify(currentListing));

			}

		},

		// return listings from localStorage
		getListingsFromStorage: function(listingTypeId) {

			var storedListings = {};
			var jsonData = '';

			jsonData = localStorage.getItem("jrCompare");

			if (jsonData !== '' && jsonData !== '{}') {

				storedListings = JSON.parse(jsonData);


				if (listingTypeId !== undefined) {

					storedListings = storedListings['listingType'+listingTypeId];

				}

			}

			return storedListings;
		},

		// remove listing from localStorage
		removeListingFromStorage: function (listing) {

			// get all lisitngs stored for comparison
			var storedListings = jreviewsCompare.getListingsFromStorage();

			delete storedListings['listingType'+listing.typeId]['listing'+listing.id];

			// save updated object
			localStorage.setItem("jrCompare", JSON.stringify(storedListings));
		},

		removeListingTypeFromStorage: function (listing) {

			// get all lisitngs stored for comparison
			var storedListings = jreviewsCompare.getListingsFromStorage();

			delete storedListings['listingType'+listing.typeId];

			// save updated object
			localStorage.setItem("jrCompare", JSON.stringify(storedListings));
		}

	};

})(jQuery);