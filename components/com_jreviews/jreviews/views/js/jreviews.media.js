var jreviewsMedia = jreviewsMedia || {};

jreviewsMedia._options = {
	galleryId: 'mediaGallery',
	uploader: {},
	mediaDiv: {},
	polling_period: 20000 // ms - encoding check job status
};

(function($) {

	$.fn.serializeObject = function()
	{
		var o = {};
		var a = this.serializeArray();
		$.each(a, function() {
			if (o[this.name] !== undefined) {
				if (!o[this.name].push) {
					o[this.name] = [o[this.name]];
				}
				o[this.name].push(this.value || '');
			} else {
				o[this.name] = this.value || '';
			}
		});
		return o;
	};

	jreviewsMedia.upload_valid = true;

	jreviewsMedia.addElement = function(key,el) {
		this._el = this._el || [];
		this._el[key] = el;
	};

	jreviewsMedia.getElement = function(key) {
		return this._el[key];
	};

	jreviewsMedia.initUploader = function(id, o)
	{
		$('#jr-terms-checkbox-upload, #jr-terms-checkbox-link').removeAttr('checked');

		// Initialize embed tab
		var embedButton = $('#jr-embed-submit');

		embedButton.on('click',function(e) {

			embedButton.trigger('beginUpload');

			e.stopImmediatePropagation();

			if(jreviewsMedia.upload_valid === true) {

				var button = $(this),
					embedUrl = $('input#jr-embed-url'),
					spinner = $('<span class="jrLoadingSmall"></span>');

				if(embedUrl.val() !== '') {

					button.attr('disabled','disabled').after(spinner);

					var embeddingVideo = jreviewsMedia.embed();

					embeddingVideo.done(function() {

						spinner.remove();
						button.removeAttr('disabled');
					});
				}
			}

		});

		// Initialize upload from url tab
		var urlButton = $('#jr-upload-url-submit');

		urlButton.on('click',function(e) {

			urlButton.trigger('beginUpload');

			e.stopImmediatePropagation();

			if(jreviewsMedia.upload_valid === true) {

				var button = $(this),
					uploadUrl = $('input#jr-upload-url'),
					spinner = $('<span class="jrLoadingSmall"></span>');

				if(uploadUrl.val() !== '') {

					button.attr('disabled','disabled').after(spinner);

					var uploadingUrl = jreviewsMedia.uploadFromUrl();

					uploadingUrl.done(function() {

						spinner.remove();
						button.removeAttr('disabled');
					});
				}
			}

		});

		// Initialize upload tab
		$('div.jr-tabs').find("div[id$='-tab']").on('delete',function() {

			var mediaList = $('.media-file-list'),
				mediaListUrl = $('.media-file-list-url'),
				embedList = $('.media-linked-videos');

			if(mediaList.children('div').length === 0) {

				mediaList.closest('fieldset').hide();

				$('.jr-upload-complete-actions').slideUp();
			}

			if(mediaListUrl.children('div').length === 0) {

				mediaListUrl.closest('fieldset').hide();

				$('.jr-upload-url-complete-actions').slideUp();
			}

			if(embedList.children('div').length === 0) {

				embedList.hide();

				$('.jr-embed-complete-actions').slideUp();
			}

		});

		var $_this = this;

		this._options.uploader =
		{
			element: document.getElementById(uploader_id = id || 'jr-media-uploader'),
			action: s2AjaxUri,
			params: $('#jr-uploaded-media').serializeObject(),
			template: $.trim($('#jr-uploader-template').html()),
			fileTemplate: $.trim($('#jr-progress-template').html()),
			disableCancelForFormUploads: true,
			classes: {
				button: 'jr-upload-button',
				drop: 'jr-upload-drop-area',
				dropActive: 'jr-upload-drop-area-active',
				list: 'jr-file-list',
				file: 'jr-upload-file',
				spinner: 'jr-upload-spinner',
				size: 'jr-upload-size',
				cancel: 'jr-upload-cancel',
				progress: 'jr-upload-progress',
				success: 'jr-upload-success',
				fail: 'jr-upload-fail'
			},
			messages: {
				typeError: jreviews.__t('MEDIA_UPLOAD_TYPE_ERROR'),
				sizeError: jreviews.__t('MEDIA_UPLOAD_SIZE_ERROR'),
				emptyError: jreviews.__t('MEDIA_UPLOAD_EMPTY_ERROR'),
				noFilesError: jreviews.__t('MEDIA_UPLOAD_NOFILE_ERROR'),
				onLeave: jreviews.__t('MEDIA_UPLOAD_ONLEAVE')
			},
			showMessage: function(message) {

				$('.jr-media-message').show().prepend('<li>'+message+'</li>');
			},
			onSubmit: function(id, fileName) {

				jreviewsMedia.upload_valid = true;

				$('.jr-upload-button input').trigger('beginUpload');

				if(!jreviewsMedia.upload_valid) {

					return false;
				}

				this.params = $('#jr-form-media-upload').serializeObject();

				// validate user info for guests
				var userForm = $('#jr-user-info');

				if(userForm.length > 0) {

					var	userInfo = userForm.serializeObject();

					$.extend( this.params, userInfo );
				}

				var uploadList = $('.jr-file-list').detach();

				$('.media-file-list').show().append(uploadList);

				$('.jr-upload-complete-actions').slideUp();

			},
			onComplete: function(id, filename, res) {

				res.id = id;

				if(!res.success && res.str !== undefined) {

					$('.jr-media-message').append(jreviews.__t(res.str,{add_ul:false})).show();

					return false;
				}

				if(res.success === true) {
					$_this.addMediaDiv(res,filename);
				}

				if(res.state == 'finished' && res.thumb_url !== '') {
					$_this.updateThumb(res);
				}

				if(res.success && res.encoding_job !== undefined) {
					$_this.checkEncodingJobStatus(res);
				}

				if ($_this.jrUploader._filesInProgress === 0) {
					$('.jr-upload-complete-actions').slideDown();
				}

			}
		};

		// Override the built-in methods
		qq.extend(qq.FileUploader.prototype, {

			_addToList: function(id, fileName)
			{
				// Invert order in which files are added to file list
				var item = qq.toElement(this._options.fileTemplate);

				item.qqFileId = id;

				var fileElement = this._find(item, 'file');
				qq.setText(fileElement, this._formatFileName(fileName));
				this._find(item, 'size').style.display = 'none';

				this._listElement.appendChild(item);
				this._listElement.insertBefore(item,this._listElement.childNodes[0]);
			},
			/**
			* delegate click event for cancel link
			**/
			_bindCancelEvent: function() {
				var self = this,
				list = this._listElement;

				qq.attach(list, 'click', function(e) {
					e = e || window.event;
					var target = e.target || e.srcElement;

					if (qq.hasClass(target, self._classes.cancel)) {
						qq.preventDefault(e);

						var item = target.parentNode.parentNode;
						self._handler.cancel(item.qqFileId);
						qq.remove(item);

						$('div.jr-tabs').find("div[id$='-tab']").trigger('delete');
					}
				});
			},
			_onProgress: function(id, fileName, loaded, total)
			{
				// Use $ UI progress indicator plugin
				qq.FileUploaderBasic.prototype._onProgress.apply(this, arguments);

				var item = this._getItemByFileId(id);
				var size = this._find(item, 'size');
				size.style.display = 'inline';

				var progress = this._find(item, 'progress');

				var text;

				if (loaded != total) {
					// Update the progress bar with the current progress value
					var current = Math.round(loaded / total * 100);
					$(progress).progressbar({ value: current });
					text = current + '% of ' + this._formatSize(total);
				}
				else {
					// Set the progress bar to completely full
					$(progress).progressbar({ value: 100 });
					text = this._formatSize(total);
				}

				qq.setText(size, text);
			},
			// Show failed validation for files, but continue uploading files remaining
			_uploadFileList: function(files)
			{
				var $_this = this;
				var validated = [];

				for (var i=0; i<files.length; i++) {
					if ( this._validateFile(files[i])) {
						validated.push(files[i]);
					}
				}

				for (var k=0; k<validated.length; k++) {
					this._uploadFile(validated[k]);
				}
			},
			// Supports specifying different size limits for different file types
			_validateFile: function(file)
			{
				var name, size;

				if (file.value) {
					// it is a file input
					// get input value and remove path to normalize
					name = file.value.replace(/.*(\/|\\)/, "");
				} else {
					// fix missing properties in Safari
					name = file.fileName !== undefined ? file.fileName : file.name;
					size = file.fileSize !== undefined ? file.fileSize : file.size;
				}

				this._options.allowedExtensions = [];

				if(this._options.fileValidation === undefined) return true;

				// Build the allowed extensions array and get the allowed fileSize

				var ext = (-1 !== name.indexOf('.')) ? name.replace(/.*[.]/, '').toLowerCase() : '';

				for (var i=0; i<this._options.fileValidation.length; i++) {

					$.merge(this._options.allowedExtensions,this._options.fileValidation[i]['allowedExtensions']);

					if($.inArray(ext,this._options.fileValidation[i]['allowedExtensions']) > -1)
					{
						this._options.sizeLimit = this._options.fileValidation[i]['sizeLimit'];
					}
				}

				if (! this._isAllowedExtension(name)) {
					this._error('typeError', name);
					return false;

				} else if (size === 0) {
					this._error('emptyError', name);
					return false;

				} else if (size && this._options.sizeLimit && size > this._options.sizeLimit) {
					this._error('sizeError', name);
					return false;

				} else if (size && size < this._options.minSizeLimit) {
					this._error('minSizeError', name);
					return false;
				}

				return true;
			}
		});

		qq.extend(this._options.uploader,o);

		this.jrUploader = new qq.FileUploader(this._options.uploader);

		jreviewsMedia.uploadValidation();
	};

	/**
	 * Validates terms of service acceptance and guest user fields
	 */
	jreviewsMedia.uploadValidation = function() {

		// validate user info for guests
		var userForm = $('#jr-user-info'),
			termsCheckbox = $('#jr-terms-checkbox-upload, #jr-terms-checkbox-url, #jr-terms-checkbox-link');

		// TOS
		termsCheckbox.on('change',function(e) {

			var el = $(this);

			if(el.is(':checked')) {

				termsCheckbox.attr('checked','checked');
			}
			else {

				termsCheckbox.removeAttr('checked');
			}
		});

		// Ensure user info is filled in for guests
		$('.jr-upload-button input, #jr-upload-url-submit, #jr-embed-submit').on('click beginUpload',function(e) {

			var	userInfo = userForm.serializeObject() || {},
				name = userInfo["data[Media][name]"],
				email = userInfo["data[Media][email]"],
				validation = $('.jr-media-message'),
				name_required = userForm.find('.jr-user-name').data('required') || false,
				email_required = userForm.find('.jr-user-email').data('required') || false;

			validation.html('');

			jreviewsMedia.upload_valid = true;

			if(userForm.length > 0)
			{
				if((email !== undefined && email_required && email === '') || (name !== undefined && name_required && name === '')) {

					if(email_required && email === '') validation.show().prepend('<li class="jr-user-validation">'+jreviews.__t('VALIDATE_EMAIL')+'</li>');

					if(name_required && name === '') validation.show().prepend('<li class="jr-user-validation">'+jreviews.__t('VALIDATE_NAME')+'</li>');

					jreviewsMedia.upload_valid = false;

					e.preventDefault();
				}
				else {

					jreviewsMedia.upload_valid = true;
				}

			}

			if(termsCheckbox.length && !termsCheckbox.is(':checked')) {

				validation.show().prepend('<li class="jr-user-validation">'+jreviews.__t('VALIDATE_TOS')+'</li>');

				jreviewsMedia.upload_valid = false;

				e.preventDefault();
			}
			else {

				jreviewsMedia.upload_valid = jreviewsMedia.upload_valid && true;
			}

			if(validation.find('li').length === 0) validation.slideUp();
		});
	};

	jreviewsMedia.addMediaDiv = function(res, filename) {

		var embed = res.embed || false;

		var tmplTmp = $($.trim($('#jr-media-template').html())).addClass('jr-'+res.media_type).attr('id','mediaForm'+res.media_id),
			form_id = 'mediaForm'+res.media_id;

		var tmpl;

		if(res.embed === true || res.upload_url === true) {

			tmpl = tmplTmp.wrap('<div class="jr-upload-success jrMediaDiv" />').parent();
		}
		else {

			tmpl = tmplTmp;
		}

		var spinner = tmpl.find('.jr-media-spinner');

		if(res.media_type == 'photo') {
			tmpl.find('.jr-media-input-description').closest('.jrFieldDiv').remove();
		}

		tmpl.find('.jr-media-update').on('click',function(e) {

			e.preventDefault();

			spinner.show();

			jreviews.dispatch({
				controller:'media',
				action:'_saveEdit',
				method: 'post',
				form_id: form_id
			})
			.done(function(res) {

				spinner.hide();
			});
		});

		tmpl.find('.jr-media-delete-upload').on('click',function(e) {

			var form_id = tmpl.is('form') ? tmpl.attr('id') : tmpl.find('form').attr('id');

			e.preventDefault();

			spinner.removeClass('jrHidden');

			var deletingMedia = jreviews.dispatch({controller:'media',action:'_delete',method: 'post',form_id: form_id});

			deletingMedia.done(function(res) {

				var deleteDiv = tmpl.hasClass('jr-upload-success') ? tmpl : tmpl.closest('.jr-upload-success');

				deleteDiv.slideUp(300,function() {

					$(this).remove();

					$('div.jr-tabs').find("div[id$='-tab']").trigger('delete');
				});
			});

		});

		tmpl.find('.jr-media-filename').html(filename);

		if(res.approved == 1) {
			tmpl.find('.jr-media-approved').css('display','inline-block');
		}
		else {
			tmpl.find('.jr-media-pending').css('display','inline-block');
		}

		tmpl.find('.jr-media-input-title').val(res.title ? res.title : filename);

		tmpl.find('.jr-media-input-description').html(res.description ? res.description : '');

		// Add inputs
		tmpl.find('.jr-media-media-id').val(res.media_id);

		tmpl.find('.jr-media-token').attr('name',res.token);

		// Store reference to the whole object to update it later
		this.addElement(res.media_id, tmpl);

		// Get the uploader object for this particular file so we can replace it with the media template
		var fileItem = this.jrUploader._getItemByFileId(res.id);

		if(res.embed)
		{
			$('.media-linked-videos').show().prepend(tmpl);
		}
		else {

			if(res.media_type == 'video' || res.media_type == 'audio') {

				tmpl.find('.jr-encoding-progress, .jr-encoding-info').show();
			}

			if(fileItem) {

				$(fileItem).html(tmpl);
			}
			else {

				$('.media-file-list-url').append(tmpl).show();
			}
		}

	};

	jreviewsMedia.updateThumb = function(res) {

		if(res.thumb_url !== undefined && res.thumb_url !== '') {

			var tmpl = this.getElement(res.media_id);

			var img = $('<img />').attr('src',res.thumb_url).css({'width':'75px','height':'auto'});

			tmpl.find('div.jr-media-thumb').html(img);
		}
	};

	jreviewsMedia.checkEncodingJobStatus = function(o) {

		if(o.encoding_job === null) return;

		var $_this = this,
			job_id = o.encoding_job.id,
			media_id = o.media_id;

		var checkingJobStatus = jreviews.dispatch({'method':'get','type':'json','controller':'media_upload','action':'checkJobStatus','data':{"job_id": job_id, 'media_id':media_id}});

		checkingJobStatus.done(function(res) {

			var tmpl = $_this.getElement(media_id);

			if(res.state == 'processing') {

					setTimeout(function() {$_this.checkEncodingJobStatus(o);}, jreviewsMedia._options.polling_period); // wait x ms
			}
			else if(res.state == 'failed') {
				tmpl.find('.jr-media-encoding-failed').css('display','inline-block');
			}
			else if(res.state == 'cancelled') {
				tmpl.find('.jr-media-encoding-cancelled').css('display','inline-block');
			}
			else {

				$('.jr-encoding-progress, .jr-encoding-info').remove();

				$_this.updateThumb(res);
			}
		});
	};

	jreviewsMedia.embed = function() {

		var $_this = this,
			controller = jreviews.admin ? 'admin/admin_media_upload' : 'media_upload';

		var embeddingVideo = jreviews.dispatch({controller:controller,action:'_embedVideo',form_id:'jr-form-media-embed',type:'json'});

		$('.jr-media-message').html('');

		embeddingVideo.done(function(res) {

			if(res.success) {

				$('input#jr-embed-url').val('');

				$_this.addMediaDiv(res,res.service);

				$_this.updateThumb(res);

				$('.jr-embed-complete-actions').slideDown();
			}
			else {

				$('.jr-media-message').html(jreviews.__t(res.str)).show();
			}

			return res;
		});

		return embeddingVideo;
	};

	jreviewsMedia.uploadFromUrl = function() {

		var $_this = this,
			controller = jreviews.admin ? 'admin/admin_media_upload' : 'media_upload';

		var uploadingUrl = jreviews.dispatch({controller:controller,action:'_uploadUrl',form_id:'jr-form-media-upload-url',type:'json'});

		$('.jr-media-message').html('');

		uploadingUrl.done(function(res) {

			$('input#jr-upload-url').val('');

			if(res.success === true) {

				$_this.addMediaDiv(res,res.filename);

				if(res.state == 'finished' && res.thumb_url !== '') {
					$_this.updateThumb(res);
				}

				if(res.success && res.encoding_job !== undefined) {
					$_this.checkEncodingJobStatus(res);
				}

				$('.jr-upload-url-complete-actions').slideDown();

			}
			else {

				$('.jr-media-message').html(jreviews.__t(res.str)).show();
			}

			return res;
		});

		return uploadingUrl;
	};

	/**
	* Initializes vote and reporting functions for media
	*/
	jreviewsMedia.initActions = function(class_name) {

		var gallery = $('.'+class_name),
			mediaActions = gallery.find('.jr-media-actions'),
			listing_id = mediaActions.data('listing-id'),
			review_id = mediaActions.data('review-id'),
			media_id = mediaActions.data('media-id'),
			extension = mediaActions.data('media-extension'),
			videoSlider,
			photoGallery;

		mediaActions
			.data('media-id',media_id)
			.data('listing-id',listing_id)
			.data('review-id',review_id)
			.data('extension',extension);

		var voteButtons = mediaActions.find('.jr-media-like-dislike button'),
			voteLike = mediaActions.find('.jr-media-like'),
			voteDislike = mediaActions.find('.jr-media-dislike');

		// Disable buttons if already voted

		if(gallery.hasClass('jr-video-gallery')) {

			sliderItem = gallery.find('#jr-video-slider').find('[data-media-id="'+media_id+'"]');

			if(sliderItem.length > 0) {

				if(undefined === sliderItem.attr('data-voted')) {

					voteButtons.removeAttr('disabled');
				}
				else {

					voteButtons.attr('disabled','disabled');
				}
			}
		}
		else if(gallery.hasClass('jr-photo-gallery')) {

			if(undefined === gallery.data('voted-'+media_id)) {

				voteButtons.removeAttr('disabled');
			}
			else {

				var mediaData = gallery.data('media'+media_id)  || {};

				if(mediaData.likes) voteLike.find('.jr-count').html(mediaData.likes);

				if(mediaData.dislikes) voteDislike.find('.jr-count').html(mediaData.dislikes);

				voteButtons.attr('disabled','disabled');
			}
		}

		// Bind action click events
		gallery
			.off('click','.jr-media-like-dislike button')
			.on('click','.jr-media-like-dislike button',function() {

				var el = $(this),
					action = el.data('like-action'),
					voteCountSpan = el.find('.jr-count'),
					vouteCount;

				var submitVotingAction = jreviews.dispatch({'controller':'media','action':action,'type':'json',data:{'m':media_id}});

				submitVotingAction.done(function(res) {

					voteLike.off('click');

					voteDislike.off('click');

					voteCount = parseInt(voteCountSpan.html(),10) + 1;

					if(res.success) voteCountSpan.html(voteCount);

					if(gallery.hasClass('jr-video-gallery') && sliderItem.length > 0) {

						// Update new vote counts in slider data in case we leave and come back to this item.
						sliderItem.attr('data-' + (action == '_like' ? 'likes' : 'dislikes'),  voteCount);

						sliderItem.attr('data-voted',1);
					}
					else if(gallery.hasClass('jr-photo-gallery')) {

						if(res.success) {

							var mediaData = gallery.data('media'+media_id)  || {};

							if(action == '_like') {

								mediaData.likes = voteCount;
								mediaData.dislikes = parseInt(voteDislike.find('.jr-count').html(),10);
							}
							else {

								mediaData.likes = parseInt(voteLike.find('.jr-count').html(),10);
								mediaData.dislikes = voteCount;
							}

							gallery.data('media'+media_id,mediaData);
						}

						gallery.data('voted-'+media_id,1);
					}

					voteButtons.attr('disabled','disabled');
				});
		});
	};

	jreviewsMedia.videoGallery = {

		init: function() {

			if('undefined' !== typeof(videojs)) {

				var videoGallery = $('div.jr-video-gallery');

				if(videoGallery.length) {

					var player = videoGallery.find('#jr-video-player');

					// make video.js responsive
					if (player.length) {

						var videoPlayer = videojs("jr-video-player").ready(function(){

							var videoObject = this,
								videoWidth = player.data('width'),
								videoHeight = player.data('height'),
								aspectRatio = videoHeight/videoWidth;

							function resizeVideoJS(){
								// Get the parent element's actual width
								var width = document.getElementById(videoObject.b.id).parentElement.offsetWidth;
								videoObject.width(width).height( width * aspectRatio ); // Set width to fill parent element, Set height
							}

							resizeVideoJS();

							window.onresize = resizeVideoJS;

							var tab = videoGallery.closest('.jr-tabs');

							// If the map is inside a jQuery tab we delay the initialization until the tab is shown
							if(tab.length) {

								tab.bind('tabsshow', function(event, ui) {

									if (ui.panel.id == videoGallery.closest('.ui-tabs-panel').attr('id') /* tab id */) {

										resizeVideoJS();
									}
								});
							}

						});

						player.on('jrVideoDestroy',function() {

							videoPlayer.destroy();

							$(this).remove();

						});
					}

					videoGallery.fitVids(); // make videos responsive

					jreviewsMedia.videoGallery.slider(videoGallery);
				}
			}
		},

		slider: function(videoGallery) {

			var videoSlider = videoGallery.find('#jr-video-slider'),
				videoItems = videoSlider.find('.jr-slider-item'),
				numVideos = videoItems.length,
				media_id = videoSlider.data('media-id'),
				outer_width = videoGallery.width();

			var resizeSlider = function() {

				outer_width = videoGallery.width();

				videoSlider.addClass('jrSliderSideArrows');

				if (numVideos < 2) {

					videoSlider.hide();

				}

				maxNumVideos = Math.floor(outer_width/130);

				if (numVideos > maxNumVideos) {

					var sliderWrapper = videoSlider.not('.jr-ready').addClass('jr-ready').find('.jrVideoList').bxSlider({
						infiniteLoop: false,
						pager: false,
						controls: true,
						minSlides: 2,
						maxSlides: 10,
						moveSlides: 1,
						slideMargin: 0,
						slideWidth: 122
					});

					var tab = videoSlider.closest('.jr-tabs');

	                // If the slider is inside a jQuery tab we reload the slider when the tab is shown
	                if(tab.length) {

	                    tab.bind('tabsshow', function(event, ui) {

	                        if (ui.panel.id == videoSlider.closest('.ui-tabs-panel').attr('id') /* tab id */) {

	                            sliderWrapper.reloadSlider();
	                        }
	                    });
	                }

				} else {

					if (typeof sliderWrapper !== 'undefined') {

						sliderWrapper.destroySlider();

					}

					videoSlider.removeClass('jrSliderSideArrows');

				}
			};

			resizeSlider();

			$(window).resize(function(){
				resizeSlider();
				if (typeof sliderWrapper !== 'undefined') {
					sliderWrapper.reloadSlider();
				}
			});

			jreviewsMedia.initActions('jr-video-gallery');

			videoSlider.on('click','a.mediaVideo',function(e) {

				e.preventDefault();

				var mediaThumb = $(this).closest('div.jr-slider-item').clone(),
					path = $(this).data('path'),
					videoEmbedBox = $('.jr-video-embed-player'),
					videoPlayerBox = $('.video-js-box'),
					videoPlayer = _V_('jr-video-player'),
					videoFrame,
					link = mediaThumb.find('a'),
					mediaThumbLink = mediaThumb.find('a'),
					mediaActions = $('.jr-media-actions'),
					media_id = mediaThumbLink.data('media-id');

				var videoGalleryCurrentVideo = videoGallery.find('div#jr-video-current-info');

				// Update media action ids likes and reporting
				mediaActions.data({
					'listing-id':mediaThumbLink.data('listing-id'),
					'review-id':mediaThumbLink.data('review-id'),
					'media-id':media_id,
					'media-extension':mediaThumbLink.data('media-extension'),
					'likes':mediaThumbLink.data('likes'),
					'dislikes':mediaThumbLink.data('dislikes')
				});

				jreviewsMedia.initActions('jr-video-gallery');

				// Increase view count

				if($(this).data('views') === undefined) {

					jreviews.dispatch({controller:'media',action:'_increaseView',data:{m:media_id}});

					$(this).data('views',true);
				}

				mediaThumb.find('.jr-media-info').removeClass('jrHidden');

				videoGalleryCurrentVideo.html(mediaThumb.html()).on('click','a',function(e) {

					e.preventDefault();
				});

				videoGallery.find('.jr-media-actions .jr-media-like .jr-count').html(mediaThumbLink.data('likes'));

				videoGallery.find('.jr-media-actions .jr-media-dislike .jr-count').html(mediaThumbLink.data('dislikes'));

				if(link.data('embed') !== '') {

					if(videoEmbedBox.find('iframe').length === 0) {
						videoFrame = $('<iframe></iframe>');
					}
					else {
						videoFrame = videoEmbedBox.find('iframe');
					}

					$.each(link.data('embedAttr'),function(k,v) {
						videoFrame.attr(k,v);
					});

					if(videoEmbedBox.find('iframe').length === 0) {
						videoFrame.appendTo(videoEmbedBox);
					}

					videoPlayerBox.hide();

					videoEmbedBox.show();

				}
				else {

					videoEmbedBox.hide();

					videoPlayerBox.show();

					videoPlayer.src([
						{ type: "video/mp4", src: path + ".mp4" },
						{ type: "video/webm", src: path + ".webm" }
						]);

					$('.vjs-poster').attr('src',path + '.jpg');

					videoPlayer.load();
				}

				window.onresize(); // Fix for videos in tabs

				videoGallery.fitVids();
			});

			if(media_id) {

				videoSlider.find('[data-media-id="'+media_id+'"]').trigger('click');
			}
			else {

				videoSlider.first().trigger('click');
			}
		}
	};

	jreviewsMedia.reorder = function(event,ui) {

		var sortableDiv = ui.item.closest('.ui-sortable');
			listing_id = sortableDiv.data('listing-id'),
			extension = sortableDiv.data('extension'),
			media_type = sortableDiv.data('media-type'),
			tokens = sortableDiv.data('token-s'),
			tokeni = sortableDiv.data('token-i'),
			ordering = sortableDiv.sortable('toArray');

		var data = {
			'data[listing_id]': listing_id,
			'data[extension]': extension,
			'data[ordering]': ordering,
			'data[media_type]': media_type
		};

		data[tokens] = 1;

		data[tokeni] = 1;

		var reorderingMedia = jreviews.dispatch({method:'get',controller:'media',action:'reorder',data: data});

		reorderingMedia.done(function(res) {

			if(!res.success) {

				jreviews.dialog.alert(jreviews.__t('PROCESS_REQUEST_ERROR'));
			}
		});
	};

	jreviewsMedia.photoGallery = {

		init: function() {

			var photoPage = $('div.jr-photo-gallery');

			if(photoPage.length === 0) return false;

			var tab = photoPage.closest('.jr-tabs');

			// If the gallery is inside a jQuery tab we delay the initialization until the tab is shown
			if(tab.length) {

				tab.bind('tabsshow', function(event, ui) {

					if (ui.panel.id == photoPage.closest('.ui-tabs-panel').attr('id') /* tab id */) {

						setTimeout(function() {
							jreviewsMedia.photoGallery.render(photoPage);
						},100);
					}
				});
			}
			else {

				jreviewsMedia.photoGallery.render(photoPage);
			}

		},

		render: function(photoPage) {

			var listingDetailPage = photoPage.closest('.jr-listing-detail'),
				photoSlideshow = photoPage.find('#jr-photo-slideshow'),
				media_id = photoSlideshow.data('media-id'),
				height = photoSlideshow.data('height'),
				lightbox = false,
				showIndex;

			if(media_id !== undefined) {

				$.each(data,function(index,media) {

					if(media.m == media_id) {
						showIndex = index;
						return false;
					}
				});
			}

			if (photoSlideshow.parent().hasClass('jrPhotoGalleryCompact')) {
				lightbox = true;
			}

			photoSlideshow.galleria({
				debug: false,
				dataSource: data,
				responsive: true,
				height: height,
				initialTransition: 'fade',
				lightbox: lightbox,
				layerFollow: false,
				imagePosition: 'center bottom',
				show: showIndex,
				extend: function(options) {

					var gallery = this; // "this" is the gallery instance

					// hide arrows and thumbs when there is only one image
					if( gallery.getDataLength() == 1 ){

						gallery.$('image-nav').hide();
						var thumbContainer = gallery.$('thumbnails-container');
						var thumbHeight = thumbContainer.height();
						var galleryContainer = gallery.$('container');
						var galleryHeight = galleryContainer.height() - thumbHeight;

						gallery.$('thumbnails-container').hide();
						$('#jr-photo-slideshow').height(galleryHeight);

					}

					if(window.history.replaceState) {

						gallery.bind('loadstart',function() {

							var currIndex = gallery.getIndex();

							$.each(data,function(index,media) {

								if(currIndex == index) {

									if(!listingDetailPage.length) {

										var newParams = {};

										newParams.m = media.m;

										var query_string = jreviewsMedia.makeNewQueryString(newParams);

										var path = window.location.pathname.replace(new RegExp( "\/m:[a-z0-9]+\/", "i" ),'/');

										window.history.replaceState(newParams, '', path + query_string);

									}

									if($(this).data('views') === undefined) {

										jreviews.dispatch({controller:'media',action:'_increaseView',data:{m:media.m}});

										$(this).data('views',true);
									}
								}
							});
						});

					}

					if (photoSlideshow.not('jrPhotoOverlay')) {

						gallery.bind('image', function(e) {
							$('#jr-gallery-description').html(e.galleriaData.layer);
						});

					}

					gallery.bind('image',function() {

						jreviewsMedia.initActions('jr-photo-gallery');

					});
				}
			});


			if (photoSlideshow.hasClass('jrPhotoOverlay')) {

				photoSlideshow.hover(
					function () {
						photoSlideshow.find('.jr-photo-info, .jr-photo-caption').hide().slideDown(200);
					},
					function () {
						photoSlideshow.find('.jr-photo-info, .jr-photo-caption').show().slideUp(200);
					}
				);

			}
		}
	};

	jreviewsMedia.audioPlayer = function() {

		var audioPlayers = $('div.jr-audio-player');

		audioPlayers.each(function() {

			var player = $(this),
				trackDiv = player.siblings('.jr-audio-tracks'),
				tracks = player.data('tracks'),
				swpath = player.data('swpath');

			new jPlayerPlaylist(
				{jPlayer: '#'+player.attr('id'),cssSelectorAncestor: '#'+trackDiv.attr('id')},
				tracks,
				{swfPath: swpath,supplied: "oga, m4a, mp3",playlistOptions:
					{
						freeItemClass: "jrButton jrSmall",
						downloadText: '<span class="jrIconArrowDown"></span>' + jreviews.__t('DOWNLOAD')
					}
				}
			);
		});

		var playlist = jQuery('.jp-playlist');

		playlist.each(function(){

			var list = jQuery(this);

			var player = list.closest('.jr-audio-tracks').siblings('.jr-audio-player');

			var tracks = player.data('tracks');

			jQuery(tracks).each(function(index,row){

				var track = list.find('li').eq(index).find('div');

				var download = '<span style="float:right"><a href="" class="jrButton jrSmall"><span class="jrIconArrowDown"></span>Download</a></span>';

				track.append(download);
			});

		});
	};

	jreviewsMedia.getUrlParams = function() {

		var url = window.location.search;
		url = url.replace('?', '');
		var queries = url.split('&');

		var params = {};

		for(var i = 0; i < queries.length; i++) {
			var param = queries[i].split('=');
			if(param[1] !== undefined) {
				params[param[0]] = param[1];
			}
		}

		return params;
	};

	jreviewsMedia.makeNewQueryString = function(replacement) {

		var paramsObj = jreviewsMedia.getUrlParams(),
			stringArray = [],
			string = '';

		$.each(replacement,function(k,v) {
				paramsObj[k] = v;
		});

		if(jreviews.jparams) {

			$.each(paramsObj,function(k,v) {
				stringArray.push(k+'='+v);
			});

			return '?'+stringArray.join('&');
		}
		else {

			$.each(paramsObj,function(k,v) {
				stringArray.push(k+':'+v);
			});

			return stringArray.join('/') + '/';
		}
	};

})(jQuery);

/*
* FitVids 1.0
*
* Copyright 2011, Chris Coyier - http://css-tricks.com + Dave Rupert - http://daverupert.com
* Credit to Thierry Koblentz - http://www.alistapart.com/articles/creating-intrinsic-ratios-for-video/
* Released under the WTFPL license - http://sam.zoy.org/wtfpl/
*
* Date: Thu Sept 01 18:00:00 2011 -0500
*/

(function($){

  $.fn.fitVids = function( options ) {
	var settings = {
	  customSelector: null
	};

	var div = document.createElement('div'),
		ref = document.getElementsByTagName('base')[0] || document.getElementsByTagName('script')[0];

	div.className = 'fit-vids-style';
	div.innerHTML = '&shy;<style>         \
	  .fluid-width-video-wrapper {        \
		 width: 100%;                     \
		 position: relative;              \
		 padding: 0;                      \
	  }                                   \
										  \
	  .fluid-width-video-wrapper iframe,  \
	  .fluid-width-video-wrapper object,  \
	  .fluid-width-video-wrapper embed {  \
		 position: absolute;              \
		 top: 0;                          \
		 left: 0;                         \
		 width: 100%;                     \
		 height: 100%;                    \
	  }                                   \
	</style>';

	ref.parentNode.insertBefore(div,ref);

	if ( options ) {
	  $.extend( settings, options );
	}

	return this.each(function(){
	  var selectors = [
		"iframe[src*='player.vimeo.com']",
		"iframe[src*='www.youtube.com']",
		"iframe[src*='www.youtube-nocookie.com']",
		"iframe[src*='www.dailymotion.com']",
		"object",
		"embed"
	  ];

	  if (settings.customSelector) {
		selectors.push(settings.customSelector);
	  }

	  var $allVideos = $(this).find(selectors.join(','));

	  $allVideos.each(function(){
        var $this = $(this);
        if (this.tagName.toLowerCase() === 'embed' && $this.parent('object').length || $this.parent('.fluid-width-video-wrapper').length) { return; }
        var height = ( this.tagName.toLowerCase() === 'object' || ($this.attr('height') && !isNaN(parseInt($this.attr('height'), 10))) ) ? parseInt($this.attr('height'), 10) : $this.height(),
            width = !isNaN(parseInt($this.attr('width'), 10)) ? parseInt($this.attr('width'), 10) : $this.width(),
            aspectRatio = height / width;
        if(!$this.attr('id')){
          var videoID = 'fitvid' + Math.floor(Math.random()*999999);
          $this.attr('id', videoID);
        }
        $this.wrap('<div class="fluid-width-video-wrapper"></div>').parent('.fluid-width-video-wrapper').css('padding-top', (aspectRatio * 100)+"%");
        $this.removeAttr('height').removeAttr('width');
      });
	});
  };
})( jQuery );