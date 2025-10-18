<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CL_Helpers {
    public static function now() {
        return current_time( 'mysql' );
    }

    public static function boolval( $val ) {
        if ( is_bool( $val ) ) return $val;
        $true_vals = [ '1', 1, 'true', 'on', 'yes' ];
        return in_array( $val, $true_vals, true );
    }

    public static function sanitize_bool( $val ) {
        return self::boolval( $val ) ? 1 : 0;
    }

    public static function get_settings() {
        $defaults = [
            'sender_name' => get_bloginfo( 'name' ),
            'sender_email' => get_bloginfo( 'admin_email' ),
            'admin_recipients' => get_bloginfo( 'admin_email' ),
            'public_page_id' => 0,
            'invite_subject' => __( 'Jouw configuratielink', 'configurator-links' ),
            'invite_body' => __( 'Hallo {client_name},\n\nJe kunt jouw opties kiezen via: {unique_link}', 'configurator-links' ),
            'submission_subject' => __( 'Nieuwe configuratie ontvangen', 'configurator-links' ),
            'submission_body' => __( 'Beste {client_name},\n\nWe hebben je keuzes ontvangen.\n\n{selections_table}', 'configurator-links' ),
            'remove_data_on_uninstall' => 0,
        ];
        $opts = get_option( 'cl_settings', [] );
        return wp_parse_args( $opts, $defaults );
    }

    public static function update_settings( $new ) {
        $settings = self::get_settings();
        $settings = array_merge( $settings, $new );
        update_option( 'cl_settings', $settings );
        return $settings;
    }

    public static function get_public_link( $token ) {
        $settings = self::get_settings();
        $url = home_url( '/' );
        if ( ! empty( $settings['public_page_id'] ) ) {
            $url = get_permalink( (int) $settings['public_page_id'] );
        }
        return add_query_arg( 't', rawurlencode( $token ), $url );
    }

    public static function generate_token() {
        try {
            return bin2hex( random_bytes( 16 ) );
        } catch ( Exception $e ) {
            return wp_generate_password( 32, false, false );
        }
    }

    public static function esc_html_array( $arr ) {
        return array_map( 'esc_html', (array) $arr );
    }

    // CSV helpers
    public static function csv_read_uploaded_file( $file ) {
        if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return [ 'header' => [], 'rows' => [] ];
        }
        $fh = fopen( $file['tmp_name'], 'rb' );
        if ( ! $fh ) { return [ 'header' => [], 'rows' => [] ]; }
        $header = [];
        $rows = [];
        $i = 0;
        while ( ( $cols = fgetcsv( $fh ) ) !== false ) {
            if ( 0 === $i ) {
                $header = array_map( 'trim', $cols );
            } else {
                $rows[] = $cols;
            }
            $i++;
        }
        fclose( $fh );
        return [ 'header' => $header, 'rows' => $rows ];
    }

    public static function csv_download( $filename, $header, $rows ) {
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $filename ) );
        $out = fopen( 'php://output', 'w' );
        if ( ! empty( $header ) ) { fputcsv( $out, $header ); }
        foreach ( $rows as $r ) { fputcsv( $out, $r ); }
        fclose( $out );
        exit;
    }

    public static function sanitize_email_list( $str ) {
        $parts = array_filter( array_map( 'trim', explode( ',', (string) $str ) ) );
        $emails = [];
        foreach ( $parts as $p ) {
            $e = sanitize_email( $p );
            if ( is_email( $e ) ) { $emails[] = $e; }
        }
        return $emails;
    }

    public static function build_selections_table_html( $group_map, $option_map, $selections ) {
        // $group_map: id=>object(name)
        // $option_map: id=>object(name,image_url)
        // $selections: group_id => [option_id,...]
        ob_start();
        echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">';
        echo '<thead><tr><th style="text-align:left">' . esc_html__( 'Groep', 'configurator-links' ) . '</th><th style="text-align:left">' . esc_html__( 'Opties', 'configurator-links' ) . '</th></tr></thead><tbody>';
        foreach ( (array) $selections as $gid => $opt_ids ) {
            $gname = isset( $group_map[ $gid ] ) ? $group_map[ $gid ]->name : ('#' . (int) $gid);
            $opts_html = [];
            foreach ( (array) $opt_ids as $oid ) {
                if ( isset( $option_map[ $oid ] ) ) {
                    $o = $option_map[ $oid ];
                    $img = ! empty( $o->image_url ) ? '<br><img src="' . esc_url( $o->image_url ) . '" alt="" style="max-width:120px;height:auto;border:1px solid #e2e8f0;border-radius:4px" />' : '';
                    $opts_html[] = esc_html( $o->name ) . $img;
                }
            }
            echo '<tr><td>' . esc_html( $gname ) . '</td><td>' . wp_kses_post( implode( '<br>', $opts_html ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</tbody></table>';
        return ob_get_clean();
    }
}
