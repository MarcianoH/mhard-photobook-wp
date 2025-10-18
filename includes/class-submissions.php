<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CL_Submissions {
    public static function init() {
        // Public form submission handlers (works for logged-in and guests)
        add_action( 'admin_post_nopriv_cl_submit_config', [ __CLASS__, 'handle_submit' ] );
        add_action( 'admin_post_cl_submit_config', [ __CLASS__, 'handle_submit' ] );
        // Admin export
        add_action( 'admin_post_cl_export_submissions', [ __CLASS__, 'handle_export' ] );
    }

    public static function table() { global $wpdb; return $wpdb->prefix . 'configurator_submissions'; }

    public static function get_all( $limit = 200 ) {
        global $wpdb;
        $subs = self::table();
        $clients = CL_Clients::table();
        $limit = (int) $limit;
        if ( $limit <= 0 || $limit > 2000 ) { $limit = 200; }
        $sql = "SELECT s.*, c.name AS client_name, c.email AS client_email FROM $subs s LEFT JOIN $clients c ON c.id = s.client_id ORDER BY s.id DESC LIMIT $limit";
        return $wpdb->get_results( $sql );
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        echo '<div class="wrap"><h1>' . esc_html__( 'Inzendingen', 'configurator-links' ) . '</h1>';
        $export_url = wp_nonce_url( admin_url( 'admin-post.php?action=cl_export_submissions' ), 'cl_export_submissions' );
        echo '<p><a class="button" href="' . esc_url( $export_url ) . '">' . esc_html__( 'Exporteer CSV', 'configurator-links' ) . '</a></p>';
        $items = self::get_all();
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__( 'Datum', 'configurator-links' ) . '</th><th>' . esc_html__( 'Klant', 'configurator-links' ) . '</th><th>' . esc_html__( 'E-mail', 'configurator-links' ) . '</th><th>' . esc_html__( 'Token', 'configurator-links' ) . '</th></tr></thead><tbody>';
        if ( $items ) {
            foreach ( $items as $it ) {
                echo '<tr>';
                echo '<td>' . (int) $it->id . '</td>';
                echo '<td>' . esc_html( $it->submitted_at ) . '</td>';
                echo '<td>' . esc_html( $it->client_name ?? '' ) . '</td>';
                echo '<td><a href="mailto:' . esc_attr( $it->client_email ?? '' ) . '">' . esc_html( $it->client_email ?? '' ) . '</a></td>';
                echo '<td><code>' . esc_html( $it->token ) . '</code></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5">' . esc_html__( 'Geen inzendingen gevonden.', 'configurator-links' ) . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public static function handle_submit() {
        // No capability check (public)
        check_admin_referer( 'cl_submit_config', 'cl_nonce' );

        $token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
        if ( empty( $token ) ) { wp_die( esc_html__( 'Ongeldige token.', 'configurator-links' ) ); }
        $client = CL_Clients::get_by_token( $token );
        if ( ! $client || (int) $client->active !== 1 ) { wp_die( esc_html__( 'Token ongeldig of inactief.', 'configurator-links' ) ); }

        $raw_selections = isset( $_POST['selections'] ) && is_array( $_POST['selections'] ) ? $_POST['selections'] : [];
        $groups = CL_Groups::get_all( [ 'active' => 1 ] );
        $group_map = [];
        foreach ( (array) $groups as $g ) { $group_map[ (int) $g->id ] = $g; }

        // Normalize and validate selections
        $normalized = [];
        foreach ( (array) $raw_selections as $gid_str => $vals ) {
            $gid = (int) $gid_str;
            if ( ! isset( $group_map[ $gid ] ) ) { continue; }
            $type = $group_map[ $gid ]->type;
            if ( 'multi' === $type ) {
                $opt_ids = array_map( 'intval', (array) $vals );
            } else {
                $opt_ids = [];
                if ( is_array( $vals ) ) {
                    // In case someone posts array for single, take first
                    $first = array_shift( $vals );
                    $opt_ids[] = (int) $first;
                } else {
                    $opt_ids[] = (int) $vals;
                }
            }
            // Filter option ids to those belonging to this group
            $valid_opts = [];
            $options = CL_Options::get_all( [ 'group_id' => $gid, 'active' => 1 ] );
            $opt_map = [];
            foreach ( (array) $options as $o ) { $opt_map[ (int) $o->id ] = $o; }
            foreach ( $opt_ids as $oid ) {
                if ( isset( $opt_map[ (int) $oid ] ) ) { $valid_opts[] = (int) $oid; }
            }
            if ( ! empty( $valid_opts ) ) { $normalized[ $gid ] = array_values( array_unique( $valid_opts ) ); }
        }

        // Store submission
        self::create( $client, $token, $normalized );
        // Update last submitted
        CL_Clients::touch( $client->id, [ 'last_submitted' => CL_Helpers::now() ] );

        // Prepare and send emails
        $option_map = [];
        foreach ( array_keys( $normalized ) as $gid ) {
            $opts = CL_Options::get_all( [ 'group_id' => $gid, 'active' => 1 ] );
            foreach ( (array) $opts as $o ) { $option_map[ (int) $o->id ] = $o; }
        }
        $selections_table = CL_Helpers::build_selections_table_html( $group_map, $option_map, $normalized );
        $settings = CL_Helpers::get_settings();
        $vars = [
            'client_name' => $client->name ?? '',
            'unique_link' => CL_Helpers::get_public_link( $client->token ?? '' ),
            'selections_table' => $selections_table,
            'site_name' => get_bloginfo( 'name' ),
        ];
        // Client confirmation
        $subject_client = CL_Emails::replace_placeholders( $settings['submission_subject'] ?? __( 'Je inzending is ontvangen', 'configurator-links' ), $vars );
        $body_client = CL_Emails::render_template_html( $settings['submission_body'] ?? '', $vars );
        if ( ! empty( $client->email ) ) {
            CL_Emails::send_html( $client->email, $subject_client, $body_client );
        }
        // Admin notification
        $admins = CL_Helpers::sanitize_email_list( $settings['admin_recipients'] ?? '' );
        if ( ! empty( $admins ) ) {
            $subject_admin = $subject_client;
            $body_admin = $body_client;
            CL_Emails::send_html( $admins, $subject_admin, $body_admin );
        }

        // Redirect back to public page with success flag
        $redirect = add_query_arg( [ 't' => rawurlencode( $token ), 'submitted' => '1' ], CL_Helpers::get_public_link( $token ) );
        wp_safe_redirect( $redirect );
        exit;
    }

    public static function create( $client, $token, $selections ) {
        global $wpdb;
        $table = self::table();
        $now = CL_Helpers::now();
        $ua = substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), 0, 250 );
        $ip = substr( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ), 0, 64 );
        $wpdb->insert( $table, [
            'client_id' => (int) $client->id,
            'token' => $token,
            'selections' => wp_json_encode( $selections ),
            'submitted_at' => $now,
            'user_agent' => $ua,
            'ip' => $ip,
        ], [ '%d','%s','%s','%s','%s','%s' ] );
        return $wpdb->insert_id;
    }

    public static function handle_export() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Geen toegang', 'configurator-links' ) );
        }
        check_admin_referer( 'cl_export_submissions' );
        $rows = self::get_all( 2000 );
        $header = [ 'id', 'submitted_at', 'client_name', 'client_email', 'token', 'selections' ];
        $csv_rows = [];
        foreach ( $rows as $r ) {
            $csv_rows[] = [
                (int) $r->id,
                $r->submitted_at,
                $r->client_name,
                $r->client_email,
                $r->token,
                $r->selections,
            ];
        }
        CL_Helpers::csv_download( 'submissions-' . date( 'Ymd-His' ) . '.csv', $header, $csv_rows );
    }
}
