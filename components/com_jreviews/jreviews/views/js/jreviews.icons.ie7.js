window.onload = function() {
	function addIcon(el, entity) {
		var html = el.innerHTML;
		el.innerHTML = '<span style="font-family: \'jrIcons\'">' + entity + '</span>' + html;
	}
	var icons = {
			'jrIconListing' : '&#xe000;',
			'jrIconAddListing' : '&#xe001;',
			'jrIconEditListing' : '&#xe002;',
			'jrIconEdit' : '&#xe003;',
			'jrIconNote' : '&#xe003;',
			'jrIconDelete' : '&#xe004;',
			'jrIconSearch' : '&#xe005;',
			'jrIconManage' : '&#xe006;',
			'jrIconSync' : '&#xe006;',
			'jrIconCart' : '&#xe007;',
			'jrIconReviews' : '&#xe008;',
			'jrIconAddReview' : '&#xe009;',
			'jrIconComments' : '&#xe00a;',
			'jrIconAddComment' : '&#xe00b;',
			'jrIconThumbUp' : '&#xe00c;',
			'jrIconThumbDown' : '&#xe00d;',
			'jrIconEditor' : '&#xe00e;',
			'jrIconUsers' : '&#xe00f;',
			'jrIconPhoto' : '&#xe010;',
			'jrIconPhotos' : '&#xe010;',
			'jrIconVideo' : '&#xe011;',
			'jrIconVideos' : '&#xe011;',
			'jrIconAudio' : '&#xe012;',
			'jrIconAttachment' : '&#xe013;',
			'jrIconAttachments' : '&#xe013;',
			'jrIconMedia' : '&#xe014;',
			'jrIconAddMedia' : '&#xe015;',
			'jrIconGraph' : '&#xe016;',
			'jrIconRequired' : '&#xe017;',
			'jrIconRemove' : '&#xe018;',
			'jrIconCancel' : '&#xe018;',
			'jrIconPlus' : '&#xe019;',
			'jrIconNew' : '&#xe019;',
			'jrIconCopy' : '&#xe019;',
			'jrIconMinus' : '&#xe01a;',
			'jrIconYes' : '&#xe01b;',
			'jrIconSubmit' : '&#xe01b;',
			'jrIconPublished' : '&#xe01b;',
			'jrIconSave' : '&#xe01b;',
			'jrIconApply' : '&#xe01b;',
			'jrIconNo' : '&#xe01c;',
			'jrIconUnpublished' : '&#xe01c;',
			'jrIconWarning' : '&#xe01d;',
			'jrIconBullet' : '&#xe01e;',
			'jrIconCalendar' : '&#xe01f;',
			'jrIconMap' : '&#xe020;',
			'jrIconPin' : '&#xe021;',
			'jrIconCompare' : '&#xe022;',
			'jrIconFavorite' : '&#xe023;',
			'jrIconUnfavorite' : '&#xe024;',
			'jrIconNotFeatured' : '&#xe025;',
			'jrIconEmptyStar' : '&#xe025;',
			'jrIconFeatured' : '&#xe026;',
			'jrIconStar' : '&#xe026;',
			'jrIconArrowLeft' : '&#xe027;',
			'jrIconArrowRight' : '&#xe028;',
			'jrIconArrowDown' : '&#xe029;',
			'jrIconArrowUp' : '&#xe02a;',
			'jrIconLeft' : '&#xe02b;',
			'jrIconPrev' : '&#xe02b;',
			'jrIconRight' : '&#xe02c;',
			'jrIconNext' : '&#xe02c;',
			'jrIconUp' : '&#xe02d;',
			'jrIconDown' : '&#xe02e;',
			'jrIconMessage' : '&#xe02f;',
			'jrIconClaim' : '&#xe030;',
			'jrIconDrag' : '&#xe031;',
			'jrIconSort' : '&#xe032;',
			'jrIconInfo' : '&#xe033;',
			'jrIconList' : '&#xe034;',
			'jrIconCamera' : '&#xe035;',
			'jrIconPreview' : '&#xe036;',
			'jrIconRSSListing' : '&#xe037;',
			'jrIconRSSReview' : '&#xe038;',
			'jrIconPrint' : '&#xe039;'
		},
		els = document.getElementsByTagName('*'),
		i, attr, html, c, el;
	for (i = 0; i < els.length; i += 1) {
		el = els[i];
		attr = el.getAttribute('data-icon');
		if (attr) {
			addIcon(el, attr);
		}
		c = el.className;
		c = c.match(/jrIcon[^\s'"]+/);
		if (c) {
			addIcon(el, icons[c[0]]);
		}
	}
};