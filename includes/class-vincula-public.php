<?php
/**
 * Front end: shortcode, query, markup and inline styles.
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

        add_action( 'wp_footer', function() {
            echo '<style>
            .vincula-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                justify-content: flex-start;
                margin: 20px 0;
            }
            .vincula-item {
                flex: 0 0 calc(50% - 10px);
                max-width: calc(50% - 10px);
                background: #f9f9f9;
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                padding: 16px 12px;
                box-sizing: border-box;
                transition: all 0.2s ease;
                box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            }
            .vincula-item:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 16px rgba(0,0,0,0.08);
                border-color: #b0b0b0;
            }
            .vincula-link {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                text-decoration: none;
                color: #333;
            }
            .vincula-icon {
                width: 72px;
                height: 72px;
                border-radius: 50%;
                object-fit: contain;
                background: #fff;
                padding: 4px;
                margin-bottom: 8px;
                border: 1px solid #eee;
            }
            .vincula-name {
                font-weight: 600;
                font-size: 1rem;
                margin-bottom: 4px;
                color: #1a1a1a;
            }
            .vincula-desc {
                font-size: 0.85rem;
                color: #666;
                line-height: 1.4;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            @media (max-width: 600px) {
                .vincula-item {
                    flex: 0 0 100%;
                    max-width: 100%;
                }
            }
            </style>';
        } );

        return $output;
    }
}
