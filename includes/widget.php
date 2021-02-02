<?php
/**
 * Elementor payro24 Widget.
 *
 * Elementor widget that inserts the payro24 transaction result.
 */
class Elementor_payro24_Widget extends \Elementor\Widget_Base {

    /**
     * Retrieve payro24 widget name.
     * @return string Widget name.
     */
    public function get_name() {
        return 'payro24';
    }

    /**
     * Retrieve payro24 widget title.
     * @return string Widget title.
     */
    public function get_title() {
        return __( 'payro24', 'plugin-name' );
    }

    /**
     * Get widget icon.
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'fa fa-code';
    }

    /**
     * Retrieve the list of categories the payro24 widget belongs to.
     *
     * @return array Widget categories.
     */
    public function get_categories() {
        return [ 'general' ];
    }

    /**
     * Adds different input fields to allow the user to change and customize the widget settings.
     */
    protected function _register_controls() {

        $this->start_controls_section(
            'payro24_section',
            [
                'label' => __( 'payro24 result message', 'payro24-elementor' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'payro24_classes',
            [
                'label' => __( 'Extra classes', 'plugin-name' ),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->end_controls_section();

    }

    /**
     * Render payro24 widget output on the frontend.
     */
    protected function render() {

        $settings = $this->get_settings_for_display();

        $classes = $settings['payro24_classes'];

        if( !empty( $_GET['payro24_status'] ) && !empty( $_GET['payro24_message'] ) ){
            $color = $_GET['payro24_status'] == 'failed' ? '#f44336' : '#8BC34A';

            echo sprintf( '<div class="payro24-elementor-widget %s">', $classes );
            echo sprintf( '<b style="color:%s; text-align:center; display: block;">%s</b>', $color, sanitize_text_field( $_GET['payro24_message'] ) );
            echo '</div>';
        }

    }

}
