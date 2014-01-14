(function($) {

	$(document).ready(function() {

		// Prevent submenu from displaying offscreen
		$('[class^="menu-horiz-"] > li.deeper:not([class^="menu-horiz-tworow"] > li.deeper)').on('mouseenter mouseleave', function (e) {

			var elm = $('> ul, > .menu-module', this);
			var off = elm.offset();
			var l = off.left;

			var w = elm.outerWidth();
			if (elm.find("li.deeper li.deeper li.deeper").length) {
				w*=4;
			} else if (elm.find("li.deeper li.deeper").length) {
				w*=3;
			} else if (elm.find("li.deeper").length)  {
				w*=2;
			}

			var docW = $(window).width();

			var isEntirelyVisible = (l+ w <= docW);

			if (!isEntirelyVisible ) {
				$(this).addClass('menu-edge');
			} else {
				$(this).removeClass('menu-edge');
			}

		});

	});

	// Mobile Menu

	var ww = document.body.clientWidth;

	var MobileMenu = function(elem, bWidth, mTitle) {
		this.mobileMenuToggle = $(elem);
		this.bWidth = bWidth;
		this.mTitle = mTitle;
		this.mobileMenuIcon = this.mobileMenuToggle.find(".mobile-menu-icon");
		this.mobileMenu = this.mobileMenuToggle.next();
		this.menuClass = this.mobileMenu.attr('class');
		this.mobileMenuClass = 'mobile-menu';
		this.init();
	}

	MobileMenu.prototype = {

		init: function() {

			var self = this;

			self.mobileMenuIcon.text(self.mTitle);

			if ((self.menuClass.indexOf('light') > -1)) {
				self.mobileMenuClass = 'mobile-menu mobile-light';
			}

			if ((self.menuClass.indexOf('dark') > -1)) {
				self.mobileMenuClass = 'mobile-menu mobile-dark';
			}

			self.mobileMenuToggle.click(function(e) {
				e.preventDefault();
				$(this).next().toggle();
			});

			self.adjustMenu();

			$(window).bind('resize orientationchange', function() {
				ww = document.body.clientWidth;
				self.adjustMenu();
			});

		},

		adjustMenu: function() {

			var self = this;

			if (ww > self.bWidth) {
				self.mobileMenuToggle.hide();
				self.mobileMenu.show();
				self.mobileMenu.attr('class',self.menuClass);
			} else {
				self.mobileMenuToggle.show();
				self.mobileMenu.hide();
				self.mobileMenu.attr('class',self.mobileMenuClass);
			}

		}

	}

    $.fn.mMenu = function(bWidth, mTitle) {

        return this.each(function() {
            new MobileMenu(this, bWidth, mTitle);
        });

    };

})(jQuery);