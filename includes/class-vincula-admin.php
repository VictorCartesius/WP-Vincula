<?php
/**
 * Admin: custom post type, meta boxes, list columns, media uploader and Quick Edit.
 *
 * @package Vincula
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Vincula_Admin {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_' . VINCULA_POST_TYPE, array( $this, 'save_meta_fields' ) );
        add_filter( 'manage_' . VINCULA_POST_TYPE . '_posts_columns', array( $this, 'set_custom_columns' ) );
        add_action( 'manage_' . VINCULA_POST_TYPE . '_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_uploader' ) );

        // Quick Edit support.
        add_action( 'quick_edit_custom_box', array( $this, 'render_quick_edit_fields' ), 10, 2 );
        add_action( 'admin_print_footer_scripts', array( $this, 'print_quick_edit_script' ) );
        add_filter( 'wp_insert_post_data', array( $this, 'filter_quick_edit_description' ), 10, 2 );
    }

    public function register_post_type() {
        $labels = array(
            'name'               => 'Friend Links',
            'singular_name'      => 'Friend Link',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Friend Link',
            'edit_item'          => 'Edit Friend Link',
            'new_item'           => 'New Friend Link',
            'view_item'          => 'View Friend Link',
            'search_items'       => 'Search Friend Links',
            'not_found'          => 'No friend links found',
            'not_found_in_trash' => 'No friend links found in trash',
            'menu_name'          => 'Vincula',
        );

        $args = array(
            'labels'            => $labels,
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'menu_position'     => 80,
            'menu_icon'         => 'dashicons-networking',
            'capability_type'   => 'post',
            'supports'          => array( 'title', 'editor' ),
            'has_archive'       => false,
            'rewrite'           => false,
            'query_var'         => false,
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => false,
        );

        register_post_type( VINCULA_POST_TYPE, $args );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'vincula_url',
            'URL (Required)',
            array( $this, 'render_url_field' ),
            VINCULA_POST_TYPE,
            'normal',
            'high'
        );
        add_meta_box(
            'vincula_logo',
            'Logo Image URL (Optional)',
            array( $this, 'render_logo_field' ),
            VINCULA_POST_TYPE,
            'normal',
            'high'
        );
        add_meta_box(
            'vincula_order',
            'Priority (lower numbers are displayed first)',
            array( $this, 'render_order_field' ),
            VINCULA_POST_TYPE,
            'side',
            'default'
        );
    }

    public function render_url_field( $post ) {
        $url = get_post_meta( $post->ID, '_vincula_url', true );
        ?>
        <label for="vincula_url">Enter the full URL (including https://)</label>
        <input type="url" name="vincula_url" id="vincula_url" value="<?php echo esc_url( $url ); ?>" style="width:100%;" placeholder="https://example.com" required />
        <?php
    }

    // Logo field with media uploader.
    public function render_logo_field( $post ) {
        $logo = get_post_meta( $post->ID, '_vincula_logo', true );
        ?>
        <label for="vincula_logo">Enter the logo image URL or click "Upload Image"</label>
        <div style="display:flex; align-items:center; gap:10px;">
            <input type="url" name="vincula_logo" id="vincula_logo" value="<?php echo esc_url( $logo ); ?>" style="flex:1;" placeholder="https://example.com/logo.png" />
            <button type="button" class="button" id="vincula_upload_logo">Upload Image</button>
        </div>
        <p class="description">If left empty, the site's favicon is fetched automatically.</p>
        <div id="vincula_logo_preview" style="margin-top:8px;">
            <?php if ( $logo ) : ?>
                <img src="<?php echo esc_url( $logo ); ?>" style="max-height:50px; border:1px solid #ddd; border-radius:4px; padding:2px;" />
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#vincula_upload_logo').click(function(e) {
                e.preventDefault();
                var frame = wp.media({
                    title: 'Select or Upload Logo',
                    button: { text: 'Use this image' },
                    multiple: false
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#vincula_logo').val(attachment.url);
                    $('#vincula_logo_preview').html('<img src="' + attachment.url + '" style="max-height:50px; border:1px solid #ddd; border-radius:4px; padding:2px;" />');
                });
                frame.open();
            });
            // Update the preview when the URL changes.
            $('#vincula_logo').on('change', function() {
                var val = $(this).val();
                if (val) {
                    $('#vincula_logo_preview').html('<img src="' + val + '" style="max-height:50px; border:1px solid #ddd; border-radius:4px; padding:2px;" />');
                } else {
                    $('#vincula_logo_preview').html('');
                }
            });
        });
        </script>
        <?php
    }

    public function render_order_field( $post ) {
        $order = get_post_meta( $post->ID, '_vincula_order', true );
        if ( $order === '' ) $order = 0;
        ?>
        <label for="vincula_order">Priority</label>
        <input type="number" name="vincula_order" id="vincula_order" value="<?php echo esc_attr( $order ); ?>" min="0" step="1" style="width:100%;" />
        <?php
    }

    public function save_meta_fields( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        if ( isset( $_POST['vincula_url'] ) ) {
            update_post_meta( $post_id, '_vincula_url', esc_url_raw( $_POST['vincula_url'] ) );
        }
        if ( isset( $_POST['vincula_logo'] ) ) {
            update_post_meta( $post_id, '_vincula_logo', esc_url_raw( $_POST['vincula_logo'] ) );
        }
        if ( isset( $_POST['vincula_order'] ) ) {
            update_post_meta( $post_id, '_vincula_order', intval( $_POST['vincula_order'] ) );
        }
    }

    public function set_custom_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( 'title' === $key ) {
                $new_columns['vincula_order'] = 'Priority';
                $new_columns['vincula_url']   = 'URL';
                $new_columns['vincula_logo']  = 'Logo';
            }
        }
        return $new_columns;
    }

    public function render_custom_columns( $column, $post_id ) {
        if ( 'vincula_order' === $column ) {
            echo get_post_meta( $post_id, '_vincula_order', true ) ?: '0';
        }
        if ( 'vincula_url' === $column ) {
            $url = get_post_meta( $post_id, '_vincula_url', true );
            echo $url ? '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $url ) . '</a>' : '';
        }
        if ( 'vincula_logo' === $column ) {
            $logo = get_post_meta( $post_id, '_vincula_logo', true );
            echo $logo ? '<img src="' . esc_url( $logo ) . '" style="max-height:30px; border-radius:4px;" />' : 'Automatic';
        }
    }

    // Enqueue the media uploader script for the logo field.
    public function enqueue_media_uploader( $hook ) {
        global $post_type;
        if ( VINCULA_POST_TYPE === $post_type ) {
            wp_enqueue_media();
        }
    }

    /**
     * Render the fields shown inside the Quick Edit form.
     *
     * Quick Edit renders a single template form, so the fields are printed
     * once even though this callback fires for every custom column.
     *
     * @param string $column_name Custom column being rendered.
     * @param string $post_type   Current post type.
     */
    public function render_quick_edit_fields( $column_name, $post_type ) {
        if ( VINCULA_POST_TYPE !== $post_type ) return;

        static $rendered = false;
        if ( $rendered ) return;
        $rendered = true;
        ?>
        <fieldset class="inline-edit-col-right vincula-quick-edit">
            <div class="inline-edit-col">
                <label class="inline-edit-group">
                    <span class="title">Priority</span>
                    <span class="input-text-wrap">
                        <input type="number" name="vincula_order" value="0" min="0" step="1" />
                    </span>
                </label>
                <label class="inline-edit-group">
                    <span class="title">URL</span>
                    <span class="input-text-wrap">
                        <input type="url" name="vincula_url" value="" placeholder="https://example.com" />
                    </span>
                </label>
                <label class="inline-edit-group">
                    <span class="title">Logo URL</span>
                    <span class="input-text-wrap">
                        <input type="url" name="vincula_logo" value="" placeholder="Leave empty to use the favicon" />
                    </span>
                </label>
                <label class="inline-edit-group">
                    <span class="title">Description</span>
                    <span class="input-text-wrap">
                        <textarea name="vincula_description" rows="3" style="width:100%;"></textarea>
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Print the data map and the script that fills the Quick Edit form.
     */
    public function print_quick_edit_script() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'edit-' . VINCULA_POST_TYPE !== $screen->id ) return;

        $posts = isset( $GLOBALS['wp_query']->posts ) ? $GLOBALS['wp_query']->posts : array();
        $data  = array();

        foreach ( $posts as $post ) {
            if ( VINCULA_POST_TYPE !== $post->post_type ) continue;

            $data[ $post->ID ] = array(
                'url'         => (string) get_post_meta( $post->ID, '_vincula_url', true ),
                'logo'        => (string) get_post_meta( $post->ID, '_vincula_logo', true ),
                'order'       => (string) (int) get_post_meta( $post->ID, '_vincula_order', true ),
                'description' => (string) $post->post_content,
            );
        }

        $json = wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
        ?>
        <script>
        (function($) {
            var vinculaData = <?php echo $json ? $json : '{}'; ?>;

            if (typeof inlineEditPost === 'undefined') {
                return;
            }

            var originalEdit = inlineEditPost.edit;

            inlineEditPost.edit = function(id) {
                var result = originalEdit.apply(this, arguments);
                var postId = 0;

                if (id && typeof id === 'object') {
                    postId = parseInt(this.getId(id), 10);
                } else {
                    postId = parseInt(id, 10);
                }

                var values = vinculaData[postId];
                if (!values) {
                    return result;
                }

                var $form = $('#edit-' + postId);
                if (!$form.length) {
                    return result;
                }

                $form.find('input[name="vincula_url"]').val(values.url);
                $form.find('input[name="vincula_logo"]').val(values.logo);
                $form.find('input[name="vincula_order"]').val(values.order);
                $form.find('textarea[name="vincula_description"]').val(values.description);

                return result;
            };
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Apply the Quick Edit description to the post content.
     *
     * Quick Edit sends no native content field, so the value is injected here.
     *
     * @param array $data    Slashed post data.
     * @param array $postarr Raw post array.
     * @return array
     */
    public function filter_quick_edit_description( $data, $postarr ) {
        if ( ! isset( $_POST['vincula_description'] ) ) {
            return $data;
        }
        if ( ! isset( $data['post_type'] ) || VINCULA_POST_TYPE !== $data['post_type'] ) {
            return $data;
        }

        $data['post_content'] = wp_slash( wp_kses_post( wp_unslash( $_POST['vincula_description'] ) ) );

        return $data;
    }
}
