<?php

/**
 * Main plugin class file.
 *
 * @package Woo Warranty Registration/Includes
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main plugin class.
 */
class WWPR
{
	/**
	 * The single instance of WWPR.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * Local instance of WWPR_Admin_API
	 *
	 * @var WWPR_Admin_API|null
	 */
	public $admin = null;

	/**
	 * Settings class object
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version; //phpcs:ignore

	/**
	 * The token.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token; //phpcs:ignore

	/**
	 * The main plugin file.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for JavaScripts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor funtion.
	 *
	 * @param string $file File constructor.
	 * @param string $version Plugin version.
	 */
	public function __construct($file = '', $version = '1.0.0')
	{
		$this->_version = $version;
		$this->_token   = 'WWPR';

		// Load plugin environment variables.
		$this->file       = $file;
		$this->dir        = dirname($this->file);
		$this->assets_dir = trailingslashit($this->dir) . 'assets';
		$this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

		$this->script_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook($this->file, array($this, 'install'));

		// Load frontend JS & CSS.
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 10);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 10);

		//Load our special scripts if on a wooocommmerce account page.
		add_action('wp_enqueue_scripts', array($this, 'enqueue_account_scripts'), 10);

		add_action('woocommerce_loaded', 'woo_load_actions');

		function woo_load_actions()
		{
			if (is_user_logged_in()) {
				add_action('wp_head', 'registered_warranties');
			}
		}

		function registered_warranties()
		{
			global $wpdb;
			$userID = get_current_user_id();
			$results = $wpdb->get_results($wpdb->prepare("SELECT order_id FROM wp_user_warranties WHERE customer_id = $userID"));
			$data['registered_warranties'] = $results; ?>
			<script>
				var registeredWarranties = '<?php echo json_encode($data); ?>';
				let userID = '<?php echo $userID ?>';
			</script>
<?php
		}


		// Load admin JS & CSS.
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);

		//Load Admin Modules
		add_action('init', 'warranty_endpoint');
		add_filter('woocommerce_get_query_vars',  array($this, 'warranty_query_vars'), 0);
		add_filter('woocommerce_account_menu_items',  array($this, 'warranty_account_link'));
		add_action('woocommerce_account_warranty_endpoint',  array($this, 'warranty_account_content'));

		//Add Product Edit Page Panels
		add_action('woocommerce_product_write_panel_tabs', array($this, 'warranty_admin_woo_panel'));
		add_action('woocommerce_product_data_panels', array($this, 'panel_add_custom_box'));

		//Save our ship! I mean meta
		add_action('woocommerce_process_product_meta', array($this, 'save_product_warranty'));

		// Load API for generic admin functions.
		if (is_admin()) {
			$this->admin = new WWPR_Admin_API();
		}

		// Handle localisation.
		$this->load_plugin_textdomain();
		add_action('init', array($this, 'load_localisation'), 0);
	} // End __construct ()

	public function warranty_endpoint()
	{
		add_rewrite_endpoint('warranty', EP_ROOT | EP_PAGES);
	}

	public function warranty_query_vars($vars)
	{
		$vars[] = 'warranty';
		return $vars;
	}

	public function warranty_account_link($items)
	{
		$items['warranty'] = 'Warranties';
		return $items;
	}

	public function warranty_account_content()
	{
		echo file_get_contents(__DIR__ . '/vue-templates/warranties.html');
	}

	public function warranty_admin_woo_panel()
	{
		echo ' <li class="warranty_tab tax_options hide_if_external"><a href="#warranty_product_data"><span>' . __('Warranty', 'woocommerce') . '</span></a></li>';
	}

	public function panel_add_custom_box()
	{
		global $post, $wpdb, $thepostid, $woocommerce;

		$warranty_type_value = get_post_meta($post->ID, '_warranty_type', true);

		if (trim($warranty_type_value) == '') {
			update_post_meta($post->ID, '_warranty_type', 'no_warranty');
			$warranty_type_value = 'no_warranty';
		}

		$warranty_duration_value = get_post_meta($post->ID, '_warranty_duration', true);

		if (trim($warranty_duration_value) == '') {
			update_post_meta($post->ID, '_warranty_duration', 0);
			$warranty_duration_value = 0;
		}

		$warranty_unit_value = get_post_meta($post->ID, '_warranty_unit', true);

		if (trim($warranty_unit_value) == '') {
			update_post_meta($post->ID, '_warranty_unit', 'day');
			$warranty_unit_value = 'day';
		}

		$currency = get_woocommerce_currency_symbol();
		$inline = '
			var warranty_fields_toggled = false;
			$("#product_warranty_default").change(function() {

				if ($(this).is(":checked")) {
					$(".warranty_field").attr("disabled", true);
				} else {
					$(".warranty_field").attr("disabled", false);
				}

			}).change();

			$("#product_warranty_type").change(function() {
				$(".show_if_included_warranty, .show_if_addon_warranty").hide();

				if ($(this).val() == "included_warranty") {
					$(".show_if_included_warranty").show();
				} else if ($(this).val() == "addon_warranty") {
					$(".show_if_addon_warranty").show();
				}
			}).change();

			$("#included_warranty_length").change(function() {
				if ($(this).val() == "limited") {
					$(".limited_warranty_length_field").show();
				} else {
					$(".limited_warranty_length_field").hide();
				}
			}).change();

			var tmpl = "<tr>\
							<td valign=\"middle\">\
								<span class=\"input\"><b>+</b> ' . $currency . '</span>\
								<input type=\"text\" name=\"addon_warranty_amount[]\" class=\"input-text sized\" size=\"4\" value=\"\" />\
							</td>\
							<td valign=\"middle\">\
								<input type=\"text\" class=\"input-text sized\" size=\"3\" name=\"addon_warranty_length_value[]\" value=\"\" />\
								<select name=\"addon_warranty_length_duration[]\">\
									<option value=\"days\">' . __('Days', 'wc_warranty') . '</option>\
									<option value=\"weeks\">' . __('Weeks', 'wc_warranty') . '</option>\
									<option value=\"months\">' . __('Months', 'wc_warranty') . '</option>\
									<option value=\"years\">' . __('Years', 'wc_warranty') . '</option>\
								</select>\
							</td>\
							<td><a class=\"button warranty_addon_remove\" href=\"#\">&times;</a></td>\
						</tr>";

			$(".btn-add-warranty").click(function(e) {
				e.preventDefault();

				$("#warranty_addons").append(tmpl);
			});

			$(".warranty_addon_remove").on("click", function(e) {
				e.preventDefault();

				$(this).parents("tr").remove();
			});

			$("#variable_warranty_control").change(function() {
				if ($(this).val() == "variations") {
					$(".hide_if_control_variations").hide();
					$(".show_if_control_variations").show();
				} else {
					$(".hide_if_control_variations").show();
					$(".show_if_control_variations").hide();
					$("#warranty_product_data :input[id!=variable_warranty_control]").change();
				}
			}).change();

			$("#variable_product_options").on("woocommerce_variations_added", function() {
				$("#variable_warranty_control").change();
			});

			$("#woocommerce-product-data").on("woocommerce_variations_loaded", function() {
				$("#variable_warranty_control").change();
			});
			';

		if (function_exists('wc_enqueue_js')) {
			wc_enqueue_js($inline);
		} else {
			$woocommerce->add_inline_js($inline);
		}

		$warranty       = warranty_get_product_warranty($post->ID);
		$warranty_label = $warranty['label'];
		$default_warranty = false;
		$control_type   = 'parent';

		$product = wc_get_product($post->ID);

		if ($product->is_type('variable')) {
			$control_type = get_post_meta($post->ID, '_warranty_control', true);
			if (!$control_type) {
				$control_type = 'variations';
			}
		}

		$default_warranty = isset($warranty['default']) ? $warranty['default'] : false;

		if (empty($warranty_label)) {
			$warranty_label = __('Warranty', 'wc_warranty');
		}
		$plugin_dir = ABSPATH . 'wp-content/plugins/Woocommerce-Paramotor-Registration/';

		include $plugin_dir . '/templates/admin/WWPR-product-panel.php';
	}

	public function variables_panel($loop, $data, $variation)
	{
		global $woocommerce;

		$warranty       = warranty_get_product_warranty($variation->ID);
		$warranty_label = $warranty['label'];
		$warranty_default = isset($warranty['default']) ? $warranty['default'] : false;

		if (empty($warranty_label)) {
			$warranty_label = __('Warranty', 'wc_warranty');
		}

		$currency = get_woocommerce_currency_symbol();
		$inline = '
			$("#variable_product_options").on("change", ".warranty_default_checkbox", function() {
				var id = $(this).data("id");

				if ($(this).is(":checked")) {
					$(".warranty_"+id).attr("disabled", true);
				} else {
					$(".warranty_"+id).attr("disabled", false);
				}
			}).change();

			$("#variable_product_warranty_type_' . $loop . '").change(function() {
				$(".variable_show_if_included_warranty_' . $loop . ', .variable_show_if_addon_warranty_' . $loop . '").hide();

				if ($(this).val() == "included_warranty") {
					$(".variable_show_if_included_warranty_' . $loop . '").show();
				} else if ($(this).val() == "addon_warranty") {
					$(".variable_show_if_addon_warranty_' . $loop . '").show();
				}
			}).change();

			$("#variable_included_warranty_length_' . $loop . '").change(function() {
				if ($(this).val() == "limited") {
					$(".variable_limited_warranty_length_field_' . $loop . '").show();
				} else {
					$(".variable_limited_warranty_length_field_' . $loop . '").hide();
				}
			}).change();

			var tmpl_' . $loop . ' = "<tr>\
							<td valign=\"middle\">\
								<span class=\"input\"><b>+</b> ' . $currency . '</span>\
								<input type=\"text\" name=\"variable_addon_warranty_amount[' . $loop . '][]\" value=\"\" style=\"min-width:50px; width:50px;\" />\
							</td>\
							<td valign=\"middle\">\
								<input type=\"text\" style=\"width:50px;\" name=\"variable_addon_warranty_length_value[' . $loop . '][]\" value=\"\" />\
								<select name=\"variable_addon_warranty_length_duration[' . $loop . '][]\" style=\"width: auto !important;\">\
									<option value=\"days\">' . __('Days', 'wc_warranty') . '</option>\
									<option value=\"weeks\">' . __('Weeks', 'wc_warranty') . '</option>\
									<option value=\"months\">' . __('Months', 'wc_warranty') . '</option>\
									<option value=\"years\">' . __('Years', 'wc_warranty') . '</option>\
								</select>\
							</td>\
							<td><a class=\"button warranty_addon_remove_variable_' . $loop . '\" data-loop=\"' . $loop . '\" href=\"#\">&times;</a></td>\
						</tr>";

			$(".btn-add-warranty-variable").click(function(e) {
				e.preventDefault();
				$("#variable_warranty_addons_' . $loop . '").append(tmpl_' . $loop . ');
			});

			$(".warranty_addon_remove_variable_' . $loop . '").on("click", function(e) {
				e.preventDefault();

				$(this).parents("tr").eq(0).remove();
			});
			';

		if (function_exists('wc_enqueue_js')) {
			wc_enqueue_js($inline);
		} else {
			$woocommerce->add_inline_js($inline);
		}
	}

	/* Product Warranty Data */
	public function save_product_warranty($post_ID)
	{
		if (isset($_POST['product-type']) && $_POST['product-type'] == 'variable') {
			return;
		}

		if (!empty($_POST['product_warranty_default']) && $_POST['product_warranty_default'] == 'yes') {
			delete_post_meta($post_ID, '_warranty');
		} elseif (isset($_POST['product_warranty_type'])) {
			$product_warranty = array();

			if ($_POST['product_warranty_type'] == 'no_warranty') {
				$product_warranty = array('type' => 'no_warranty');
				update_post_meta($post_ID, '_warranty', $product_warranty);
			} elseif ($_POST['product_warranty_type'] == 'included_warranty') {
				$product_warranty = array(
					'type'      => 'included_warranty',
					'length'    => $_POST['included_warranty_length'],
					'value'     => $_POST['limited_warranty_length_value'],
					'duration'  => $_POST['limited_warranty_length_duration']
				);
				update_post_meta($post_ID, '_warranty', $product_warranty);
			} elseif ($_POST['product_warranty_type'] == 'addon_warranty') {
				$no_warranty = (isset($_POST['addon_no_warranty'])) ? $_POST['addon_no_warranty'] : 'no';
				$amounts    = $_POST['addon_warranty_amount'];
				$values     = $_POST['addon_warranty_length_value'];
				$durations  = $_POST['addon_warranty_length_duration'];
				$addons     = array();

				for ($x = 0; $x < count($amounts); $x++) {
					if (!isset($amounts[$x]) || !isset($values[$x]) || !isset($durations[$x])) continue;

					$addons[] = array(
						'amount'    => $amounts[$x],
						'value'     => $values[$x],
						'duration'  => $durations[$x]
					);
				}

				$product_warranty = array(
					'type'                  => 'addon_warranty',
					'addons'                => $addons,
					'no_warranty_option'    => $no_warranty
				);
				update_post_meta($post_ID, '_warranty', $product_warranty);
			}

			if (isset($_POST['warranty_label'])) {
				update_post_meta($post_ID, '_warranty_label', stripslashes($_POST['warranty_label']));
			}
		}
	}

	/**
	 * Load frontend CSS.
	 *
	 * @access  public
	 * @return void
	 * @since   1.0.0
	 */
	public function enqueue_styles()
	{
		wp_register_style($this->_token . '-frontend', esc_url($this->assets_url) . 'css/frontend.css', array(), $this->_version);
		wp_enqueue_style($this->_token . '-frontend');
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_register_script($this->_token . '-frontend', esc_url($this->assets_url) . 'js/frontend' . $this->script_suffix . '.js', array('jquery'), $this->_version, true);
		wp_enqueue_script($this->_token . '-frontend');

	} // End enqueue_scripts ()

	public function enqueue_account_scripts()
	{
		if (is_account_page()) {
			wp_enqueue_script('vueCDN', 'https://cdn.jsdelivr.net/npm/vue@2.6.12');
			wp_enqueue_script('axiosCDN', 'https://unpkg.com/axios@0.2.1/dist/axios.min.js');
			wp_enqueue_script('aqsCDN', 'https://unpkg.com/qs@6.10.1/dist/qs.js');
		}
	}

	/**
	 * Admin enqueue style.
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return void
	 */
	public function admin_enqueue_styles($hook = '')
	{
		wp_register_style($this->_token . '-admin', esc_url($this->assets_url) . 'css/admin.css', array(), $this->_version);
		wp_enqueue_style($this->_token . '-admin');
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 *
	 * @access  public
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function admin_enqueue_scripts($hook = '')
	{
		wp_register_script('paramotor-js', esc_url($this->assets_url) . 'js/admin.js', array('jquery'), $this->_version, true);
		wp_register_script($this->_token . '-admin', esc_url($this->assets_url) . 'js/admin' . $this->script_suffix . '.js', array('jquery'), $this->_version, true);
		wp_enqueue_script($this->_token . '-admin');
		wp_enqueue_script('paramotor-js');
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_localisation()
	{
		load_plugin_textdomain('wordpress-plugin-template', false, dirname(plugin_basename($this->file)) . '/lang/');
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain()
	{
		$domain = 'wordpress-plugin-template';

		$locale = apply_filters('plugin_locale', get_locale(), $domain);

		load_textdomain($domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo');
		load_plugin_textdomain($domain, false, dirname(plugin_basename($this->file)) . '/lang/');
	} // End load_plugin_textdomain ()

	/**
	 * Main WWPR Instance
	 *
	 * Ensures only one instance of WWPR is loaded or can be loaded.
	 *
	 * @param string $file File instance.
	 * @param string $version Version parameter.
	 *
	 * @return Object WWPR instance
	 * @see WWPR()
	 * @since 1.0.0
	 * @static
	 */
	public static function instance($file = '', $version = '1.0.0')
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self($file, $version);
		}

		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone()
	{
		_doing_it_wrong(__FUNCTION__, esc_html(__('Cloning of WWPR is forbidden')), esc_attr($this->_version));
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup()
	{
		_doing_it_wrong(__FUNCTION__, esc_html(__('THE GLARP ZONE IS FOR FLARPING AND UNFLARPING ONLY!')), esc_attr($this->_version));
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function install()
	{
		$this->_log_version_number();
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE `{$wpdb->base_prefix}user_warranties` (
		order_id INT UNSIGNED NULL, 
		order_serial text NULL, 
		customer_id INT UNSIGNED NULL, 
		registered_at date NULL,
		claimed_at date NULL, 
		PRIMARY KEY  (order_id)) $charset_collate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	} // End install ()

	/**
	 * Log the plugin version number.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	private function _log_version_number()
	{ //phpcs:ignore
		update_option($this->_token . '_version', $this->_version);
	} // End _log_version_number ()
}
