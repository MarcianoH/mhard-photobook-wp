<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CL_Emails {
    public static function init() {}

    public static function send_html( $to, $subject, $body ) {
        $settings = CL_Helpers::get_settings();
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        if ( ! empty( $settings['sender_email'] ) ) {
            $from = sprintf( '%s <%s>', $settings['sender_name'], $settings['sender_email'] );
            $headers[] = 'From: ' . $from;
        }
        return wp_mail( $to, $subject, $body, $headers );
    }

    public static function replace_placeholders( $text, $vars ) {
        $map = [];
        foreach ( $vars as $k => $v ) {
            $map['{' . $k . '}'] = $v;
        }
        return strtr( $text, $map );
    }

    public static function render_template_html( $text, $vars ) {
        $replaced = self::replace_placeholders( $text, $vars );
        // Allow basic HTML; convert line breaks for plain text templates
        $replaced = wp_kses_post( $replaced );
        return wpautop( $replaced );
    }

    public static function send_invitation( $client ) {
        if ( empty( $client ) || empty( $client->email ) ) { return false; }
        $settings = CL_Helpers::get_settings();
        $vars = [
            'client_name' => $client->name ?? '',
            'unique_link' => CL_Helpers::get_public_link( $client->token ?? '' ),
            'site_name' => get_bloginfo( 'name' ),
        ];
        $subject = self::replace_placeholders( $settings['invite_subject'] ?? __( 'Jouw configuratielink', 'configurator-links' ), $vars );
        $body = self::render_template_html( $settings['invite_body'] ?? '', $vars );
        return self::send_html( $client->email, $subject, $body );
    }
}
