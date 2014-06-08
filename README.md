Pilau Slideshow
=========

A jQuery-driven slideshow plugin for WordPress.

**NOTE:** Depends on the [Developer's Custom Fields](https://github.com/gyrus/WordPress-Developers-Custom-Fields) plugin.

**NOTE:** Currently only slideshows with the `fade` rotate type are responsive.

## Installation

Note that the plugin folder should be named `slideshow`. This is because if the [GitHub Updater plugin](https://github.com/afragen/github-updater) is used to update this plugin, if the folder is named something other than this, it will get deleted, and the updated plugin folder with a different name will cause the plugin to be silently deactivated. Also, the folder name is hard-coded in the plugin's CSS.

## Basic use

Add a filter to your theme to specify which pages slideshows will apply to (see the `scope` parameter for [Developer's Custom Fields](http://sltaylor.co.uk/wordpress/developers-custom-fields-docs/#functions-boxes-fields)):

	add_filter( 'ps_scope', 'my_ps_scope' );
	function my_ps_scope( $scope ) {
		return array( 'posts' => array( 693 ) );
	}

### Template code

In the templates for pages with slideshows, use the following to output the page's slideshow:

	$PS = null;
	if ( class_exists( 'Pilau_Slideshow' ) ) {
		$PS = Pilau_Slideshow::get_instance();
	}
	$PS->slideshow();

To activate fullscreen mode, before outputting the slideshow:

	$PS->activate_fullscreen();

Slideshows can start from slides other than the first one by passing the `ps` query string parameter, set to the 1-based index of the slide.

### Shortcodes

Use the `[pilau-slideshow]` shortcode to place the slideshow in the content area of posts where the slideshow has been applied using the `ps_scope` filter.

### Custom slides

By default, each slideshow is built from images selected out of the images uploaded to the post in question.

In order to use custom slides, set the _Slideshow type_ option to "Custom slides". This will get rid of the default _Slideshow images_ custom fields box, and the developer must set up their own system to select slides. The slides themselves are passed through via the `ps_custom_slides` filter.

## Filter hooks

* `ps_scope` - Use to modify the scope for using slideshows (see the `scope` parameter for [Developer's Custom Fields](http://sltaylor.co.uk/wordpress/developers-custom-fields-docs/#functions-boxes-fields))
* `ps_disable_default_css` - Use to disable the default CSS
* `ps_custom_field_settings_box_args` - For altering the settings custom fields box arguments
* `ps_custom_field_images_box_args` - For altering the images custom fields box arguments
* `ps_slideshow_image_markup` - Use to alter the markup for each image, if not using custom slides; passes the markup string, the image ID
* `ps_slide_classes` - Use to alter the classes applied to each slide; passes the classes array, the slide ID
* `ps_custom_slides` - Use to pass in custom slides; passes the (empty) slides array, the slideshow object. The returned array should contain each slide's markup.
* `ps_mobile_breakpoint` - Change the mobile breakpoint, in pixels (default: 640)
