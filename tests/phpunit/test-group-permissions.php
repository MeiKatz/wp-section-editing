<?php

/**
 * Integration tests for group permissions operations
 *
 * @group bu
 * @group bu-section-editing
 **/
class Test_Secdor_Group_Permissions extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->factory->group = new WP_UnitTest_Factory_For_Group( $this->factory );
		register_post_type( 'custom', array( 'hierarchical' => false ) );
	}

	function tearDown() {
		parent::tearDown();

		unregister_post_type( 'custom' );
	}

	/**
	 */
	function test_get_and_update_group_permissions() {

		$group = $this->factory->group->create( array( 'name' => __FUNCTION__ ) );
		$posts = $this->factory->post->create_many( 3, array( 'post_type' => 'post' ) );
		$pages = $this->factory->post->create_many( 5, array( 'post_type' => 'page' ) );

		$allowedposts = $group->get_allowed_posts([
			"post_type" => "post",
		]);
		$allowedpages = $group->get_allowed_posts([
			"post_type" => "page",
		]);

		$this->assertTrue( empty( $allowedposts ) );
		$this->assertTrue( empty( $allowedpages ) );

		$perms = array(
			'post' => array( 'allowed' => $posts ),
			'page' => array( 'allowed' => $pages ),
			);

		$group->permissions()->update( $perms );

		$allowedposts = $group->get_allowed_posts([
			"post_type" => "post",
			"fields" => "ids",
		]);
		$allowedpages = $group->get_allowed_posts([
			"post_type" => "page",
			"fields" => "ids",
		]);
		$allowedposts = array_map( 'intval', $allowedposts );
		$allowedpages = array_map( 'intval', $allowedpages );
		$this->assertEquals( asort( $posts ), asort( $allowedposts ) );
		$this->assertEquals( asort( $pages ), asort( $allowedpages ) );

	}

	/**
	 */
	function test_delete_group_permissions() {

		$posts = $this->factory->post->create_many( 2, array( 'post_type' => 'post' ) );
		$pages = $this->factory->post->create_many( 2, array( 'post_type' => 'page' ) );

		$perms = array(
			'post' => array( 'allowed' => $posts ),
			'page' => array( 'allowed' => $pages ),
			);

		$group = $this->factory->group->create( array( 'name' => __FUNCTION__, 'perms' => $perms ) );

		$allowedposts = $group->get_allowed_posts([
			"post_type" => "post",
			"fields" => "ids",
		]);
		$allowedpages = $group->get_allowed_posts([
			"post_type" => "page",
			"fields" => "ids",
		]);
		$allowedposts = array_map( 'intval', $allowedposts );
		$allowedpages = array_map( 'intval', $allowedpages );
		$this->assertEquals( asort( $posts ), asort( $allowedposts ) );
		$this->assertEquals( asort( $pages ), asort( $allowedpages ) );

		$group->permissions()->delete();

		$allowedposts = $group->get_allowed_posts([
			"post_type" => "post",
		]);
		$allowedpages = $group->get_allowed_posts([
			"post_type" => "page",
		]);

		$this->assertTrue( empty( $allowedposts ) );
		$this->assertTrue( empty( $allowedpages ) );

	}

	/**
	 */
	function test_group_can_edit() {

		$posts = $this->factory->post->create_many( 2, array( 'post_type' => 'post' ) );
		$pages = $this->factory->post->create_many( 2, array( 'post_type' => 'page' ) );
		$custom_posts = $this->factory->post->create_many( 2, array( 'post_type' => 'custom' ) );

		$perms = array(
			'post' => array( 'allowed' => array( $posts[0] ) ),
			'page' => array( 'allowed' => array( $pages[1] ) ),
			);

		$group = $this->factory->group->create(
			array(
				'name' => __FUNCTION__,
				'perms' => $perms,
				'global_edit' => array('custom')
			)
		);

		$this->assertTrue( $group->can_edit( reset( $posts ) ) );
		$this->assertFalse( $group->can_edit( next( $posts ) ) );
		$this->assertFalse( $group->can_edit( reset( $pages ) ) );
		$this->assertTrue( $group->can_edit( next( $pages ) ) );
		$this->assertTrue( $group->can_edit( reset( $custom_posts ) ) );
		$this->assertTrue( $group->can_edit( next( $custom_posts ) ) );
	}

	/**
	 * Coverage for group meta inheritance on post save
	 */
	function test_transition_post_status_inheritance() {
		$allowed = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$perms = array( 'page' => array( 'allowed' => array( $allowed ) ) );
		$group = $this->factory->group->create( array( 'name' => __FUNCTION__, 'perms' => $perms ) );

		add_action( 'transition_post_status', array( 'Secdor\\Groups_Admin', 'transition_post_status' ), 10, 3 );

		// 1. New top-level page (should not be editable)
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => 0, 'post_status' => 'publish' ) );
		$this->assertFalse( $group->can_edit( $post ) );

		// 2. Page place in editable section (should be editable)
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $allowed, 'post_status' => 'publish' ) );
		$this->assertTrue( $group->can_edit( $post ) );

		// 3. Non-hierarchical post type (should not be editable)
		$post = $this->factory->post->create( array( 'post_type' => 'post', 'post_status' => 'publish' ) );
		$this->assertFalse( $group->can_edit( $post ) );

		// 4. Draft -> Publish in non-editable section
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => 0, 'post_status' => 'draft' ) );
		$post = wp_update_post( array( 'ID' => $post, 'post_status' => 'publish' ) );
		$this->assertFalse( $group->can_edit( $post ) );

		// 5. Draft -> Publish in editable section
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => 0, 'post_status' => 'draft' ) );
		$post = wp_update_post( array( 'ID' => $post, 'post_status' => 'publish', 'post_parent' => $allowed ) );
		$this->assertTrue( $group->can_edit( $post ) );

		// 6. Publish -> draft
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $allowed, 'post_status' => 'publish' ) );
		$this->assertTrue( $group->can_edit( $post ) );
		$post = wp_update_post( array( 'ID' => $post, 'post_status' => 'draft' ) );
		$this->assertFalse( $group->can_edit( $post ) );

		// 7. Publish -> trash (should not lose section editing privileges)
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $allowed, 'post_status' => 'publish' ) );
		$this->assertTrue( $group->can_edit( $post ) );
		$post = wp_update_post( array( 'ID' => $post, 'post_status' => 'trash' ) );
		$this->assertTrue( $group->can_edit( $post ) );

		register_post_type( 'bu_link', array( 'hierarchical' => true ) );
		$link = $this->factory->post->create( array( 'post_type' => 'bu_link', 'post_parent' => $allowed, 'post_status' => 'publish' ) );
		$this->assertTrue( $group->can_edit( $link ) );

		register_post_type( 'flat', array( 'hierarchical' => false ) );
		$flat = $this->factory->post->create( array( 'post_type' => 'flat', 'post_parent' => $allowed, 'post_status' => 'publish' ) );
		$this->assertFalse( $group->can_edit( $flat ) );
	}
}
