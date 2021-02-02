<?php
/**
 * Class payro24_Action_After_Submit
 * Custom elementor form action after submit to process payment
 */

use Elementor\Controls_Manager;
use Elementor\Settings;

class payro24_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {

    public function __construct() {
        if ( is_admin() ) {
            add_action( 'elementor/admin/after_create_settings/' . Settings::PAGE_ID, [ $this, 'register_admin_fields' ], 10 );
        }
    }

    /**
     * Return the action name
     * @return string
     */
    public function get_name() {
        return 'payro24';
    }

    /**
     * Returns the action label
     *
     * @return string
     */
    public function get_label() {
        return __( 'payro24', 'payro24-elementor' );
    }

    /**
     * Runs the action after submit
     *
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
     */
    public function run( $record, $ajax_handler ) {
        global $wpdb;

        $settings = $record->get( 'form_settings' );

        $api_key = $this->get_global_setting('payro24_api_key');
        $sandbox = $this->get_global_setting('payro24_sandbox') || 'false';
        $currency = $this->get_global_setting('payro24_currency') || 'rial';

        if ( empty( $api_key ) ) {
            $this->show_error( __( 'payro24 settings is not configured.', 'payro24-elementor' ), $ajax_handler );
        }

        // Get submitted Form data
        $raw_fields = $record->get( 'fields' );

        // Normalize the Form Data
        $fields = [];
        foreach ( $raw_fields as $id => $field ) {
            $fields[ $id ] = $field['value'];
        }

        // Process the amount
        if ( empty( $fields[ $settings['payro24_amount_field'] ] )) {
            $this->show_error( __( 'Amount should not be empty.', 'payro24-elementor' ), $ajax_handler );
        }
        $amount = intval( $fields[ $settings['payro24_amount_field'] ] );
        $amount = $amount * ($currency == 'rial' ? 1 : 10);

        // Set all other fields
        $name = !empty( $fields[ $settings['payro24_name_field'] ] ) ? $fields[ $settings['payro24_name_field'] ] : '';
        $phone = !empty( $fields[ $settings['payro24_phone_field'] ] ) ? $fields[ $settings['payro24_phone_field'] ] : '';
        $email = !empty( $fields[ $settings['payro24_email_field'] ] ) ? $fields[ $settings['payro24_email_field'] ] : '';
        $desc = !empty( $fields[ $settings['payro24_desc_field'] ] ) ? $fields[ $settings['payro24_desc_field'] ] : '';
        $order_id = time();

        $row = [
            'order_id' => $order_id,
            'post_id' => sanitize_text_field($_POST['post_id']),
            'trans_id' => '',
            'amount' => $amount,
            'phone' => $phone,
            'description' => $desc,
            'email' => $email,
            'created_at' => time(),
            'status' => 'pending',
            'log' => '',
            'return_url' => $_REQUEST['referrer'],
        ];
        $row_format = [
            '%d',
            '%d',
            '%s',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            "%s",
            "%s",
        ];

        $data = [
            'order_id'	=> $order_id,
            'amount'	=> $amount,
            'name'		=> $name,
            'phone'		=> $phone,
            'mail'		=> $email,
            'desc'		=> $desc,
            'callback'	=> add_query_arg( 'elementor_payro24_action', 'callback', get_home_url() ),
        ];
        $headers = [
            'Content-Type'=> 'application/json',
            'P-TOKEN'	=> $api_key,
            'p-SANDBOX'	=> $sandbox,
        ];
        $args = [
            'body'		=> json_encode( $data ),
            'headers'	=> $headers,
            'timeout'	=> 15,
        ];

        $payro24 = new payro24_Elementor_Extension;
        $response = $payro24->call_gateway_endpoint( 'https://api.payro24.ir/v1.1/payment', $args );
        if ( is_wp_error( $response ) ) {
            $error = $response->get_error_message();
            $row['status'] = 'failed';
            $row['log'] = $error;
            $wpdb->insert( $wpdb->prefix . $payro24::payro24_TABLE_NAME, $row, $row_format );

            $this->show_error( $error, $ajax_handler );
        }

        $http_status	= wp_remote_retrieve_response_code( $response );
        $result			= wp_remote_retrieve_body( $response );
        $result			= json_decode( $result );

        if ( 201 !== $http_status || empty( $result ) || empty( $result->link ) ) {
            $error = sprintf( '%s (code: %s)', $result->error_message, $result->error_code );
            $row['status'] = 'failed';
            $row['log'] = $error;
            $wpdb->insert( $wpdb->prefix . $payro24::payro24_TABLE_NAME, $row, $row_format );

            $this->show_error( $error, $ajax_handler );
        }

        $row['trans_id'] = $result->id;
        $row['status'] = 'bank';
        $wpdb->insert( $wpdb->prefix . $payro24::payro24_TABLE_NAME, $row, $row_format );

        $ajax_handler->add_response_data( 'redirect_url', $result->link );

    }

    /**
     * Register Settings Section
     *
     * Registers the Action controls
     *
     * @access public
     * @param \Elementor\Widget_Base $widget
     */
    public function register_settings_section( $widget ) {

        $widget->start_controls_section(
            'section_payro24',
            [
                'label' => __( 'payro24', 'payro24-elementor' ),
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );

        $widget->add_control(
            'payro24_msg',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => sprintf( __( 'Set your Default Values in the <a href="%1$s" target="_blank">Integrations Settings</a>.', 'payro24-elementor' ), Settings::get_url() . '#tab-integrations' ),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
            ]
        );

        $widget->add_control(
            'payro24_amount_field',
            [
                'label' => __( 'Amount Field ID', 'payro24-elementor' ),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $widget->add_control(
            'payro24_email_field',
            [
                'label' => __( 'Email Field ID', 'payro24-elementor' ),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $widget->add_control(
            'payro24_name_field',
            [
                'label' => __( 'Name Field ID', 'payro24-elementor' ),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $widget->add_control(
            'payro24_phone_field',
            [
                'label' => __( 'Phone Field ID', 'payro24-elementor' ),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $widget->add_control(
            'payro24_desc_field',
            [
                'label' => __( 'Desc Field ID', 'payro24-elementor' ),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $widget->end_controls_section();

    }

    /**
     * Clears form settings on export
     * @access Public
     * @param array $element
     */
    public function on_export( $element ) {
        unset(
            $element['payro24_api_key'],
            $element['payro24_sandbox'],
            $element['payro24_amount_field'],
            $element['payro24_email_field'],
            $element['payro24_name_field'],
            $element['payro24_phone_field'],
            $element['payro24_desc_field'],
        );
    }

    /**
     * @param Settings $settings
     */
    public function register_admin_fields( Settings $settings ) {

        $settings->add_section( Settings::TAB_INTEGRATIONS, 'payro24', [
            'callback' => function() {
                echo '<hr><h2>' . esc_html__( 'payro24', 'payro24-elementor' ) . '</h2>';
            },
            'fields' => [
                'payro24_api_key' => [
                    'label' => __( 'payro24 API Key', 'payro24-elementor' ),
                    'field_args' => [
                        'type' => 'text',
                        'desc' => sprintf( __( 'To integrate with our forms you need an <a href="%s" target="_blank">API Key</a>.', 'payro24-elementor' ), 'https://payro24.ir/dashboard/web-services/' ),
                    ],
                ],
                'payro24_sandbox' => [
                    'label' => __( 'Sandbox mode', 'payro24-elementor' ),
                    'field_args' => [
                        'type' => 'select',
                        'default' => 'false',
                        'options' => [
                            'true' => __( 'Yes', 'payro24-elementor' ),
                            'false' => __( 'No', 'payro24-elementor' ),
                        ],
                    ],
                ],
                'payro24_currency' => [
                    'label' => __( 'Default Currency', 'payro24-elementor' ),
                    'field_args' => [
                        'type' => 'select',
                        'default' => 'rial',
                        'options' => [
                            'rial' => __( 'Rial', 'payro24-elementor' ),
                            'toman' => __( 'Toman', 'payro24-elementor' ),
                        ],
                    ],
                ],
            ],
        ] );

    }

    /**
     * @param $name
     *
     * @return bool|mixed|void
     */
    private function get_global_setting( $name ) {
        return get_option( 'elementor_' . $name );
    }

    /**
     * @param $message
     * @param $ajax_handler
     */
    private function show_error( $message, $ajax_handler ) {

        wp_send_json_error( [
            'message' => $message,
            'data' => $ajax_handler->data,
        ] );

    }
}
