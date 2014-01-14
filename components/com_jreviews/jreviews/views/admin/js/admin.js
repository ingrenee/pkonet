var jreviews = jreviews || {},
    jrPage,
    jrAdmin;

jreviews.admin = true;

// Get rid of the Joomla menu toolbars

(function($) {

$(document)
    .ajaxSend( function() {
        $("div.jr-page-spinner").show();
    })

    .ajaxComplete(function() {
        $("div.jr-page-spinner").fadeOut();
    })

    .ajaxStop(function() {

        jreviews.addOnloadAjax('datepicker',          jreviews.datepicker);

        jreviews.addOnloadAjax('images',              jreviews.page.loadImages);

        jreviews.addOnloadAjax('group-location',      jreviews.group.changeLocation);

        $.each(jreviews._onloadFnAjax, function(i,fn) {
            if(fn !== undefined) {
                fn();
            }
        });

    });

jreviews.apply = false;

jreviews = $.extend(jreviews, {
    lang: jreviews.lang || {},
    _onloadFn: [],
    _onloadFnAjax: [],
    _onloadFnKeys: [],
    _onloadFnAjaxKeys: []
});

jreviews.addOnload = function(fn_name, fn) {

    if($.inArray(fn_name,jreviews._onloadFnKeys) === -1) {

        jreviews._onloadFn.push(fn);
        jreviews._onloadFnKeys.push(fn_name);
    }
};

jreviews.addOnloadAjax = function(fn_name, fn) {

    if($.inArray(fn_name,jreviews._onloadFnAjaxKeys) === -1) {
        jreviews._onloadFnAjax.push(fn);
        jreviews._onloadFnAjaxKeys.push(fn_name);
    }
};

jreviews.onload = function() {

    jreviews.addOnload('stats',                   jreviews.stats);

    jreviews.addOnload('news-feed',               jreviews.newsFeed);

    jreviews.addOnload('version-check',           jreviews.versionCheck);

    jreviews.addOnload('menus',                   jreviews.menu.init);

    jreviews.addOnload('license',                 jreviews.license.init);

    jreviews.addOnload('installer',               jreviews.installer.init);

    jreviews.addOnload('datepicker',              jreviews.datepicker);

    jreviews.addOnload('popup',                   jreviews.popup);

    jreviews.addOnload('tabs',                    jreviews.tabs);

    jreviews.addOnload('form-checkall',           jreviews.tools.checkAll);

    jreviews.addOnload('open-site-url',           jreviews.openSiteUrl);

    jreviews.addOnload('image-delayed',           jreviews.imageDelayed);

    jreviews.addOnload('about',                   jreviews.about.init);

    // Browse and moderation
    jreviews.addOnload('moderation',              jreviews.moderation.init);

    jreviews.addOnload('listing-browse',          jreviews.listing.browse);

    jreviews.addOnload('review-browse',           jreviews.review.browse);

    jreviews.addOnload('media-browse',            jreviews.media.browse);

    jreviews.addOnload('media-submit',            jreviews.media.init);

    jreviews.addOnload('moderation',              jreviews.moderation.init);

    // Configuration and setup
    jreviews.addOnload('configuration',           jreviews.config.init);

    jreviews.addOnload('group',                   jreviews.group.init);

    jreviews.addOnload('field',                   jreviews.field.init);

    jreviews.addOnload('listing-type',            jreviews.listing_type.init);

    jreviews.addOnload('theme',                   jreviews.theme.init);

    // Keep last so other events are bound first
    jreviews.addOnload('page-init',               jreviews.page.init);


    $.each(jreviews._onloadFn, function(i,fn) {
        if(fn !== undefined) {
            fn();
        }
    });
};

head.ready(function() {

    $('#toolbar-box,#submenu-box').remove();

    $('.subhead-collapse').remove();

    jrPage = $('div.jr-page');

    jrAdmin = $('#jr-admin');

    jreviews.onload();
});

jreviews.__t = function(string, options) {

    var defaults = {
        'add_ul':true
    };

    options = $.extend(defaults,options);

    if(typeof string == 'string') {

        return jreviews.lang[string] || string;
    }
    else {

        var out = [];

        $.each(string, function(i,s) {

            var t, l;

            if(typeof s == 'string') {

                t = jreviews.lang[s] || s;

            }
            else {

                l = jreviews.lang[s.shift()];

                s.unshift(l);

                t = window['sprintf'].apply(null,s);
            }

            out.push('<li>' + t + '</li>');
        });

        if(options.add_ul) {

            return '<ul>' + out.join('') + '</ul>';
        }
        else {

            return out.join('');
        }
    }

};

jreviews.dispatch = function(options) {

    options = options || {};

    var method =  (options.form_id !== undefined || options.form !== undefined) ? 'POST' : 'GET';

    if(undefined !== options.method) method = options.method;

    var data =  options.controller !== undefined ? $.param({'data[controller]':options.controller,'data[action]':options.action}) : {};

    var type = options.type || "json";

    var ajaxUrl = options.frontend ? s2AjaxUri.replace(/administrator\//,'',s2AjaxUri) : s2AjaxUri;

    if(undefined !== options.form_id || undefined !== options.form)
    {
        var form = options.form || $('#'+options.form_id);

        if(undefined !== options.controller) {

            form.find('input[name=data\\[controller\\]], input[name=data\\[action\\]]').remove();
        }

        data = form.serialize()+'&'+data;
    }

    if(options.data) data = data + '&' + $.param(options.data);

    return $.ajax({type: method, url: ajaxUrl, data: data, dataType: options.type});
};

jreviews.versionCheck = function() {

    var gettingVersion = jreviews.dispatch({method:'get',controller:'admin/common',action:'getVersion'});

    gettingVersion.done(function(res) {

        if(res.isNew) {

            var newVersion = $('<button class="jrButton jrGreen">'+jreviews.__t('NEW_VERSION_AVAILABLE')+'</a>');

            newVersion.on('click',function(e) {

                e.preventDefault();

                jreviews.menu.load('admin_updater','index');
            });

            $('#jr-version').html(newVersion).fadeIn();
        }

    });

};

jreviews.openSiteUrl = function() {

    jrPage.on('click','a.jr-site-url, button.jr-site-url',function(e) {

        e.preventDefault();

        var el = $(this),
            data = el.data('ids');

        var submittingAction = jreviews.dispatch({method:'get',type:'text',controller:'admin/admin_reports',action:'_getSiteUrl',data: data});

        submittingAction.done(function(url) {

            if(url !== '') {

                var w = window.open( url, 'siteWindow', "width=800,height=640" );

                w.focus();
            }

        });
    });
};

jreviews.imageDelayed = function() {

    jrPage.find('img').attr('src', $(this).data('src'));

};

jreviews.datepicker = function() {

    if($().datepicker) {

        try {
            jreviews.datepickerClear();
        } catch (err) {}

        $.datepicker.setDefaults({
            showOn: 'both',
            buttonImage: jreviews.calendar_img,
            showButtonPanel: true,
            buttonImageOnly: true,
            buttonText: 'Calendar',
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });

        $('.jr-date:not(".jr_ready")').addClass('jr-ready').each(function() {
            $(this).datepicker({
                'yearRange':$(this).data('yearrange'),
                'minDate':$(this).data('mindate'),
                'maxDate':$(this).data('maxdate')
            });
        });
    }
};

jreviews.datepickerClear = function() {

    var old_fn = $.datepicker._updateDatepicker;

    $.datepicker._updateDatepicker = function(inst) {

        old_fn.call(this, inst);

        var buttonPane = $(this).datepicker("widget").find(".ui-datepicker-buttonpane");

        if(buttonPane.find('.clearDate').length === 0) {
            $("<button class='clearDate' type='button' class='ui-datepicker-clean ui-state-default ui-priority-primary ui-corner-all'>"+jreviews.__t('CLEAR_DATE')+"</button>").appendTo(buttonPane).click(function(ev) {
                $.datepicker._clearDate(inst.input);
            });
        }
    };
};

jreviews.newsFeed = function() {

    var feedDiv = jrPage.find('div#jr-news-feed');

    if(feedDiv.length) {

        var spinner = feedDiv.find('#news-spinner');

        var loadingFeed = jreviews.dispatch({method:'get',type:'html',controller:'admin/common',action:'feed'});

        loadingFeed.done(function(html){

            spinner.hide();

            feedDiv.find('#news-content').html(html).slideDown('slow');

        });

    }
};

jreviews.stats = function() {

    var loadingStats = jreviews.dispatch({method:'get',type:'json',controller:'admin/common',action:'getStats'});

    loadingStats.done(function(res) {

        $.each(res, function(key, val) {

            jrPage.find('.jr-stats-'+key).html(val);

        });

        jrPage.find('span[class^="jr-stats-"]').fadeIn();
    });

};

jreviews.page = {

    init: function() {

        var page = jrPage;

        // Prevent form from submitting on enter
        $(window).keydown(function(e) {

            if(e.keyCode === 13) {

                if($(e.target).prop('type') == 'textarea') return true;

                e.preventDefault();

                $(e.target).trigger('blur');

                return false;
            }
        });

        // Initialize ajax pagination
        jreviews.page.pageNav(page);

        // Insert ip address
        page.on('click','a.jr-insert-ip',function() {

            $(this).prev('span').find('input').val(jreviews.ipaddress);
        });

        // edit
        page.on('click','img.jr-edit, a.jr-edit, div.jr-edit, button.jr-edit',function(e) {

            e.preventDefault();

            var el = $(this),
                form = el.closest('form'),
                object_type = form.data('object-type'),
                el_object_type = el.data('object-type');

            if(el_object_type) {
                object_type = el_object_type;
            }

            jreviews[object_type].edit($(this),page);
        });

        // save settings
        page.on('click','button.jr-save-settings',function(e) {

            e.preventDefault();

            var el = $(this),
                form = el.closest('form');

            var savingSettings = jreviews.dispatch({type:'html',form:form});

            savingSettings.done(function(html) {

                if(html !== '') {

                    page.html(html).promise().done(function(){
                        jreviews.tools.statusUpdate(jreviews.__t('SETTINGS_SAVED'));

                    });
                }
                else {

                    jreviews.tools.statusUpdate(jreviews.__t('SETTINGS_SAVED'));
                }

            });
        });

        // Back button
        page.on('click','button.jr-cancel',function(e) {

            e.preventDefault();

            var currPage = page.children('div').eq(0),
                object_type = jreviews.getController(currPage,true);

            var prevPageDetached = jrAdmin.data('prevPage.'+object_type);

            // currPage.fadeOut('fast').promise().done(function() {

                currPage.find('.jr-wysiwyg-editor').RemoveTinyMCE();

                prevPageDetached.insertBefore(currPage).fadeIn('fast');

                currPage.remove();

                $.removeData(jrAdmin,'prevPage.'+object_type);
            // });
        });

        // publish
        page.on('click','button.jr-publish',function(e) {

            e.preventDefault();

            jreviews.page.publish($(this));

        });

        // toggle - generic state toggler
        page.on('click','button.jr-toggle',function(e) {

            e.preventDefault();

            jreviews.page.toggle($(this));

        });

        // delete one
        page.on('click','form#jr-page-form button.jr-delete',function(e) {

            e.preventDefault();

            var el = $(this),
                row = el.closest('.jr-layout-outer');

            page.find('.jr-row-cb').removeAttr('checked');

            row.find('.jr-row-cb').attr('checked','checked');

            jreviews.page.del($(this),page);
        });

        // delete All
        page.on('click','form#jr-page-form button.jr-delete-all',function(e) {

            e.preventDefault();

            jreviews.page.del($(this),page);
        });

        // delete in moderation page
        page.on('click','div.jr-moderation button.jr-delete-moderation',function(e) {

            e.preventDefault();

            jreviews.moderation.delModeration($(this));
        });

        // reorder
        page.on('click','a.jr-reorder',function(e) {

            e.preventDefault();

            jreviews.page.reorder($(this),page);

        });

        // update row
        page.on('update','div.jr-layout-outer',function(e) {

            e.preventDefault();

            jreviews.page.update($(this));

        });

        // popup window
        page.on('click','a.jr-popup-window',function(e){

            e.preventDefault();

            $(this).popUpWindow();
        });

        // preview
        page.on('click','.jr-preview',function(e) {

            e.preventDefault();

            // Call dialog
            var el = $(this),
                layout = el.closest('.jr-layout-outer'),
                title = layout.find('.jr-title a').html() || layout.find('.jr-title').html() || '',
                html = layout.find('.jr-preview-html').html(),
                buttons = {};

            $.jrDialog(html, {buttons:buttons,title:title,width:'640px',height:480});
        });

        // generate slugs for names
        page.on('blur','input.jr-title',function() {

            var el = $(this),
                form = el.closest('form'),
                slug_field = el.data('slug'),
                numbers = el.data('numbers') || true,
                text = el.val(),
                space_char = el.data('slug-space') !== undefined ? el.data('slug-space') : '-',
                slug;

            var slugField = form.find('.'+slug_field);

            if(slug_field && slugField.length && text !== '' && slugField.val() === '') {

                var max_length = slugField.attr('maxlength');

                slug = jreviews.tools.slug(text,{numbers:numbers,spaceReplaceChar:space_char});

                if(max_length !== undefined && max_length > 0) {

                    slug = slug.substring(0, max_length);
                }

                slugField.val(slug);
            }
        });

        page.on('blur','input.jr-name:not("[readonly]")',function() { // Don't use on field editing

            var el = $(this),
                title = page.find('.jr-title'),
                form = title.closest('form'),
                slug_field = title.data('slug'),
                numbers = title.data('numbers') || true,
                text = el.val(),
                space_char = title.data('slug-space') !== undefined ? title.data('slug-space') : '-',
                slug;

            var slugField = form.find('.'+slug_field);

            if(slug_field && slugField.length && text !== '') {

                slug = jreviews.tools.slug(text,{numbers:numbers,spaceReplaceChar:space_char});

                slugField.val(slug);
            }
        });

        // control fields
        page.on('mousemove','form:not(".jr-ready-control-field")',function() {

            jreviews.field.control_setup($(this));

            $(this).addClass('jr-ready-control-field');

        });

        // edit in place
        page.on('click','a.jr-edit-inplace',function(e) {

            e.preventDefault();

            jreviews.page.editInPlace($(this),page);

        });

        // category seo manager, automatic save on changes
        page.on('change blur','.jr-categories-seo input,.jr-categories-seo textarea',function(e) {

            e.preventDefault();

            var el = $(this),
                row = el.parents('.jr-layout-outer'),
                data = row.find('input,textarea').serializeArray();

            var submittingAction = jreviews.dispatch({method:'post',controller:'admin/categories',action:'_saveSeo',data:data});

        });

        // sortable
        page
            .on('mousemove','div.jr-sortable div.jr-layout-outer',function() {

                var sortableDiv = $('div.jr-sortable');

                var group = $(this).data('group');

                // Find all rows of the same group to get the starting order  number
                var min_order = sortableDiv.find('[data-group="'+group+'"]').first().data('order');

                sortableDiv.data('min_order',min_order);

                sortableDiv.sortable({
                    axis: 'y',
                    items: '[data-group="'+group+'"]',
                    containment: 'parent',
                    handle: '.jr-sort-handle',
                    cursor: 'move',
                    placeholder: 'ui-state-highlight',
                    forcePlaceholderSize: true,
                    stop: function(event,ui) {

                        var orderData = [];

                        page.find('div.jr-layout-outer').each(function(index,val) {

                            var el = $(this);

                            if(el.data('group') === group) {
                                el.data('order',index + min_order);

                            }

                            orderData.push({
                                id: el.data('id'),
                                order: index + min_order
                            });
                        });


                        var controller = jreviews.getController(ui.item);

                        var reordering = jreviews.dispatch({method:'post',controller:controller,action:'reorder',data:{'data[min]':min_order,'data[order]':orderData}});

                        // reordering.done(function(res) {
                            //
                        // });

                       sortableDiv.sortable('destroy');
                    }
                });

            })
            .on('mouseleave','mouseenter','div.jr-sortable div.jr-layout-outer',function() {

                page.find('div.jr-sortable').sortable('destroy');

            });

        // media edit
        page.on('click','.jr-edit-media',function() {

            jreviews.media.view($(this));
        });
    },

    loadImages: function() {

        jrPage.find('img:not(".jr-img-ready")').each(function(){

            var src = $(this).data('src');

            if(src) {

                $(this).attr('src', src).addClass('jr-img-ready');
            }
        });
    },

    publish: function(el) {

        var id = el.data('id'),
            icon = el.find('span').eq(0),
            states = el.data('states'),
            controller = jreviews.getController(el);

        var submittingAction = jreviews.dispatch({method:'get',type:'json',controller:controller,action:'_publish',data:{id:id}});

        submittingAction.done(function(res) {

            if(res.success) {
                icon.attr('class',states[res.state]);
            }
        });
    },

    pageNav: function(page) {

        page.on('click','div.jr-pagenav a',function(e) {

            e.preventDefault();

            $(this).trigger('jrPageNav');

        });

        page.on('change','div.jr-pagenav select.jr-pagenav-limit, div.jr-filters select',function(e) {

            e.preventDefault();

            $(this).trigger('jrPageNav');

        });

        page.on('blur','div.jr-filters input[type=text]:not(".jr-date")',function(e) {

            e.preventDefault();

            $(this).trigger('jrPageNav');

        });

        page.on('change','div.jr-filters .jr-date',function(e) {

            e.preventDefault();

            $(this).trigger('jrPageNav');

        });

        page.on('click','button.jr-search', function(e) {

            e.preventDefault();

            $(this).trigger('jrPageNav');

        });

        page.on('jrPageNav','div.jr-pagenav a, div.jr-pagenav select.jr-pagenav-limit, div.jr-filters select, div.jr-filters input[type=text], button.jr-search',function(e) {

            e.preventDefault();

            var el = $(this),
                form = el.closest('form'),
                pageNav = page.find('div.jr-pagenav'),
                pageFilters = page.find('div.jr-filters, div.jr-filters-search').find('input,select'),
                limit = Number($('div.jr-pagenav select.jr-pagenav-limit').first().find(':selected').text()),
                page_number = 1,
                max_page = Number(pageNav.find('a.jr-pagenav-page').last().html());

            if(el.hasClass('jr-pagenav-page')) {

                page_number = Number(el.html());
            }
            else if(el.hasClass('jr-pagenav-next')) {

                page_number = Number(el.siblings('span.jr-pagenav-current').html()) + 1;

                page_number = page_number > max_page ? page_number : page_number;
            }
            else if(el.hasClass('jr-pagenav-prev')) {

                page_number = Number(el.siblings('span.jr-pagenav-current').html()) - 1;

                page_number = page_number < 0 ? 1 : page_number;
            }

            var controller = jreviews.getController(el),
                action = form.find('input[name="data[action]"]').val() || 'index';

            var data = pageFilters.serializeArray();

            data.push({name:'page',value:page_number});

            if(isNaN(limit))
            {
                data.push({name:'limit',value:'all'});
            }
            else if(undefined !== limit) {

                data.push({name:'limit',value:limit});
            }

            var loadingPage = jreviews.dispatch({method:'get',type:'html',controller:controller,action:action,data:data});

            loadingPage.done(function(html) {

                page.html(html);
            });

        });
    },

    editInPlace: function(el,page) {

        var div = el.parent('div').first();

        var rowHeight = div.height(),
            fieldid = el.data('field-id'),
            type = el.data('type'),
            column = el.data('column'),
            label_text = el.data('label'),
            value = el.data('state') === 1 ? $.trim(el.html()) : '',
            backup_value = el.html();

        var templates = {
            text: $('<input type="text">').css({width:'95%'}),
            textarea: $('<textarea>').css({width:'95%',height:'100px'})
        };

        var label = $('<label>').html(label_text);

        var submit = $('<button class="jrButton jrSmall jrGreen"><span class="jrIconSave"></span> '+jreviews.__t('SAVE')+'</button>'),
            cancel = $('<button class="jrButton jrSmall "><span class="jrIconCancel"></span> '+jreviews.__t('CANCEL')+'</button>'),
            buttons = $('<div class="jrRight">').append(submit,'&nbsp;',cancel);

        page.find('div.jr-eip-form').remove();

        div.height(rowHeight);

        el.hide();

        submit.on('click',function(e){

            e.preventDefault();

            var controller = jreviews.getController(el);

            value = $(this).closest('.jr-eip-form').find('input, textarea').val();

            var data = {'data[fieldid]':fieldid,'data[column]':column,'data[value]':value};

            var savingValue = jreviews.dispatch({method:'post',controller:controller,action:'saveInPlace',data:data});

            savingValue.done(function() {

                page.find('div.jr-eip-form').remove();

                if(value === '') {
                    value = '<span class="jrIconEdit"></span>'+jreviews.__t('EDIT');
                }

                el.data('state',1).html(value).show();

            });

        });

        cancel.on('click',function(e){

            e.preventDefault();

            $(this).closest('div.jr-eip-form').remove();

            el.show();

        });

        if(value === '<span class="jrIconEdit"></span>'+jreviews.__t('EDIT')) {
            value = '';
        }

        var form = $('<div class="jr-eip-form jrRoundedPanel">')
                    .css({width:'200px',position:'relative',top:'-5px',left:'-50px','z-index':1000})
                    .append(label, templates[type].val(value).css('margin','5px 0'), buttons, $('<div class="jrClear">'));

        form.insertAfter(el).focus();
    },

    pageNavData: function(page){

        var pageNav = page.find('div.jr-pagenav').last(),
            pageFilters = page.find('div.jr-filters').find('input,select'),
            limit = Number(pageNav.find('select.jr-pagenav-limit').find(':selected').text()),
            page_number = Number(pageNav.find('span.jr-pagenav-current').html() || 1);

        var data = pageFilters.serializeArray();

        data.push({name:'page',value:page_number});

        if(undefined !== limit) {

            data.push({name:'limit',value:limit});
        }

        return data;
    },

    toggle: function(el) {

        var id = el.data('id'),
            data = el.data('columns'),
            icon = el.find('span').eq(0),
            states = el.data('states'),
            object_type = el.closest('form').data('object-type');

        data.id = id;

        data.object_type = object_type;

        var submittingAction = jreviews.dispatch({method:'get',type:'json',controller:'admin/common',action:'toggleState',data:data});

        submittingAction.done(function(res) {

            if(res.success) {
                icon.attr('class',states[res.state]);
            }
        });
    },

    reorder: function(el,page) {

        var data = el.data(),
            id = el.data('id'),
            row = el.closest('div.jr-layout-outer'),
            controller;

        jreviews.tools.flashRow(row);

        controller = jreviews.getController(el);

        var submittingAction = jreviews.dispatch({method:'get',type:'html',controller:controller,action:'reorder',data:data});

        submittingAction.done(function(html) {

            page.html(html).promise().done(function() {

                var row = page.find('div.jr-layout-outer[data-id="'+id+'"]');

                jreviews.tools.flashRow(row);

            });

        });
    },

    update: function(row) {

        var id = row.data('id'),
            controller;

        controller = jreviews.getController(row);

        var submittingAction = jreviews.dispatch({method:'get',type:'json',controller:controller,action:'update',data:{id:id}});

        submittingAction.done(function(data) {

            row.find('[data-key]').each(function() {

                var el = $(this),
                    key = el.data('key').split('.'),
                    type = el.data('keyType'),
                    value = data[key[0]][key[1]] || null,
                    text;

                if(value !== null) {

                    text = $.isArray(value) ? value.join(',') : value;

                    if(type == 'class') {

                        var states = el.data('states'),
                            classArray = [];

                        $.each(states,function(i,v) {

                            if ($.inArray(v, classArray) == -1) classArray.push(v);

                        });

                        if(el.is('button,a')) {

                            el.children().first().removeClass(classArray.join(' ')).addClass(states[value]);
                        }
                        else {

                            el.removeClass(classArray.join(' ')).addClass(states[value]);
                        }
                    }
                    else if(el.is(':input')) {

                        el.val(text);
                    }
                    else {

                        el.html(text);
                    }

                }

            });

            jreviews.tools.flashRow(row);
        });

        return submittingAction;

    },

    del: function(el,page) {

        var lang = el.data('lang'),
            form = $('form#jr-page-form'),
            text = el.find('span').eq(1),
            extra_params = form.data('extra-params'),
            controller;

        controller = jreviews.getController(el);

        var deleteSubmit = new $.Deferred();

        deleteSubmit.done(function(dialog) {

            var checkedRows = form.find('input.jr-row-cb:checked'),
                data = checkedRows.serializeArray();

            data.push({name:'extra_params',value:extra_params});

            var deletingListing = jreviews.dispatch({method:'get',type:'json',controller:controller,action:'_delete','data':data});

            deletingListing.done(function(res) {

                if(res.success) {

                    dialog.dialog('close');

                    jreviews.tools.removeRow(checkedRows.closest('.jr-layout-outer'));
                }
                else if(res.str.length) {

                    dialog.dialog('option','buttons',[]);

                    dialog.html(jreviews.__t(res.str));
                }
                else {

                    dialog.dialog('close');
                }
            });

        });

        // Call dialog
        var buttons = {};

        buttons[jreviews.__t('DELETE')] = function() { deleteSubmit.resolve($(this)); };

        buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

        $.jrDialog(lang.del_confirm, {buttons:buttons,title:text.html(),width:'640px'});

        var buttonPane = $('.ui-dialog-buttonpane');

        buttonPane.find('button:contains('+jreviews.__t('DELETE')+')').addClass('jrButton').prepend('<span class="jrIconDelete"></span>');

        buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');
    }

};

jreviews.about = {

    init: function() {

        var page = jrPage;

        page.on('click','button.jr-fixit',function(e) {

            e.preventDefault();

            var el = $(this);
                task = el.data('task'),
                form = el.closest('form');

            form.find('input#jr-task').val(task);

            form.submit();
        });
    }

};

jreviews.installer = {

    init: function() {

        var page = jrPage;

        page.on('click','div.jr-installer button.jr-installer-update[data-status="1"]',function(e) {

            e.preventDefault();

            jreviews.installer.update($(this), page);

        });

        page.on('click','div.jr-installer button.jr-delete-addon',function(e) {

            e.preventDefault();

            jreviews.installer.removeAddon($(this), page);

        });

        page.on('change','div.jr-installer input#jr-betas',function() {

            var data = {updater_betas: $(this).is(':checked') ? '1' : '0'};

            var loadingManifest = jreviews.dispatch({method:'get',controller:'admin/admin_updater',action:'betas',data:data});

            loadingManifest.done(function(){

                jreviews.menu.load('admin_updater','index');

            });

        });
    },

    setButtonStates: function() {

        var page = jrPage;

        page.find('div.jr-installer button[data-status="1"]').css('color','orange');

        page.find('div.jr-installer button[data-status="1"]').css('color','green');

        page.find('div.jr-installer button[data-status="0"]').css('color','red').attr('disabled','disabled');

    },

    update: function (el, page) {

        var title = el.data('title'),
            package_type = el.data('type'),
            name = el.data('name'),
            version = el.data('version'),
            backup = jQuery('#backup_confirm').is(':checked') ? 1 : 0,
            settings;

        var spinner = $("div.jr-page-spinner");

        var html = $('<div>').html(jreviews.__t('UPDATER_PACKAGE_TRANSFERING')).append('<span class="jrLoadingSmall">');

        if(!page.find('input#update_overwrite').is(':checked')) {

            $.jrAlert(jreviews.__t('UPDATER_INSTALL_CONFIRM'));
        }
        else {

            var beginUpdate = new $.Deferred(),
                extractPackage = new $.Deferred(),
                upgradePackage = new $.Deferred();

            beginUpdate.done(function() {

                var data = {type:package_type,name:name,version:version,backup:backup};

                var downloadingPackage = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_updater',action:'_installPackage',data:data});

                downloadingPackage.done(function(res) {

                    var msg = $('<p>'+jreviews.__t(res.str)+'</p>');

                    html.html(msg);

                    if(res.success === true) {

                        msg.css('color','green');

                        if(package_type == 'addon') {

                            el.val(jreviews.__t('UPDATER_INSTALLED')).css('color','red').attr('disabled','disabled');
                        }
                        else if(package_type=='component') {

                            var update_url = 'index.php?option=com_'+name+'&format=raw&update=1';

                            spinner.show();

                            extractPackage.resolve(update_url);
                        }
                    }
                    else {

                        msg.css('color','red');
                    }
                });
            });

            extractPackage.done(function(update_url) {

                var extractingPackage = $.ajax({
                    type: 'GET',
                    url: s2AjaxUriBase+'administrator/'+update_url,
                    dataType: 'json'
                });

                spinner.hide();

                extractingPackage.done(function(res){

                    if(res.success) {

                        if(name == 's2framework') {

                            html.append($('<p>'+jreviews.__t('UPDATER_COMPONENT_SUCCESS')+'<p>').css('color','green'))
                                .append($('<p>'+jreviews.__t('UPDATER_RELOAD_PAGE')+'</p>').css({'font-weight':'bold','font-size':'13px','text-decoration':'underline'}));

                            el.val(jreviews.__t('UPDATER_INSTALLED')).css('color','red').attr('disabled','disabled');

                        } else {

                            html.append($('<p>'+jreviews.__t('UPDATER_PACKAGE_EXTRACT_OK')+'<p>').css('color','green'));

                            var install_url = 'index.php?option=com_'+name+'&url=install/index/task:upgrade&format=raw';

                            spinner.show();

                            upgradePackage.resolve(install_url);
                        }
                    } else {

                        html.append($('<p>'+jreviews.__t('UPDATER_PACKAGE_EXTRACT_FAIL')+'<p>').css('color','red'));
                    }
                });
            });

            upgradePackage.done(function(install_url) {

                var upgradingPackage = $.ajax({
                    type: 'GET',
                    url: s2AjaxUriBase+'administrator/'+install_url,
                    dataType: 'json'
                });

                upgradingPackage.done(function(res) {

                    spinner.hide();

                    if(res.success) {

                        html
                            .append($('<p>'+jreviews.__t('UPDATER_COMPONENT_SUCCESS')+'<p>').css('color','green'))

                            .append($('<p>'+jreviews.__t('UPDATER_RELOAD_PAGE')+'</p>').css({'font-weight':'bold','font-size':'13px','text-decoration':'underline'}));

                        el.val(jreviews.__t('UPDATER_INSTALLED')).css('color','red').attr('disabled','disabled');

                    } else {

                        html.append($('<p>'+jreviews.__t(res.str)+'</p>').css('color','red'));
                    }
                });

            });

            // Call dialog
            var buttons = {},
                open = function() { beginUpdate.resolve();};

            $.jrDialog(html, {title: title, buttons:buttons, open:open, width:'600px'});
        }
    },

    removeAddon: function(el, page) {

        var name = el.data('name'),
            data = {name:name};

        var deleteSubmit = new $.Deferred();

        deleteSubmit.done(function(dialog) {

            var removingAddon = jreviews.dispatch({method:'get',controller:'admin/admin_updater',action:'_removeAddon',data:data});

            removingAddon.done(function(res) {

                if(res.success === true)
                {
                    dialog.dialog('option','buttons',[]);

                    dialog.html(jreviews.__t('UPDATER_ADDON_REMOVED_OK'));

                    el.val(jreviews.__t('UDPATER_REMOVED')).css('color','red').attr('disabled','disabled');

                } else {

                    dialog.dialog('option','buttons',[]);

                    dialog.html(jreviews.__t('UPDATER_ADDON_REMOVED_FAIL'));
                }
            });

        });

        // Call dialog
        var buttons = {};

        buttons[jreviews.__t('DELETE')] = function() { deleteSubmit.resolve($(this)); };

        buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

        $.jrDialog(jreviews.__t('UPDATER_ADDON_REMOVE_CONFIRM'), {buttons:buttons,title:'',width:'640px'});

        var buttonPane = $('.ui-dialog-buttonpane');

        buttonPane.find('button:contains('+jreviews.__t('DELETE')+')').addClass('jrButton').prepend('<span class="jrIconDelete"></span>');

        buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');
    }

};

jreviews.license = {

    init: function() {

        var page = jrPage;

        page.on('click','button#jr-license-submit',function(e){

            e.preventDefault();

            var el = $(this),
                form = el.closest('form');

            if($('input#jr-license-number').val() === '') {

                $.jrAlert(jreviews.__t('LICENSE_VALIDATE'));

            } else {

                form.submit();
            }
        });
    }
};

jreviews.config = {

    init: function() {

        var page = jrPage;

        page.on('click','div.jr-configuration button.jr-twitter-setup',function(e) {

            e.preventDefault();

            jreviews.config.twitterSetup($(this));

        });

        // Theme setup
        page.on('change','div.jr-configuration select#jr-site-theme',function() {

            page.find('div.jr-configuration #jr-site-theme-desc').html(page.find('#theme-'+this.value).html());
        });

        page.on('change','div.jr-configuration select#jr-mobile-theme',function() {

            page.find('div.jr-configuration #jr-mobile-theme-desc').html(page.find('#theme-'+this.value).html());
        });

        // Rss setup
        if(page.find('div.jr-configuration select#rss_image').val() === '') {

            page.find('img#jr-feed-img').hide();
        }

        page.on('change','div.jr-configuration select#rss_image',function() {

            var el = $(this);
                img = page.find('img#jr-feed-img'),
                selected = el.val();

            if(selected !== '') {

                img.attr('src', (jreviews.cms == 1 ? '../images/stories/' : '../images/') +  selected).show();

            } else {

                img.hide();
            }

        });

    },

    twitterSetup: function(el) {

        var title = el.html();

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_twitter',action:'form'});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SAVE')] = function() {

                var dialog = $(this),
                    form = dialog.find('form');

                jreviews.dispatch({form:form}).done(function() {

                    dialog.dialog('close');
                });

            };

            buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

            var dialog = $.jrDialog(html, {title: title, buttons:buttons, width:'600px'});

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SAVE')+')').addClass('jrButton jrGreen').prepend('<span class="jrIconSave"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
    }

};

jreviews.category = {

    edit: function(el,page) {

        var id = el.data('id') || 0,
            form = el.closest('form'),
            task = id > 0 ? 'edit' : 'create',
            title = el.html();

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/categories',action:task,data:{id:id}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SAVE')] = function() {

                var dialog = $(this),
                    form = dialog.find('form'),
                    buttonPane = $('.ui-dialog-buttonpane'),
                    validation = buttonPane.find('.jr-validation');

                var submittingForm = jreviews.dispatch({form:form,data:{'data[task]':task}});

                validation = validation.length ? validation : $('<div class="jr-validation jrError">');

                validation.hide();

                submittingForm.done(function(res) {

                    if(res.success) {

                        dialog.dialog('close');

                        if(res.isNew) {

                            jreviews.category.save(el,page,form);

                        }
                        else {

                            el.closest('div.jr-layout-outer').trigger('update');
                        }
                    }
                    else if(res.str.length) {

                        buttonPane.prepend(validation.html(jreviews.__t(res.str)));

                        validation.fadeIn();
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
    },

    save: function(el,page,form) {

        form = form || el.closest('form');

        var validation = form.find('div.jr-validation');

        validation.hide();

        var submittingForm = jreviews.dispatch({form:form});

        submittingForm.done(function(res) {

            if(res.success) {

                page.html(res.html).fadeIn('fast').promise().done(function() {

                    $.each(res.id,function(index,id) {

                        var row = page.find('div.jr-layout-outer[data-id="'+id+'"]');

                        jreviews.tools.flashRow(row);
                    });
                });

            }
            else if(res.str.length) {

                validation.html(jreviews.__t(res.str)).show().delay(5000).fadeOut();
            }

        });

    }

};

jreviews.directory = {

    edit: function(el,page) {

        var id = el.data('id') || 0,
            form = el.closest('form');

        if(Number(id) === 0) {

            jreviews.directory.save(el,page,form);

            return false;
        }

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/directories',action:'edit',data:{id:id}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SAVE')] = function() {

                var dialog = $(this),
                    form = dialog.find('form'),
                    submittingForm = jreviews.dispatch({form:form}),
                    buttonPane = $('.ui-dialog-buttonpane'),
                    validation = buttonPane.find('.jr-validation');

                validation = validation.length ?  validation : $('<div class="jr-validation jrError">');

                validation.hide();

                submittingForm.done(function(res) {

                    if(res.success) {

                        dialog.dialog('close');

                        el.closest('div.jr-layout-outer').trigger('update');

                    }
                    else if(res.str.length) {

                        buttonPane.prepend(validation.html(jreviews.__t(res.str)));

                        validation.fadeIn();
                    }

                });

            };

            buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

            var dialog = $.jrDialog(html, {buttons:buttons,width:'700px'});

            jreviews.field.control_setup(dialog.find('form'));

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SAVE')+')').addClass('jrButton jrGreen').prepend('<span class="jrIconSave"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
    },

    save: function(el,page,form) {

        form = form || el.closest('form');

        var validation = form.find('div.jr-validation');

        validation.hide();

        var submittingForm = jreviews.dispatch({form:form});

        submittingForm.done(function(res) {

            if(res.success) {

                page.html(res.html).fadeIn('fast').promise().done(function() {

                    var row = page.find('div.jr-layout-outer[data-id="'+res.id+'"]');

                    row.jrScrollTo({offset:-200,duration: 500});

                    jreviews.tools.flashRow(row);
                });

            }
            else if(res.str.length) {

                validation.html(jreviews.__t(res.str)).show().delay(5000).fadeOut();
            }

        });

    }

};


jreviews.field = {

    init: function() {

        var page = jrPage;

        page.on('click','button.jr-edit-fieldoptions',function(e) {

            e.preventDefault();

            jreviews.fieldoption.browse($(this),page);
        });

        page.on('click','button.jr-field-length',function(e) {

            e.preventDefault();

            jreviews.field.changeLength($(this),page);

        });

    },

    edit: function(el,page) {

        var id = el.data('id'),
            location = el.data('location') || '';

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/fields',action:'edit',data:{id:id,location:location}});

        loadingForm.done(function(html) {

            var prevPage = page.children('div').eq(0),
                currPage = $('<div>');

            currPage.insertBefore(prevPage);

            jrAdmin.data('prevPage.fields',prevPage.detach());

            currPage.html(html).jrScrollTo();

            var form = currPage.find('form'),
                id = Number(form.data('id')),
                location = form.data('location');

            // Save
            currPage.on('click','button.jr-save, button.jr-apply',function(e) {

                e.preventDefault();

                if($(this).hasClass('jr-apply')) {
                    el.data('apply',true);
                }

                jreviews.field.save(el, form, currPage);
            });

            // Render multiselect widget
            $('.jr-multiselect').multiselect({'minWidth':'200','height':'auto','selectedList':3});

            // Show/hide different parts of the form depending on the type of field
            var varchar = form.data('jr-box-varchar');

            var allElements = ['jr-box-varchar','jr-box-banner','jr-box-required','jr-box-controlfield','jr-box-description','jr-box-listsort','jr-box-click2add','jr-box-click2search','jr-box-submitaccess','jr-box-relatedlistings'];

            var disallowedElements = {
                'default': ['jr-box-varchar','jr-box-banner','jr-box-relatedlistings'],
                'banner': ['jr-box-varchar','jr-box-required','jr-box-controlfield','jr-box-description','jr-box-listsort','jr-box-click2add','jr-box-click2search','jr-box-submitaccess','jr-box-relatedlistings'],
                'checkboxes': ['jr-box-click2add','jr-box-listsort','jr-box-banner','jr-box-relatedlistings'],
                'select': ['jr-box-banner','jr-box-relatedlistings'],
                'radiobuttons': ['jr-box-click2add','jr-box-banner','jr-box-relatedlistings'],
                'selectmultiple': ['jr-box-listsort','jr-box-banner','jr-box-relatedlistings'],
                'email': ['jr-box-click2search','jr-box-click2add','jr-box-banner','jr-box-relatedlistings'],
                'website': ['jr-box-varchar','jr-box-click2search','jr-box-click2add','jr-box-banner','jr-box-relatedlistings'],
                'integer': ['jr-box-varchar','jr-box-click2add','jr-box-banner','jr-box-relatedlistings'],
                'date': ['jr-box-varchar','jr-box-click2add','jr-box-banner','jr-box-relatedlistings'],
                'decimal': ['jr-box-varchar','jr-box-click2add','jr-box-banner','jr-box-relatedlistings'],
                'textarea': ['jr-box-varchar','jr-box-click2add','jr-box-banner','jr-box-relatedlistings'],
                'text': ['jr-box-click2add','jr-box-banner','jr-box-relatedlistings'],
                'code': ['jr-box-varchar','jr-box-click2add','jr-box-banner','jr-box-relatedlistings'],
                'relatedlisting': ['jr-box-click2search','jr-box-click2add','jr-box-banner']
            };

            if(varchar === false) {

                disallowedElements.checkboxes.push('jr-box-varchar');

                disallowedElements.select.push('jr-box-varchar');

                disallowedElements.radiobuttons.push('jr-box-varchar');

                disallowedElements.selectmultiple.push('jr-box-varchar');
            }

            currPage.find('#jr-autocomplete-settings').css('display','none');

            // Field type
            currPage.find('#jr-type').on('change',function() {

                var field_type = $(this).val();

                field_type = field_type === '' ? 'default' : field_type;

                currPage.find('#jr-code-description').toggle(field_type === 'code');

                // Show/hide different parts of the form
                var boxes = disallowedElements[field_type];

                $.each(allElements,function(key,box) {

                    var show = $.inArray(box,boxes) === -1,
                        settingBox = currPage.find('#'+box);

                    if(settingBox.length) {

                        if(show) {

                            settingBox.toggle(true).find('input,textarea,select').removeAttr('disabled');
                        }
                        else {
                            settingBox.toggle(false).find('input,textarea,select').attr('disabled','disabled');
                        }
                    }
                });

                // Autocomplete UI
                var autoComplete = $.inArray(field_type,['select','selectmultiple']) > -1;

                currPage.find('#jr-autocomplete')
                    .toggle(autoComplete)
                    .children(':input')
                    .removeAttr('disabled');

                currPage.find('#jr-autocomplete-settings').toggle(autoComplete);

                // Load Advanced options
                var advancedOptions = currPage.find('#jr-advancedoptions'),
                    advancedOuter = currPage.find('#jr-box-advancedoptions');

                if(field_type !== 'default' && field_type !== 'banner') {

                    var loadingAdvancedOptions = jreviews.dispatch({method:'get',type:'html',controller:'admin/fields',action:'checkType',data:{id:id,type:field_type,location:location}});

                    loadingAdvancedOptions.done(function(html) {

                        advancedOptions.html(html);

                        advancedOuter.toggle(html !== '');
                    });

                }
                else {

                    advancedOptions.html('');

                    advancedOuter.hide();
                }

                // Process controlled fields
                var loadingControlFields = jreviews.dispatch({method:'get',type:'text',controller:'admin/fieldoptions',action:'_controlledByCheck',data:{id:id}});

                loadingControlFields.done(function(count) {

                    var fieldOptionCount = currPage.find('#jr-fieldoption-count');

                    fieldOptionCount.data('count',Number(count));

                    if(Number(count) > 0) {

                        fieldOptionCount.html(count).closest('div').fadeIn('slow');

                        currPage.one('click','input#control_field',function() {

                           $.jrAlert(__a("You need to remove the FieldOption relationships first",true));
                        });

                    }
                    else {

                        fieldOptionCount.fadeOut().closest('div').hide();

                    }
                });

             }).trigger('change');

        });

    },

    save: function(el, form, currPage) {

        var row = el.closest('div.jr-layout-outer'),
            buttons = form.find('.jr-buttons'),
            apply = el.data('apply') || false,
            validation = currPage.find('div.jr-validation'),
            data = {};

        var prevPage = jrAdmin.data('prevPage.fields');

       // Actions run on buttton press
        buttons.find('button').attr('disabled','disabled');

        validation.hide();

        data.apply = apply;

        var submittingForm = jreviews.dispatch({form:form,data:data});

        submittingForm.done(function(res) {

            if(res.success) {

                if(apply) {

                    jreviews.tools.apply();

                    el.data('apply',false);
                }
                else if(res.isNew) {

                    currPage.fadeOut().promise().done(function() {

                        prevPage.html(res.html);

                        var row = prevPage.find('div.jr-layout-outer[data-id="'+res.id+'"]');

                        prevPage.insertAfter(currPage).fadeIn('fast').promise().done(function() {

                            row.jrScrollTo({offset:-200,duration: 500});

                            jreviews.tools.flashRow(row);
                        });

                        currPage.remove();

                    });
                }
                else {

                    currPage.fadeOut('fast').promise().done(function() {

                        prevPage.insertAfter(currPage).fadeIn('fast').promise().done(function() {

                            row.trigger('update');

                            row.jrScrollTo({offset:-200,duration: 500});
                        });

                        currPage.remove();

                    });
                }
            }
            else if(res.str.length) {

                validation.html(jreviews.__t(res.str)).show();
            }

        });

        submittingForm.always(function() {

            form.find('button').removeAttr('disabled');
        });
    },

    control_setup: function(form) {

        var settings = {
            field_id        :   '',
            field_search    :   'jr-control-field-search', // class
            value_search    :   'jr-control-value-search', // class
            value_div       :   'jr-control-value-div',
            value_option    :   'jr-control-value-option', // class
            model           :   'FieldOption',
            multipleValues  : false
        };

        var fieldInput = form.find('.' + settings.field_search),
            valueInput = form.find('.' + settings.value_search),
            valueDiv = form.find('.' + settings.value_div),
            fieldLocation = form.data('location') || form.find('input.jr-field-location').val(),
            fieldid = form.data('fieldid');

        settings.model = form.data('model') || settings.model;

        // Init vars
        if(fieldInput.val() === '') {

            valueInput.attr('disabled','disabled');
        }

        fieldInput.data('old_val',fieldInput.val());

        valueDiv.on('change addcheckbox','input',function() {

            var cb = $(this);

            if(cb.not(':checked')) {

                valueInput.removeData(cb.val());

                cb.closest('label').remove();
            }

            valueDiv.toggle(valueDiv.find('input:checked').length ? true : false);

        }).trigger('change');

        // Setup the control field
        fieldInput
            .on('blur',function() {

                var value = $(this).val();

                if(value === '' || value !== fieldInput.data('old_val')) {

                    valueDiv.html('').hide();
                }

                if(value === '') {

                    valueInput.attr('disabled','disabled');
                }
                else {

                    valueInput.removeAttr('disabled');
                }

                fieldInput.data('old_val',fieldInput.val());
            })
            .autocomplete({
                minLength: 1,
                source: function(request, response) {

                    if(fieldInput.data('cache.'+request.term)) {

                        return response(fieldInput.data('cache.'+request.term));

                    }
                    else {

                        var data = {'data[limit]':12,'data[field]':request.term,'data[fieldid]':fieldid,'data[location]':fieldLocation};

                        var loadingFields = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_control_fields',action:'_loadFields',data:data});

                        loadingFields.done(function(res) {

                            fieldInput.data('cache.'+request.term, res);

                            response(res);
                        });

                    }

                },
                select: function( event, ui ) {

                    // Load relevant field values in new list
                    fieldInput.data('fieldid',ui.item.id);

                    valueInput.val('');

                    if(ui.item.id !== '') {

                        valueInput.removeAttr('disabled').focus();
                    }
                    else {

                        valueInput.attr('disabled','disabled');
                    }

                    if(ui.item.value === '' || ui.item.value !== fieldInput.data('old_val')) {

                        valueDiv.html('').hide();
                    }
                }
            });

        // Setup the control value
        valueInput
            .autocomplete({
                minLength: 1,
                source: function(request, response) {
                    // Make sure
                    // Create array of current selected checkbox values
                    var checkedValues = valueDiv.find(':input:checked').map(function(i, cb) {

                      return this.value;

                    });

                    if(valueInput.data('cache.'+request.term)) {

                        var cachedData = valueInput.data('cache.'+request.term);

                        var dataResp = [];

                        $(cachedData).each(function(i,row) {

                            if(row !== undefined) {

                                if($.inArray(row.value,checkedValues) === -1) {

                                    dataResp.push(cachedData[i]);
                                }
                            }
                        });

                        return response(dataResp);
                    }
                    else {

                        var data = {'data[limit]':12,'data[field_id]':fieldInput.data('fieldid'),'data[value]':request.term};

                        var loadingValues = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_control_fields',action:'_loadValues',data:data});

                        loadingValues.done(function(res) {

                            valueInput.data('cache.' + request.term, res);

                            var dataResp = [];

                            $(res).each(function(i,row) {

                                if(row !== undefined) {

                                    if($.inArray(row.value,checkedValues) === -1) {

                                        dataResp.push(res[i]);
                                    }
                                }
                            });

                            response(dataResp);
                        });

                    }
                },
                search: function( event, ui) {

                    if(valueInput.data('field_id') === '') {

                        return false;
                    }

                },
                select: function( event, ui ) {

                    if(ui.item.value !== '') {

                        var checkboxAttr = {
                            'class':settings.value_option+'-'+ui.item.value,
                            'name':'data['+settings.model+'][control_value][]',
                            'value':ui.item.value
                        };

                        var checkbox = $('<input type="checkbox" checked="checked" value="'+ui.item.value+'" />').attr(checkboxAttr);

                        checkbox.click(function() {

                            valueInput.removeData(ui.item.value);
                        });

                        var label = $('<label for="'+settings.value_option+'-'+ui.item.value+'" />').css('text-align','left');

                        label.append(checkbox).append('<span>'+ui.item.label+'</span>');

                        valueDiv.show().append(label);
                    }

                    valueInput.val('').focus();

                    return false;
                }
            });

        $('.ui-autocomplete').css('white-space','nowrap');
    },

    changeLength:function(el,page) {

        var id = el.data('id'),
            form = el.closest('form');

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/fields',action:'_changeFieldLength',data:{id:id,task:'form'}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SAVE')] = function() {

                var dialog = $(this),
                    form = dialog.find('form'),
                    validation = buttonPane.find('.jr-validation');

                validation = validation.length ? validation : $('<div class="jr-validation jrError">');

                var submittingForm = jreviews.dispatch({form:form});

                submittingForm.done(function(res) {

                    if(res.success) {

                        dialog.dialog('close');

                        page.find('#max-length-value').html(res.maxlength);

                    }
                    else if(res.str.length) {

                        buttonPane.prepend(validation.html(jreviews.__t(res.str)));

                        validation.fadeIn();
                    }

                });

            };

            buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

            var dialog = $.jrDialog(html, {buttons:buttons,width:'800'});

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SAVE')+')').addClass('jrButton jrGreen').prepend('<span class="jrIconSave"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
    }
};

jreviews.fieldoption = {

    browse: function(el,page) {

        var fieldid = el.data('id');

        var submittingAction = jreviews.dispatch({method:'get',type:'html',controller:'admin/fieldoptions',action:'index',data:{id:fieldid}});

        var checkControlFields = new $.Deferred();

        checkControlFields.done(function(page, id) {

            var checkingControlFields = jreviews.dispatch({method:'get',type:'text',controller:'admin/fields',action:'_controlledByCheck',data:{id:id}});

            checkingControlFields.done(function(count) {

                page.find('#control-field-check').toggle(Number(count) > 0);

                if(Number(count) > 0) {
                    page.find('#control_field_new').one('click',function() {
                       $.jrAlert("You need to remove the Field relation first");
                    });
                }

            });
        });

        submittingAction.done(function(html) {

            var prevPage = jrPage.children('div').eq(0),
                currPage = $('<div>');

            jreviews.field.prevPage = prevPage;

            currPage.insertBefore(prevPage);

            jrAdmin.data('prevPage.fieldoptions',prevPage.detach());

            currPage.html(html);

            currPage.on('click','button.jr-clear',function(e) {

                e.preventDefault();

                var form = $(this).closest('form');

                form.find('input[type=text]').each(function() {
                    $(this).val('');
                });

                form.find('div.jr-control-value-div').html('').hide();
            });

            checkControlFields.resolve(page, fieldid);
        });

    },

    edit: function(el,page) {

        var id = el.data('id') || 0,
            form = el.closest('form');

        if(id === 0) {

            jreviews.fieldoption.save(el,page,form);

            return false;
        }

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/fieldoptions',action:'edit',data:{id:id}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SAVE')] = function() {

                var dialog = $(this),
                    form = dialog.find('form'),
                    buttonPane = $('.ui-dialog-buttonpane'),
                    validation = buttonPane.find('.jr-validation');

                var submittingForm = jreviews.dispatch({form:form});

                validation = validation.length ? validation : $('<div class="jr-validation jrError">');

                submittingForm.done(function(res) {

                    if(res.success) {

                        dialog.dialog('close');

                        el.closest('div.jr-layout-outer').trigger('update');

                    }
                    else if(res.str.length) {

                        buttonPane.prepend(validation.html(jreviews.__t(res.str)));

                        validation.fadeIn();
                    }

                });

            };

            buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

            var dialog = $.jrDialog(html, {buttons:buttons,width:'800px'});

            jreviews.field.control_setup(dialog.find('form'));

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SAVE')+')').addClass('jrButton jrGreen').prepend('<span class="jrIconSave"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
    },

    save: function(el,page,form) {

        form = form || el.closest('form');

        var validation = form.find('div.jr-validation');

        validation.hide();

        var submittingForm = jreviews.dispatch({form:form});

        submittingForm.done(function(res) {

            if(res.success) {

                var controlField = form.find('.jr-control-field-search').val(),
                    controlValueDivHTML = form.find('.jr-control-value-div').html(),
                    controlFieldId = form.find('.jr-control-field-search').data('fieldid');

                page.html(res.html).fadeIn('fast').promise().done(function() {

                    var row = page.find('div.jr-layout-outer[data-id="'+res.id+'"]');

                    page.find('.jr-control-field-search').val(controlField);

                    page.find('.jr-control-value-div').html(controlValueDivHTML);

                    if(controlField !== '') {

                        page.find('.jr-control-field-search').data('fieldid',controlFieldId);
                    }

                    if(row.length) {

                        row.jrScrollTo({offset:-200,duration: 500});

                        jreviews.tools.flashRow(row);
                    }

                });

            }
            else if(res.str.length) {

                validation.html(jreviews.__t(res.str)).show().delay(5000).fadeOut();
            }

        });

    }
};

jreviews.group = {

    init: function() {

        var page = jrPage;

        page.on('click','button.jr-clear',function(e) {

            e.preventDefault();

            var form = $(this).closest('form');

            form.find('input[type=text]').each(function() {
                $(this).val('');
            });

            form.find('div.jr-control-value-div').html('').hide();
        });

        page.on('grouplocation change','form select#jr-group-type',function(e) {

            e.preventDefault();

            $(this).next('input').val($(this).val());

            page.find('form span#jr-group-type-label').html($(this).find('option:selected').text());

            page.find('form input#jr-group-type-hidden').val($(this).find('option:selected').val());

        }).trigger('change');

    },

    changeLocation: function() {

        jrPage.find('form select#jr-group-type').trigger('grouplocation');

    },

    edit: function(el,page) {

        var id = el.data('id') || 0,
            form = el.closest('form');

        if(Number(id) === 0) {

            jreviews.group.save(el,page,form);

            return false;
        }

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/groups',action:'edit',data:{id:id}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SAVE')] = function() {

                var dialog = $(this),
                    form = dialog.find('form'),
                    submittingForm = jreviews.dispatch({form:form}),
                    buttonPane = $('.ui-dialog-buttonpane'),
                    validation = buttonPane.find('.jr-validation');

                validation = validation.length ?  validation : $('<div class="jr-validation jrError">');

                validation.hide();

                submittingForm.done(function(res) {

                    if(res.success) {

                        dialog.dialog('close');

                        el.closest('div.jr-layout-outer').trigger('update');

                    }
                    else if(res.str.length) {

                        buttonPane.prepend(validation.html(jreviews.__t(res.str)));

                        validation.fadeIn();
                    }

                });

            };

            buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

            var dialog = $.jrDialog(html, {buttons:buttons,width:'960px'});

            jreviews.field.control_setup(dialog.find('form'));

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SAVE')+')').addClass('jrButton jrGreen').prepend('<span class="jrIconSave"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
    },

    save: function(el,page,form) {

        form = form || el.closest('form');

        var validation = form.find('div.jr-validation');

        validation.hide();

        var submittingForm = jreviews.dispatch({form:form});

        submittingForm.done(function(res) {

            if(res.success) {

                page.html(res.html).fadeIn('fast').promise().done(function() {

                    var row = page.find('div.jr-layout-outer[data-id="'+res.id+'"]');

                    row.jrScrollTo({offset:-200,duration: 500});

                    jreviews.tools.flashRow(row);
                });

            }
            else if(res.str.length) {

                validation.html(jreviews.__t(res.str)).show().delay(5000).fadeOut();
            }

        });

    }
};

jreviews.media = {

    init: function() {

        // Media Upload Form
        var page = jrPage,
            uploadPage = page.find('div.jr-media-upload');

        if(uploadPage.length){

            var mediaUploadForm  = uploadPage.find('form#jr-form-media-upload'),
                fileValidation = mediaUploadForm.data('file-validation');

            jreviewsMedia.initUploader('jr-media-uploader',{fileValidation: fileValidation});
        }

    },

    browse: function() {

        var page = jrPage;

        // filter media from review and listing browse pages
        page.on('click','a.jr-media-filter, button.jr-media-filter',function(e) {

            e.preventDefault();

            var el = $(this),
                listing_id = el.data('listing-id') || 0,
                review_id = el.data('review-id') || 0,
                user_id = el.data('user-id') || 0,
                extension = el.data('extension') || 'com_content',
                data;

            data = {listing_id:listing_id,review_id:review_id,user_id:user_id,extension:extension};

            var prevPage = page.children('div').eq(0),
                currPage = $('<div>');

            currPage.insertBefore(prevPage);

            jrAdmin.data('prevPage.admin_media',prevPage.detach());

            jreviews.menu.load('admin_media','browse',data);
        });

        // set main media
        page.on('click','button.jr-media-main',function(e){

            e.preventDefault();

            jreviews.media.setMain($(this),page);
        });
    },

    download: function(media_id, token1, token2) {

         $('#jrDownload'+media_id).remove();

        var iframe = $('<iframe id="jrDownload'+media_id+'" style="display:none;"></iframe>');

        iframe.attr('src',s2AjaxUri+'&url=admin_media/download&m='+media_id+'&'+token1+'=1&'+token2+'=1').appendTo('body');
    },

    edit: function(el, page) {

        var id = el.data('id') || 0,
            title = ''; /*el.html();*/

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_media',action:'edit',data:{id:id}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SAVE')] = function() {

                var dialog = $(this),
                    form = dialog.find('form');

                var submittingForm = jreviews.dispatch({form:form});

                submittingForm.done(function(res) {

                    if(res.success) {

                        dialog.dialog('close');

                        el.closest('div.jr-layout-outer').trigger('update');
                    }
                    if(res.str.length) {

                        dialog.dialog('option','buttons',[]);

                        dialog.html(jreviews.__t(res.str));
                    }
                });

            };

            buttons[jreviews.__t('CANCEL')] = function() {

                $(this).dialog('close');

                $(this).find('#jr-video-player').trigger('jrVideoDestroy');

                $(this).find('#jr-audio-player').remove();
            };

            var close = function() {

                $(this).find('#jr-video-player').trigger('jrVideoDestroy');

                $(this).find('#jr-audio-player').remove();
            };

            var open = function() {

                var dialog = $(this),
                    videoGallery = dialog.find('div.jr-video-gallery'),
                    audio = dialog.find('#jr-audio-player'),
                    thumbnailDiv = dialog.find('.jr-thumbnails');

                if(thumbnailDiv.length) {

                    thumbnailDiv.find('.jr-thumb-del')
                        .on('click',function() {

                            var el = $(this);

                            if(el.data('delete') == 1) {

                                el.data('delete',0); // Prevent multiple clicks

                                var data = {id:el.data('id'), size:el.data('size')};

                                var deletingThumb = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_media',action:'_deleteThumb',data:data});

                                deletingThumb.done(function(res) {

                                    if(res.success) el.fadeOut(500);
                                });
                            }

                        })
                        .on('mouseenter',function() {

                            var thumb = $(this);

                            var delButton = $('<span class="jrIconDelete jrIconOnly">');

                            $(this)
                                .data('delete',1)
                                .data('contents',$(this).html())
                                .css('width',$(this).width()+'px')
                                .css('height',$(this).height()+'px')
                                .html(delButton);
                        })
                        .on('mouseleave',function() {

                            $(this)
                                .data('delete',0)
                                .html($(this).data('contents'));
                        });

                }

               if(videoGallery.length) {

                    jreviewsMedia.videoGallery.init();
                }

                if(audio.length) {

                    jreviewsMedia.audioPlayer();
                }

                jreviews.userAutocomplete(dialog.find('form'));
            };

            var dialog = $.jrDialog(html, {title: title, open: open, close: close, buttons:buttons, width:'800px'});

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SAVE')+')').addClass('jrButton jrGreen').prepend('<span class="jrIconSave"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
     },

     setMain:function(el,page) {

        var icon = el.find('span').eq(0),
            listing_id = el.data('listing-id'),
            media_id = el.data('id'),
            states = el.data('states');

        var data = {
            listing_id:listing_id,
            media_id:media_id
        };

        el.attr('disabled','disabled');

        var settingMainMedia = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_media',action:'setMainMedia',data:data});

        settingMainMedia.done(function(res){

            if(res.success === true) {

                var process = function() {

                    page.find('button.jr-media-main').not(el).removeAttr('disabled').each(function() {

                        $(this).find('span').eq(0).attr('class',states[0]);

                    });
                };

                $.when( process() ).done(function() {

                    icon.attr('class',states[1]);

                });
            }

        });
     }
};

jreviews.listing = {

    browse: function() {

        var page = jrPage;

        // feature jreviews
        page.on('click','div.jr-listings-list button.jr-feature',function(e) {

            e.preventDefault();

            jreviews.listing.feature($(this));

        });

        // claim status
        page.on('click','div.jr-listings-list button.jr-claim',function(e) {

            e.preventDefault();

            jreviews.listing.claimStatus($(this));
        });

        // access
        page.on('click','div.jr-listings-list a.jr-access',function(e) {

            e.preventDefault();

            jreviews.listing.access($(this));
        });
    },

    feature: function(el) {

        var id = el.data('id'),
            icon = el.find('span').eq(0),
            states = el.data('states');

        var submittingAction = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_listings',action:'_feature',data:{id:id}});

        submittingAction.done(function(res) {

            if(res.success) {
                icon.attr('class',states[res.state]);
            }
        });
    },

    claimStatus: function(el) {

        var text = el.find('span').eq(1),
            id = el.data('id'),
            user_id = el.data('user-id'),
            states = el.data('states'),
            referrer = el.data('referrer'),
            row = el.closest('div.jr-layout-outer'),
            classes = [];

        classes = $.map(states,function(v) { return v;}).join(' ');

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_claims',action:'edit',data:{id:id,user_id:user_id,referrer:referrer}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SAVE')] = function() {

                var dialog = $(this),
                    form = dialog.find('form');

                var submittingForm = jreviews.dispatch({form:form});

                submittingForm.done(function(res) {

                    dialog.dialog('close');

                    el.removeClass(classes);

                    if(form.find('select[name="data[Claim][approved]"]').val() == 1) {

                        el.addClass(states[res.state]);
                    }

                    row.trigger('update');
                });

            };

            buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

            var dialog = $.jrDialog(html, {buttons:buttons,title:text.html(),width:'640px'});

            jreviews.userAutocomplete(dialog.find('form'));

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SAVE')+')').addClass('jrButton jrGreen').prepend('<span class="jrIconSave"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
    },

    access: function(el) {

        var id = el.data('id'),
            states = el.data('states'),
            link = el.find('span').eq(0),
            lang = el.data('lang');

        var submittingAction = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_listings',action:'_changeAccess',data:{id:id}});

        submittingAction.done(function(res) {

            if(res.success) {
                link.attr('class',states[jreviews.cms == 1 ? res.state - 1 : res.state]);
                link.html(lang[jreviews.cms == 1 ? res.state - 1 : res.state]);
            }
        });
    },

    edit: function (el,page) {

        var id = el.data('id'),
            referrer = el.data('referrer');

        referrer = referrer || 'browse';

        var pageSpinner = $('.jr-page-spinner').show();

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_listings',action:'edit',data:{id:id,referrer:referrer}});

        loadingForm.done(function(html) {

            var prevPage = page.children('div').eq(0),
                currPage = $('<div>');

            currPage.insertBefore(prevPage);

            jrAdmin.data('prevPage.admin_listings',prevPage.detach());

            currPage.html(html).find('.jr-tabs').tabs();

            var form = currPage.find('form'),
                listing_id = form.find('#listing_id').val(),
                save = form.find('button.jr-save'),
                saveNew = form.find('button.jr-new');

            form.find('.jr-title').focus();

            form.find('.jr-wysiwyg-editor').tinyMCE();

            // Save
            save.on('click',function(e) {

                e.preventDefault();

                jreviews.listing.save(el, form, currPage);
            });

            // Save as new
            saveNew.on('click',function(e) {

                e.preventDefault();

                form.append($('<input name="data[saveAsNew]" value="1">')).data('isNew',true);

                jreviews.listing.save(el, form, currPage);
            });

            jreviews.userAutocomplete(form);

            var loadingFields = form.jreviewsFields({'entry_id':listing_id,'value':false,'page_setup':true});

            if(loadingFields) {

                loadingFields.done(function() {

                    prevPage.fadeOut('fast').promise().done(function() {

                        currPage.fadeIn('fast');

                        currPage.jrScrollTo();

                        pageSpinner.hide();
                    });
                });
            }

        });

    },

    save: function(el, form, currPage) {

        var row = el.closest('div.jr-layout-outer'),
            buttons = form.find('.jr-buttons'),
            editorAreas = form.find('.jr-wysiwyg-editor'),
            validation = form.find('.jr-validation'),
            count = 0,
            selected = [],
            category,
            parent_category;

        var prevPage = jrAdmin.data('prevPage.admin_listings');

        $("select[id^=cat_id]").each(function() {

            var value = $(this).val();

            if(value > 0) {
                selected.push($(this));
            }
        });

        count = selected.length;

        if(count === 1)
        {
            form.find('#category').val(selected[0].find('option:selected').text().replace(/(- )+/,''));
        }
        else if(count > 1)
        {
            form.find('#category').val(selected[count-1].find('option:selected').text().replace(/(- )+/,''));
            form.find('#parent_category').val(selected[count-2].find('option:selected').text().replace(/(- )+/,''));
        }

        /* end copy text of selected cat to hidden fields for use in Geomaps */

        try {
            editorAreas.RemoveTinyMCE();

        }
        catch(err) {}

        // Set valid custom fields
        form.jrSetValidFields().join(',');

        // Actions run on buttton press
        buttons.find('button').attr('disabled','disabled');

        validation.hide();

        var submittingForm = jreviews.dispatch({form:form});

        submittingForm.done(function(res) {

            if(res.success === true) {

                if(res.isNew) {

                    prevPage.remove();

                    currPage.html(res.html);

                    row = currPage.find('div.jr-layout-outer[data-id="'+res.id+'"]');

                    row.jrScrollTo({offset:-200,duration: 500});

                    jreviews.tools.flashRow(row);
                }
                else {

                    currPage.fadeOut('fast').promise().done(function() {

                        prevPage.insertAfter(currPage).fadeIn('fast').promise().done(function() {

                            row.trigger('update');

                            row.jrScrollTo({offset:-200,duration: 500});
                        });

                        currPage.remove();

                    });

                }
            }
            else if(res.str.length) {

                validation.html(jreviews.__t(res.str)).show();

                editorAreas.tinyMCE();
            }

        });

        submittingForm.always(function() {

            form.find('button').removeAttr('disabled');
        });
    }

};

jreviews.review = {

    browse: function() {

        var page = jrPage;

        // review type
        page.on('click','div.jr-reviews-list button.jr-review-type',function(e) {

            e.preventDefault();

            jreviews.review.reviewType($(this));
        });

        // owner reply
        page.on('click','div.jr-reviews-list button.jr-owner-reply',function(e) {

            e.preventDefault();

            jreviews.review.ownerReply($(this));
        });

        // filter review from review and listing browse pages
        page.on('click','.jr-review-filter, .jr-media-filter',function(e) {

            e.preventDefault();

            var el = $(this),
                listing_id = el.data('listing-id') || 0,
                type = el.data('review-type') || '',
                user_id = el.data('user-id') || 0,
                extension = el.data('extension') || 'com_content',
                filter = el.data('filter') || '',
                referrer = el.data('referrer') || '',
                data;

            data = {listing_id:listing_id,type:type,user_id:user_id,extension:extension,filter_order:filter,referrer:referrer};

            jreviews.menu.load('admin_reviews','browse',data);
        });
    },

    edit: function (el,page) {

        var id = el.data('id'),
            referrer = el.data('referrer');

        referrer = referrer || 'browse';

        var pageSpinner = $('.jr-page-spinner').show();

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_reviews',action:'edit',data:{id:id,referrer:referrer}});

        loadingForm.done(function(html) {

            var prevPage = page.children('div').eq(0),
                currPage = $('<div>');

            currPage.insertBefore(prevPage);

            jrAdmin.data('prevPage.admin_reviews',prevPage.detach());

            currPage.html(html);

            var form = currPage.find('form'),
                save = form.find('button.jr-save'),
                cancel = form.find('button.jr-cancel');

            form.find('.jr-title').focus();

            jreviews.review.starRating(form);

            // Save
            save.on('click',function(e) {

                e.preventDefault();

                jreviews.review.save(el, form, currPage);
            });

            jreviews.userAutocomplete(form);

            var loadingFields = form.jreviewsFields({'fieldLocation':'Review','entry_id':id,'value':false,'page_setup':true});

            if(loadingFields) {

                loadingFields.done(function() {

                    prevPage.fadeOut('fast').promise().done(function() {

                        currPage.fadeIn('fast');

                        currPage.jrScrollTo();

                        pageSpinner.hide();
                    });
                });
            }
        });

    },

   save: function(el, form, currPage) {

        var row = el.closest('div.jr-layout-outer'),
            buttons = form.find('.jr-buttons'),
            validation = form.find('.jr-validation');

        var prevPage = jrAdmin.data('prevPage.admin_reviews');

        // Set valid custom fields
        form.jrSetValidFields().join(',');

        // Actions run on buttton press
        buttons.find('button').attr('disabled','disabled');

        validation.hide();

        var submittingForm = jreviews.dispatch({form:form});

        submittingForm.done(function(res)
        {
            // Validation
            if(res.success) {

                currPage.fadeOut('fast').promise().done(function() {

                    if(res.html) {

                        var review = $(res.html);

                        row.html(review.html());
                    }

                    prevPage.insertAfter(currPage).fadeIn('fast').promise().done(function() {

                        row.trigger('update');

                        row.jrScrollTo({offset:-200,duration: 500});

                    });

                    currPage.remove();

                });

            }
            else if (res.str.length) {

                validation.html(jreviews.__t(res.str));
            }

        });

        submittingForm.always(function() {

            form.find('button').removeAttr('disabled');
        });
    },

    reviewType: function(el) {

        var id = el.data('id'),
            icon = el.find('span').eq(0),
            states = el.data('states'),
            lang = el.data('lang');

        var submittingAction = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_reviews',action:'changeReviewType',data:{id:id}});

        submittingAction.done(function(res) {

            if(res.success) {

                icon.attr('class',states[res.state]);
            }
            else if(res.msg in lang) {

                $.jrAlert(lang[res.msg]);
            }
            else if(res.str.length) {

                $.jrAlert(jreviews.__t(res.str));
            }
        });
    },

    ownerReply: function(el) {

        var text = el.find('span').eq(1),
            id = el.data('id'),
            states = el.data('states'),
            referrer = el.data('referrer'),
            classes = [];

        classes = $.map(states,function(v) { return v;}).join(' ');

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_owner_replies',action:'edit',data:{id:id,referrer:referrer}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SAVE')] = function() {

                var dialog = $(this),
                    form = dialog.find('form');

                var submittingForm = jreviews.dispatch({form:form});

                submittingForm.done(function(res) {

                    dialog.dialog('close');

                    el.removeClass(classes);

                    if(form.find('textarea[name="data[OwnerReply][owner_reply_text]"]').val() !== '') {

                        el.addClass(states[res.state]);
                    }

                    jreviews.tools.flashRow(el.closest('.jr-layout-outer'));

                });

            };

            buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

            $.jrDialog(html, {buttons:buttons,title:text.html(),width:'640px'});

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SAVE')+')').addClass('jrButton jrGreen').prepend('<span class="jrIconSave"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
    },

    starRating: function(form) {

        form.find('.jr-rating-stars').each(function() {

            var el = $(this),
                selector = el.data('selector'),
                inc = el.data('inc'),
                wrapper = $('<span></span>');

            if(selector === 'stars') {

                el.parent().next().append(wrapper);

                var splitStars = 1/inc; // 2 for half star ratings

                el.stars({
                    split: splitStars,
                    captionEl: wrapper
                });

            }
        });
    }
};

jreviews.listing_type = {

    init: function() {

        var page = jrPage;

        /*** LIST PAGE **/

        // copy
        page.on('click','button.jr-copy',function(e) {

            e.preventDefault();

            var el = $(this),
                copies = 1,
                id = page.find('input[type=radio]:checked').val();

            var submittingAction = jreviews.dispatch({method:'get',controller:'admin/listing_types',action:'_copy',data:{id:id,'copies':copies}});

            submittingAction.done(function(res) {

                if(res.success) {

                    page.find('div#jr-table').html(res.html);

                    var row = page.find('div.jr-layout-outer[data-id="'+res.id+'"]');

                    row.jrScrollTo({offset:-200,duration: 500});

                    jreviews.tools.flashRow(row);

                }
                else if (res.str.length) {

                    $.jrAlert(jreviews.__t(res.str));
                }
            });
        });

        // sync ratings
        page.on('click','button.jr-sync-ratings',function(e) {

            e.preventDefault();

            var submittingAction = jreviews.dispatch({method:'get',type:'html',controller:'admin/listing_types',action:'refreshReviewRatings'});

            submittingAction.done(function(html) {

                $.jrAlert(html);
            });
        });

    },

    edit: function(el,page) { // and edit

        var id = el.data('id') || 0;

        var loadingPage = jreviews.dispatch({method:'get',type:'html',controller:'admin/listing_types',action:'edit',data:{id:id}});

        loadingPage.done(function(html) {

            var prevPage = page.children('div').eq(0),
                currPage = $('<div>');

            currPage.insertBefore(prevPage);

            jrAdmin.data('prevPage.listing_types',prevPage.detach());

            currPage.html(html).jrScrollTo();

            var form = currPage.find('form');

            // Save
            currPage.on('click','button.jr-save, button.jr-apply',function(e) {

                e.preventDefault();

                if($(this).hasClass('jr-apply')) {
                    el.data('apply',true);
                }

                jreviews.listing_type.save(el, form, currPage);
            });

            currPage.on('change','.jr-field-matches',function() {

                if($(this).val() === 'diff_field') {
                    $(this).parents('div:eq(1)').next('div').slideDown();
                }
                else {
                    $(this).parents('div:eq(1)').next('div').slideUp();
                }

            });

            currPage.find('.jr-tabs').tabs();

            currPage.find('.jr-field-matches').trigger('change');

            currPage.find('.jr-multiselect').multiselect({'minWidth':200,'height':'auto','selectedList':3});

            currPage.find('.multiselectGroup').multiselect({'minWidth':300,'height':'auto','selectedList':3});

            var ratings = currPage.find('div.jr-ratings'),
                template = $.trim(currPage.find('script#jr-rating-template').html()),
                globalSetting = currPage.find('input.global-cb');

            // Add rating criteria
            currPage.on('click','button.jr-add-rating',function(e) {

                e.preventDefault();

                ratings.append(template).find('div.jr-layout').last().fadeIn();
            });

            // Remove rating criteria
            ratings.on('click','button.jr-remove-rating',function(e) {

                e.preventDefault();

                if(ratings.find('div.jr-layout').length > 1) {

                    $(this).closest('div.jr-layout').slideUp('fast').promise().done(function() {

                        $(this).remove();

                        ratings.find('input.jr-weights').trigger('sumWeights');

                    });

                }
            });

            // Set rating as required
            ratings.on('click','input.jr-rating-required',function(e) {

                var checked = $(this).is(':checked');

                $(this).prev('input').val(checked ? 1 : 0);
            });

            // Calculate weight sum
            ratings.on('blur sumWeights','input.jr-weights',function() {

                var sum = 0,
                    total = currPage.find('div.jr-sum-weights');

                ratings.find('input.jr-weights').each(function() {
                    sum += Number($(this).val());
                });

                total.html(sum.toFixed(0));

                total.removeClass('jrRed jrGreen jrOrange').addClass('jrStatusLabel');

                var totalNumber = Number(total.html());

                if(totalNumber > 100) {

                    total.addClass('jrRed');
                }
                else if (totalNumber === 100) {

                    total.addClass('jrGreen');
                }
                else if (totalNumber > 0 && totalNumber < 100) {

                    total.addClass('jrOrange');
                }

            });

            ratings.find('input.jr-weights').trigger('sumWeights');

            // Initialize global settings checkboxes
            currPage.on('click','input.global-cb',function() {

                jreviews.listing_type.processGlobals($(this));
            });

            currPage.find('input.global-cb:checked').each(function() {

                jreviews.listing_type.processGlobals($(this));
            });

        });
    },

    processGlobals: function(el) {

       var  inputs = el.closest('label').next(),
            setting = inputs.find('input,select').eq(0),
            globalSetting;

        if(el.data('globalSetting') === undefined) {

           globalSetting = $('<input type="hidden" value="-1" />').attr('name',setting.attr('name'));

           el.data('globalSetting',globalSetting);

           inputs.after(globalSetting);
        }
        else {

            globalSetting = el.data('globalSetting');
        }

        var select = inputs.find('select');

        if(el.is(':checked') === true) {

            inputs.find('input,select,button').attr('disabled','disabled');

            if(select.length) {

                if(select.multiselect && (select.hasClass('jr-multiselect') || select.hasClass('multiselectGroup'))) {

                    select.multiselect();

                    select.multiselect('disable');
                }
            }

            globalSetting.removeAttr('disabled');

        }
        else {

            inputs.find('input,select,button').removeAttr('disabled');

            if(select.length) {

                if(select.multiselect && (select.hasClass('jr-multiselect') || select.hasClass('multiselectGroup'))) {

                    select.multiselect();

                    select.multiselect('enable');
                }
            }

            globalSetting.attr('disabled','disabled');
        }
    },

    save: function(el, form, currPage) {

        var row = el.closest('div.jr-layout-outer'),
            buttons = currPage.find('.jr-toolbar button').attr('disabled','disabled'),
            id = el.data('id'),
            apply = el.data('apply') || false,
            validation = currPage.find('div.jr-validation');

        var prevPage = jrAdmin.data('prevPage.listing_types');

        validation.hide();

        var submittingForm = jreviews.dispatch({form:form,data:{apply:apply}});

        submittingForm.done(function(res) {

            if(res.success === true) {

                if(apply) {

                    jreviews.tools.apply();

                    el.data('apply',false);
                }
                else if(res.isNew) {

                    currPage.fadeOut().promise().done(function() {

                        prevPage.html(res.html);

                        var row = prevPage.find('div.jr-layout-outer[data-id="'+res.id+'"]');

                        prevPage.insertAfter(currPage).fadeIn('fast').promise().done(function() {

                            row.jrScrollTo({offset:-200,duration: 500});

                            jreviews.tools.flashRow(row);
                        });

                        currPage.remove();

                    });
                }
                else {

                    currPage.fadeOut('fast').promise().done(function() {

                        prevPage.insertAfter(currPage).fadeIn('fast').promise().done(function() {

                            row.trigger('update');

                            row.jrScrollTo({offset:-200,duration: 500});
                        });

                        currPage.remove();
                    });
                }
            }
            else if(res.str.length) {

                validation.html(jreviews.__t(res.str)).show();

            }

        });

        submittingForm.always(function() {

            buttons.removeAttr('disabled');

        });

    }
};

jreviews.moderation = {

    init: function() {

        var page = jrPage;

        // Submit moderation action
        page.on('click','div.jr-moderation button.jr-submit',function(e) {

            e.preventDefault();

            jreviews.moderation.moderate($(this));

        });

        // predefined replies
        page.on('change','div.jr-moderation select.jr-select-reply',function(e) {

            e.preventDefault();

            jreviews.moderation.showPredefinedReply($(this));

        });

        // toggle predefined replies form
        page.on('change','div.jr-moderation .jr-reply input',function(e) {

            e.preventDefault();

            var el = $(this),
                form = el.closest('.jr-layout-outer').find('.jr-form-reply');

            form.slideToggle();

        });

        // load more
        page.on('click','div.jr-moderation div.jr-load-more',function(e) {

            e.preventDefault();

            jreviews.moderation.loadMore($(this));
        });
    },

    showPredefinedReply: function(el) {

        var reply_id = el.val(),
            form = el.closest('form'),
            replies = $('div.jr-moderation .jr-predefined-replies');
            replySubject = form.find('input[name=data\\[Email\\]\\[subject\\]]'),
            replyBody = form.find('.jr-reply-body');

        if(reply_id !== '') {

            replyBody.val(replies.find('#jr-predefined-reply-'+reply_id).html());

            replySubject.val(el.find(':selected').text());
        }
        else{

            replyBody.val('');

            replySubject.val('');
        }
    },

    delModeration: function(el) {

        var lang = el.data('lang'),
            outerLayout = el.closest('div.jr-moderation'),
            processed = outerLayout.data('processed') || 0,
            id = el.data('id'),
            text = el.find('span').eq(1),
            object_type = el.closest('form').data('object-type'),
            controller = jreviews.getController(el);

        var deleteSubmit = new $.Deferred();

        deleteSubmit.done(function(dialog) {

            var deletingObject = jreviews.dispatch({method:'get',type:'json',controller:controller,action:'_delete','data':{cid:[id]}});

            deletingObject.done(function(res) {

                if(res.success) {

                    dialog.dialog('close');

                    // Decrease moderation counter
                    var counter = $('#'+object_type+'_count'),
                        val = Number(counter.html());

                    counter.html(--val);

                    outerLayout.data('processed',++processed);

                    el.closest('div.jr-layout-outer').slideUp(500).promise().done(function() {$(this).remove();});
                }
                else {

                    if(res.str.length) {

                        dialog.dialog('option','buttons',[]);

                        dialog.html(jreviews.__t(res.str));
                    }
                    else {

                        dialog.dialog('close');
                    }
                }
            });

        });

        // Call dialog
        var buttons = {};

        buttons[jreviews.__t('DELETE')] = function() { deleteSubmit.resolve($(this)); };

        buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

        $.jrDialog(lang.del_confirm, {buttons:buttons,title:text.html(),width:'640px'});

        var buttonPane = $('.ui-dialog-buttonpane');

        buttonPane.find('button:contains('+jreviews.__t('DELETE')+')').addClass('jrButton').prepend('<span class="jrIconDelete"></span>');

        buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');
    },

    moderate: function(el) {

        var form = el.closest('form'),
            outerLayout = el.closest('div.jr-moderation'),
            object_type = form.data('object-type'),
            layout = form.closest('.jr-layout-outer'),
            processed = outerLayout.data('processed') || 0,
            state;

        form.find(".jr-moderate-state").each(function() {

            if($(this).is(':checked')) {
                state = Number($(this).val());
                return false;
            }

        });

        var submittingForm = jreviews.dispatch({form:form});

        submittingForm.done(function(res) {

            if(state === 0) {

                form.html('<div class="jrWarning">'+jreviews.__t("MODERATION_HELD")+'</div>');
            }
            else {

                layout.slideUp(500).promise().done(function() {$(this).remove();});

                // Decrease moderation counter
                var counter = $('#'+object_type+'_count'),
                    val = parseInt(counter.html(),10);

                counter.html(--val);

                outerLayout.data('processed',++processed);

                if(val === 0) {
                    counter.closest('li').remove();
                }
            }

        });
    },

    loadMore: function(el) {

        var spinner = $('<span class="jrLoadingMedium">'),
            outerLayout = el.closest('div.jr-moderation'),
            pages = outerLayout.data('pages'),
            page = parseInt(outerLayout.data('page'),10) || 2,
            limit = parseInt(outerLayout.data('limit'),10),
            processed = parseInt(outerLayout.data('processed'),10),
            elHtml = el.html(),
            bind = el.data('bind');

        el.html(spinner);

        var submittingAction = jreviews.dispatch({method:'get',type:'html',controller:bind[0],action:bind[1],data:{processed:processed,page:page,limit:limit}});

        submittingAction.done(function(html) {

            el.before(html);

            el.html(elHtml);

            outerLayout.data('page', page + 1);

            if(page === pages || html === '') { el.remove(); }
        });
    }
};

jreviews.theme = {

    init: function() {

        var page = jrPage;

        page.on('click','button.jr-theme-categories',function(e) {

            e.preventDefault();
        });

        // save settings
        page.on('click','button.jr-save-theme-settings',function(e) {

            e.preventDefault();

            var el = $(this),
                form = el.closest('form');

            var action = 'saveCategory';

            var savingSettings = jreviews.dispatch({method:'post',type:'text',controller:'admin/themes',action:action,data:form.find('div.jr-table :input').serializeArray()});

            savingSettings.done(function() {

                jreviews.tools.statusUpdate(jreviews.__t('SETTINGS_SAVED'));
            });
        });

    }
};

jreviews.menu = {

    init: function() {

        var admin = $('div#jr-admin');

        admin.on('click','a.jr-menu, button.jr-menu',function(e) {

            e.preventDefault();

            var action = $(this).data('action') || 'index';

            jreviews.menu.load($(this).data('controller'),action);
        });

        admin.on('click','a.jr-menu-addon, button.jr-menu-addon',function(e) {

            e.preventDefault();

            jreviews.menu.loadAddon($(this),admin);
        });

        admin.on('click','a.jr-main-menu, button.jr-main-menu',function() {

            jreviews.menu.loadMain($(this),admin);
        });

        admin.on('click','a#rebuild-reviewer-ranks',function() {

            jreviews.tools.rebuildReviewerRanks();
        });

        admin.on('click','a#rebuild-media-counts',function() {

            jreviews.tools.rebuildMediaCounts();
        });

        admin.on('click','a#jr-clear-cache-registry',function(e) {

            e.preventDefault();

            $.post(s2AjaxUri,{'data[controller]':'admin/common','data[action]':'clearCacheRegistry'},function(msg) {

                var dialog = $.jrAlert(msg,{buttons:{}});

                setTimeout(function() {
                    dialog.dialog('close');
                },1500);
            });
        });
    },

    load: function(controller,action,data) {

        var page = jrPage;

        data = data || {};

        var loadingAction = jreviews.dispatch({method:'get',type:'html',controller:'admin/'+controller,action:action,data:data});

        loadingAction.done(function(html) {

            // Init tabs
            page.html(html).find('div.jr-tabs').tabs().fadeIn('fast');

            // Init multi-select
            page.find('select[multiple="multiple"]').multiselect({'minWidth':200,'height':'auto','selectedList':3});

            // Installer/updater
            if(controller == 'admin_updater') {

                jreviews.installer.setButtonStates();
            }

        });

        return loadingAction;
    },

    loadMain: function(el,admin) {

        jrPage.jrScrollTo({'duration':100});

        $('#addon_module').slideUp('slow',function() {$('#main_modules').slideDown('slow');});
    },

    loadAddon: function(el,admin) {

        var folder = el.attr('id'),
            view = 'menu',
            controller = el.data('controller'),
            action = el.data('action');

        var loadingAddonMenu = jreviews.dispatch({method:'get',type:'html',controller:'admin/common',action:'loadView',data:{folder:folder,view:view}});

        loadingAddonMenu.done(function(html){

            $('#main_modules').slideUp(function(){

                $('#addon_module').html(html).slideDown();
            });

        });

        jreviews.menu.load(controller,action);
    },

    moderation_counter: function(element_id) {

        var index = $('#'+element_id),
            val = Number(index.html());

        index.html(--val);

        if(val === 0) {

            index.closest('li').remove();
        }
    }
};

jreviews.userAutocomplete = function(form) {

    var el = form.find('input.jr-user-autocomplete');

    var settings = {
        'target_userid'     : 'jr-user-id-ac',
        'target_name'       : 'jr-user-name-ac',
        'target_username'   : 'jr-user-username-ac',
        'target_email'      : 'jr-user-email-ac'
    };

    if(el.val() === '') {

        var userid = form.find('.'+settings.target_userid).val();

        if(Number(userid) > 0) {

            var loadingAction = jreviews.dispatch({method:'get',type:'text',controller:'users',action:'_getUsername',data:{id:userid}});

            loadingAction.done(function(username) {

                el.val(username);
            });
        }
    }

    el
        .on('blur',function() {

            if(el.val() === '') {
                form.find('.' + settings.target_userid +
                    ', .' + settings.target_email +
                    ', .' + settings.target_name +
                    ', .' + settings.target_username).val('');
            }
        })
        .autocomplete({

            source: function( request, response ) {
                var cache = el.data('cache') || {};
                var term = request.term;
                if ( term in cache ) {
                    response( cache[ term ] );
                    return;
                }

                var searching = jreviews.dispatch({frontend:true,method:'get',type:'json',controller:'users',action:'_getList',data: {q: term}});

                searching.done(function(res) {

                    cache[ term ] = res;
                    el.data('cache',cache);
                    response(res);

                });
            },

            select: function( event, ui) {
                form.find('.'+settings.target_userid).val(ui.item.id);
                form.find('.'+settings.target_email).val(ui.item.email);
                form.find('.'+settings.target_name).val(ui.item.name);
                form.find('.'+settings.target_username).val(ui.item.username);
            },
            minLength: 2

        });

    $('.ui-autocomplete').css('white-space','nowrap');
};

jreviews.listingAutocomplete = function(form, options) {

    var el = options.acfield || form.find('input.jr-listing-autocomplete');

    var settings = {
        'target_listingid'  : 'jr-listing-id-ac',
        'target_title'      : 'jr-listing-title-ac',
        'target_alias'      : 'jr-listing-alias-ac',
        'target_url'        : 'jr-listing-url-ac'
    };

    if(el.val() === '') {

        var id = form.find('.'+settings.target_listingid).val();

        if(Number(id) > 0) {

            var loadingAction = jreviews.dispatch({frontend:true,method:'get',type:'json',controller:'listings',action:'_getList',data:{id:id}});

            loadingAction.done(function(res) {

                if(options.onSelect) {

                    options.onSelect(el, res[0]);
                }
                else {

                    el.val(res[0].value);
                }
            });
        }
    }

    el
        .on('blur',function() {

            if(el.val() === '') {
                form.find('.' + settings.target_listingid +
                    ', .' + settings.target_title +
                    ', .' + settings.target_alias +
                    ', .' + settings.target_url).val('');
            }
        })
        .autocomplete({

            source: function( request, response ) {
                var cache = el.data('cache') || {};
                var term = request.term;
                if ( term in cache ) {
                    response( cache[ term ] );
                    return;
                }

                var searching = jreviews.dispatch({frontend:true,method:'get',type:'json',controller:'listings',action:'_getList',data: {search: term}});

                searching.done(function(res) {

                    cache[ term ] = res;
                    el.data('cache',cache);
                    response(res);

                });
            },

            select: function(event, ui) {

                form.find('.'+settings.target_listingid).val(ui.item.id);
                form.find('.'+settings.target_title).val(ui.item.value);
                form.find('.'+settings.target_alias).val(ui.item.alias);
                form.find('.'+settings.target_url).val(ui.item.url);

                if(options.onSelect) {

                    options.onSelect(el, ui);
                }
            },
            minLength: 2

        });

    $('.ui-autocomplete').css('white-space','nowrap');
};

jreviews.getController = function(el, nameOnly) {

    var object_type_el = el.data('object-type'),
        object_type_form = el.closest('form').data('object-type'),
        object_type_form_children = el.find('form').data('object-type'),
        object_type,
        controller;

    object_type = object_type_el || object_type_form || object_type_form_children;

    switch(object_type) {

        case 'listing':
            controller = 'admin_listings';
        break;
        case 'media':
            controller = 'admin_media';
        break;
        case 'review':
            controller = 'admin_reviews';
        break;
        case 'inquiry':
            controller = 'admin_inquiry';
        break;
        case 'claim':
            controller = 'admin_claims';
        break;
        case 'discussion':
            controller = 'admin_discussions';
        break;
        case 'reply':
            controller = 'admin_owner_replies';
        break;
        case 'report':
            controller = 'admin_reports';
        break;
        case 'directory':
            controller = 'directories';
        break;
        case 'category':
            controller = 'categories';
        break;
        case 'group':
            controller = 'groups';
        break;
        case 'field':
            controller = 'fields';
        break;
        case 'fieldoption':
            controller = 'fieldoptions';
        break;
        case 'listing_type':
            controller = 'listing_types';
        break;
        case 'configuration':
            controller = 'configuration';
        break;
        case 'access':
            controller = 'access';
        break;
        case 'theme':
            controller = 'themes';
        break;
        case 'seo':
            controller = 'seo';
        break;
        default:
            controller = 'admin_' + object_type;
        break;
    }

    return nameOnly ? controller : 'admin/'+controller;
};

jreviews.tools = {

    apply: function() {
        jreviews.tools.statusUpdate("Your changes were applied.");
    },

    flashRow: function(row) {
        row = typeof row === 'string' ? $('#'+row) : row;
        row.effect('highlight',{},4000);
    },

    checkAll: function() {

        var page = jrPage;

        page.on('change','form#jr-page-form input.jr-cb-all',function() {

            var el = $(this),
                form = page.find('form#jr-page-form'),
                checkboxes = form.find('input.jr-row-cb');

            if(el.is(':checked')) {
                checkboxes.attr('checked','checked');
            }
            else {
                checkboxes.removeAttr('checked');
            }
        });
    },

    rebuildReviewerRanks: function() {

        $.get(s2AjaxUri,{'data[controller]':'admin/common','data[action]':'_rebuildReviewerRanks'},function(msg) {
            $.jrAlert(msg);
        });
    },

    rebuildMediaCounts: function() {

        $.get(s2AjaxUri,{'data[controller]':'admin/common','data[action]':'_rebuildMediaCounts'},function(msg) {
            $.jrAlert(msg);
        });
    },

    slug: function(text, options) {

        var defaults = {
            spaceReplaceChar    :   '', // Replacement char for spaces
            numbers             :   true
        };

        var settings = $.extend(defaults, options);

        var r = text.toLowerCase();

        r = r.replace(/[\/\|=!$%^°&()*;:@#+.,]/g,"");
        r = r.replace(/[àáâãäå]/ig,"a");
        r = r.replace(/æ/ig,"ae");
        r = r.replace(/ç/ig,"c");
        r = r.replace(/[èéêë]/ig,"e");
        r = r.replace(/[ìíîï]/ig,"i");
        r = r.replace(/ñ/ig,"n");
        r = r.replace(/[òóôõö]/ig,"o");
        r = r.replace(/ß/ig,"ss");
        r = r.replace(/œ/ig,"oe");
        r = r.replace(/[ùúûü]/ig,"u");
        r = r.replace(/[ýÿ]/ig,"y");
        if(!settings.numbers) {
            r = r.replace(/[0-9]/g,"");
        }
        r = r.replace(/_/g,settings.spaceReplaceChar);
        r = r.replace(/\s+/g,settings.spaceReplaceChar);
        return r;
    },

    removeRow: function(row) {

        row = typeof row === 'string' ? $('#'+row_id) : row;

        row.effect('highlight',{},500).fadeOut('medium',function() {$(this).remove();});
    },

    statusUpdate: function(msg) {

        $('#jr-status').html(msg).fadeIn('medium').delay(1500).fadeOut('slow');
    }
};

jreviews.tabs = function(page) {

    page = page || jrPage;

    page.find('div.jr-tabs').tabs();
};

jreviews.popup = function() {

    jrPage.not('.jr-ready-popup').addClass('jr-ready-popup').on('mouseenter',function() {

        $(this).find('.jr-more-info')
            .jrPopup({
                className: 'jrPopup',
                delay: 300
            });

    });
};

})(jQuery);

/* Configuration functions */
function clearSelect(name) {
    var element = document.getElementById(name);
    var count = element.length;
    for (i=0; i < count; i++) {
        element.options[i].selected = '';
    }
}

/*********************** BEGIN PLUGINS ***************************/

/* custom dialog functions */
(function($) {

    $.jrDialog = function(html, options) {

        $('div.jr-dialog').dialog('destroy').remove();

        var params = {},
            dialogDiv = $('<div class="jr-dialog jrDialogContent jrHidden"></div>');

        params = {
            title:          '',
            width:          '640px',
            height:         'auto',
            dialogClass:    'jrDialog',
            resizable:      false,
            modal:          true,
            autoOpen:       true
        };

        params = $.extend(params,options);

        dialogDiv.html(html);

        dialogDiv.dialog(params);

        return dialogDiv;
    };

    $.jrAlert = function(text, options) {

        var defaults = {
           dialogClass: 'jrDialog',
           modal: true,
           autoOpen: true,
           width: '400px',
           height: 'auto',
           buttons: {
                'OK': function() {
                    $(this).dialog('close');
                }
            }
        };

        var params = $.extend(defaults, options);

        $('div.jr-alert').dialog('destroy').remove();

        var dialog = $('<div class="jr-alert">' + text + '</div>');

        dialog.dialog(params);

        return dialog;
    };

})(jQuery);
/* getCSS plugin */

(function($) {

    $.fn.popUpWindow = function() {
        var el = $(this),
            winResize = "location=0,menubar=0,resizable=1,scrollbars=0,status=0,toolbar=0",
            url = el.attr('href'),
            title = el.html();

        var w = window.open( url, title, winResize + ",width=800,height=640" );

        if (w !== null) w.focus();
    };

})(jQuery);

(function($) {

   $.getCSS = function( url, media ) {
      $(document.createElement('link') ).attr({
          href: url,
          media: media || 'screen',
          type: 'text/css',
          rel: 'stylesheet'
      }).appendTo('head');
   };

})(jQuery);


/* scrollTo plugin */

(function($) {

    $.fn.jrScrollTo = function(options,onAfter) {

        var settings = $.extend({}, $.fn.jrScrollTo.defaults, options);

        var targetOffset = $(this).offset().top + settings.offset;

        $('html,body').animate({scrollTop: targetOffset}, settings.duration, settings.easing, onAfter);

        return $(this);
    };

    $.fn.jrScrollTo.defaults = {
        offset: -30,
        duration: 1000,
        easing: 'swing'
    };

})(jQuery);


/* tinyMCE plugin */
(function($) {

    $.fn.tinyMCE = function() {

        var el = this,
            timeout = 1000;

        try {

            setTimeout(function() {

                if (typeof tinyMCE == 'object') {

                    return el.each(function() {

                        tinyMCE.execCommand('mceAddControl', false, this.id);
                    });
                }

            }, timeout);

        } catch (err) {

            console(err);
        }
    };

    $.fn.RemoveTinyMCE = function() {

        var el = this;

        try {

            if (tinyMCE) {

                return el.each(function() {

                    tinyMCE.execCommand('mceRemoveControl', true, this.id);
                });
            }

        } catch (err) {

            //
        }
    };

})(jQuery);


/* jrPopup plugin */
(function($) {

    var Popup = function(elem, options) {
        this.elem = elem;
        this.$elem = $(elem);

        this.options = $.extend({}, $.fn.jrPopup.options, options);
        this.init();
    };

    Popup.prototype = {

        init: function() {

            var self = this;

            self.popupDiv = self.$elem.next('.'+self.options.className);

            if (self.popupDiv.length && $.trim(self.popupDiv.html()) !== '') {
                self.$popupDiv = $(self.popupDiv);
                self.$elem.hover(
                    function() {
                        self.show();
                    },
                    function() {
                        self.hide();
                    }
                );
                self.$popupDiv.hover(
                    function() {
                        self.show();
                    },
                    function() {
                        self.hide();
                    }
                );
            }

        },

        show: function() {

            var self = this;
            var pos = self.getPosition();

            self.$popupDiv.stop(true, true);

            self.clearTimeouts();

            self.showTimeout = setTimeout(function() {
                self.$popupDiv.appendTo('body').css({'top' : pos.top, 'left': pos.left});
                self.$popupDiv.fadeIn(100);
            }, 300);

        },

        hide: function() {

            var self = this;

            self.clearTimeouts();

            self.showTimeout = setTimeout(function() {
                $(self.popupDiv).fadeOut(100, function() {
                    self.$elem.after($(this));
                });
            }, self.options.delay);

        },

        clearTimeouts: function() {

            if (this.showTimeout) {
                clearTimeout(this.showTimeout);
                this.showTimeout = 0;
            }

            if (this.showTimeout) {
                clearTimeout(this.showTimeout);
                this.showTimeout = 0;
            }

        },

        getPosition: function() {

            var elemTop = this.$elem.offset().top,
                elemLeft = this.$elem.offset().left,
                elemWidth = this.$elem.outerWidth(),
                elemHeight = this.$elem.outerHeight();

            return {
                'left'  : elemLeft + elemWidth/2 - this.$popupDiv.outerWidth()/2 + 'px',
                'top'   : elemTop - this.$popupDiv.outerHeight() - 15 + 'px'
            };

        }

    };

    $.fn.jrPopup = function(options) {

        return this.each(function() {
            new Popup(this, options);
        });

    };

    $.fn.jrPopup.options = {
        className: 'jrPopup',
        delay : 300
    };

})(jQuery);

/**
 * Copyright (c) 2010 Jakob Westhoff
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
(function( window ) {
    var sprintf = function( format ) {
        // Check for format definition
        if ( typeof format != 'string' ) {
            throw "sprintf: The first arguments need to be a valid format string.";
        }

        /**
         * Define the regex to match a formating string
         * The regex consists of the following parts:
         * percent sign to indicate the start
         * (optional) sign specifier
         * (optional) padding specifier
         * (optional) alignment specifier
         * (optional) width specifier
         * (optional) precision specifier
         * type specifier:
         *  % - literal percent sign
         *  b - binary number
         *  c - ASCII character represented by the given value
         *  d - signed decimal number
         *  f - floating point value
         *  o - octal number
         *  s - string
         *  x - hexadecimal number (lowercase characters)
         *  X - hexadecimal number (uppercase characters)
         */
        var r = new RegExp( /%(\+)?([0 ]|'(.))?(-)?([0-9]+)?(\.([0-9]+))?([%bcdfosxX])/g );

        /**
         * Each format string is splitted into the following parts:
         * 0: Full format string
         * 1: sign specifier (+)
         * 2: padding specifier (0/<space>/'<any char>)
         * 3: if the padding character starts with a ' this will be the real
         *    padding character
         * 4: alignment specifier
         * 5: width specifier
         * 6: precision specifier including the dot
         * 7: precision specifier without the dot
         * 8: type specifier
         */
        var parts      = [];
        var paramIndex = 1;
        while ( part = r.exec( format ) ) {
            // Check if an input value has been provided, for the current
            // format string
            if ( paramIndex >= arguments.length ) {
                throw "sprintf: At least one argument was missing.";
            }

            parts[parts.length] = {
                /* beginning of the part in the string */
                begin: part.index,
                /* end of the part in the string */
                end: part.index + part[0].length,
                /* force sign */
                sign: ( part[1] == '+' ),
                /* is the given data negative */
                negative: ( parseInt( arguments[paramIndex] ) < 0 ) ? true : false,
                /* padding character (default: <space>) */
                padding: ( part[2] == undefined )
                         ? ( ' ' ) /* default */
                         : ( ( part[2].substring( 0, 1 ) == "'" )
                             ? ( part[3] ) /* use special char */
                             : ( part[2] ) /* use normal <space> or zero */
                           ),
                /* should the output be aligned left?*/
                alignLeft: ( part[4] == '-' ),
                /* width specifier (number or false) */
                width: ( part[5] != undefined ) ? part[5] : false,
                /* precision specifier (number or false) */
                precision: ( part[7] != undefined ) ? part[7] : false,
                /* type specifier */
                type: part[8],
                /* the given data associated with this part converted to a string */
                data: ( part[8] != '%' ) ? String ( arguments[paramIndex++] ) : false
            };
        }

        var newString = "";
        var start = 0;
        // Generate our new formated string
        for( var i=0; i<parts.length; ++i ) {
            // Add first unformated string part
            newString += format.substring( start, parts[i].begin );

            // Mark the new string start
            start = parts[i].end;

            // Create the appropriate preformat substitution
            // This substitution is only the correct type conversion. All the
            // different options and flags haven't been applied to it at this
            // point
            var preSubstitution = "";
            switch ( parts[i].type ) {
                case '%':
                    preSubstitution = "%";
                break;
                case 'b':
                    preSubstitution = Math.abs( parseInt( parts[i].data ) ).toString( 2 );
                break;
                case 'c':
                    preSubstitution = String.fromCharCode( Math.abs( parseInt( parts[i].data ) ) );
                break;
                case 'd':
                    preSubstitution = String( Math.abs( parseInt( parts[i].data ) ) );
                break;
                case 'f':
                    preSubstitution = ( parts[i].precision == false )
                                      ? ( String( ( Math.abs( parseFloat( parts[i].data ) ) ) ) )
                                      : ( Math.abs( parseFloat( parts[i].data ) ).toFixed( parts[i].precision ) );
                break;
                case 'o':
                    preSubstitution = Math.abs( parseInt( parts[i].data ) ).toString( 8 );
                break;
                case 's':
                    preSubstitution = parts[i].data.substring( 0, parts[i].precision ? parts[i].precision : parts[i].data.length ); /* Cut if precision is defined */
                break;
                case 'x':
                    preSubstitution = Math.abs( parseInt( parts[i].data ) ).toString( 16 ).toLowerCase();
                break;
                case 'X':
                    preSubstitution = Math.abs( parseInt( parts[i].data ) ).toString( 16 ).toUpperCase();
                break;
                default:
                    throw 'sprintf: Unknown type "' + parts[i].type + '" detected. This should never happen. Maybe the regex is wrong.';
            }

            // The % character is a special type and does not need further processing
            if ( parts[i].type ==  "%" ) {
                newString += preSubstitution;
                continue;
            }

            // Modify the preSubstitution by taking sign, padding and width
            // into account

            // Pad the string based on the given width
            if ( parts[i].width != false ) {
                // Padding needed?
                if ( parts[i].width > preSubstitution.length )
                {
                    var origLength = preSubstitution.length;
                    for( var j = 0; j < parts[i].width - origLength; ++j )
                    {
                        preSubstitution = ( parts[i].alignLeft == true )
                                          ? ( preSubstitution + parts[i].padding )
                                          : ( parts[i].padding + preSubstitution );
                    }
                }
            }

            // Add a sign symbol if neccessary or enforced, but only if we are
            // not handling a string
            if ( parts[i].type == 'b'
              || parts[i].type == 'd'
              || parts[i].type == 'o'
              || parts[i].type == 'f'
              || parts[i].type == 'x'
              || parts[i].type == 'X') {
                if ( parts[i].negative == true ) {
                    preSubstitution = "-" + preSubstitution;
                }
                else if ( parts[i].sign == true ) {
                    preSubstitution = "+" + preSubstitution;
                }
            }

            // Add the substitution to the new string
            newString += preSubstitution;
        }

        // Add the last part of the given format string, which may still be there
        newString += format.substring( start, format.length );

        return newString;
    };

    // Register the new sprintf function as a global function, as well as a
    // method to the String object.
    window.sprintf = sprintf;
    String.prototype.printf = function() {
        var newArguments = Array.prototype.slice.call( arguments );
        newArguments.unshift( String( this ) );
        return sprintf.apply( undefined, newArguments );
    };
})( window );
