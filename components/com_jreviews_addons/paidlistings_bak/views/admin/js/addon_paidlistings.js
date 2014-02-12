jreviews.paid = jreviews.paid || {};

(function($,undefined) {

jreviews.paid = {

    init: function() {

        var page = jrPage;

        page.on('click','div.jr-paid-admin button#jr-chart-update',function(e) {

            e.preventDefault();

            jreviews.paid.chartUpdate(page);
        });

        page.on('click','button.jr-paid-txn',function(e) {

            e.preventDefault();

            jreviews.paid.orderTxn($(this));

        });

        page.on('click','.jr-paid-orders-list .jr-user-autocomplete:not(".jr-ready")',function(e) {

            e.preventDefault();

            var el = $(this);

            jreviews.userAutocomplete(el.closest('form'));

        });

        page.on('click','.jr-paid-plans-list .jr-plan-create-orders',function(e) {

            e.preventDefault();

            jreviews.paidlistings_plans.generateOrders($(this));
        });

        page.on('click','.jr-paid-plans-list .jr-plan-sync-orders',function(e) {

            e.preventDefault();

            jreviews.paidlistings_plans.updateOrders($(this));
        });

        page.on('click','.jr-invoice',function(e) {

            e.preventDefault();

            jreviews.paid.invoice.print($(this));
        });

        jreviews.addOnloadAjax('paid-charts', jreviews.paid.loadGoogleCharts);
    },

    loadGoogleCharts: function() {

        google.load('visualization', '1', {'packages':['corechart'], callback:jreviews.paid.chartSetup});

    },

    chartSetup: function() {

        var page = jrPage,
            divChart = page.find('div.jr-paid-admin:not(".jr-ready-chart")');

        if(divChart.length) {

            divChart.addClass('jr-ready-chart');

            // Date range
            var dates = page.find('input#jr-date-from, input#jr-date-to').datepicker({
                defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: 3,
                onSelect: function(selectedDate) {

                    var option = this.id == "jr-date-from" ? "minDate" : "maxDate";

                    var instance = $(this).data("datepicker");

                    var date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);

                    dates.not(this).datepicker("option", option, date);

                    page.find('#'+this.id+'-alt').val($.datepicker.formatDate('yy-mm-dd', date));
                }
            });

            jreviews.paid.chartUpdate(page);
        }
    },

    chartUpdate: function(page) {

        var data = {
            date_from: page.find('input#jr-date-from-alt').val(),
            date_to: page.find('input#jr-date-to-alt').val()
        };

        var loadingSales = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_paidlistings',action:'getSalesData',data:data});

        loadingSales.done(function(res) {

            jreviews.paid.salesChart(page,res.revenue, data);

            jreviews.paid.volumeChart(page,res.volume, data);

        });

    },

    salesChart: function(page, data, dateRange) {

        var total_revenue = 0,
            salesDiv = page.find('#jr-sales-chart'),
            curr_symbol = salesDiv.data('curr-symbol'),
            title = salesDiv.data('title');

        salesDiv.empty();

        if(!data.length) return false;

        // Create data table object for google chart
        var dataTable = new google.visualization.DataTable();
        dataTable.addColumn('date', 'Date');
        dataTable.addColumn('number', 'Sales');
        dataTable.addColumn({type: 'string', role: 'annotation'});

        if(data.length == 1) {
            total_revenue += data[0][1];
            dataTable.addRow([new Date(dateRange.date_from), 0, curr_symbol+0]);
            dataTable.addRow([new Date(data[0][0]), data[0][1], curr_symbol+data[0][1]]);
            dataTable.addRow([new Date(dateRange.date_to), 0, curr_symbol+0]);
        } else {
            $(data).each(function(i){
                total_revenue += data[i][1];
                dataTable.addRow([new Date(data[i][0]), data[i][1], curr_symbol+data[i][1]]);
            });
        }

        page.find('#jr-total-sales').html(jreviews.paid.formatCurrency(total_revenue,curr_symbol));

        var formatCurrency = new google.visualization.NumberFormat({prefix: curr_symbol});
        formatCurrency.format(dataTable, 1);

        var options = {
          title: title,
          width: '100%',
          height: '300',
          fontSize: 12,
          colors: ['#216c2a'],
          titleTextStyle: {
            color: '#111',
            fontSize: 24
          },
          chartArea: {
            width: '92%',
            height: 200,
            top: 40,
            right: 40,
            bottom: 40,
            left: 40
          },
          vAxis: {
            format: "$#",
            minValue: 10
          },
          lineWidth: 4,
          pointSize: 8,
          legend: 'none'
        };

        var chart = new google.visualization.AreaChart(salesDiv[0]);
        chart.draw(dataTable, options);

        $(window).resize(function () {
           chart.draw(dataTable, options);
        });

    },

   volumeChart: function (page, data, dateRange) {

        var total_volume = 0,
            volumeDiv = page.find('#jr-volume-chart'),
            title = volumeDiv.data('title');

        volumeDiv.empty();

        if(!data.length) return false;

        // Create data table object for google chart
        var dataTable = new google.visualization.DataTable();
        dataTable.addColumn('date', 'Date');
        dataTable.addColumn('number', 'Orders');
        dataTable.addColumn({type: 'number', role: 'annotation'});

        if(data.length == 1) {
            dataTable.addRow([new Date(dateRange.date_from), 0, 0]);
            dataTable.addRow([new Date(data[0][0]), data[0][1], data[0][1]]);
            dataTable.addRow([new Date(dateRange.date_to), 0, 0]);
        } else {
            $(data).each(function(i){
                dataTable.addRow([new Date(data[i][0]), data[i][1], data[i][1]]);
            });
        }

        page.find('#jr-total-volume').html(Number(total_volume));

        var options = {
          title: title,
          width: '100%',
          height: '300',
          fontSize: 12,
          titleTextStyle: {
            color: '#111',
            fontSize: 24
          },
          chartArea: {
            width: '92%',
            height: 200,
            top: 40,
            right: 40,
            bottom: 40,
            left: 40
          },
          vAxis: {
            minValue: 4
          },
          lineWidth: 4,
          pointSize: 8,
          legend: 'none'
        };

        var chart = new google.visualization.AreaChart(volumeDiv[0]);
        chart.draw(dataTable, options);

        $(window).resize(function () {
           chart.draw(dataTable, options);
        });

    },

    formatCurrency: function(num,symbol) {

        num = num.toString().replace(/\$|\,/g,'');

        if (isNaN(num)) num = "0";

        sign = (num == (num = Math.abs(num)));

        num = Math.floor(num * 100 + 0.50000000001);

        cents = num % 100;

        num = Math.floor(num / 100).toString();

        if (cents < 10) cents = "0" + cents;

        for (var i = 0; i < Math.floor((num.length - (1 + i)) / 3); i++) {

            num = num.substring(0, num.length - (4 * i + 3)) + ',' + num.substring(num.length - (4 * i + 3));
        }

        return (((sign) ? '' : '-') + symbol + num + '.' + cents);
    },

    getTreeCheckedIds: function(tree_id) {

        var checked_ids = [];

        $("#"+tree_id).find("li.jstree-checked").each(function () {

            if(this.id.match(/^([0-9]+|jr_[a-z0-9]+)$/) !== null){

                checked_ids.push(this.id);

            }

        });

        return checked_ids;
    },

    orderTxn: function(el) {

        var id = el.data('id'),
            listing_id = el.data('listing-id'),
            txn_id = el.data('txn-id');

        var loadingTxn = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_paidlistings_txn',action:'getOrderTxn',data:{id:id,txn_id:txn_id,listing_id:listing_id}});

        loadingTxn.done(function(html) {

            $.jrDialog(html,{width:'850px',height:'600',position:'top'});

        });
    },

    plan: {

        controlFieldClass: {},

        planList: {},

        plan_selected: null,

        // Displays/hides fields in listing based on paid plan
        formFilter: function(page) {

            var plan_id = jreviews.paid.plan.plan_selected;

            if(plan_id === null) return false;

            var controlFieldClass = jreviews.paid.plan.controlFieldClass,
                planFields = jreviews.paid.plan.planList['plan'+plan_id].fields,
                planFieldsInput = page.find('#jr-paid-plan-fields'),
                tabs = controlFieldClass.getTabs();

            if(planFieldsInput.length === 0) {

                page.append('<input type="hidden" id="jr-paid-plan-fields" name="data[Paid][fields]" value="'+planFields+'" />');
            }
            else {

                planFieldsInput.val(planFields);
            }

            var planList = page.find('#jr-paid-plan-list');

            if(planList.find(':radio[value='+plan_id+']').length === 0) {

                page.append('<input type="hidden" name="data[PaidOrder][plan_id]" value="'+plan_id+'" />');
            }

            page.find('fieldset').each(function() {

                var fieldset = $(this);

                if(fieldset.attr('class')=='jr-form-review') return false;

                var fieldDiv = fieldset.children('.jrFieldDiv'),
                    fieldDivCount = fieldDiv.length;

                if(fieldDivCount > 0)
                {
                    fieldDiv.each(function() {

                        var field = $(this).find(':input[class^="jr_"]');

                        if(field.length) {

                            var fname = field.attr('class').split(' ')[0];

                            // load the field object from the controlField class
                            if(typeof jreviews.paid.plan.controlFieldClass.getFieldObj == 'function') {

                                 var fieldObject = jreviews.paid.plan.controlFieldClass.getFieldObj(fname);

                                 if(fieldObject !== false) {

                                    if($.inArray(fname,planFields) == -1) {

                                        fieldObject./*data('isActive',false).*/closest('div.jrFieldDiv').css('display','none');

                                        fieldDivCount--;
                                    }
                                    else if(fieldObject.data('isControlled') === false) {

                                        fieldObject.closest('div.jrFieldDiv').show('fast');
                                    }
                                }
                            }
                        }
                    });

                    if(fieldDivCount === 0) {

                        fieldset.css('display','none');

                        if(tabs.length && fieldset.attr('id'))  {

                            tabs.filter('li#tab_'+fieldset.attr('id').replace('group_','')).hide('fast');
                        }
                    }
                    else if (fieldset.data('isControlled') !== true || fieldset.data('isActive') === true) {

                        /* Only unhide groups that are not controlled by other fields */
                        fieldset.fadeIn();

                        if(tabs.length && fieldset.attr('id')) {

                            tabs.filter('li#tab_'+fieldset.attr('id').replace('group_','')).fadeIn('slow');
                        }
                    }
                }
            });
        }
    },
    invoice: {

        print : function(el) {

            var url = el.data('url');

            window.open(
                url,
                'invoice',
                'location=no,status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=yes,resizable=yes,width=900,height=600,directories=no'
            );
        }
    }
};

jreviews.paidlistings_coupons = {

    edit: function(el,page) { // and edit

        var id = el.data('id') || 0;

        var loadingPage = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_paidlistings_coupons',action:'edit',data:{id:id}});

        loadingPage.done(function(html) {

            var prevPage = page.children('div').eq(0),
                currPage = $('<div>');

            currPage.insertBefore(prevPage);

            var prevPageDettached = prevPage.detach();

            currPage.html(html).jrScrollTo();

            var form = currPage.find('form');

            // Initialize user autocomplete
            jreviews.paidlistings_coupons.initCategoryTree(form);

            var userSearchInput = form.find('.jr-user-autocomplete'),
                userCheckboxDiv = form.find('.jr-user-checkbox-div');

            if(userCheckboxDiv.find(':input').length === 0) {

                userCheckboxDiv.hide();
            }

            form.on('click','.jr-coupon-user',function() {

                if(userCheckboxDiv.find(':input').length==1) {

                    userCheckboxDiv.hide();
                }

                $(this).parent('label').remove();

            });

            userSearchInput.autocomplete({
                minLength: 2,
                source: function( request, response ) {
                    // Make sure
                    // Create array of current selected checkbox values
                    var checkedValues = userCheckboxDiv.find(':input:checked').map(function(i, cb) {

                      return this.value;
                    });

                    if(userSearchInput.data('cache.'+request.term)) {

                        var cachedData = userSearchInput.data('cache.'+request.term);

                        var dataResp = [];

                        $(cachedData).each(function(i,row) {

                            if(row !== undefined) {

                                if($.inArray(row.id,checkedValues) == -1) dataResp.push(cachedData[i]);
                            }
                        });

                        response(dataResp);
                    }
                    else {

                        var loadingUsers = jreviews.dispatch({method:'get',type:'json',controller:'users',action:'_getList','data':{limit:12,q:request.term}});

                        loadingUsers.done(function(data) {

                            userSearchInput.data('cache.'+request.term, data);

                            var dataResp = [];

                            $(data).each(function(i,row) {

                                if(row !== undefined) {

                                    if($.inArray(row.id,checkedValues) == -1) dataResp.push(data[i]);
                                }
                            });

                            response(dataResp);
                        });
                    }
                },
                select: function( event, ui ) {

                    if(ui.item.id !== '' && !userSearchInput.data(ui.item.id)) {

                        var checkboxAttr = {
                            'id':'jr-coupon-user-'+ui.item.id,
                            'class':'jr-coupon-user',
                            'name':'data[PaidCoupon][coupon_users][]',
                            'value':ui.item.id
                        };

                        var checkbox = $('<input type="checkbox" checked="checked" />').attr(checkboxAttr);

                        var label = $('<label for="jr-coupon-user-'+ui.item.id+'" />').css('text-align','left');

                        label.append(checkbox).append(ui.item.name);

                        userSearchInput.val('').focus();

                        userCheckboxDiv.show().append(label);
                    }

                    this.value = '';

                    return false;
                }
            });


            // Save
            currPage.on('click','button.jr-save',function(e) {

                e.preventDefault();

                jreviews.paidlistings_coupons.save(el, form, currPage, prevPageDettached);
            });

            // Cancel
            currPage.on('click','button.jr-cancel',function(e) {

                e.preventDefault();

                prevPageDettached.insertAfter(currPage).fadeIn('fast');

                currPage.remove();

            });

            currPage.find('.jr-tabs').tabs();

        });

    },

    save: function(el, form, currPage, prevPageDettached) {

        var row = el.closest('div.jr-layout-outer'),
            buttons = currPage.find('.jr-toolbar button').attr('disabled','disabled'),
            id = el.data('id'),
            validation = currPage.find('div.jr-validation');

        validation.hide();

        currPage.find('#cat_ids').val(jreviews.paid.getTreeCheckedIds('jr-cat-tree'));

        var submittingForm = jreviews.dispatch({form:form});

        submittingForm.done(function(res) {

            if(res.success === true) {

                if(res.isNew) {

                    currPage.fadeOut().promise().done(function() {

                        prevPageDettached.html(res.html);

                        var row = prevPageDettached.find('div.jr-layout-outer[data-id="'+res.id+'"]');

                        prevPageDettached.insertAfter(currPage).fadeIn('fast').promise().done(function() {

                            row.jrScrollTo({offset:-100,duration: 500});

                            jreviews.tools.flashRow(row);
                        });

                        currPage.remove();

                    });
                }
                else {

                    currPage.fadeOut('fast').promise().done(function() {

                        prevPageDettached.insertAfter(currPage).fadeIn('fast').promise().done(function() {

                            // row.trigger('update');

                            row.jrScrollTo({offset:-100,duration: 500});

                            jreviews.tools.flashRow(row);
                        });

                        currPage.remove();

                    });
                }
            }
            else if(res.str !== '') {

                validation.html(jreviews.__t(res.str)).show();

            }

        });

        submittingForm.always(function() {

            buttons.removeAttr('disabled');

        });

    },
    initCategoryTree: function(page) {

        var catTree = page.find('#jr-cat-tree');

        page.on('click','button.jr-cat-tree-toggle',function(e) {

            e.preventDefault();

            catTree.jstree($(this).hasClass('jr-tree-open') ? 'close_all' : 'open_all');

            $(this).toggleClass('jr-tree-open');
        });

        catTree.bind("loaded.jstree",function (e,data) {

            // Select current categories using the curr_cat_ids hidden field
            $.each(page.find('#curr_cat_ids').val().split(","),function(i,id) {

                data.inst.check_node($("#"+id));
            });
        })
        .jstree({
            // Attach jstree plugin to cat-tree div
            "themes": {"theme": "default","icons":false},
            "plugins": ["themes","ui","json_data","checkbox"],
            "json_data" : {
                "ajax" : {
                    "type": "get",
                    "url" : s2AjaxUri + '&url=admin_paidlistings_coupons/jsonCategoryTree'
                }
            },
            "types": { "types" :
                {"default": {renameable: false, deletable: false, creatable: false, draggable: false} }
            }
        });
    }
};

jreviews.paidlistings_emails = {

    edit: function(el,page) {

        var id = el.data('id'),
            title = el.data('name'),
            row = el.closest('div.jr-layout-outer');

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_paidlistings_emails',action:'edit',data:{id:id}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SUBMIT')] = function() {

                var dialog = $(this),
                    form = dialog.find('form');

                var submittingForm = jreviews.dispatch({form:form});

                submittingForm.done(function(res) {

                    dialog.dialog('close');

                    jreviews.tools.flashRow(row);
                });

            };

            buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

            $.jrDialog(html, {title:title,position:'top',buttons:buttons,width:'800px'});

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SUBMIT')+')').addClass('jrButton').prepend('<span class="jrIconYes"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
    }

};

jreviews.paidlistings_plans = {

   formSetup: function(planPage) {

        var durationPeriod = $('select[name="data[PaidPlan][plan_array][duration_period]"]'),
            durationPeriodClone = durationPeriod.clone();

        jreviews.paidlistings_plans.initCategoryTree(planPage);

        jreviews.paidlistings_plans.initFieldGroupsTree(planPage);

        planPage.on('change','form',function() {

            var form = $(this),
                prev_payment_type = form.data('payment_type') || null,
                prev_plan_type = form.data('plan_type') || null;

            var plan_type = planPage.find('select[name="data[PaidPlan][plan_type]"] option:selected').val(),
                payment_type = planPage.find('select[name="data[PaidPlan][payment_type]"] option:selected').val(),
                duration_sel = durationPeriod.find('option:selected').val(),
                durationNumber = planPage.find(':input[name="data[PaidPlan][plan_array][duration_number]"]'),
                planPrice = planPage.find(':input[name="data[PaidPlan][plan_price]"]'),
                trialLimitRow = planPage.find(':input[name="data[PaidPlan][plan_array][trial_limit]"]').closest('.jrGrid24'),
                moderationRow = planPage.find(':input[name="data[PaidPlan][plan_array][moderation]"]').closest('.jrGrid24'),
                submitFormShowRow = planPage.find(':input[name="data[PaidPlan][plan_array][submit_form]"]').closest('.jrGrid24'),
                planExclusiveRow = planPage.find(':input[name="data[PaidPlan][plan_upgrade_exclusive]"]').closest('.jrGrid24'),
                paymentTypeMsg = planPage.find('#jr-paid-type-msg'),
                planUpgradeMsg = planPage.find('#jr-paid-upgrade-msg');

            form.data('payment_type', payment_type);

            form.data('plan_type', plan_type);

            if(duration_sel == 'never') {

                durationNumber.val(0).attr("disabled","disabled");
            }
            else {

                durationNumber.removeAttr("disabled");
            }

            if(prev_payment_type != payment_type) {

                if(payment_type == 2) {

                    paymentTypeMsg.fadeIn();

                    trialLimitRow.fadeIn();
                }
                else {
                    paymentTypeMsg.fadeOut();

                    trialLimitRow.fadeOut();
                }

            }

            if(prev_plan_type != plan_type) {

                switch(plan_type){

                    case '0': //New listing

                        planUpgradeMsg.fadeOut();

                        planExclusiveRow.fadeOut();

                        moderationRow.fadeIn();

                        submitFormShowRow.fadeIn();

                    break;

                    case '1': //Upgrade listing

                        if(duration_sel != 'never') {

                            planUpgradeMsg.fadeIn();
                        }
                        else {

                            planUpgradeMsg.fadeOut();
                        }

                        planExclusiveRow.fadeIn();

                        moderationRow.fadeOut();

                        submitFormShowRow.fadeOut();

                    break;
                }

            }

            if(prev_payment_type != payment_type) {

                switch(payment_type){

                    case '0': // single payment

                        planPrice.removeAttr('disabled');

                        durationPeriod.
                            html(durationPeriodClone.html())
                            .val(duration_sel);

                    break;

                    case '1': // subscription

                        planPrice.removeAttr('disabled');

                        durationPeriod.
                            html(durationPeriodClone.html())
                            .find("option[value='never']").remove();

                        // durationPeriod.val('months');

                    break;

                    case '2':

                        planPrice.val(0).attr('disabled','disabled');

                        durationPeriod.html(durationPeriodClone.html()).val(duration_sel);

                        break;
                }
            }

        });

        planPage.find('form').trigger('change');
    },

    edit: function(el,page) { // and edit

        var id = el.data('id') || 0;

        var loadingPage = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_paidlistings_plans',action:'edit',data:{id:id}});

        loadingPage.done(function(html) {

            var prevPage = page.children('div').eq(0),
                currPage = $('<div>');

            currPage.insertBefore(prevPage);

            var prevPageDettached = prevPage.detach();

            currPage.html(html).jrScrollTo();

            jreviews.paidlistings_plans.formSetup(currPage);

            var form = currPage.find('form');

            // Save
            currPage.on('click','button.jr-save',function(e) {

                e.preventDefault();

                jreviews.paidlistings_plans.save(el, form, currPage, prevPageDettached);
            });

            // Cancel
            currPage.on('click','button.jr-cancel',function(e) {

                e.preventDefault();

                prevPageDettached.insertAfter(currPage).fadeIn('fast');

                currPage.remove();

            });

            currPage.find('.jr-tabs').tabs();
        });

    },

    save: function(el, form, currPage, prevPageDettached) {

        var row = el.closest('div.jr-layout-outer'),
            buttons = currPage.find('.jr-toolbar button').attr('disabled','disabled'),
            id = el.data('id'),
            validation = currPage.find('div.jr-validation');

        validation.hide();

        currPage.find('#cat_ids').val(jreviews.paid.getTreeCheckedIds('jr-cat-tree'));

        currPage.find('#field_names').val(jreviews.paid.getTreeCheckedIds('jr-field-tree'));

        var submittingForm = jreviews.dispatch({form:form});

        submittingForm.done(function(res) {

            if(res.success === true) {

                if(res.isNew) {

                    currPage.fadeOut().promise().done(function() {

                        prevPageDettached.html(res.html);

                        var row = prevPageDettached.find('div.jr-layout-outer[data-id="'+res.id+'"]');

                        prevPageDettached.insertAfter(currPage).fadeIn('fast').promise().done(function() {

                            row.jrScrollTo({offset:-100,duration: 500});

                            jreviews.tools.flashRow(row);
                        });

                        currPage.remove();

                    });
                }
                else {

                    currPage.fadeOut('fast').promise().done(function() {

                        prevPageDettached.insertAfter(currPage).fadeIn('fast').promise().done(function() {

                            row.trigger('update');
                        });

                        currPage.remove();

                    });
                }
            }
            else if(res.str !== '') {

                validation.html(jreviews.__t(res.str)).show();

            }

        });

        submittingForm.always(function() {

            buttons.removeAttr('disabled');

        });

    },

    initFieldGroupsTree: function(page) {

        var fieldTree = page.find("#jr-field-tree"),
            plan_id = fieldTree.data('plan-id') || null,
            cat_id = fieldTree.data('cat-id') || null;

        // Field Groups tree
        if(fieldTree.length) {

            page.on('click','button.jr-field-tree-toggle',function(e) {

                e.preventDefault();

                fieldTree.jstree($(this).hasClass('jr-tree-open') ? 'close_all' : 'open_all');

                $(this).toggleClass('jr-tree-open');
            });

            fieldTree
                .bind("loaded.jstree",function(e,data) {

                   $.each($('#curr_plan_group_ids').val().split(","),function(i,id){

                        data.inst.open_node($("#g"+id));
                    });

                    $(this).jstree('close_all');
                })
                .bind("open_node.jstree",function(e,data) {

                   $.each($('#curr_plan_field_ids').val().split(","),function(i,id){

                        if(id !== '') {

                            if(!data.inst.is_checked('#'+id)) data.inst.check_node("#"+id);
                        }
                    });
                })
                .bind("check_node.jstree",function(e,data) {

                    var sel_node_id = data.rslt.obj.attr('id');

                    data.inst.open_node($("#"+sel_node_id));
                })
                .jstree({
                    "themes": {"theme": "default","icons":false},
                    "plugins": ["themes","ui","json_data","checkbox","sort"],
                    "json_data" : {
                        "ajax" : {
                            "type": "get",
    //                        "data": function(n){return {"group_id":$(n).attr('id') || 0, "action":"checked"};},
                            "url" : s2AjaxUri + '&url=admin_paidlistings_plans/jsonFieldGroups/plan_id:'+plan_id+'/cat_id:'+cat_id
                        }/*,
                        "progressive_render": true*/
                    },
                    "types": { "types" :
                        {"default": {renameable: false, deletable: false, creatable: false, draggable: false} }
                    }
                });
        }
    },

    initCategoryTree: function(page) {

        /************************************
        *  Category tree
        ************************************/
        var fieldTree = page.find('#jr-field-tree');

        var catTree = page.find("#jr-cat-tree");

        catTree.data('page_setup',true);

        page.on('click','button.jr-cat-tree-toggle',function(e) {

            e.preventDefault();

            catTree.jstree($(this).hasClass('jr-tree-open') ? 'close_all' : 'open_all');

            $(this).toggleClass('jr-tree-open');
        });

        catTree.bind("loaded.jstree",function (e,data) {

            // Select current categories using the curr_cat_ids hidden field
           $.each($('#curr_plan_cat_ids').val().split(","),function(i,id) {

                if(id > 0) data.inst.check_node($("#"+id,'#jr-cat-tree'));
            });

            catTree.jstree('close_all');

            catTree.data('page_setup',false);

        })
        .bind("uncheck_node.jstree",function (e,data) {

            var sel_node_id = data.rslt.obj.attr('id');

            $('#curr_plan_field_ids').val(jreviews.paid.getTreeCheckedIds('jr-field-tree'));

            var loadingCats = jreviews.dispatch({
                method:'get',
                type:'json',
                controller:'admin/admin_paidlistings_plans',
                action:'getGroupIdsByNode',
                data:{node_id:jreviews.paid.getTreeCheckedIds('jr-cat-tree')}
            });

            loadingCats.done(function(group_ids){ // Returns groups that should be shown for current selected cat nodes

                fieldTree.find('li:not(.jstree-leaf)').each(function() {

                    if(-1 == $.inArray($(this).attr('id'),group_ids)) {
                        // Remove groups that are not in the list of returned groups
                        fieldTree.jstree("delete_node",'#'+$(this).attr('id'));
                    }

                });

            });

        })
        .bind("check_node.jstree",function(e,data) {

            // Create field group nodes in Field Tree - those that don't already exist
            var sel_node_id = [];

            catTree.find("li.jstree-checked").each(function() {

                if(this.id.match(/^([0-9]+)$/) !== null) {

                    sel_node_id.push(this.id);
                }
            });

            sel_node_id = sel_node_id.join(",");

            if(false === catTree.data('page_setup')) {

                var loadingGroups = jreviews.dispatch({
                    method:'get',
                    type:'json',
                    controller:'admin/admin_paidlistings_plans',
                    action:'jsonFieldGroups',
                    data:{cat_id:sel_node_id}
                });

                loadingGroups.done(function(node){

                    $.each(node,function(i,n){

                        if(!fieldTree.find('#'+n.attr.id).length){

                            fieldTree.jstree("create_node",fieldTree,"first",n,function(){

                                $.each(n.children,function(i,child){

                                    fieldTree.jstree("create_node",$('#'+n.attr.id),"last",child);

                                });

                            });
                            // Group is automatically opened in the create_node event for the field-tree
                        }
                    });
                });
            }
        })
        .jstree({
            "themes": {"theme": "default","icons":false},
            "plugins": ["themes","ui","json_data","checkbox"],
            "json_data" : {
                "ajax" : {
                    "type": "get",
                    "url" : s2AjaxUri + '&url=admin_paidlistings_plans/jsonCategoryTree'
                }
            },
            "types": { "types" :
                {"default": {renameable: false, deletable: false, creatable: false, draggable: false} }
            }
        });
    },

    generateOrders: function(el, task) {

        var plan_id = el.data('plan-id');

        el.data('Orders.abort',0); // Used to stop the ajax request

        if(task == 'process')
        {
            jreviews.paid.orders.generateOrders(plan_id, 'process');

            return;
        }

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_paidlistings_orders',action:'generateOrders',data:{id:plan_id,task:'init'}});

        loadingForm.done(function(html) {

            var buttons = {};

            buttons[jreviews.__t('START')] = function() {

                var dialog = $(this),
                    dialogOuter = dialog.closest('.jrDialog');

                var generatingOrders = jreviews.dispatch({method:'get',type:'text',controller:'admin/admin_paidlistings_orders',action:'generateOrders',data:{id:plan_id,task:'process'}});

                generatingOrders.done(function(count){

                    dialog.find('#jr-listing-count').html(count);

                    if(el.data('Orders.abort') == 1) { return false; }

                    if( Number(count) === 0 ) {

                        dialogOuter.find('.ui-dialog-buttonpane').slideUp();
                    }
                    else {

                        dialogOuter.find('button:contains('+jreviews.__t('START')+')').trigger('click');
                    }
                });
            };

            buttons[jreviews.__t('CANCEL')] = function() {

                el.data('Orders.abort',1);

                $(this).dialog('close');
            };

            var dialogClose = function() {

                el.data('Orders.abort',1);
            };

            var dialog = $.jrDialog(html, {title:el.html(),width:'600px',buttons:buttons, close: dialogClose});

            var count = dialog.find('#jr-listing-count').html();

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('START')+')').addClass('jrButton').prepend('<span class="jrIconYes"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

            if(Number(count)===0) buttonPane.slideUp();
        });
    },

    updateOrders: function(el, task) {

        var plan_id = el.data('plan-id');

        el.data('UpdateOrders.abort',0); // Used to stop the ajax request

        if(task == 'process')
        {
            jreviews.paid.orders.generateOrders(plan_id, 'process');

            return;
        }

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_paidlistings_orders',action:'updateOrders',data:{id:plan_id,task:'init'}});

        loadingForm.done(function(html) {

            var buttons = {};

            buttons[jreviews.__t('START')] = function() {

                var dialog = $(this),
                    dialogOuter = dialog.closest('.jrDialog');

                var generatingOrders = jreviews.dispatch({method:'get',type:'text',controller:'admin/admin_paidlistings_orders',action:'updateOrders',data:{id:plan_id,task:'process'}});

                generatingOrders.done(function(count){

                    dialog.find('#jr-order-count').html(count);

                    if(el.data('UpdateOrders.abort') == 1) { return false; }

                    if( Number(count) === 0 ) {

                        dialogOuter.find('.ui-dialog-buttonpane').slideUp();
                    }
                    else {

                        dialogOuter.find('button:contains('+jreviews.__t('START')+')').trigger('click');
                    }
                });
            };

            buttons[jreviews.__t('CANCEL')] = function() {

                el.data('UpdateOrders.abort',1);

                $(this).dialog('close');
            };

            var dialogClose = function() {

                el.data('UpdateOrders.abort',1);
            };

            var dialog = $.jrDialog(html, {title:el.html(),width:'600px',buttons:buttons, close: dialogClose});

            var count = dialog.find('#jr-order-count').html();

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('START')+')').addClass('jrButton').prepend('<span class="jrIconYes"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

            if(Number(count)===0) buttonPane.slideUp();
        });
    }
};

jreviews.paidlistings_handlers = {

    edit: function(el,page) {

        var id = el.data('id') || 0;

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_paidlistings_handlers',action:'edit',data:{id:id}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SUBMIT')] = function() {

                var dialog = $(this),
                    form = dialog.find('form'),
                    validation = form.find('.jr-validation');

                validation.html('').hide();

                var submittingForm = jreviews.dispatch({form:form});

                submittingForm.done(function(res) {

                    if(res.success) {

                        dialog.dialog('close');

                        el.closest('div.jr-layout-outer').trigger('update');

                    }
                    else if(res.str) {

                        validation.html(jreviews.__t(res.str)).show();
                    }

                });

            };

            buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

            var dialog = $.jrDialog(html, {title: el.html(), buttons:buttons,width:'800px'}),
                form = dialog.find('form');

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SUBMIT')+')').addClass('jrButton').prepend('<span class="jrIconYes"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
    }
};

jreviews.paidlistings_orders = {

    edit: function(el,page) {

        var id = el.data('id') || 0;

        var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_paidlistings_orders',action:'edit',data:{id:id}});

        loadingForm.done(function(html) {

            // Call dialog
            var buttons = {};

            buttons[jreviews.__t('SUBMIT')] = function() {

                var dialog = $(this),
                    form = dialog.find('form');

                form.find('#field_names').val(jreviews.paid.getTreeCheckedIds('jr-field-tree'));

                var submittingForm = jreviews.dispatch({form:form});

                submittingForm.done(function(res) {

                    if(res.success) {

                        dialog.dialog('close');

                        el.closest('div.jr-layout-outer').trigger('update');
                    }
                    else if(res.msg) {

                        dialog.dialog('option','buttons',[]);

                        dialog.html(res.msg);
                    }

                });

            };

            buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

            var dialog = $.jrDialog(html, {buttons:buttons,width:'960px'}),
                form = dialog.find('form');

            dialog.find('.jr-tabs').tabs();

            jreviews.userAutocomplete(form);

            jreviews.paidlistings_plans.initFieldGroupsTree(dialog);

            var buttonPane = $('.ui-dialog-buttonpane');

            buttonPane.find('button:contains('+jreviews.__t('SUBMIT')+')').addClass('jrButton').prepend('<span class="jrIconYes"></span>');

            buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')').addClass('jrButton').prepend('<span class="jrIconCancel"></span>');

        });
    }
};



jreviews.addOnload('paidlistings-admin',     jreviews.paid.init);

})(jQuery);