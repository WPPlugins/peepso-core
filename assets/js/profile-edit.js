// Move in all methods used only in editing Profile information into this file
// This would include things like confirm_remove_avatar() and confirm_remove_cover_photo()
// since those are only allowed when a user is able to edit the Profile page

/*
 * User interactions for profile avatar page
 * @package PeepSo
 * @author PeepSo
 */

//$PeepSo.log("profile-avatar.js");

function PsProfileAvatar()
{
	this.g_coords = { x: 0, y: 0, x2: 0, y2: 0, w: 0, h: 0 };
}

var profileavatar = new PsProfileAvatar();

// called to save coordinates while cropping
PsProfileAvatar.prototype.setCoords = function(set)
{
	this.g_coords.x = set.x;
	this.g_coords.y = set.y;
	this.g_coords.x2 = set.x2;
	this.g_coords.y2 = set.y2;
	this.g_coords.w = set.w;
	this.g_coords.h = set.h;
};

// called to save and crop thumbnail
PsProfileAvatar.prototype.saveThumbnail = function()
{
	var that = this;
	var is_tmp = +jQuery('#cWindowContent [name=is_tmp]').val() ? 1 : 0;

	var $img = jQuery('#cWindowContent .large-profile-pic'),
		imgWidth = $img.width(),
		imgHeight = $img.height(),
		imgOrigWidth = $img[0].naturalWidth || imgWidth,
		imgOrigHeight = $img[0].naturalHeight || imgHeight,
		ratio = imgOrigWidth / imgWidth,
		maxLength = 1500,
		ratio;

	// shinken very large image
	if (( imgOrigWidth > maxLength ) || ( imgOrigHeight > maxLength )) {
		if ( imgOrigWidth > imgOrigHeight ) {
			imgOrigHeight = ( maxLength / imgOrigWidth ) * imgOrigHeight;
			imgOrigWidth = maxLength;
		} else {
			imgOrigWidth = ( maxLength / imgOrigHeight ) * imgOrigWidth;
			imgOrigHeight = maxLength;
		}
	}

	// update ratio
	ratio = imgOrigWidth / imgWidth;

	req = {
		u: peepsodata.userid,
		x: Math.round(this.g_coords.x * ratio),
		y: Math.round(this.g_coords.y * ratio),
		x2: Math.round(this.g_coords.x2 * ratio),
		y2: Math.round(this.g_coords.y2 * ratio),
		width: imgOrigWidth,
		height: imgOrigHeight,
		tmp: is_tmp,
		_wpnonce: jQuery("#_photononce").val()
	};

	$PeepSo.get("profile.crop", req, function(resp) {
		var rand = '?' + Math.floor( Math.random() * 10000 );
		var $img = jQuery("#cWindowContent .large-profile-pic");

		ps_crop.detach( $img );

		if (resp.success) {
			if (is_tmp) {
				jQuery("#cWindowContent .js-focus-avatar img").attr("src", resp.data.image_url + rand);
			} else {
				that.refreshThumbnail();
			}
		}
	});

	// show the update button, but hide the save button
	jQuery("#cWindowContent .update-thumbnail").show();
	jQuery("#cWindowContent .update-thumbnail-save").hide();
	jQuery("#cWindowContent .update-thumbnail-guide").hide();
};

// called to select coordinates on an image
PsProfileAvatar.prototype.imgSelect = function(img, selection)
{
	this.g_coords.x = selection.x1;
	this.g_coords.y = selection.y1;
	this.g_coords.x2 = selection.x2;
	this.g_coords.y2 = selection.y2;
	this.g_coords.w = selection.width;
	this.g_coords.h = selection.height;
};


// updates thumbnail image
PsProfileAvatar.prototype.updateThumbnail = function()
{
	var $img = jQuery("#cWindowContent .large-profile-pic");
	ps_crop.init({
		elem: $img,
		change: jQuery.proxy(function( coords ) {
			this.imgSelect( $img[0], {
				x1: coords.x,
				y1: coords.y,
				x2: coords.x + coords.width,
				y2: coords.y + coords.height,
				width: coords.width,
				height: coords.height
			});
		}, this )
	});

	// show the save button, but hide the update button
	jQuery("#cWindowContent .update-thumbnail").hide();
	jQuery("#cWindowContent .update-thumbnail-save").show();
	jQuery("#cWindowContent .update-thumbnail-guide").show();
};

// forces update of thumbnail image
PsProfileAvatar.prototype.refreshThumbnail = function()
{
	var rand = Math.random();

	var src = jQuery("#cWindowContent .thumbnail-profile-pic").attr("src");
	jQuery("#cWindowContent .thumbnail-profile-pic").attr("src", src + "?" + rand);
	var src = jQuery(".js-focus-avatar img").attr("src");
	var url = src.split("?", 1);
	jQuery(".js-focus-avatar img").attr("src", url + "?" + rand);

	// force image reload in iframe
	var iframe = document.getElementById("ps-profile-avatar-iframe");
	iframe.innerHTML = iframe.innerHTML;

	// force reloads of all references to the avatar
	var fr = jQuery("#ps-profile-avatar-iframe");
	var src = fr.attr("src");
	fr.attr("src", src);
	// check for thumbnail; fix reference url
	if (-1 !== src.indexOf("/peepso/assets/images/user")) {
		var newsrc = jQuery(".js-focus-avatar img").attr("src");
		newsrc.replace("-full.jpg", ".jpg");
		src = newsrc;
	}

	var imgs = jQuery("img.cavatar");
	jQuery.each(imgs, function(idx, img) {
		var author = jQuery(img).data("author");
		author = parseInt(author);
		if (author === peepsodata.currentuserid) {
			var href = jQuery(img).attr("src");
			if (href.indexOf("?") > 0)
				href = href.split("?", 1);

			jQuery(img).attr("src", href + "?" + rand);
		}
	});
};

// remove artifacts left over from using the image selection tool
PsProfileAvatar.prototype.cleanup_after_imgareaslect = function()
{
	jQuery(".imgareaselect-selection").parent().hide();
	jQuery(".imgareaselect-outer").hide();
};


// do initialization on document ready event
jQuery(document).ready( function ($)
{
	// window close events need to clean up possible artifacts left by imgareaselect
	ps_observer.add_filter("pswindow_close", function() { profileavatar.cleanup_after_imgareaslect(); } );
});

// EOF
