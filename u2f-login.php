<?php
/*
 * Plugin Name: U2F Login
 * Plugin URI: http://www.extendwings.com
 * Description: Make WordPress login secure with U2F (Universal Second Factor) protocol
 * Version: 0.1.0-dev
 * Author: Daisuke Takahashi(Extend Wings)
 * Author URI: http://www.extendwings.com
 * License: AGPLv3 or later
 * Text Domain: u2f
 * Domain Path: /languages/
*/

if( ! function_exists('add_action') ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if( version_compare( PHP_VERSION, '5.5', '<') ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php');
	deactivate_plugins( __FILE__ );
}

if( version_compare( get_bloginfo('version'), '4.0', '<') ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php');
	deactivate_plugins( __FILE__ );
}

add_action('init', array('U2F', 'init') );

class U2F {
	static $instance;

	const VERSION = '0.1.0-dev';

	static function init() {
		if( ! self::$instance ) {
			if( did_action('plugins_loaded') )
				self::plugin_textdomain();
			else
				add_action('plugins_loaded', array(__CLASS__, 'plugin_textdomain') );

			self::$instance = new U2F;
		}
		return self::$instance;
	}

	private function __construct() {
	//	add_filter('authenticate', array( &$this, 'authenticate'), 25, 3);
		add_action('admin_menu', array( &$this, 'users_menu') );
		add_action('admin_print_scripts-users_page_security-key', array( &$this, 'admin_print_scripts') );

		add_filter('plugin_row_meta', array( &$this, 'plugin_row_meta'), 10, 2);
	}

	public function authenticate( $user, $username, $password ) {
		if(is_a($user, 'WP_User') ) {
			return $user;
		}

		if( !empty( $_POST['u2f_token'] )) {
			$u2f_token = $_POST['u2f_token'];
			/*
			Validate token!!
			*/
		}

		return false;
	}

	public function users_menu() {
		add_users_page(__('Security Key', 'u2f'), __('Your Security Key', 'u2f'), 'read', 'security-key', array( &$this, 'render_users_menu') );
	}

	public function render_users_menu() {
		if( ! class_exists('WP_List_Table') ) {
			require_once( ABSPATH.'wp-admin/includes/class-wp-list-table.php');
		}

		$data = array(
			array(
				'id'        => 1,
				'name'      => 'YubiKey NEO',
				'added'     => 1414041511,
				'last_used' => time(),
			),
			array(
				'id'        => 2,
				'name'      => 'YubiKey Standard',
				'added'     => 1411449511,
				'last_used' => time() - 10000,
			),
		);

		require_once( plugin_dir_path( __FILE__ ) . 'class.list-table.php');
		$list_table = new U2F_List_Table( $data );
		$list_table->prepare_items();
		?>
		<div class="wrap">
			<h2><?php _e('Security Key', 'u2f'); ?></h2>
			<h3><?php _e('Security Keys associated with your account', 'u2f'); ?></h3>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $list_table->display() ?>
			</form>

			<h3><?php _e('Add another Security Key', 'u2f'); ?></h3>
			<span class="button button-primary button-large"><?php _e('Register', 'u2f'); ?></span>
		</div><!-- wrap -->
	<?php
	}

	public function admin_print_scripts() {
		echo '<script src="chrome-extension://pfboblefjcgdjicmnffhdgionmgcdmne/u2f-api.js"></script>' . PHP_EOL;
	}

	static function plugin_textdomain() {
		load_plugin_textdomain('u2f', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
	}

	function plugin_row_meta( $links, $file ) {
		if( plugin_basename( __FILE__ ) === $file ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url('http://www.extendwings.com/donate/'),
				__('Donate', 'u2f')
			);
		}
		return $links;
	}
}
