<?php
/**
 * Custom functions that act independently of the theme templates
 *
 * Eventually, some of the functionality here could be replaced by core features
 *
 * @package Padhang
 */

/**
 * Get our wp_nav_menu() fallback, wp_page_menu(), to show a home link.
 *
 * @param array $args Configuration arguments.
 * @return array
 */
function padhang_page_menu_args( $args ) {
	$args['show_home'] = true;
	return $args;
}
add_filter( 'wp_page_menu_args', 'padhang_page_menu_args' );

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function padhang_body_classes( $classes ) {
	// Adds a class of group-blog to blogs with more than 1 published author.
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}

	if ( is_singular() ) {
		$classes[] = 'singular';
	}

	return $classes;
}
add_filter( 'body_class', 'padhang_body_classes' );

/**
 * Filters wp_title to print a neat <title> tag based on what is being viewed.
 *
 * @param string $title Default title text for current view.
 * @param string $sep Optional separator.
 * @return string The filtered title.
 */
function padhang_wp_title( $title, $sep ) {
	if ( is_feed() ) {
		return $title;
	}
	
	global $page, $paged;

	// Add the blog name
	$title .= get_bloginfo( 'name', 'display' );

	// Add the blog description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) ) {
		$title .= " $sep $site_description";
	}

	// Add a page number if necessary:
	if ( $paged >= 2 || $page >= 2 ) {
		$title .= " $sep " . sprintf( __( 'Page %s', 'padhang' ), max( $paged, $page ) );
	}

	return $title;
}
add_filter( 'wp_title', 'padhang_wp_title', 10, 2 );

/**
 * Sets the authordata global when viewing an author archive.
 *
 * This provides backwards compatibility with
 * http://core.trac.wordpress.org/changeset/25574
 *
 * It removes the need to call the_post() and rewind_posts() in an author
 * template to print information about the author.
 *
 * @global WP_Query $wp_query WordPress Query object.
 * @return void
 */
function padhang_setup_author() {
	global $wp_query;

	if ( $wp_query->is_author() && isset( $wp_query->post ) ) {
		$GLOBALS['authordata'] = get_userdata( $wp_query->post->post_author );
	}
}
add_action( 'wp', 'padhang_setup_author' );

/**
 * Get the first URL on a post. Falls back to the post permalink if no URL is found.
 *
 * @return string The Link format URL.
 */
function padhang_get_link_url() {
	$content = get_the_content();
	$has_url = get_url_in_content( $content );

	return ( $has_url ) ? $has_url : apply_filters( 'the_permalink', get_permalink() );
}

if ( ! function_exists( 'padhang_comment' ) ) :
/**
 * Template for comments and pingbacks.
 * Used as a callback by wp_list_comments() for displaying the comments.
 */
function padhang_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case 'pingback' :
		case 'trackback' :
		// Display trackbacks differently than normal comments.
	?>
	<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
		<p><?php _e( 'Pingback:', 'padhang' ); ?> <?php comment_author_link(); ?> <?php edit_comment_link( __( '(Edit)', 'padhang' ), '<span class="edit-link">', '</span>' ); ?></p>
	<?php
			break;
		default :
		// Proceed with normal comments.
		global $post;
	?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
		<article id="comment-<?php comment_ID(); ?>" class="comment">
			<div class="comment-avatar">
				<?php echo get_avatar( $comment, $args['avatar_size'] ); ?>
			</div><!-- .comment-avatar -->
			
			<footer class="comment-meta">
				<div class="comment-author vcard">
					<?php 
						printf( '<b class="fn">%1$s</b> %2$s',
							get_comment_author_link(),
							( $comment->user_id === $post->post_author ) ? '<span>' . __( 'Post author', 'padhang' ) . '</span>' : ''
						);
					?>
				</div><!-- .comment-author -->

				<div class="comment-metadata">
					<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>" title="<?php printf( _x( '%1$s at %2$s', '1: date, 2: time', 'padhang' ), get_comment_date(), get_comment_time() ); ?>">
							<time datetime="<?php comment_time( 'c' ); ?>">
								<?php comment_date(); ?>
							</time>
					</a>
					<span class="sep">&middot;</span>
					<?php edit_comment_link( __( 'Edit', 'padhang' ), '<span class="edit-link">', '</span>' ); ?>
				</div><!-- .comment-metadata -->

				<?php if ( '0' == $comment->comment_approved ) : ?>
					<p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'padhang' ); ?></p>
				<?php endif; ?>
			</footer><!-- .comment-meta -->

			<div class="comment-content">
				<?php comment_text(); ?>
			</div><!-- .comment-content -->

			<div class="reply">
				<?php comment_reply_link( array_merge( $args, array( 'reply_text' => __( 'Reply', 'padhang' ), 'after' => ' <span>&darr;</span>', 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
			</div><!-- .reply -->
		</article><!-- #comment-## -->
	<?php
		break;
	endswitch; // end comment_type check
}
endif;

/**
 * Get the URL for Google Webfonts with support for language character.
 * see: http://themeshaper.com/2014/08/13/how-to-add-google-fonts-to-wordpress-themes/
 * 
 * @return string The fonts URL.
 */
function padhang_fonts_url() {
	$fonts_kit = get_theme_mod( 'fonts_kit', 'roboto' );
	$fonts_url = '';
	$font_families = array();

	/* Translators: If there are characters in your language that are not
	* supported by Roboto or Roboto Slab, translate this to 'off'. Do not translate
	* into your own language.
	*/
	$roboto = _x( 'on', 'Roboto font: on or off', 'padhang' );
	$roboto_slab = _x( 'on', 'Roboto Slab font: on or off', 'padhang' );

	/* Translators: If there are characters in your language that are not
	* supported by Open Sans or Bitter, translate this to 'off'. Do not translate
	* into your own language.
	*/
	$opensans = _x( 'on', 'Open Sans font: on or off', 'padhang' );
	$bitter = _x( 'on', 'Bitter font: on or off', 'padhang' );
	
	switch ( $fonts_kit ) {
		case 'opensans' :
			if ( 'off' !== $opensans || 'off' !== $bitter ) {
				if ( 'off' !== $opensans ) {
					$font_families[] = 'Open Sans:400,400italic,700,700italic';
				}

				if ( 'off' !== $bitter ) {
					$font_families[] = 'Bitter:400italic,700';
				}
			}
			break;

		default:
			if ( 'off' !== $roboto || 'off' !== $roboto_slab ) {
				if ( 'off' !== $roboto ) {
					$font_families[] = 'Roboto:400,400italic,700,700italic';
				}

				if ( 'off' !== $roboto_slab ) {
					$font_families[] = 'Roboto Slab:300,700';
				}
			}
	}

	$query_args = array(
		'family' => urlencode( implode( '|', $font_families ) ),
		'subset' => urlencode( 'latin,latin-ext' ),
	);

	$fonts_url = add_query_arg( $query_args, '//fonts.googleapis.com/css' );

	return $fonts_url;
}

/**
 * Give author avatar for status format post
 */
function padhang_status_post( $content ) {
	if( has_post_format( 'status' ) ) {
		$avatar = get_avatar( get_the_author_meta( 'ID' ), 64, '', get_the_author_meta( 'display_name' ) );

		return $avatar . $content;
	}

	return $content;
}
add_filter( 'the_content', 'padhang_status_post', 10, 1 );