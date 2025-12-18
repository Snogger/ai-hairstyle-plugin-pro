<?php
/**
 * Custom Elementor widget: AI Try-On Form Linker.
 * Selects an Elementor form and adds webhook for unlock/booking.
 */
if ( defined( 'ELEMENTOR_VERSION' ) ) {
    class AI_Hairstyle_Elementor_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'ai_tryon_form_linker'; }
        public function get_title() { return 'AI Try-On Form Linker'; }
        // TODO: Controls + render
    }
    // Register widget later
}