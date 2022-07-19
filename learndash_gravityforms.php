<?php
/**
 * @package LearnDash LMS - GravityForms Integration
 * @version 2.1.1.10
 */
/*
Plugin Name: LearnDash LMS - GravityForms Integration
Plugin URI: http://www.learndash.com
Description: LearnDash LMS - GravityForms Integration 
Version: 2.1.2.1
Author: LearnDash
Author URI: http://www.learndash.com
Text Domain: learndash-gravity-forms
Doman Path: /languages/
*/

class learndash_gravityforms {
	public $debug = false;
	
	function __construct() {
		$this->setup_constants();

		add_action( 'plugins_loaded', array( $this, 'load_translation' ) );	

		$this->check_dependency();

		add_action( 'plugins_loaded', function() {
			if ( LearnDash_Dependency_Check_LD_Gravity_Forms::get_instance()->check_dependency_results() ) {
				$this->hooks();
			}
		} );
	}

	public function hooks()
	{
		add_action( 'admin_head', [ $this, 'form_editor_header_scripts' ], 100 );
		add_action( 'gform_editor_js', [ $this, 'form_editor_scripts' ], 100 );

		add_action( 'gform_userregistration_feed_settings_fields', array( $this, 'add_section' ), 10, 2 );
		add_action( 'gform_user_registered', array( $this, 'completed_registration' ), 10, 4 );
		add_action( 'gform_user_updated', array( $this, 'completed_registration' ), 10, 4 );

		// Payment hooks
		add_action( 'gform_post_payment_completed', array( $this, 'completed_payment' ), 10, 2 );
		add_action( 'gform_post_payment_refunded', array( $this, 'refunded_payment' ), 10, 2 );

		// Subscription hooks
		add_action( 'gform_post_subscription_started', array( $this, 'subscription_started' ), 10, 2 );
		add_action( 'gform_subscription_canceled', array( $this, 'subscription_canceled' ), 10, 3 );
	}

	public function setup_constants()
	{
		if ( ! defined( 'LEARNDASH_GRAVITY_FORMS_VERSION' ) ) {
			define( 'LEARNDASH_GRAVITY_FORMS_VERSION', '2.1.2' );
		}

		// Plugin file
		if ( ! defined( 'LEARNDASH_GRAVITY_FORMS_FILE' ) ) {
			define( 'LEARNDASH_GRAVITY_FORMS_FILE', __FILE__ );
		}		

		// Plugin folder path
		if ( ! defined( 'LEARNDASH_GRAVITY_FORMS_PLUGIN_PATH' ) ) {
			define( 'LEARNDASH_GRAVITY_FORMS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
		}

		// Plugin folder URL
		if ( ! defined( 'LEARNDASH_GRAVITY_FORMS_PLUGIN_URL' ) ) {
			define( 'LEARNDASH_GRAVITY_FORMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}
	}

	public function check_dependency()
	{
		include LEARNDASH_GRAVITY_FORMS_PLUGIN_PATH . 'includes/class-dependency-check.php';

		LearnDash_Dependency_Check_LD_Gravity_Forms::get_instance()->set_dependencies(
			array(
				'sfwd-lms/sfwd_lms.php' => array(
					'label'       => '<a href="https://learndash.com">LearnDash LMS</a>',
					'class'       => 'SFWD_LMS',
					'min_version' => '3.0.0',
				),
				'gravityforms/gravityforms.php' => array(
					'label'       => '<a href="https://www.gravityforms.com/">Gravity Forms</a>',
					'class'       => 'GFForms',
					'min_version' => '2.4.0',
				),
				'gravityformsuserregistration/userregistration.php' => array(
					'label'       => '<a href="https://www.gravityforms.com/">Gravity Forms User Registration Addon</a>',
					'class'       => 'GF_User_Registration_Bootstrap',
					'min_version' => '4.5',
				),
			)
		);

		LearnDash_Dependency_Check_LD_Gravity_Forms::get_instance()->set_message(
			__( 'LearnDash LMS - Gravity Forms Integration Add-on requires the following plugin(s) to be active:', 'learndash-gravity-forms' )
		);
	}

	public function load_translation() {
		load_plugin_textdomain( 'learndash-gravity-forms', false, LEARNDASH_GRAVITY_FORMS_PLUGIN_PATH . 'languages/' );

		// include translation/update class
		include LEARNDASH_GRAVITY_FORMS_PLUGIN_PATH . 'includes/class-translations-ld-gravity-forms.php';
	}

	/**
	 * Load necessary scripts and styles on form editor page header
	 * 
	 * @return void
	 */
	public function form_editor_header_scripts() 
	{
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gf_edit_forms' ) {
			return;
		}

		?>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
		<link rel="stylesheet" href="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . 'assets/vendor/select2-jquery/css/select2.min.css' ); ?>">
		<style>
			body #gfield_settings_choices_container {
				max-height: initial;
			}

			body .learndash {
				border: 1px solid #ddd;
				border-top: 0;
				border-radius: 5px;
				margin-top: 5px;
				margin-bottom: 15px;
			}

			.learndash .ui-icon {
				display: inline-block;
			}

			.learndash .select-wrapper  {
				margin-bottom: 10px;
			}

			.learndash-options {
				margin-bottom: 15px;
			}

			.learndash .accordion-content {
				height: auto !important;
				display: block; 
			}

			.learndash .select2-container {
				width: 100%;
				border: 1px solid #ddd;
				border-radius: 5px;
			}

			.learndash .select2-container ul {
				width: 94%;
			}

			.learndash .select2-container li {
				width: auto;
				float: left;
				border: 1px solid #ddd;
				padding: 3px;
				border-radius: 10px;
				margin-right: 5px;
			}

			.learndash .select2-container li.select2-search {
				clear: both;
				border: none;
				width: 99%;
			}

			.learndash .select2-container li.select2-search input {
				width: 99% !important;
				padding: 0 3px;
				border: 1px solid #ddd;
			}

			.learndash .select2-container .select2-selection {
				padding: 5px 0;
			}

			.learndash .select2-container .select2-selection:focus {
				outline: none;
			}

			/* Select2 Dropdown */
			.select2-container.select2-container--open .select2-dropdown {
				border-color: #ddd;
				border-top: 1px solid #ddd;
			}

			.select2-dropdown .select2-results__options {
				max-height: 300px;
				overflow: auto;
			}

			.select2-dropdown .select2-results__options .select2-results__option {
				margin: 0;
			}

			.select2-dropdown .select2-results__options .select2-results__option[aria-selected="true"] {
				background-color: #ddd;
			}
		</style>

		<script src="<?php echo esc_url( get_option( 'siteurl' ) . '/wp-includes/js/jquery/ui/accordion.min.js' ); ?>"></script>
		<script src="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . 'assets/vendor/select2-jquery/js/select2.full.min.js' ); ?>"></script>
		<?php
	}

	/**
	 * Load necessary scripts on form editor page footer
	 * 
	 * @return void
	 */
	public function form_editor_scripts() 
	{
		$courses = $this->get_courses();
		$groups  = $this->get_groups();

		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				function ldUpdateSelect2Fields() {
					// Select2 event
					$( '.select2' ).each( function( index, el ) {
						$( this ).on( 'select2:select select2:unselect', function( e ) {
							var selected =  $( this ).val() || [],
								field = GetSelectedField();
								type = $( this ).data( 'type' );
								index = $( this ).closest( '.learndash-options' ).data( 'index' );

							field.choices[ index ][ type ] = selected;
						} );
					});
				}

				gform.addAction( 'gform_load_field_choices', function( field ) {
					console.log( 'field: ', field );

					var field_rows = $( '.field-choice-row' ),
						selected_courses,
						selected_course,
						selected_groups,
						selected_group;

					field_rows.each( function( index, el ) {
						var str = '<div class="learndash learndash-options accordion" data-index="' + index + '">';
							str += '<h3><?php echo esc_attr( 'LearnDash Options' ); ?></h3>';
							str += '<div class="accordion-content">';
								str += '<div class="ld-gf-course-options select-wrapper">';
									str += '<label for="learndash-gf-courses"><?php _e( 'Courses', 'learndash-gravity-forms' ) ?></label>'
									str += '<select name="_learndash_gf_courses[]" multiple="multiple" class="select2" data-type="courses">';
										<?php foreach( $courses as $index => $course ) : ?>
											selected_courses = field[0].choices[ index ].hasOwnProperty( 'courses' ) ? field[0].choices[ index ].courses : undefined;
											selected_course = undefined !== selected_courses && selected_courses.indexOf( '<?php echo $course->ID ?>' ) > -1 ? 'selected' : '';

											str += '<option value="<?php echo esc_attr( $course->ID ) ?>" ' + selected_course + '><?php echo esc_attr( $course->post_title ); ?></option>';
										<?php endforeach; ?>
									str += '</select>';
								str += '</div>';

								str += '<div class="ld-gf-group-options select-wrapper">';
									str += '<label for="learndash-gf-groups"><?php _e( 'Groups', 'learndash-gravity-forms' ) ?></label>'
									str += '<select name="_learndash_gf_groups[]" multiple="multiple" class="select2" data-type="groups">';
										<?php foreach( $groups as $index => $group ) : ?>
											selected_groups = field[0].choices[ index ].hasOwnProperty( 'courses' ) ? field[0].choices[ index ].groups : undefined;
											selected_group = undefined !== selected_groups && selected_groups.indexOf( '<?php echo $group->ID ?>' ) > -1 ? 'selected' : '';

											str += '<option value="<?php echo esc_attr( $group->ID ) ?>" ' + selected_group + '><?php echo esc_attr( $group->post_title ); ?></option>';
										<?php endforeach; ?>
									str += '</select>';
								str += '</div>';
							str += '</div>';
						str += '</div>';

						$( this ).after( str );
					} );
						

					setTimeout( function() {
						$( '.learndash .select2' ).select2({
							closeOnSelect: false,
							width: '100%',
						});

						$( '.learndash.accordion' ).accordion({
							active: false,
							collapsible: true,
						});

						ldUpdateSelect2Fields();
					}, 100 );
				}, 10 );
			} );
		</script>
		<?php
	}

	function add_section( $fields, $form ) {
		$courses = $this->list_courses();
		$groups  = $this->list_groups();
		
		$f = array();
		foreach ($fields as $key => $value) {
			$f[$key] = $value;
			if($key == "additional_settings")
			{
				$f['learndash_settings'] = array(
					'title'			=> __("LearnDash Settings", 'learndash-gravity-forms'),
					'description'	=> '',
					'fields'		=> array()
				);
			}
		}
		$fields = $f;

		$fields['learndash_settings']['fields'][] = array(
			'name'  => 'gf_user_registration_paid_form',
			'label' => __( 'Paid Form', 'learndash-gravity-forms' ),
			'type'  => 'checkbox',
			'choices' => array(
				array(
					'label' => __( 'Enable', 'learndash-gravity-forms' ),
					'value' => 1,
					'name'  => 'gf_user_registration_paid_form'
				)
			),
			'tooltip' => __( 'Check this box if this form is a paid one. It will bypass this user registration feed when enrolling user to the courses and use payment hook instead.', 'learndash-gravity-forms' ),
		);

		$fields['learndash_settings']['fields'][] = array(
			'name'      => 'gf_user_registration_ldcourses',
			'label'     => __( 'Courses', 'learndash-gravity-forms' ),
			'type'      => 'checkbox',
			'choices'   => $courses,
		);

		$fields['learndash_settings']['fields'][] = array(
			'name'      => 'gf_user_registration_ldgroups',
			'label'     => __( 'Groups', 'learndash-gravity-forms' ),
			'type'      => 'checkbox',
			'choices'   => $groups,
		);

		$accesslevels = $this->list_accesslevels();
		if(!empty($accesslevels)) {
			$fields['learndash_settings']['fields'][] = array(
					'name'      => 'gf_user_registration_ldaccess',
					'label'     => __( 'Access Levels', 'learndash-gravity-forms' ),
					'type'      => 'select',
					'choices'   => $accesslevels,
				  //  'tooltip' => sprintf( '<h6>%s</h6> %s', __( 'Tooltip Header', 'my-text-domain' ), __( 'This is the tooltip description', 'my-text-domain' ) ),
				);
		}
		return $fields;
	}

	function debug($msg) {
		if(!isset($_GET['debug']) && !$this->debug)
			return;

		$original_log_errors = ini_get('log_errors');
		$original_error_log = ini_get('error_log');
		ini_set('log_errors', true);
		ini_set('error_log', dirname(__FILE__).DIRECTORY_SEPARATOR.'debug.log');
		
		global $ld_sf_processing_id;
		if(empty($ld_sf_processing_id))
		$ld_sf_processing_id	= time();
		
		error_log("[$ld_sf_processing_id] ".print_r($msg, true)); //Comment This line to stop logging debug messages.
		//echo "<pre>"; print_r($msg); echo "</pre>";

		ini_set('log_errors', $original_log_errors);
		ini_set('error_log', $original_error_log);		
	}

	function completed_registration( $user_id, $config, $entry, $user_pass ) {
		if ( isset( $config['meta']['gf_user_registration_paid_form'] ) && $config['meta']['gf_user_registration_paid_form'] ) {
			if ( $entry['payment_status'] !== 'Paid' ) {
				return;
			}
		}

		$this->toggle_access( $entry );
	}

	/**
	 * Enroll/unenroll users to/from courses/groups based on form entry
	 * 
	 * @param  array   $entry  Form entry
	 * @param  boolean $remove True to unenroll|default to false
	 * @return void
	 */
	public function toggle_access( $entry, $remove = false ) {
		$form = GFAPI::get_form( $entry['form_id'] );

		if ( ! $remove ) {
			if ( ! apply_filters( 'learndash_gravity_forms_enroll_user', true, $entry, $form ) ) {
				return;
			}
		} else {
			if ( ! apply_filters( 'learndash_gravity_forms_unenroll_user', true, $entry, $form ) ) {
				return;
			}
		}

		// Enroll user via GF user registration feed settings
		if ( function_exists( 'gf_user_registration' ) ) {
			$feeds = gf_user_registration()->get_feeds( $entry['form_id'] );

			foreach ( $feeds as $feed ) {
				if ( ! gf_user_registration()->is_feed_condition_met( $feed, $form, $entry ) ) {
					continue;
				}

				// Get user ID
				$email_id = $feed['meta']['email'];
				$email = $entry[ $email_id ];
				$user = get_user_by( 'email', $email );

				if ( $user ) {
					$user_id = $user->ID;
				} else {
                    //continue;
                    //updated by shabbar to fix update bundle error
                    if($feed['meta']['feedType'] == 'update' && is_user_logged_in())
                    {
                        $user_id = get_current_user_ID();
                    }
                    else{
                        continue;
                    }
				}

				// Enroll users from field LearnDash options
				foreach ( $form['fields'] as $field ) {
					if ( ! empty( $entry[ $field['id'] ] ) ) {
						if ( ! empty( $field['choices'] ) ) {
							foreach ( $field['choices'] as $choice ) {
								// if ( $choice['value'] == $entry[ $field['id'] ] ) {
								if ( 
									$choice['value'] == $entry[ $field['id'] ]
									|| strpos( $entry[ $field['id'] ], "\"{$choice['value']}\"" ) !== false 
								) {
									// Enroll
									foreach ( $choice['courses'] as $course_id ) {
										ld_update_course_access( $user_id, $course_id, $remove );
									}

									foreach ( $choice['groups'] as $group_id ) {
										ld_update_group_access( $user_id, $group_id, $remove );
									}
								}
							}
						}
					}
				}

				foreach ( $feed['meta']['gf_user_registration_ldcourses'] as $course_id => $enabled ) {
					if ( $enabled ) {
						ld_update_course_access( $user_id, $course_id, $remove );
					}
				}

				foreach ( $feed['meta']['gf_user_registration_ldgroups'] as $group_id => $enabled ) {
					if ( $enabled ) {
						ld_update_group_access( $user_id, $group_id, $remove );
					}
				}
			}
		}
	}

	/**
	 * Triggered on gform_post_payment_completed action hook
	 *
	 * Enroll users to form courses.
	 * 
	 * @param  array  $entry  Form entry
	 * @param  array  $action Form action
	 * @return void
	 */
	public function completed_payment( $entry, $action ) {
		$this->toggle_access( $entry );
	}

	/**
	 * Triggered on gform_post_payment_refunded action hook
	 *
	 * Unenroll users from form courses.
	 * 
	 * @param  array  $entry  Form entry
	 * @param  array  $action Form action
	 * @return void
	 */
	public function refunded_payment( $entry, $action ) {
		$this->toggle_access( $entry, $remove = true );
	}

	/**
	 * Triggered on gform_post_subscription_started action hook
	 *
	 * Enroll users to form courses.
	 * 
	 * @param  array  $entry  		Form entry
	 * @param  array  $subscription Subscription details
	 * @return void
	 */
	public function subscription_started( $entry, $subscription ) {
		$this->toggle_access( $entry );
	}

	/**
	 * Triggered on gform_subscription_canceled action hook
	 *
	 * Unenroll users from form courses.
	 * 
	 * @param  array  $entry 		   Form entry
	 * @param  array  $feed   		   Subscription details
	 * @param  int    $transaction_id  Transaction ID
	 * @return void
	 */
	public function subscription_canceled( $entry, $feed, $transaction_id ) {
		$this->toggle_access( $entry, $remove = true );
	}

	public function get_courses() {
		return $courses = get_posts( [
			'post_type' => 'sfwd-courses',
			'status' => 'publish',
			'posts_per_page' => -1,
			'order' => 'ASC',
			'orderby' => 'title',
		] );
	}

	public function get_groups()
	{
		return $groups = get_posts( [
			'post_type' => 'groups',
			'status' => 'publish',
			'posts_per_page' => -1,
			'order' => 'ASC',
			'orderby' => 'title',
		] );
	}
	
	public function list_courses() {
		$courses_options = [];

		$courses = get_posts( [
			'post_type' => 'sfwd-courses',
			'status' => 'publish',
			'posts_per_page' => -1,
		] );

		foreach ( $courses as $course ) {
			$courses_options[] = [
				'label' => $course->post_title,
				'value' => 1,
				'name'  => 'gf_user_registration_ldcourses[' . $course->ID . ']',
			];
		}

		return $courses_options;
	}

	public function list_groups()
	{
		$groups_options = [];

		$groups = get_posts( [
			'post_type' => 'groups',
			'status' => 'publish',
			'posts_per_page' => -1,
		] );

		foreach ( $groups as $group ) {
			$groups_options[] = [
				'label' => $group->post_title,
				'value' => 1,
				'name'  => 'gf_user_registration_ldgroups[' . $group->ID . ']',
			];
		}

		return $groups_options;
	}

	public function list_accesslevels() {
		$access = array();
		if(function_exists('learndash_plus_get_levels'))
		{
			$accesslevels = learndash_plus_get_levels();
			if(empty($accesslevels[0])) {
				$access[0] = array(
					"label" => __("Don't Assign", 'learndash-gravity-forms'),
					"value"	=> 0,
					"name"	=> "gf_user_registration_ldaccess",
				);
			}
			
			foreach($accesslevels as $id=>$v) {
				$access[] = array(
						"label"	=> $v["name"],
						"value"	=> $id, 
						"name"	=> "gf_user_registration_ldaccess",
					);
			}
		}
		return $access;
	}
}

new learndash_gravityforms();
