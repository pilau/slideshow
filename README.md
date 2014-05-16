Pilau Slideshow
=========

A JavaScript-driven slideshow plugin for WordPress.

**NOTE:** Depends on the [Developer's Custom Fields](https://github.com/gyrus/WordPress-Developers-Custom-Fields) plugin.

**NOTE:** Currently only slideshows with the `fade` rotate type are responsive.

## Basic use

Create an instance of the plugin class in your theme:

	$PS = null;
	if ( class_exists( 'Pilau_Slideshow' ) ) {
		$PS = Pilau_Slideshow::get_instance();
	}

Add a filter to your theme to specify which pages slideshows will apply to (see the `scope` parameter for [Developer's Custom Fields](http://sltaylor.co.uk/wordpress/developers-custom-fields-docs/#functions-boxes-fields)):

	add_filter( 'ps_scope', 'my_ps_scope' );
	function my_ps_scope( $scope ) {
		return array( 'posts' => array( 693 ) );
	}

In the templates for pages with slideshows, use the following to output the page's slideshow:

	$PS->slideshow();

## Filter hooks

* `ps_scope` - Use to modify the scope for using slideshows (see the `scope` parameter for [Developer's Custom Fields](http://sltaylor.co.uk/wordpress/developers-custom-fields-docs/#functions-boxes-fields))
* `ps_disable_default_css` - Use to disable the default CSS
* `ps_slideshow_image_markup` - Use to alter the markup for each image; passes two parameters, the markup string and the image ID