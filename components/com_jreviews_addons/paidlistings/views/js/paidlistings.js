/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
(function($,undefined) {

    var jrPage = $('div.jr-page');

    jreviews.paid = {

        init: function() {

            jrPage = jrPage.length > 0 ? jrPage : $('div.jr-page');

            // My Account page
            jreviews.paid.account.init();

            // Plans Page
            jreviews.paid.plan.init();

            // Order dialog

            jrPage.on('click','.jr-paid-buy',function(e) {

                e.preventDefault();

                jreviews.paid.order.dialogForm($(this));
            });

            // Order form - inline post listing submit

            jrPage.on('plgBeforeRenderListingSave','.jr-submit-listing', function(e, res) {

                jreviews.paid.order.inlineForm($(this), res);

                return false;
            });

            // Plan selection in submit form

            var listingForm = jrPage.find('#jr-form-listing');

            if(listingForm.length) {

                listingForm.on('click',':radio[name="data[PaidOrder][plan_id]"]',function() {

                    jreviews.paid.plan.plan_selected = $(this).val();

                    jreviews.paid.plan.formFilter(listingForm);
                });
            }
        },

        account: {

            init: function() {

                var myAccountPage = $('#jr-paid-myaccount');

                if(myAccountPage.length) {

                    myAccountPage.find(".jr-tabs-myaccount").tabs({ cache: true });

                    myAccountPage.on('click','#jr-form-account-details .jr-save', function(e) {

                        e.preventDefault();

                        jreviews.paid.account.save($(this));
                    });

                    myAccountPage.on('click','.jr-invoice',function(e) {

                        e.preventDefault();

                        jreviews.paid.invoice.print($(this));
                    });
                }
            },

            save: function(el) {

                var spinner = $('.jrLoadingSmall').fadeIn('fast');

                var form = $('#jr-form-account-details'),
                    validation = form.find('.jr-validation');

                var submittingAction = jreviews.dispatch({method:'post',type:'json',form:form});

                submittingAction.done(function(res) {

                    if(res.success){

                        validation.html(jreviews.__t('PAID_ACCOUNT_SAVED')).fadeIn().delay(5000).slideUp();
                     }
                     else {

                        validation.html(jreviews.__t('PROCESS_REQUEST_ERROR')).fadeIn().delay(5000).slideUp();
                     }
                });

                submittingAction.always(function() {

                    spinner.hide();
                });
            }
        },

        order: {

            inlineForm: function(el, res) {

                var data = {
                    listing_id : res.listing_id || 0,
                    plan_id : res.plan_id || 0,
                    plan_type : res.plan_type || 0,
                    referrer : res.referrer || ''
                };

                var page = $('.jr-form-listing-outer');

                if(res.track) {

                    $('body').append(res.track);
                }

                var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'paidlistings_orders',action:'_getOrderForm',data:data});

                loadingForm.done(function(html) {

                    page.html(html).jrScrollTo();

                    page.find('[id^="jr-paid-order-step"]').hide().css('min-height','400px');

                    var form = page.find('form'),
                        prev = form.find('.jr-back'),
                        next = prev.next('button');

                    next.show();

                    form.data('step', jreviews.paid.plan.plan_selected ? 2 : 1);

                    if(jreviews.paid.plan.plan_selected) {

                        page.find('#jr-paid-order-step2').show();

                        prev.show();
                    }
                    else {

                        page.find('#jr-paid-order-step1').show();
                    }

                    form.find('.jr-buttons').show();

                    form.on('click','.jr-back',function(e) {

                        e.preventDefault();

                        step = Number(form.data('step')) - 1;

                        jreviews.paid.order.previous(form, Number(form.data('step')) - 1, $(this));

                    });

                    form.on('click','.jr-continue',function(e) {

                        e.preventDefault();

                        var step = Number(form.data('step')) + 1;

                        jreviews.paid.order.next(form, step, $(this));
                    });

                    form.on('click','.jr-checkout',function(e) {

                        e.preventDefault();

                        jreviews.paid.order.placeOrder(page);
                    });

                    jreviews.paid.order.initOrderForm(form);
                });

                return false;
            },

            dialogForm: function(el) {

                var title = el.data('title');

                var data = {
                    listing_id : el.data('listing-id') || 0,
                    order_id : el.data('order-id') || 0,
                    plan_id : el.data('plan-id') || 0,
                    plan_type : el.data('plan-type') || 0,
                    renewal : el.data('renewal') || 0,
                    referrer : el.data('referrer') || ''
                };

                var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'paidlistings_orders',action:'_getOrderForm',data:data});

                loadingForm.done(function(html) {

                    // Call dialog
                    var buttons = {};

                    buttons[jreviews.__t('BACK')] = function(e) {

                        var dialog = $(this),
                            button = $(e.target),
                            step = Number($(this).data('step')) - 1;

                        jreviews.paid.order.previous(dialog, Number($(this).data('step')) - 1, button);
                    };

                    buttons[jreviews.__t('CONTINUE')] = function(e) {

                        var dialog = $(this),
                            button = $(e.target),
                            step = Number($(this).data('step')) + 1;

                        jreviews.paid.order.next(dialog, step, button);
                    };

                    buttons[jreviews.__t('PLACE_ORDER')] = function(e) {

                        var dialog = $(this);

                        jreviews.paid.order.placeOrder(dialog);
                    };

                    var dialog = $.jrDialog(html, {buttons:buttons,title:title,width:'640px',minHeight:'400'});

                    dialog.data('step',1);

                    dialog.find('#jr-paid-listing').html(title);

                    // Plan selection
                    var buttonPane = $('.ui-dialog-buttonpane'),
                        next = buttonPane.find('button:contains('+jreviews.__t('CONTINUE')+')'),
                        prev = buttonPane.find('button:contains('+jreviews.__t('BACK')+')'),
                        order = buttonPane.find('button:contains('+jreviews.__t('PLACE_ORDER')+')');

                    next.css('padding-right',0).prepend('<span class="jrIconNext jrRight" style="margin-left:10px;margin-right:0;"></span>');

                    prev.prepend('<span class="jrIconPrev"></span>').hide();

                    order.prepend('<span class="jrIconCart"></span>').hide();

                    jreviews.paid.order.initOrderForm(dialog);
                });
            },

            initOrderForm: function(form) {

               // Plan selection
                form.on('change', 'input[type=radio][id^="jr-plan"]', function() {

                    var plan = $(this).data('plan'),
                        price = $(this).data('price'),
                        currencySymbol = form.find('#jr-paid-currency-symbol').html();

                    form.find('#jr-paid-plan-title').html(plan);

                    form.find('#jr-paid-plan-selected').html(plan + ' ' + currencySymbol + price);

                    form.data('plan_id',$(this).val());

                    form.data('price',price);

                    jreviews.paid.order.paymentType(form, $(this));
                });

                // Coupon validation
                form.on('click', '.jr-paid-coupon-validate', function(e) {

                    e.preventDefault();

                    jreviews.paid.order.validateCoupon(form);
                });

                // Terms of service textarea
                form.on('click','#jr-paid-tos',function() {

                    jreviews.paid.order.tosToggle($(this));
                });

                form.find('input:radio:checked').trigger('change').click();
            },

            next: function(dialog, step, next) {

                var new_step = step,
                    stepOne = dialog.find('#jr-paid-order-step1'),
                    currentStepDiv = dialog.find('#jr-paid-order-step' + (step - 1)),
                    buttonPane = $('.ui-dialog-buttonpane, .jr-buttons'),
                    price = Number(dialog.data('price')),
                    prev = next.prev('button'),
                    order = next.next('button');

                buttonPane.find('.jr-validation').fadeOut().remove();

                // Validation for all steps
                if((step == 2 || step ==3) && currentStepDiv.find(':radio:checked').length === 0) {

                    var validation = currentStepDiv.find('.jr-validation').clone();

                    buttonPane.prepend(validation);

                    validation.fadeIn();

                    return;
                }

                if(price === 0) {
                // Skip directly to checkout step

                    dialog.find('#jr-paid-coupon').hide();

                    new_step = step + 1;

                    currentStepDiv.fadeOut(function() {

                        dialog.find('#jr-paid-order-step' + new_step).fadeIn();

                    });
                }
                else if (price > 0) {

                    dialog.find('#jr-paid-coupon').show();

                    currentStepDiv.fadeOut(function() {

                        dialog.find('#jr-paid-order-step' + new_step).fadeIn();
                    });
                }

                if(new_step == 3) {

                    next.hide();

                    order.show();
                }

                if(new_step > 1) {

                    prev.show();
                }

                dialog.data('step', new_step);
            },

            previous: function(dialog, step, prev) {

                var new_step = step,
                    currentStepDiv = dialog.find('#jr-paid-order-step' + (step + 1)),
                    buttonPane = $('.ui-dialog-buttonpane, .jr-buttons'),
                    next = prev.next('button'),
                    order = next.next('button');

                buttonPane.find('.jr-validation').fadeOut().remove();

                if(Number(dialog.data('price')) === 0) {

                    new_step = 1;

                    currentStepDiv.fadeOut(function() {

                        dialog.find('#jr-paid-order-step' + new_step).fadeIn();});
                }
                else {

                    currentStepDiv.fadeOut(function(){

                        dialog.find('#jr-paid-order-step' + new_step).fadeIn();
                    });
                }

                if(new_step < 2) {

                    prev.hide();
                }

                if(new_step < 3) {

                    order.hide();
                }

                next.show();

                dialog.data('step', new_step);
            },

            placeOrder: function(dialog) {

                var form = dialog.find('form'),
                    tos = form.data('tos'),
                    currentStepDiv = dialog.find('#jr-paid-order-step3'),
                    buttonPane = $('.ui-dialog-buttonpane, .jr-buttons'),
                    tosAccept = dialog.find('#jr-paid-tos-accept');

                buttonPane.find('.jr-validation').fadeOut().remove();

                if(tos == 2 && tosAccept && !tosAccept.is(':checked')){

                    var validation = currentStepDiv.find('.jr-validation').clone();

                    buttonPane.prepend(validation);

                    validation.html(jreviews.__t('PAID_VALIDATE_TOS')).fadeIn();

                    return false;
                }
                else {

                    var buttons = buttonPane.find('button');

                    buttons.attr('disabled','disabled');

                    var spinner = $('<span class="jrLoadingSmall">');

                    buttons.first().before(spinner);

                    var placingOrder = jreviews.dispatch({method:'post',type:'json',form:form});

                    placingOrder.done(function(res) {

                        if(res.success) {

                            if(res.tracking) {

                                try {

                                    dialog.find('#jr-paid-tracking').remove();

                                    var tracking = $('<div id="jr-paid-tracking" class="jrHidden">').html(res.tracking);

                                    dialog.append(tracking);

                                }
                                catch (e) {

                                    console.log('payment tracking error.');
                                    console.log(e);
                                }
                            }

                            if(res.url) {

                                window.location.href = res.url;
                            }
                            else if(res.form) {

                                $(res.form).attr('display','none').appendTo(dialog).submit();

                            }
                        }
                        else {

                            var validation = currentStepDiv.find('.jr-validation').clone();

                            spinner.remove();

                            buttons.removeAttr('disabled');

                            buttonPane.prepend(validation);

                            if(res.str.length) {

                                validation.html(jreviews.__t(res.str)).fadeIn();

                            }
                            else {

                                validation.html(jreviews.__t('PROCESS_REQUEST_ERRROR')).fadeIn();
                            }
                        }

                    });
                }
            },

            paymentType: function(dialog, el) {

                var type = el.data('payment-type'),
                    price = Number(el.data('price')),
                    tax_rate = Number(el.data('tax-rate')) || 0,
                    singlePayment = dialog.find('#jr-single-payment'),
                    subscriptionPayment = dialog.find('#jr-subscription-payment');

                if(type === 0) {

                    singlePayment.show();

                    subscriptionPayment.hide();

                } else {

                    singlePayment.hide();

                    subscriptionPayment.show();
                }

                dialog.find('#jr-order-price,#jr-order-subtotal').html(jreviews.paid.order.formatCurrency((price).toFixed(2)));

                dialog.find('#jr-order-tax').html(jreviews.paid.order.formatCurrency((price*tax_rate).toFixed(2)));

                dialog.find('#jr-order-total').html(jreviews.paid.order.formatCurrency((price+price*tax_rate).toFixed(2)));

                // enable / disable payment methods
                dialog.find('#jr-single-payment input:radio[data-points-balance]').each(function(idx, el) {

                    var balance = parseFloat($(el).data('pointsBalance'));

                    if(balance < price) {

                        $(el).attr('disabled', 'disabled');

                        dialog.find('#' + el.id + '_balance').show();

                    } else {

                        dialog.find('#' + el.id + '_balance').hide();

                        dialog.find(el).removeAttr('disabled');
                    }
                });

                dialog.find('#jr-paid-plan-selected').html();
            },

            validateCoupon : function(dialog) {

                var couponCodeHidden = dialog.find('input[name="data[PaidOrder][coupon_name]"]'),
                    couponCodeInput = dialog.find('#jr-coupon-code'),
                    couponError = dialog.find('#jr-coupon-error');

                if(couponCodeInput.val() === '') return false;

                var data = {
                    coupon: couponCodeInput.val(),
                    plan_id: Number(dialog.data('plan_id')),
                    listing_id: Number(dialog.find('input[name="data[PaidOrder][listing_id]"]').val()),
                    order_amount: Number(dialog.data('price'))
                };

                couponError.fadeOut();

                var validatingCoupon = jreviews.dispatch({method:'get',type:'json',controller:'paidlistings_orders',action:'validateCoupon',data:data});

                validatingCoupon.done(function(res) {

                    if(res.success) {

                        couponCodeHidden.val(data.coupon);

                        for (var key in res ) {

                            if(dialog.find('#'+key)){

                                dialog.find('#'+key).html(res[key]);
                            }
                        }

                        couponCodeInput.val('');

                        dialog.find('#jr-coupon-success').fadeIn().delay(2000).fadeOut();

                    } else {

                        couponError.fadeIn().delay(2000).fadeOut();
                    }
                });
            },

            tosToggle : function(el) {

                if(el.data('height')===undefined) el.data('height',el.height());

                var tos_h = el.data('height');

                var tos_h2 = el.height();

                el.height(tos_h2 == tos_h ? (tos_h2*2) : tos_h);
            },

            formatCurrency: function(num,symbol) {

                if(undefined === symbol) symbol = '';

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
            }

        },

        plan: {

            controlFieldClass: {},

            planList: {},

            plan_selected: null,

            init: function() {

                var planPage = jrPage.closest('.jr-paid-plans'),
                    planInfo = jrPage.find('.jr-plan-info');

                if(planPage.length) jreviews.paid.plan.formSetup(planPage);

                if(planInfo.length) jreviews.paid.plan.adjustLayout(planInfo);
            },

            adjustLayout: function(page) {

                var divCount = page.eq(0).find('div').length;

                for (var i=0;i < divCount; i++) {

                    var group = page.find('div:nth-child('+i+')'),

                    height = jreviews.paid.plan.getHeight(group);

                    group.height(height);
                }

            },

            getHeight: function(group) {

                var tallest = 0;

                group.each(function() {

                    var curr_height = $(this).height();

                    if(curr_height > tallest) tallest = curr_height;
                });

                return tallest;
            },

            /**
             * In Plans page toggle plans based on category selection
             */
            formSetup: function(page) {

                var form = page.find('form');

                jreviews.paid.plan.submitCategory(form);
            },

            submitCategory: function (form) {

                var formLoadingDiv = $('<div class="jrRoundedPanel" style="text-align:center;"><span class="jrLoadingMedium" style="display:inline;padding:20px;"></span>'+jreviews.__t('Loading...')+'</div>');

                form.on('change','.jr-cat-select', function(e) {

                    var el = $(this);
                        formChooser = form.find('.jr-paid-categories'),
                        formPlans = form.find('.jr-plans-layout'),
                        formCategories = form.find('.jr-form-categories'),
                        selected_cat_id = parseInt(form.find('.jr-cat-select').last().val(),10);

                    var level = formCategories.find('select').index(el) + 1;

                    var data = formChooser.find('select,input').serializeArray();

                    data.push({name:'data[level]',value:level});

                    data.push({name:'data[catid]',value:el.val()});

                    var submittingCategory = jreviews.dispatch({method:'get',type:'json',controller:'paidlistings_plans',action:'_loadForm',data:data});

                    formLoadingDiv.insertAfter(formChooser);

                    submittingCategory.done(function(res) {

                        if(res.level !== undefined) {

                            var catLists = formCategories.children("select");

                            catLists.each(function(index) {if(index > res.level) { $(this).remove(); }});
                        }

                        if(res.select !== undefined) {

                            formCategories.append(res.select);
                        }

                        formLoadingDiv.remove();

                        switch(res.action) {

                            case 'show_form':

                                formPlans.html(res.html).show();

                                planInfo = formPlans.find('.jr-plan-info');

                                jreviews.paid.plan.adjustLayout(planInfo);

                                break;

                            case 'hide_form':

                                formPlans.hide();

                                break;

                            case 'no_access':

                                formPlans.html(jreviews.__t('LISTING_SUBMIT_DISALLOWED')).show();

                            break;
                        }
                    });
                });
            },

            /**
             * In Listing Submit page to toggle plan fields
             */

            formFilter: function(page) {

                var plan_id = jreviews.paid.plan.plan_selected;

                if(plan_id === null || plan_id === undefined) return false;

                var controlFieldClass = jreviews.paid.plan.controlFieldClass,
                    planData = jreviews.paid.plan.planList['plan'+plan_id],
                    planFields = planData.fields,
                    free = planData.free || 0,
                    planFieldsInput = page.find('#jr-paid-plan-fields'),
                    tabs = controlFieldClass.getTabs();

                var attachments = Number(planData.attachments) > 0 || planData.attachments === '',
                    photos = Number(planData.photos) > 0 || planData.photos === '',
                    videos = Number(planData.videos) > 0 || planData.videos === '',
                    audio = Number(planData.audio) > 0 || planData.audio === '';

                if(attachments || photos || videos || audio) {

                    page.find('.jr-media').parent('div').show();

                    page.find('.jr-media-paid').toggle(free ? false : true);

                    page.find('.jr-media').toggle(free ? true : false);
                }
                else {

                    page.find('.jr-media').parent('div').hide();
                }

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

    jreviews.addOnload('paidlistings',     jreviews.paid.init);

})(jQuery);

JRPaid =
{
     Plans: {

        load: function (params) {
           jQuery.get(s2AjaxUri+'&cat_id='+params.cat_id,{
                'data[controller]':'paidlistings_plans',
                'data[action]':'index',
                'tmpl':'component',
                'format':'raw'
                },
                function(html){
                    jQuery('#jrPlansPage').html(html);
                }
            );
        }
    }
};
