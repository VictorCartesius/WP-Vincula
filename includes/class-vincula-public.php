<?php
/**
 * Front end: shortcode, query, markup and stylesheet enqueue.
 *
 * @package Vincula
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Vincula_Public {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'vincula', array( $this, 'render_cards' ) );
        // Kept for backwards compatibility with pages using the earlier shortcode name.
        add_shortcode( 'friend_cards', array( $this, 'render_cards' ) );
        // Load the stylesheet in the document head so that themes which swap page
        // content without a full reload (PJAX/AJAX navigation) still render the cards.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    public function enqueue_styles() {
        wp_enqueue_style( 'vincula', VINCULA_URL . 'assets/vincula.css', array(), VINCULA_VERSION );
    }

    public function render_cards() {
        $args = array(
            'post_type'      => VINCULA_POST_TYPE,
            'posts_per_page' => -1,
            'meta_key'       => '_vincula_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        );
        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            return '<p>No friend links available.</p>';
        }

        $output = '<div class="vincula-grid">';

        while ( $query->have_posts() ) {
            $query->the_post();
            $id   = get_the_ID();
            $name = get_the_title();
            $url  = get_post_meta( $id, '_vincula_url', true );
            if ( empty( $url ) ) continue;

            $desc = get_the_content();
            // Use the custom logo, or fall back to the site's favicon.
            $logo = get_post_meta( $id, '_vincula_logo', true );
            if ( empty( $logo ) ) {
                $domain = parse_url( $url, PHP_URL_HOST );
                $logo = 'https://www.google.com/s2/favicons?domain=' . $domain;
            }

            $output .= '<div class="vincula-item">';
            $output .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" class="vincula-link">';
            $output .= '<img src="' . esc_url( $logo ) . '" alt="' . esc_attr( $name ) . '" class="vincula-icon" loading="lazy">';
            $output .= '<span class="vincula-name">' . esc_html( $name ) . '</span>';
            if ( $desc ) {
                $output .= '<span class="vincula-desc">' . esc_html( $desc ) . '</span>';
            }
            $output .= '</a>';
            $output .= '</div>';
        }

        wp_reset_postdata();
        $output .= '</div>';

        return $output;
    }
}
