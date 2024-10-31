<?php
/*
*	@runTestsInSeparateProcesses
*/
class NecessaryToolsPluginAjaxTest extends WP_Ajax_UnitTestCase {

	public function simulate_admin_user( $factory_user ) {
		$admin_user = $factory_user->create_object(
			array(
				'user_login' => 'test_admin',
				'user_pass' =>	'....',
				'role' => 'administrator'
			)
		);
		wp_set_current_user( $admin_user );
	}

	/**
	 *	@group ajax
	 */
	public function test_clone_post_with_valid_posts() {
		global $_POST;
		$NecessaryTools = new NecessaryToolsPlugin();

		$this->simulate_admin_user( $this->factory->user );

		// create the fake parent post
		$post_title = 'Super Awesome Test Post';
		$post_type = 'post';
		$post_status = 'publish';
		$post_category = array();
		$post_tag = array();
		$post_format = array();
		$post_format_terms = array(
			0 => 'aside',
			1 => 'link',
			2 => 'gallery'
		);
		
		// create new fake categories, tags, and taxonomies for the fake post
		$new_cat_1 = $this->factory->category->create();
		$new_cat_2 = $this->factory->category->create();
		$new_tag_1 = $this->factory->tag->create();
		$new_tag_2 = $this->factory->tag->create();
		foreach ( $post_format_terms as $index => $term ) {
			$new_format_n = $this->factory->term->create_object(
				array(
					'name' => ucfirst( $term ),
					'taxonomy' => 'post_format',
					array(
						'slug' => $term
					)
				)
			);
			$post_format[] = $new_format_n;
		}
		
		$post_category = array(
			$new_cat_1,
			$new_cat_2
		);
		$post_tag = array(
			$new_tag_1,
			$new_tag_2
		);
		$post_id = $this->factory->post->create( 
			array( 
				'post_title' => $post_title,
				'post_type' => $post_type,
				'post_status' => $post_status,
				'post_category' => $post_category,
				'tags_input' => $post_tag
			)
		);
		$nonce = wp_create_nonce( "{$NecessaryTools->nonce_actions['nt_clone_post']}_{$post_id}" );

		// the post format taxonomy has to be added after the post is created
		$this->factory->term->add_post_terms( $post_id, $post_format, 'post_format' );

		// prepare the fake AJAX call
		$_POST['post_id'] = $post_id;
		$_POST['post_type'] = $post_type;
		$_POST['_ajax_nonce'] = $nonce;

		// clone the post
		$err_msg = '';
		try {
			$this->_handleAjax( 'nt_clone_post' );
		} catch ( WPAjaxDieStopException $e ) {
			$err_msg = $e->getMessage();
		}

		$this->assertTrue( isset( $e ) );
		$this->assertNotEquals( 0, $err_msg );

		// escape the admin_url before it is used in the regex match
		$admin_url = str_replace( '/', '\/', esc_url( admin_url() ) );

		// check for the cloned post id from the fake AJAX response
		$regex = '/(?<=('.$admin_url.'post\.php\?post=))[0-9]+(?=(&action=edit))/';
		
		$this->assertRegExp( $regex, $err_msg );
		
		preg_match( $regex, $err_msg, $matches );

		// get the data for the cloned post
		$clone_id = $matches[0];
		$clone_custom = get_post_custom( $clone_id );

		// set up an array of categories for the cloned post
		$clone_tax = get_object_taxonomies( get_post_type( $clone_id ) );

		$this->assertTrue( ! empty( $clone_tax ) );

		$clone_term_id_array = array();
		if ( ! empty( $clone_tax ) ) {
			foreach ( $clone_tax as $key => $tax ) {
				$clone_term = get_the_terms( $clone_id, $tax );
				if ( ! empty( $clone_term ) ) {
					foreach ( $clone_term as $term_index => $term ) {
						$clone_term_id_array[ $tax ][] = $term->term_id;
					}
					sort( $clone_term_id_array[ $tax ] );
				}
			}
		}

		$this->assertEquals( $post_category, $clone_term_id_array['category'] );
		$this->assertEquals( $post_tag, $clone_term_id_array['post_tag'] );
		$this->assertEquals( $post_format, $clone_term_id_array['post_format'] );
	}

	/**
	 *	@group ajax
	 */
	public function test_clone_post_with_faulty_post_id_as_sql_injection() {
		global $_POST;
		$NecessaryTools = new NecessaryToolsPlugin();

		$this->simulate_admin_user( $this->factory->user );

		$post_id = "-1; SELECT * FROM 'wp_users';";
		$nonce = wp_create_nonce( "{$NecessaryTools->nonce_actions['nt_clone_post']}_{$post_id}" );
		$post_type = 'post';

		$this->assertEquals( -1, intval( $post_id ) );

		// prepare AJAX call with faulty data
		$_POST['post_id'] = $post_id;
		$_POST['post_type'] = $post_type;
		$_POST['_ajax_nonce'] = $nonce;

		// clone the post
		$err_msg = '';
		try {
			$this->_handleAjax( 'nt_clone_post' );
		} catch ( WPAjaxDieStopException $e ) {
			$err_msg = $e->getMessage();
		}

		$this->assertTrue( isset( $e ) );
		$this->assertEquals( 0, $err_msg );
	}

	/**
	 *	@group ajax
	 */
	public function test_clone_post_with_faulty_post_id_as_string() {
		global $_POST;
		$NecessaryTools = new NecessaryToolsPlugin();

		$this->simulate_admin_user( $this->factory->user );

		$post_id = 'eval(';
		$nonce = wp_create_nonce( "{$NecessaryTools->nonce_actions['nt_clone_post']}_{$post_id}" );
		$post_type = 'post';

		$this->assertEquals( 0, intval( $post_id ) );

		// prepare AJAX call with faulty data
		$_POST['post_id'] = $post_id;
		$_POST['post_type'] = $post_type;
		$_POST['_ajax_nonce'] = $nonce;

		// clone the post
		$err_msg = '';
		try {
			$this->_handleAjax( 'nt_clone_post' );
		} catch ( WPAjaxDieStopException $e ) {
			$err_msg = $e->getMessage();
		}

		$this->assertTrue( isset( $e ) );
		$this->assertEquals( 0, $err_msg );
	}

	/**
	 *	@group ajax
	 */
	public function test_clone_post_with_faulty_post_type() {
		global $_POST;
		$NecessaryTools = new NecessaryToolsPlugin();

		$this->simulate_admin_user( $this->factory->user );

		// create the fake parent post
		$post_title = 'Super Awesome Test Post';
		$post_type = 'nt_some_fake_post_type';
		$post_status = 'publish';
		$post_category = array();
		$post_tag = array();
		$post_format = array();
		$post_format_terms = array(
			0 => 'aside',
			1 => 'link',
			2 => 'gallery'
		);

		$this->assertFalse( post_type_exists( $post_type ) );
		
		// create new fake categories, tags, and taxonomies for the fake post
		$new_cat_1 = $this->factory->category->create();
		$new_cat_2 = $this->factory->category->create();
		$new_tag_1 = $this->factory->tag->create();
		$new_tag_2 = $this->factory->tag->create();
		foreach ( $post_format_terms as $index => $term ) {
			$new_format_n = $this->factory->term->create_object(
				array(
					'name' => ucfirst( $term ),
					'taxonomy' => 'post_format',
					array(
						'slug' => $term
					)
				)
			);
			$post_format[] = $new_format_n;
		}
		
		$post_category = array(
			$new_cat_1,
			$new_cat_2
		);
		$post_tag = array(
			$new_tag_1,
			$new_tag_2
		);
		$post_id = $this->factory->post->create( 
			array( 
				'post_title' => $post_title,
				'post_type' => $post_type,
				'post_status' => $post_status,
				'post_category' => $post_category,
				'tags_input' => $post_tag
			)
		);
		$nonce = wp_create_nonce( "{$NecessaryTools->nonce_actions['nt_clone_post']}_{$post_id}" );

		// the post format taxonomy has to be added after the post is created
		$this->factory->term->add_post_terms( $post_id, $post_format, 'post_format' );

		// prepare the fake AJAX call
		$_POST['post_id'] = $post_id;
		$_POST['post_type'] = $post_type;
		$_POST['_ajax_nonce'] = $nonce;

		// clone the post
		$err_msg = '';
		try {
			$this->_handleAjax( 'nt_clone_post' );
		} catch ( WPAjaxDieStopException $e ) {
			$err_msg = $e->getMessage();
		}

		$this->assertTrue( isset( $e ) );
		$this->assertEquals( 0, $err_msg );
	}

	/**
	 *	@group ajax
	 */
	public function test_clone_post_with_faulty_nonce() {
		global $_POST;
		$NecessaryTools = new NecessaryToolsPlugin();

		$this->simulate_admin_user( $this->factory->user );

		$post_id = $this->factory->post->create();
		$nonce = wp_create_nonce( "nt_faulty_nonce_{$post_id}" );
		$post_type = 'post';

		// prepare AJAX call with faulty data
		$_POST['post_id'] = $post_id;
		$_POST['post_type'] = $post_type;
		$_POST['_ajax_nonce'] = $nonce;

		// clone the post
		$err_msg = '';
		try {
			$this->_handleAjax( 'nt_clone_post' );
		} catch ( WPAjaxDieStopException $e ) {
			$err_msg = $e->getMessage();
		}

		$this->assertTrue( isset( $e ) );
		$this->assertEquals( -1, $err_msg );
	}
}
