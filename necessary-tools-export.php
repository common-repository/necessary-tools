<?php
/**
 * Necessary Tools WordPress Export
 *
 * @package necessary-tools
 */

class NecessaryToolsExport {

	/**
	 * Version number for the export format.
	 *
	 * Bump this when something changes that might affect compatibility.
	 *
	 * @since 2.5.0
	 */
	public $WXR_VERSION = '1.2';

	public function __construct() {
		add_filter( 'wxr_export_skip_postmeta', array( $this, 'wxr_filter_postmeta' ), 10, 2 );
	}

	/**
	 * Wrap given string in XML CDATA tag.
	 *
	 * @since 2.1.0
	 *
	 * @param string $str String to wrap in XML CDATA tag.
	 * @return string
	 */
	private function wxr_cdata( $str ) {
		if ( ! seems_utf8( $str ) ) {
			$str = utf8_encode( $str );
		}
		// $str = ent2ncr(esc_html($str));
		$str = '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';

		return $str;
	}

	/**
	 * Return the URL of the site
	 *
	 * @since 2.5.0
	 *
	 * @return string Site URL.
	 */
	private function wxr_site_url() {
		// Multisite: the base URL.
		if ( is_multisite() )
			return network_home_url();
		// WordPress (single site): the blog URL.
		else
			return get_bloginfo_rss( 'url' );
	}

	/**
	 * Output a cat_name XML tag from a given category object
	 *
	 * @since 2.1.0
	 *
	 * @param object $category Category Object
	 */
	private function wxr_cat_name( $category ) {
		if ( empty( $category->name ) )
			return;

		echo '<wp:cat_name>' . $this->wxr_cdata( $category->name ) . "</wp:cat_name>\n";
	}

	/**
	 * Output a category_description XML tag from a given category object
	 *
	 * @since 2.1.0
	 *
	 * @param object $category Category Object
	 */
	private function wxr_category_description( $category ) {
		if ( empty( $category->description ) )
			return;

		echo '<wp:category_description>' . $this->wxr_cdata( $category->description ) . "</wp:category_description>\n";
	}

	/**
	 * Output a tag_name XML tag from a given tag object
	 *
	 * @since 2.3.0
	 *
	 * @param object $tag Tag Object
	 */
	private function wxr_tag_name( $tag ) {
		if ( empty( $tag->name ) )
			return;

		echo '<wp:tag_name>' . $this->wxr_cdata( $tag->name ) . "</wp:tag_name>\n";
	}

	/**
	 * Output a tag_description XML tag from a given tag object
	 *
	 * @since 2.3.0
	 *
	 * @param object $tag Tag Object
	 */
	private function wxr_tag_description( $tag ) {
		if ( empty( $tag->description ) )
			return;

		echo '<wp:tag_description>' . $this->wxr_cdata( $tag->description ) . "</wp:tag_description>\n";
	}

	/**
	 * Output a term_name XML tag from a given term object
	 *
	 * @since 2.9.0
	 *
	 * @param object $term Term Object
	 */
	private function wxr_term_name( $term ) {
		if ( empty( $term->name ) )
			return;

		echo '<wp:term_name>' . $this->wxr_cdata( $term->name ) . "</wp:term_name>\n";
	}

	/**
	 * Output a term_description XML tag from a given term object
	 *
	 * @since 2.9.0
	 *
	 * @param object $term Term Object
	 */
	private function wxr_term_description( $term ) {
		if ( empty( $term->description ) )
			return;

		echo "\t\t<wp:term_description>" . $this->wxr_cdata( $term->description ) . "</wp:term_description>\n";
	}

	/**
	 * Output term meta XML tags for a given term object.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_Term $term Term object.
	 */
	private function wxr_term_meta( $term ) {
		global $wpdb;

		$termmeta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->termmeta WHERE term_id = %d", $term->term_id ) );

		foreach ( $termmeta as $meta ) {
			/**
			 * Filters whether to selectively skip term meta used for WXR exports.
			 *
			 * Returning a truthy value to the filter will skip the current meta
			 * object from being exported.
			 *
			 * @since 4.6.0
			 *
			 * @param bool   $skip     Whether to skip the current piece of term meta. Default false.
			 * @param string $meta_key Current meta key.
			 * @param object $meta     Current meta object.
			 */
			if ( ! apply_filters( 'wxr_export_skip_termmeta', false, $meta->meta_key, $meta ) ) {
				printf( "\t\t<wp:termmeta>\n\t\t\t<wp:meta_key>%s</wp:meta_key>\n\t\t\t<wp:meta_value>%s</wp:meta_value>\n\t\t</wp:termmeta>\n", $this->wxr_cdata( $meta->meta_key ), $this->wxr_cdata( $meta->meta_value ) );
			}
		}
	}

	/**
	 * Output list of authors with posts
	 *
	 * @since 3.1.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array $post_ids Array of post IDs to filter the query by. Optional.
	 */
	private function wxr_authors_list( array $post_ids = null ) {
		global $wpdb;

		if ( !empty( $post_ids ) ) {
			$post_ids = array_map( 'absint', $post_ids );
			$and = 'AND ID IN ( ' . implode( ', ', $post_ids ) . ')';
		} else {
			$and = '';
		}

		$authors = array();
		$results = $wpdb->get_results( "SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_status != 'auto-draft' $and" );
		foreach ( (array) $results as $result )
			$authors[] = get_userdata( $result->post_author );

		$authors = array_filter( $authors );

		foreach ( $authors as $author ) {
			echo "\t<wp:author>";
			echo '<wp:author_id>' . intval( $author->ID ) . '</wp:author_id>';
			echo '<wp:author_login>' . $this->wxr_cdata( $author->user_login ) . '</wp:author_login>';
			echo '<wp:author_email>' . $this->wxr_cdata( $author->user_email ) . '</wp:author_email>';
			echo '<wp:author_display_name>' . $this->wxr_cdata( $author->display_name ) . '</wp:author_display_name>';
			echo '<wp:author_first_name>' . $this->wxr_cdata( $author->first_name ) . '</wp:author_first_name>';
			echo '<wp:author_last_name>' . $this->wxr_cdata( $author->last_name ) . '</wp:author_last_name>';
			echo "</wp:author>\n";
		}
	}

	/**
	 * Output all navigation menu terms
	 *
	 * @since 3.1.0
	 */
	private function wxr_nav_menu_terms() {
		$nav_menus = wp_get_nav_menus();
		if ( empty( $nav_menus ) || ! is_array( $nav_menus ) )
			return;

		foreach ( $nav_menus as $menu ) {
			echo "\t<wp:term>";
			echo '<wp:term_id>' . intval( $menu->term_id ) . '</wp:term_id>';
			echo '<wp:term_taxonomy>nav_menu</wp:term_taxonomy>';
			echo '<wp:term_slug>' . $this->wxr_cdata( $menu->slug ) . '</wp:term_slug>';
			$this->wxr_term_name( $menu );
			echo "</wp:term>\n";
		}
	}

	/**
	 * Output list of taxonomy terms, in XML tag format, associated with a post
	 *
	 * @since 2.3.0
	 * @param WP_Post $post The post obj.
	 */
	public function wxr_post_taxonomy( $post ) {
		$taxonomies = get_object_taxonomies( $post->post_type );
		if ( empty( $taxonomies ) )
			return;
		$terms = wp_get_object_terms( $post->ID, $taxonomies );

		foreach ( (array) $terms as $term ) {
			echo "\t\t<category domain=\"{$term->taxonomy}\" nicename=\"{$term->slug}\">" . $this->wxr_cdata( $term->name ) . "</category>\n";
		}
	}

	/**
	 *
	 * @param bool   $return_me
	 * @param string $meta_key
	 * @return bool
	 */
	public function wxr_filter_postmeta( $return_me, $meta_key ) {
		if ( '_edit_lock' == $meta_key )
			$return_me = true;
		return $return_me;
	}

	/**
	 * Generates the WXR export file for download.
	 *
	 * This function is an alteration of the WP Core 'export_wp()' function in
	 * 'wp-admin/includes/export.php'.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb    $wpdb WordPress database abstraction object.
	 *
	 * @param array $args {
	 *     Arguments for generating the WXR export file for download.
	 *
	 *     @type array $ids A list of post IDs to export.
	 * }
	 */
	public function export_wp( $args = array() ) {
		global $wpdb;

		if ( ! isset( $args ) || ! isset( $args['ids'] ) ) {
			return;
		}

		/**
		 * Fires at the beginning of an export, before any headers are sent. Custom NT action.
		 *
		 * @since 2.3.0
		 *
		 * @param array $args An array of export arguments.
		 */
		do_action( 'export_wp', $args );

		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		if ( ! empty( $sitename ) ) {
			$sitename .= '.';
		}
		$date = date( 'Y-m-d' );
		$wp_filename = $sitename . 'wordpress.' . $date . '.xml';

		/**
		 * Filters the export filename.
		 *
		 * @since 4.4.0
		 *
		 * @param string $wp_filename The name of the file for download.
		 * @param string $sitename    The site name.
		 * @param string $date        Today's date, formatted.
		 */
		$filename = apply_filters( 'export_wp_filename', $wp_filename, $sitename, $date );

		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );

		$post_ids = $args['ids'];

		echo '<?xml version="1.0" encoding="' . get_bloginfo('charset') . "\" ?>\n";
	?>
<!-- This is a WordPress eXtended RSS file generated by WordPress as an export of your site. -->
<!-- It contains information about your site's posts, pages, comments, categories, and other content. -->
<!-- You may use this file to transfer that content from one site to another. -->
<!-- This file is not intended to serve as a complete backup of your site. -->

<!-- To import this information into a WordPress site follow these steps: -->
<!-- 1. Log in to that site as an administrator. -->
<!-- 2. Go to Tools: Import in the WordPress admin panel. -->
<!-- 3. Install the "WordPress" importer from the list. -->
<!-- 4. Activate & Run Importer. -->
<!-- 5. Upload this file using the form provided on that page. -->
<!-- 6. You will first be asked to map the authors in this export file to users -->
<!--    on the site. For each author, you may choose to map to an -->
<!--    existing user on the site or to create a new user. -->
<!-- 7. WordPress will then import each of the posts, pages, comments, categories, etc. -->
<!--    contained in this file into your site. -->

<?php the_generator( 'export' ); ?>
<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/<?php echo $this->WXR_VERSION; ?>/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/<?php echo $this->WXR_VERSION; ?>/"
>

<channel>
	<title><?php bloginfo_rss( 'name' ); ?></title>
	<link><?php bloginfo_rss( 'url' ); ?></link>
	<description><?php bloginfo_rss( 'description' ); ?></description>
	<pubDate><?php echo date( 'D, d M Y H:i:s +0000' ); ?></pubDate>
	<language><?php bloginfo_rss( 'language' ); ?></language>
	<wp:wxr_version><?php echo $this->WXR_VERSION; ?></wp:wxr_version>
	<wp:base_site_url><?php echo $this->wxr_site_url(); ?></wp:base_site_url>
	<wp:base_blog_url><?php bloginfo_rss( 'url' ); ?></wp:base_blog_url>

<?php $this->wxr_authors_list( $post_ids ); ?>

<?php foreach ( $cats as $c ) : ?>
	<wp:category>
		<wp:term_id><?php echo intval( $c->term_id ); ?></wp:term_id>
		<wp:category_nicename><?php echo $this->wxr_cdata( $c->slug ); ?></wp:category_nicename>
		<wp:category_parent><?php echo $this->wxr_cdata( $c->parent ? $cats[$c->parent]->slug : '' ); ?></wp:category_parent>
		<?php $this->wxr_cat_name( $c );
		$this->wxr_category_description( $c );
		$this->wxr_term_meta( $c ); ?>
	</wp:category>
<?php endforeach; ?>
<?php foreach ( $tags as $t ) : ?>
	<wp:tag>
		<wp:term_id><?php echo intval( $t->term_id ); ?></wp:term_id>
		<wp:tag_slug><?php echo $this->wxr_cdata( $t->slug ); ?></wp:tag_slug>
		<?php $this->wxr_tag_name( $t );
		$this->wxr_tag_description( $t );
		$this->wxr_term_meta( $t ); ?>
	</wp:tag>
<?php endforeach; ?>
<?php foreach ( $terms as $t ) : ?>
	<wp:term>
		<wp:term_id><?php echo $this->wxr_cdata( $t->term_id ); ?></wp:term_id>
		<wp:term_taxonomy><?php echo $this->wxr_cdata( $t->taxonomy ); ?></wp:term_taxonomy>
		<wp:term_slug><?php echo $this->wxr_cdata( $t->slug ); ?></wp:term_slug>
		<wp:term_parent><?php echo $this->wxr_cdata( $t->parent ? $terms[$t->parent]->slug : '' ); ?></wp:term_parent>
		<?php $this->wxr_term_name( $t );
		$this->wxr_term_description( $t );
		$this->wxr_term_meta( $t ); ?>
	</wp:term>
<?php endforeach; ?>
<?php if ( 'all' == $args['content'] ) $this->wxr_nav_menu_terms(); ?>

	<?php
	/** This action is documented in wp-includes/feed-rss2.php */
	do_action( 'rss2_head' );
	?>

<?php if ( $post_ids ) {
	/**
	 * @global WP_Query $wp_query
	 */
	global $wp_query;

	// Fake being in the loop.
	$wp_query->in_the_loop = true;

	// Fetch 20 posts at a time rather than loading the entire table into memory.
	while ( $next_posts = array_splice( $post_ids, 0, 20 ) ) {
	$where = 'WHERE ID IN (' . join( ',', $next_posts ) . ')';
	$posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} $where" );

	// Begin Loop.
	foreach ( $posts as $post ) {
		setup_postdata( $post );
		$is_sticky = is_sticky( $post->ID ) ? 1 : 0;
?>
	<item>
		<title><?php
			/** This filter is documented in wp-includes/feed.php */
			echo apply_filters( 'the_title_rss', $post->post_title );
		?></title>
		<link><?php the_permalink_rss() ?></link>
		<pubDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ); ?></pubDate>
		<dc:creator><?php echo $this->wxr_cdata( get_the_author_meta( 'login' ) ); ?></dc:creator>
		<guid isPermaLink="false"><?php the_guid(); ?></guid>
		<description></description>
		<content:encoded><?php
			/**
			 * Filters the post content used for WXR exports.
			 *
			 * @since 2.5.0
			 *
			 * @param string $post_content Content of the current post.
			 */
			echo $this->wxr_cdata( apply_filters( 'the_content_export', $post->post_content ) );
		?></content:encoded>
		<excerpt:encoded><?php
			/**
			 * Filters the post excerpt used for WXR exports.
			 *
			 * @since 2.6.0
			 *
			 * @param string $post_excerpt Excerpt for the current post.
			 */
			echo $this->wxr_cdata( apply_filters( 'the_excerpt_export', $post->post_excerpt ) );
		?></excerpt:encoded>
		<wp:post_id><?php echo intval( $post->ID ); ?></wp:post_id>
		<wp:post_date><?php echo $this->wxr_cdata( $post->post_date ); ?></wp:post_date>
		<wp:post_date_gmt><?php echo $this->wxr_cdata( $post->post_date_gmt ); ?></wp:post_date_gmt>
		<wp:comment_status><?php echo $this->wxr_cdata( $post->comment_status ); ?></wp:comment_status>
		<wp:ping_status><?php echo $this->wxr_cdata( $post->ping_status ); ?></wp:ping_status>
		<wp:post_name><?php echo $this->wxr_cdata( $post->post_name ); ?></wp:post_name>
		<wp:status><?php echo $this->wxr_cdata( $post->post_status ); ?></wp:status>
		<wp:post_parent><?php echo intval( $post->post_parent ); ?></wp:post_parent>
		<wp:menu_order><?php echo intval( $post->menu_order ); ?></wp:menu_order>
		<wp:post_type><?php echo $this->wxr_cdata( $post->post_type ); ?></wp:post_type>
		<wp:post_password><?php echo $this->wxr_cdata( $post->post_password ); ?></wp:post_password>
		<wp:is_sticky><?php echo intval( $is_sticky ); ?></wp:is_sticky>
<?php	if ( $post->post_type == 'attachment' ) : ?>
		<wp:attachment_url><?php echo $this->wxr_cdata( wp_get_attachment_url( $post->ID ) ); ?></wp:attachment_url>
<?php 	endif; ?>
<?php 	$this->wxr_post_taxonomy( $post ); ?>
<?php	$postmeta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE post_id = %d", $post->ID ) );
		foreach ( $postmeta as $meta ) :
			/**
			 * Filters whether to selectively skip post meta used for WXR exports.
			 *
			 * Returning a truthy value to the filter will skip the current meta
			 * object from being exported.
			 *
			 * @since 3.3.0
			 *
			 * @param bool   $skip     Whether to skip the current post meta. Default false.
			 * @param string $meta_key Current meta key.
			 * @param object $meta     Current meta object.
			 */
			if ( apply_filters( 'wxr_export_skip_postmeta', false, $meta->meta_key, $meta ) )
				continue;
		?>
		<wp:postmeta>
			<wp:meta_key><?php echo $this->wxr_cdata( $meta->meta_key ); ?></wp:meta_key>
			<wp:meta_value><?php echo $this->wxr_cdata( $meta->meta_value ); ?></wp:meta_value>
		</wp:postmeta>
<?php	endforeach;

		$_comments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved <> 'spam'", $post->ID ) );
		$comments = array_map( 'get_comment', $_comments );
		foreach ( $comments as $c ) : ?>
		<wp:comment>
			<wp:comment_id><?php echo intval( $c->comment_ID ); ?></wp:comment_id>
			<wp:comment_author><?php echo $this->wxr_cdata( $c->comment_author ); ?></wp:comment_author>
			<wp:comment_author_email><?php echo $this->wxr_cdata( $c->comment_author_email ); ?></wp:comment_author_email>
			<wp:comment_author_url><?php echo esc_url_raw( $c->comment_author_url ); ?></wp:comment_author_url>
			<wp:comment_author_IP><?php echo $this->wxr_cdata( $c->comment_author_IP ); ?></wp:comment_author_IP>
			<wp:comment_date><?php echo $this->wxr_cdata( $c->comment_date ); ?></wp:comment_date>
			<wp:comment_date_gmt><?php echo $this->wxr_cdata( $c->comment_date_gmt ); ?></wp:comment_date_gmt>
			<wp:comment_content><?php echo $this->wxr_cdata( $c->comment_content ) ?></wp:comment_content>
			<wp:comment_approved><?php echo $this->wxr_cdata( $c->comment_approved ); ?></wp:comment_approved>
			<wp:comment_type><?php echo $this->wxr_cdata( $c->comment_type ); ?></wp:comment_type>
			<wp:comment_parent><?php echo intval( $c->comment_parent ); ?></wp:comment_parent>
			<wp:comment_user_id><?php echo intval( $c->user_id ); ?></wp:comment_user_id>
<?php		$c_meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->commentmeta WHERE comment_id = %d", $c->comment_ID ) );
			foreach ( $c_meta as $meta ) :
				/**
				 * Filters whether to selectively skip comment meta used for WXR exports.
				 *
				 * Returning a truthy value to the filter will skip the current meta
				 * object from being exported.
				 *
				 * @since 4.0.0
				 *
				 * @param bool   $skip     Whether to skip the current comment meta. Default false.
				 * @param string $meta_key Current meta key.
				 * @param object $meta     Current meta object.
				 */
				if ( apply_filters( 'wxr_export_skip_commentmeta', false, $meta->meta_key, $meta ) ) {
					continue;
				}
			?>
			<wp:commentmeta>
				<wp:meta_key><?php echo $this->wxr_cdata( $meta->meta_key ); ?></wp:meta_key>
				<wp:meta_value><?php echo $this->wxr_cdata( $meta->meta_value ); ?></wp:meta_value>
			</wp:commentmeta>
<?php		endforeach; ?>
		</wp:comment>
<?php	endforeach; ?>
	</item>
<?php
	}
	}
} ?>
</channel>
</rss>
<?php
	}
}