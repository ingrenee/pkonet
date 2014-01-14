(function($) {

jreviews.everywhere = {

	init: function() {

		var page = jrPage;

		page.on('change','select#jr-everywhere-extension',function(){

			// jreviews.everywhere.selectExtension($(this),page);

		});
	},

	selectExtension: function(el,page) {

		var submittingAction = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_everywhere',action:'_loadCategories'});

		submittingAction.done(function(html){

			page.find('div#jr-everywhere-categories').html(html);
		});
	},

	edit: function(el,page) {

		var	id = el.data('id') || 0,
            form = el.closest('form'),
            task = id > 0 ? 'edit' : 'create',
            title = el.html(),
            extension = el.data('extension');

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_everywhere',action:task,data:{id:id,extension:extension}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SAVE')] = function() {

                var dialog = $(this),
                    form = dialog.find('form');

                var submittingForm = jreviews.dispatch({form:form,data:{'data[task]':task}});

                var validation = form.find('div.jr-validation');

                validation.hide();

                submittingForm.done(function(res) {

                    if(res.success) {

                        dialog.dialog('close');

						page.html(res.html).fadeIn('fast').promise().done(function() {

							$.each(res.id,function(index,id) {

								var row = page.find('div.jr-layout-outer[data-id="'+id+'"]');

								jreviews.tools.flashRow(row);
							});

						});

                    }
                    else if(res.str.length) {

                        validation.html(jreviews.__t(res.str)).show();
                    }

                });

            };

            var width = task === 'edit' ? 500 : 800;

            buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

            var dialog = $.jrDialog(html, {title: title, buttons:buttons, width:width+'px'});

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SAVE')+')').addClass('jrButton jrGreen').prepend('<span class="jrIconSave"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
	}

};

jreviews.addOnload('everywhere',		jreviews.everywhere.init);

})(jQuery);
