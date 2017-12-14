<?php

// add_action( 'genesis_entry_content', 'ps_do_slideshow', 20 );
function ps_do_slideshow() {

	// Bail if Posts 2 Posts is not active.
	if ( ! function_exists( 'p2p_register_connection_type' ) ) {
		return;
	}

	// Bail if not a single post.
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	// Get slideshows.
	$slideshows = get_posts( array(
		'connected_type'   => 'slideshow_to_posts',
		'connected_items'  => get_queried_object(),
		'nopaging'         => true,
		'suppress_filters' => false,
	) );

	// Bail if no slideshows.
	if ( ! $slideshows ) {
		return;
	}

	// Get first slideshow. There should only be one anyway, but still.
	$slideshow = $slideshows[0];
d( $slideshow->post_content );
	echo apply_filters( 'the_content', $slideshow->post_content );
}
