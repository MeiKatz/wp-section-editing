<?php

require_once dirname( __FILE__ ) . '/../tests/includes/classes.group-factory.php';

/**
 * @group bu-section-editing-selenium
 */
class BUSE_GroupEditorTests extends WP_SeleniumTestCase {

	public function setUp() {

		parent::setUp();

		// Get a group factory
		$this->factory->group = new WP_UnitTest_Factory_For_Group( $this->factory );

		// Create global state programmatically
		$this->factory->user->create(array('role'=>'section_editor'));

	}

	public function pre_test_setup() {
		$this->timeouts()->implicitWait(5000);
		$this->wp->login( $this->settings['login'], $this->settings['password'] );
	}

	// _______________________ GROUP CRUD _______________________

	/*
	 @todo more tests
	 	- update group
	 	- delete group

	 	-- NEED TO CONFIRM STATE POST GROUP SAVE/UPDATE
	*/

	public function test_create_group() {
		$this->pre_test_setup();

		$group_data = array(
			'name' => 'Test Group - Create Group',
			);

		$edit_page = new BUSE_EditGroupPage( $this );
		$edit_page->setName( $group_data['name'] );
		$edit_page->saveGroup();

		// Verify via browser
		$group_id = self::findGroupIdByName($group_data['name']);
		$this->assertNotNull( $group_id );

		// Verify against plugin API
		$groups = Secdor\Edit_Groups::get_instance();
		$group = $groups->get( $group_id );
		$this->assertEquals( $group_data['name'], $group->name );

	}

	// _______________________ PROPERTY TESTS _______________________

	/*
	 @todo more tests
	 	- attempt to save group with no name
	*/

	public function test_group_property_description() {
		$this->pre_test_setup();

		$group = $this->factory->group->create(array('name' => 'Test Group - Property - Description','description' => ''));
		$test_description = 'A test description for the group property description test';

		$edit_page = new BUSE_EditGroupPage( $this, $group->id );
		$edit_page->setDescription( $test_description );
		$edit_page->saveGroup();

		$this->assertEquals( $test_description, $edit_page->getDescription() );

	}

	// _______________________ MEMBER TESTS _______________________

	/*
	 @todo more tests
	 	- attempt to add non-existant user
	 	- remove member
	*/

	/**
	 * Tests adding user to group
	 */
	public function test_add_member() {
		$this->pre_test_setup();

		// Add a group first
		$group = $this->factory->group->create(array('name' => 'Test Group - Add Member'));
		$user_id = $this->factory->user->create(array('user_login' => 'test_editor','role'=>'section_editor'));

		$members_panel = new BUSE_EditGroupMembers( $this, $group->id );

		$members_panel->addMember( 'test_editor' );

		// Save group (reloads page as well)
		$members_panel->saveGroup();

		//  == Issue ==
		// 	Selenium works through the browser session.
		// 	Meanwhile, this code runs in a separate session, so the group fetched during the factory
		// 	creation is cached -- in both the 'option' and 'alloptions' groups.

		// 	When we change things through Selenium and then attempt to load from the DB,
		// 	we get the cached value (either from memory if we don't reload Secdor\Edit_Groups,
		// 	or from the options/alloptions object cache if we do).

		// 	Need to figure out the best way to work around this
		// 	Could be not using the API for verification at all -- stick to markup and keep
		// 	all tests firmly in the selenium browser session and only use plugin API for state generation
		// 	Or maybe there is a better solution.

		// Verify against plugin API -- DOES NOT WORK
		// $groups = Secdor\Edit_Groups::get_instance();
		// $groups->load();
		// $group_after = $groups->get( $group->id );
		// $this->assertContains( $user_id, $group->users );

		$this->assertTrue( $members_panel->hasMember( 'test_editor' ) );

	}

	// _______________________ PERMISSION TESTS _______________________

	/*
	 @todo Other needed test cases
	   - pending edits that will be committed on save (hidden input)
	  		- saving group permissions
	   - perm stats counters
	   - overlay behavior & text
	*/

	/**
	 * Excercises the Javascript responsible for propogating icon permissions on toggled state
	 *
	 * Page tree for testing:
	 *
	 * - 1. Parent Post
	 * 	 `-- 2. Child post
	 *      `-- 3. Grand child post 1
	 * 		`-- 4. Grand child post 2
	 */
	public function test_hierarchical_permission_propogation() {
		$this->pre_test_setup();

		$group = $this->factory->group->create( array('name' => 'Test Group - Hierarchical Permissions Propogation' ) );

		// Generate posts
		$pid_one = $this->factory->post->create(array('post_title' => 'Parent page', 'post_type' => 'page' ) );
		$pid_two = $this->factory->post->create(array('post_title' => 'Child page', 'post_parent' => $pid_one, 'post_type' => 'page' ) );
		$pid_three = $this->factory->post->create(array('post_title' => 'Grand child page 1', 'post_parent' => $pid_two, 'post_type' => 'page' ) );
		$pid_four = $this->factory->post->create(array('post_title' => 'Grand child page 2', 'post_parent' => $pid_two, 'post_type' => 'page' ) );

		$perms_panel = new BUSE_EditGroupPermissions( $this, $group->id );

		// Switch to page tab and load all pages
		$perms_panel->loadPostTypeEditor( 'page' );
		$perms_panel->expandAll();

		sleep(2);

		// Verify initial state
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_one ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_two ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_three ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_four ) );

		// Action: Allow "Parent page" (allow 1)
		// Expected Result: 1,2,3,4 should be allowed
		$perms_panel->togglePostState( $pid_one );

		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_one ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_two ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_three ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_four ) );

		// Action: Deny "Child page" (deny 2)
		// Expected Result: 1 should be allowed w/ denied children, 2,3,4 should be denied
		$perms_panel->togglePostState( $pid_two );

		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED_DESC_DENIED, $perms_panel->getPostState( $pid_one ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_two ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_three ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_four ) );

		// Action: Allow "Grand child page 1"
		// Expected Result: 1 should be allowed w/ denied children, 2 should be denied w/allowed children, 3 should be allowed, 4 should be denied
		$perms_panel->togglePostState( $pid_three );

		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED_DESC_DENIED, $perms_panel->getPostState( $pid_one ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED_DESC_ALLOWED, $perms_panel->getPostState( $pid_two ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_three ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_four ) );

		// Action: Allow "Grand child post 2"
		// Expected Result: 1 should be allowed w/ denied children, 2 should be denied w/ allowed children, 3 and 4 should be allowed
		$perms_panel->togglePostState( $pid_four );

		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED_DESC_DENIED, $perms_panel->getPostState( $pid_one ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED_DESC_ALLOWED, $perms_panel->getPostState( $pid_two ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_three ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_four ) );

		// Action: Allow "Child post"
		// Expected Result: 1, 2, 3, 4 should be allowed
		$perms_panel->togglePostState( $pid_two );

		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_one ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_two ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_three ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_four ) );

		// Action: Deny "Grand child page 1"
		// Expected Result: 1,2 should be allowed w/ denied children, 3 should be denied, 4 should be allowed
		$perms_panel->togglePostState( $pid_three );

		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED_DESC_DENIED, $perms_panel->getPostState( $pid_one ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED_DESC_DENIED, $perms_panel->getPostState( $pid_two ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_three ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED, $perms_panel->getPostState( $pid_four ) );

		// Action: Deny "Grand child page 2"
		// Expected Result: 1,2 should be allowed w/ denied children, 3,4 should be denied
		$perms_panel->togglePostState( $pid_four );

		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED_DESC_DENIED, $perms_panel->getPostState( $pid_one ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED_DESC_DENIED, $perms_panel->getPostState( $pid_two ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_three ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_four ) );

		// Action: Deny "Child page"
		// Expected Result: 1 should be allowed w/ denied children, 2,3,4 should be denied
		$perms_panel->togglePostState( $pid_two );

		$this->assertEquals( BUSE_EditGroupPermissions::STATE_ALLOWED_DESC_DENIED, $perms_panel->getPostState( $pid_one ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_two ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_three ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_four ) );

		// Action: Deny "Parent page"
		// Expected Result: 1,2,3,4 should be denied
		$perms_panel->togglePostState( $pid_one );

		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_one ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_two ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_three ) );
		$this->assertEquals( BUSE_EditGroupPermissions::STATE_DENIED, $perms_panel->getPostState( $pid_four ) );

	}

	// _____________________HELPERS_______________________

	/**
	 * Find group ID by looking at the edit group link found on the section groups page
	 */
	protected function findGroupIdByName( $name) {
		$this->pre_test_setup();

		$group_id = null;

		// Fetch group ID from edit link URL
		$groups_page = new BUSE_GroupsPage( $this );

		$edit_link = $this->byLinkText( $name );

		if( isset( $edit_link ) ) {
			$url = $edit_link->attribute('href');
			$parts = parse_url( $url );
			$args = wp_parse_args( $parts['query'] );
			$group_id = $args['id'];

		}

		return $group_id;

	}

}

/**
 * Page objects for group editor interface
 *
 * @todo
 *	- better isolate markup/url dependencies in to constants
 */

/**
 * Section groups list page
 */
class BUSE_GroupsPage {

	private $webdriver = null;

	const MANAGE_GROUPS_URL = '/wp-admin/admin.php?page=secdor-groups';

	function __construct( $webdriver ) {
		$this->webdriver = $webdriver;

		$this->webdriver->url( self::MANAGE_GROUPS_URL );

		$page_title = $this->webdriver->title();

		if( strpos( $this->webdriver->title(), 'Section Group' ) === false )
			throw new Exception('Section Groups page failed to load -- unable to load URL: ' . $request_url );
	}

}

/**
 * Add/Edit section group page, members panel
 */
class BUSE_EditGroupMembers extends BUSE_EditGroupPage {

	const ACTIVE_MEMBER_XPATH = "//li[contains(@class,'member') and contains(@class,'active')]//label[text()='%s']";

	function __construct( $webdriver, $group_id = null ) {

		parent::__construct( $webdriver, $group_id );

		$this->loadPanel( 'members' );

	}

	function addMember( $login ) {

		$this->group_form->populateFields( array( self::GROUP_ADD_MEMBER_INPUT => array( 'type' => 'text', 'value' => $login ) ) );

		$add_btn = $this->webdriver->byId( self::GROUP_ADD_MEMBER_BTN );
		$add_btn->click();

		// Verify member has been added before continuing (AJAX call is utilized)
        $xpath = sprintf(self::ACTIVE_MEMBER_XPATH,$login);
        $this->webdriver->byXpath( $xpath );

	}

	// @todo allow for either login or ID
	function removeMember( $id ) {

		if( is_numeric($id) ) {
			// @todo don't hard code remove_member
			$remove_btn = $this->webdriver->byId( '#remove_member_' . $id );
			$remove_btn->click();
		} else {
			// @todo find remove link based on display name label (xpath)
		}

	}

	function hasMember( $login ) {

        $xpath = sprintf(self::ACTIVE_MEMBER_XPATH,$login);
        $member_row = $this->webdriver->byXpath( $xpath );

        if( isset( $member_row ) )
        	return true;

        return false;

	}

}

/**
 * Add/Edit group page, permissions panel
 */
class BUSE_EditGroupPermissions extends BUSE_EditGroupPage {

	const STATE_ALLOWED = 'allowed';
	const STATE_DENIED = 'denied';
	const STATE_ALLOWED_DESC_DENIED = 'allowed-desc-denied';
	const STATE_DENIED_DESC_ALLOWED = 'denied-desc-allowed';

	function __construct( $webdriver, $group_id = null ) {

		parent::__construct( $webdriver, $group_id );

		$this->loadPanel( 'permissions' );

	}

	function loadPostTypeEditor( $name ) {

		$tab = $this->webdriver->byId( 'perm-panel-' . $name );
		$tab->click();

	}

	function expandAll() {

		$link = $this->webdriver->byCssSelector( '.group-panel.active .perm-tree-expand' );
		$link->click();

	}

	function togglePostState( $id ) {

		// Select post
		$post_link = $this->webdriver->byCssSelector( '#p' . $id . ' > a' );
		$post_link->click();

		// Click action button
		$action_link = $this->webdriver->byCssSelector( '#p' . $id . ' .edit-perms' );
		$action_link->click();

	}

	function getPostState( $id ) {

		$post_link = $this->webdriver->byId( 'p' . $id );
		return $post_link->attribute('rel');

	}

}

?>
