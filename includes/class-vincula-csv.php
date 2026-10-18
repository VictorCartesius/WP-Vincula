<?php
/**
 * Admin: CSV import and export of friend links.
 *
 * @package Vincula
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Vincula_CSV {

    const MENU_SLUG = 'vincula-tools';

    /**
     * Column order used by both export and the documented import format.
     */
    const COLUMNS = array( 'name', 'description', 'url', 'logo', 'priority', 'status' );

    /**
     * Accepted header aliases for each column.
     */
    const ALIASES = array(
        'name'        => array( 'name', 'title', 'site', 'site_name' ),
        'description' => array( 'description', 'desc', 'content', 'summary' ),
        'url'         => array( 'url', 'link', 'href', 'website', 'site_url' ),
        'logo'        => array( 'logo', 'logo_url', 'image', 'icon' ),
        'priority'    => array( 'priority', 'order', 'weight' ),
        'status'      => array( 'status', 'post_status' ),
    );

    const ALLOWED_STATUSES = array( 'publish', 'draft', 'pending', 'private', 'future' );

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_post_vincula_export', array( $this, 'export' ) );
        add_action( 'admin_post_vincula_import', array( $this, 'import' ) );
    }

    /**
     * Register the Import / Export screen under the Vincula menu.
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=' . VINCULA_POST_TYPE,
            'Import / Export',
            'Import / Export',
            'edit_posts',
            self::MENU_SLUG,
            array( $this, 'render_page' )
        );
    }

    /**
     * URL of the Import / Export screen, with optional query args.
     *
     * @param array $args Extra query args.
     * @return string
     */
    private function page_url( $args = array() ) {
        $url = add_query_arg(
            array(
                'post_type' => VINCULA_POST_TYPE,
                'page'      => self::MENU_SLUG,
            ),
            admin_url( 'edit.php' )
        );
        return $args ? add_query_arg( $args, $url ) : $url;
    }

    /**
     * Stop the request and bounce back to the tools screen.
     *
     * @param array $args Query args describing the outcome.
     */
    private function redirect_back( $args ) {
        wp_safe_redirect( $this->page_url( $args ) );
        exit;
    }

    /**
     * Render the Import / Export screen.
     */
    public function render_page() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html( 'You are not allowed to manage friend links.' ) );
        }

        $error = isset( $_GET['vincula_error'] ) ? sanitize_key( $_GET['vincula_error'] ) : '';
        $imported = isset( $_GET['vincula_imported'] ) ? absint( $_GET['vincula_imported'] ) : null;
        $updated  = isset( $_GET['vincula_updated'] ) ? absint( $_GET['vincula_updated'] ) : null;
        $skipped  = isset( $_GET['vincula_skipped'] ) ? absint( $_GET['vincula_skipped'] ) : null;
        ?>
        <div class="wrap">
            <h1>Import / Export</h1>

            <?php if ( 'nofile' === $error ) : ?>
                <div class="notice notice-error"><p>No CSV file was uploaded.</p></div>
            <?php elseif ( 'notcsv' === $error ) : ?>
                <div class="notice notice-error"><p>Please upload a <code>.csv</code> file.</p></div>
            <?php elseif ( 'nourlcolumn' === $error ) : ?>
                <div class="notice notice-error"><p>The CSV file has no <code>url</code> column. Download an export first to see the expected format.</p></div>
            <?php elseif ( 'unreadable' === $error ) : ?>
                <div class="notice notice-error"><p>The uploaded file could not be read.</p></div>
            <?php elseif ( null !== $imported ) : ?>
                <div class="notice notice-success">
                    <p>
                        <?php
                        printf(
                            'Imported %1$d, updated %2$d, skipped %3$d.',
                            (int) $imported,
                            (int) $updated,
                            (int) $skipped
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width:760px;">
                <h2>Export</h2>
                <p>Download every friend link as a UTF-8 CSV file. The file opens directly in Excel, Numbers or Google Sheets.</p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="vincula_export" />
                    <?php wp_nonce_field( 'vincula_export' ); ?>
                    <p><button type="submit" class="button button-primary">Download CSV</button></p>
                </form>
            </div>

            <div class="card" style="max-width:760px; margin-top:20px;">
                <h2>Import</h2>
                <p>Rows are matched against existing entries by <code>url</code>. Rows without a URL are skipped.</p>
                <p>
                    New here? <a href="<?php echo esc_url( VINCULA_URL . 'samples/friend-links-sample.csv' ); ?>" download>Download the sample CSV</a>
                    to see the exact columns, a quoted field containing a comma, and how empty values fall back.
                </p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="vincula_import" />
                    <?php wp_nonce_field( 'vincula_import' ); ?>
                    <p><input type="file" name="vincula_csv" accept=".csv,text/csv" required /></p>
                    <p>
                        <label>
                            <input type="checkbox" name="vincula_update_existing" value="1" />
                            Update entries whose URL already exists
                        </label>
                    </p>
                    <p><button type="submit" class="button button-primary">Import CSV</button></p>
                </form>
            </div>

            <div class="card" style="max-width:760px; margin-top:20px;">
                <h2>CSV format</h2>
                <p>Column order does not matter and the header row is required. Accepted headers:</p>                <table class="widefat striped" style="max-width:640px;">
                    <thead>
                        <tr><th>Column</th><th>Aliases</th><th>Notes</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>name</code></td><td><code>title</code>, <code>site</code></td><td>Falls back to the URL host when empty.</td></tr>
                        <tr><td><code>description</code></td><td><code>desc</code>, <code>content</code>, <code>summary</code></td><td>Plain text; two lines are shown on the front end.</td></tr>
                        <tr><td><code>url</code></td><td><code>link</code>, <code>href</code>, <code>website</code></td><td>Required. Full address including <code>https://</code>.</td></tr>
                        <tr><td><code>logo</code></td><td><code>logo_url</code>, <code>image</code>, <code>icon</code></td><td>Optional; favicon is used when empty.</td></tr>
                        <tr><td><code>priority</code></td><td><code>order</code>, <code>weight</code></td><td>Integer, defaults to <code>0</code>.</td></tr>
                        <tr><td><code>status</code></td><td><code>post_status</code></td><td><code>publish</code>, <code>draft</code>, <code>pending</code>, <code>private</code> or <code>future</code>. Defaults to <code>publish</code>.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Stream all entries as a CSV download.
     */
    public function export() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html( 'You are not allowed to export friend links.' ) );
        }
        check_admin_referer( 'vincula_export' );

        $posts = get_posts(
            array(
                'post_type'      => VINCULA_POST_TYPE,
                'post_status'    => self::ALLOWED_STATUSES,
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        // Sort by priority in PHP so entries missing the meta key are kept too.
        usort(
            $posts,
            function( $a, $b ) {
                return (int) get_post_meta( $a->ID, '_vincula_order', true )
                    <=> (int) get_post_meta( $b->ID, '_vincula_order', true );
            }
        );

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="vincula-friend-links-' . gmdate( 'Y-m-d' ) . '.csv"' );

        $output = fopen( 'php://output', 'w' );
        // BOM so Excel detects UTF-8.
        fwrite( $output, "\xEF\xBB\xBF" );
        fputcsv( $output, self::COLUMNS, ',', '"', '\\' );

        foreach ( $posts as $post ) {
            fputcsv(
                $output,
                array(
                    $post->post_title,
                    wp_strip_all_tags( $post->post_content ),
                    get_post_meta( $post->ID, '_vincula_url', true ),
                    get_post_meta( $post->ID, '_vincula_logo', true ),
                    (int) get_post_meta( $post->ID, '_vincula_order', true ),
                    $post->post_status,
                ),
                ',',
                '"',
                '\\'
            );
        }

        fclose( $output );
        exit;
    }

    /**
     * Create or update entries from an uploaded CSV file.
     */
    public function import() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html( 'You are not allowed to import friend links.' ) );
        }
        check_admin_referer( 'vincula_import' );

        if ( empty( $_FILES['vincula_csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['vincula_csv']['tmp_name'] ) ) {
            $this->redirect_back( array( 'vincula_error' => 'nofile' ) );
        }

        $filename = isset( $_FILES['vincula_csv']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['vincula_csv']['name'] ) ) : '';
        if ( 'csv' !== strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
            $this->redirect_back( array( 'vincula_error' => 'notcsv' ) );
        }

        $handle = fopen( $_FILES['vincula_csv']['tmp_name'], 'r' );
        if ( ! $handle ) {
            $this->redirect_back( array( 'vincula_error' => 'unreadable' ) );
        }

        $header = fgetcsv( $handle, 0, ',', '"', '\\' );
        if ( ! is_array( $header ) ) {
            fclose( $handle );
            $this->redirect_back( array( 'vincula_error' => 'unreadable' ) );
        }

        // Strip a UTF-8 BOM from the first header cell.
        $header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $header[0] );

        $map = array();
        foreach ( $header as $index => $label ) {
            $label = strtolower( trim( (string) $label ) );
            foreach ( self::ALIASES as $field => $aliases ) {
                if ( ! isset( $map[ $field ] ) && in_array( $label, $aliases, true ) ) {
                    $map[ $field ] = $index;
                }
            }
        }

        if ( ! isset( $map['url'] ) ) {
            fclose( $handle );
            $this->redirect_back( array( 'vincula_error' => 'nourlcolumn' ) );
        }

        $update_existing = ! empty( $_POST['vincula_update_existing'] );
        $counts          = array(
            'imported' => 0,
            'updated'  => 0,
            'skipped'  => 0,
        );

        while ( false !== ( $row = fgetcsv( $handle, 0, ',', '"', '\\' ) ) ) {
            if ( ! is_array( $row ) || ( 1 === count( $row ) && '' === trim( (string) $row[0] ) ) ) {
                continue;
            }

            $get = function( $field ) use ( $row, $map ) {
                return isset( $map[ $field ], $row[ $map[ $field ] ] ) ? trim( (string) $row[ $map[ $field ] ] ) : '';
            };

            $url = esc_url_raw( $get( 'url' ) );
            if ( '' === $url ) {
                $counts['skipped']++;
                continue;
            }

            $name = sanitize_text_field( $get( 'name' ) );
            if ( '' === $name ) {
                $name = (string) parse_url( $url, PHP_URL_HOST );
            }

            $status = strtolower( $get( 'status' ) );
            if ( ! in_array( $status, self::ALLOWED_STATUSES, true ) ) {
                $status = 'publish';
            }

            $data = array(
                'post_title'   => $name,
                'post_content' => wp_kses_post( $get( 'description' ) ),
                'post_status'  => $status,
            );

            $existing_id = $this->find_post_by_url( $url );

            if ( $existing_id ) {
                if ( ! $update_existing ) {
                    $counts['skipped']++;
                    continue;
                }

                $data['ID'] = $existing_id;
                $result     = wp_update_post( $data, true );

                if ( is_wp_error( $result ) ) {
                    $counts['skipped']++;
                    continue;
                }

                $post_id = $existing_id;
                $counts['updated']++;
            } else {
                $data['post_type'] = VINCULA_POST_TYPE;
                $result            = wp_insert_post( $data, true );

                if ( is_wp_error( $result ) || ! $result ) {
                    $counts['skipped']++;
                    continue;
                }

                $post_id = (int) $result;
                $counts['imported']++;
            }

            update_post_meta( $post_id, '_vincula_url', $url );
            update_post_meta( $post_id, '_vincula_logo', esc_url_raw( $get( 'logo' ) ) );
            update_post_meta( $post_id, '_vincula_order', (int) $get( 'priority' ) );
        }

        fclose( $handle );

        $this->redirect_back(
            array(
                'vincula_imported' => $counts['imported'],
                'vincula_updated'  => $counts['updated'],
                'vincula_skipped'  => $counts['skipped'],
            )
        );
    }

    /**
     * Find an existing entry by its URL.
     *
     * @param string $url Sanitized URL.
     * @return int Post ID, or 0 when nothing matches.
     */
    private function find_post_by_url( $url ) {
        $ids = get_posts(
            array(
                'post_type'      => VINCULA_POST_TYPE,
                'post_status'    => self::ALLOWED_STATUSES,
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => array(
                    array(
                        'key'   => '_vincula_url',
                        'value' => $url,
                    ),
                ),
            )
        );

        return $ids ? (int) $ids[0] : 0;
    }
}
