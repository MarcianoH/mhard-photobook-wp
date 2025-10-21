<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CL_Shortcode {
    public static function init() {
        add_shortcode( 'configurator_form', [ __CLASS__, 'render' ] );
    }

    public static function render( $atts = [] ) {
        $token = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : ''; // phpcs:ignore
        $submitted = isset( $_GET['submitted'] ) ? (int) $_GET['submitted'] : 0; // phpcs:ignore
        ob_start();
        echo '<div class="cl-form">';
        if ( empty( $token ) ) {
            echo '<p>' . esc_html__( 'Ongeldige of ontbrekende token.', 'configurator-links' ) . '</p>';
            echo '</div>';
            return ob_get_clean();
        }

        $client = CL_Clients::get_by_token( $token );
        if ( ! $client || (int) $client->active !== 1 ) {
            echo '<p>' . esc_html__( 'Deze link is ongeldig of niet actief.', 'configurator-links' ) . '</p>';
            echo '</div>';
            return ob_get_clean();
        }

        // Mark last visited
        CL_Clients::touch( $client->id, [ 'last_visited' => CL_Helpers::now() ] );

        if ( $submitted ) {
            echo '<div class="notice notice-success" style="padding:12px;border-left:4px solid #46b450;background:#f3f8f3;margin-bottom:16px">' . esc_html__( 'Bedankt! Je inzending is ontvangen.', 'configurator-links' ) . '</div>';
        }

        $groups = CL_Groups::get_all( [ 'active' => 1 ] );

        // Get all rules for frontend
        $rules = CL_Rules::get_all();
        $rules_json = wp_json_encode( $rules );
        if ( empty( $groups ) ) {
            echo '<p>' . esc_html__( 'Er zijn momenteel geen opties beschikbaar.', 'configurator-links' ) . '</p>';
            echo '</div>';
            return ob_get_clean();
        }

        $action = esc_url( admin_url( 'admin-post.php' ) );
        echo '<form method="post" action="' . $action . '">';

        // Embed rules data for JavaScript
        echo '<script type="application/json" id="cl-rules-data">' . $rules_json . '</script>';
        wp_nonce_field( 'cl_submit_config', 'cl_nonce' );
        echo '<input type="hidden" name="action" value="cl_submit_config" />';
        echo '<input type="hidden" name="token" value="' . esc_attr( $token ) . '" />';

        foreach ( (array) $groups as $g ) {
            $gid = (int) $g->id;
            $options = CL_Options::get_all( [ 'group_id' => $gid, 'active' => 1 ] );
            if ( empty( $options ) ) { continue; }
            echo '<div class="cl-group" data-group-id="' . esc_attr( $gid ) . '">';
            echo '<div class="cl-group-title">' . esc_html( $g->name ) . '</div>';
            if ( ! empty( $g->description ) ) {
                echo '<div class="cl-group-desc">' . wp_kses_post( wpautop( $g->description ) ) . '</div>';
            }
            echo '<div class="cl-options">';
            foreach ( (array) $options as $o ) {
                echo '<label class="cl-option" data-option-id="' . esc_attr( (int) $o->id ) . '">';
                $name = 'single' === $g->type ? 'selections[' . $gid . ']' : 'selections[' . $gid . '][]';
                $type = 'single' === $g->type ? 'radio' : 'checkbox';
                echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '\" value="' . (int) $o->id . '" />';
                echo '<div class="cl-option-content">';
                // Support both image_url (direct URL) and image_id (attachment ID)
                $image_src = '';
                if ( ! empty( $o->image_url ) ) {
                    $image_src = $o->image_url;
                } elseif ( ! empty( $o->image_id ) ) {
                    $image_src = wp_get_attachment_image_url( (int) $o->image_id, 'medium' );
                }
                if ( $image_src ) {
                    echo '<img src="' . esc_url( $image_src ) . '" alt="' . esc_attr( $o->name ) . '" />';
                }
                echo '<div class="cl-option-info">';
                echo '<div class="cl-option-title">' . esc_html( $o->name ) . '</div>';
                if ( ! empty( $o->description ) ) {
                    echo '<div class="cl-option-desc">' . esc_html( $o->description ) . '</div>';
                }
                echo '</div>'; // .cl-option-info
                echo '</div>'; // .cl-option-content
                echo '</label>';
            }
            echo '</div>';
        }

        echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Versturen', 'configurator-links' ) . '</button></p>';
        echo '</form>';
        echo '</div>';
        return ob_get_clean();
    }
}
