<?php
/**
 * Tests for Customize_Posts_Dummy_Support.
 *
 * @package WordPress
 * @subpackage Customize
 */

/**
 * Class Test_Customize_Posts_Dummy_Support
 *
 * @group dummy
 */
class Test_Customize_Posts_Dummy_Support extends WP_UnitTestCase {

	/**
	 * Manager.
	 *
	 * @var WP_Customize_Manager
	 */
	protected $wp_customize;

	/**
	 * Path to theme root.
	 *
	 * @var string
	 */
	protected $theme_root;

	/**
	 * Original theme directory.
	 *
	 * @var string
	 */
	protected $orig_theme_dir;

	/**
	 * User ID.
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * Customize_Posts_Dummy_Support instance.
	 *
	 * @var Customize_Posts_Dummy_Support
	 */
	protected $support;

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		$this->theme_root = str_replace( '/php/theme-support', '/data/themes', dirname( __FILE__ ) );

		$this->orig_theme_dir = $GLOBALS['wp_theme_directories'];
		// @codingStandardsIgnoreStart
		$GLOBALS['wp_theme_directories'] = array( WP_CONTENT_DIR . '/themes', $this->theme_root );
		// @codingStandardsIgnoreStop

		add_filter( 'theme_root', array( $this, '_theme_root' ) );
		add_filter( 'stylesheet_root', array( $this, '_theme_root' ) );
		add_filter( 'template_root', array( $this, '_theme_root' ) );

		// Clear caches.
		wp_clean_themes_cache();
		unset( $GLOBALS['wp_themes'] );

		$this->user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->user_id );
		switch_theme( 'dummy' );

		// The theme is not loaded yet, so we need to load the class first.
		require( $this->theme_root . '/dummy/functions.php' );

		require_once( ABSPATH . WPINC . '/class-wp-customize-manager.php' );
		// @codingStandardsIgnoreStart
		$GLOBALS['wp_customize'] = new WP_Customize_Manager();
		// @codingStandardsIgnoreStop
		$this->wp_customize = $GLOBALS['wp_customize'];

		$this->do_customize_boot_actions();
		$this->support = $this->wp_customize->posts->supports[ 'Customize_Posts_Dummy_Support' ];
	}

	/**
	 * Teardown.
	 *
	 * @inheritdoc
	 */
	function tearDown() {
		// @codingStandardsIgnoreStart
		$GLOBALS['wp_theme_directories'] = $this->orig_theme_dir;
		// @codingStandardsIgnoreStop
		remove_filter( 'theme_root', array( $this, '_theme_root' ) );
		remove_filter( 'stylesheet_root', array( $this, '_theme_root' ) );
		remove_filter( 'template_root', array( $this, '_theme_root' ) );
		wp_clean_themes_cache();
		unset( $GLOBALS['wp_themes'] );
		$this->wp_customize = null;
		$this->support = null;
		unset( $GLOBALS['wp_customize'] );
		unset( $GLOBALS['wp_scripts'] );
		unset( $_REQUEST['nonce'] );
		unset( $_REQUEST['customize_preview_post_nonce'] );
		unset( $_REQUEST['wp_customize'] );
		unset( $_GET['previewed_post'] );
		parent::tearDown();
	}

	/**
	 * Do Customizer boot actions.
	 */
	function do_customize_boot_actions() {
		// Remove actions that call add_theme_support( 'title-tag' ).
		remove_action( 'after_setup_theme', 'twentyfifteen_setup' );
		remove_action( 'after_setup_theme', 'twentysixteen_setup' );

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['customized'] = '';
		do_action( 'setup_theme' );
		$_REQUEST['nonce'] = wp_create_nonce( 'preview-customize_' . $this->wp_customize->theme()->get_stylesheet() );
		$_REQUEST['customize_preview_post_nonce'] = wp_create_nonce( 'customize_preview_post' );
		do_action( 'after_setup_theme' );
		do_action( 'customize_register', $this->wp_customize );
		$this->wp_customize->customize_preview_init();
		do_action( 'wp', $GLOBALS['wp'] );
		$_REQUEST['wp_customize'] = 'on';
		$_GET['previewed_post'] = 123;
	}

	/**
	 * Replace the normal theme root dir with our premade test dir
	 */
	function _theme_root( $dir ) {
		return $this->theme_root;
	}

	/**
	 * Test add support.
	 *
	 * @see Customize_Posts_Dummy_Support::is_support_needed()
	 */
	public function test_is_support_needed() {
		$this->assertTrue( $this->support->is_support_needed() );
	}

	/**
	 * Test add support.
	 *
	 * @see Customize_Posts_Dummy_Support::add_support()
	 */
	public function test_add_support() {
		$this->assertEquals( 10, has_action( 'customize_posts_partial_schema', array( $this->support, 'filter_partial_schema' ) ) );
	}

	/**
	 * Test add support.
	 *
	 * @see Customize_Posts_Dummy_Support::filter_partial_schema()
	 */
	public function test_filter_partial_schema() {
		$preview = new WP_Customize_Posts_Preview( $this->wp_customize->posts );
		$this->assertNotEmpty( $preview->get_post_field_partial_schema( 'post_author[biography]' ) );
	}

	/**
	 * Test render_callback().
	 *
	 * @see Customize_Posts_Dummy_Support::biography_render_callback()
	 */
	public function test_biography_render_callback() {
		$post = get_post( $this->factory()->post->create() );
		$id = sprintf( 'post[%s][%d][%s][%s]', $post->post_type, $post->ID, 'post_author', 'biography' );
		$args = array(
			'render_callback' => array( $this->support, 'biography_render_callback' ),
		);
		$partial = new WP_Customize_Post_Field_Partial( $this->wp_customize->selective_refresh, $id, $args );

		$this->go_to( get_permalink( $post->ID ) );
		$rendered = $partial->render();
		$this->assertContains( '<div id="author-info">Biography</div>', $rendered );
	}
}
