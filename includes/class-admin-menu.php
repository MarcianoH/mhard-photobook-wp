<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CL_Admin_Menu {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_post_cl_save_settings', [ __CLASS__, 'handle_save_settings' ] );
    }

    public static function register_menu() {
        add_menu_page(
            __( 'MHard Photobook', 'configurator-links' ),
            __( 'MHard Photobook', 'configurator-links' ),
            'manage_options',
            'configurator_links',
            [ __CLASS__, 'render_dashboard' ],
            'dashicons-admin-generic',
            26
        );

        add_submenu_page( 'configurator_links', __( 'Dashboard', 'configurator-links' ), __( 'Dashboard', 'configurator-links' ), 'manage_options', 'configurator_links', [ __CLASS__, 'render_dashboard' ] );
        add_submenu_page( 'configurator_links', __( 'Groepen', 'configurator-links' ), __( 'Groepen', 'configurator-links' ), 'manage_options', 'configurator_links_groups', [ 'CL_Groups', 'render_admin_page' ] );
        add_submenu_page( 'configurator_links', __( 'Opties', 'configurator-links' ), __( 'Opties', 'configurator-links' ), 'manage_options', 'configurator_links_options', [ 'CL_Options', 'render_admin_page' ] );
        add_submenu_page( 'configurator_links', __( 'Klanten', 'configurator-links' ), __( 'Klanten', 'configurator-links' ), 'manage_options', 'configurator_links_clients', [ 'CL_Clients', 'render_admin_page' ] );
        add_submenu_page( 'configurator_links', __( 'Inzendingen', 'configurator-links' ), __( 'Inzendingen', 'configurator-links' ), 'manage_options', 'configurator_links_submissions', [ 'CL_Submissions', 'render_admin_page' ] );
        add_submenu_page( 'configurator_links', __( 'Instellingen', 'configurator-links' ), __( 'Instellingen', 'configurator-links' ), 'manage_options', 'configurator_links_settings', [ __CLASS__, 'render_settings' ] );
        add_submenu_page( 'configurator_links', __( 'Import/Export', 'configurator-links' ), __( 'Import/Export', 'configurator-links' ), 'manage_options', 'configurator_links_import', [ 'CL_Importer', 'render_admin_page' ] );
    }

    public static function render_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        echo '<div class="wrap"><h1>' . esc_html__( 'MHard Photobook', 'configurator-links' ) . '</h1>';
        echo '<p>' . esc_html__( 'Beheer groepen, opties, klanten, inzendingen en instellingen.', 'configurator-links' ) . '</p>';
        echo '</div>';
    }

    public static function render_settings() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $settings = CL_Helpers::get_settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Instellingen', 'configurator-links' ); ?></h1>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'cl_save_settings', 'cl_nonce' ); ?>
                <input type="hidden" name="action" value="cl_save_settings" />

                <h2 class="title"><?php esc_html_e( 'E-mail', 'configurator-links' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="sender_name"><?php esc_html_e( 'Afzender naam', 'configurator-links' ); ?></label></th>
                        <td><input name="sender_name" id="sender_name" type="text" class="regular-text" value="<?php echo esc_attr( $settings['sender_name'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="sender_email"><?php esc_html_e( 'Afzender e-mail', 'configurator-links' ); ?></label></th>
                        <td><input name="sender_email" id="sender_email" type="email" class="regular-text" value="<?php echo esc_attr( $settings['sender_email'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="admin_recipients"><?php esc_html_e( 'Admin ontvangers (comma separated)', 'configurator-links' ); ?></label></th>
                        <td><input name="admin_recipients" id="admin_recipients" type="text" class="regular-text" value="<?php echo esc_attr( $settings['admin_recipients'] ); ?>" /></td>
                    </tr>
                </table>

                <h2 class="title"><?php esc_html_e( 'Publieke pagina', 'configurator-links' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="public_page_id"><?php esc_html_e( 'Pagina met shortcode [configurator_form]', 'configurator-links' ); ?></label></th>
                        <td>
                            <?php
                            wp_dropdown_pages( [
                                'name' => 'public_page_id',
                                'echo' => 1,
                                'show_option_none' => __( '-- Kies pagina --', 'configurator-links' ),
                                'option_none_value' => '0',
                                'selected' => (int) $settings['public_page_id'],
                            ] );
                            ?>
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php esc_html_e( 'Templates', 'configurator-links' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="invite_subject"><?php esc_html_e( 'Uitnodiging onderwerp', 'configurator-links' ); ?></label></th>
                        <td><input name="invite_subject" id="invite_subject" type="text" class="regular-text" value="<?php echo esc_attr( $settings['invite_subject'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="invite_body"><?php esc_html_e( 'Uitnodiging body', 'configurator-links' ); ?></label></th>
                        <td><textarea name="invite_body" id="invite_body" class="large-text" rows="5"><?php echo esc_textarea( $settings['invite_body'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="submission_subject"><?php esc_html_e( 'Inzending onderwerp', 'configurator-links' ); ?></label></th>
                        <td><input name="submission_subject" id="submission_subject" type="text" class="regular-text" value="<?php echo esc_attr( $settings['submission_subject'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="submission_body"><?php esc_html_e( 'Inzending body (HTML toegestaan)', 'configurator-links' ); ?></label></th>
                        <td><textarea name="submission_body" id="submission_body" class="large-text" rows="8"><?php echo esc_textarea( $settings['submission_body'] ); ?></textarea></td>
                    </tr>
                </table>

                <h2 class="title"><?php esc_html_e( 'Overige', 'configurator-links' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="remove_data_on_uninstall"><?php esc_html_e( 'Verwijder data bij uninstall', 'configurator-links' ); ?></label></th>
                        <td><label><input type="checkbox" name="remove_data_on_uninstall" value="1" <?php checked( ! empty( $settings['remove_data_on_uninstall'] ) ); ?> /> <?php esc_html_e( 'Ja, verwijder alle tabellen en opties', 'configurator-links' ); ?></label></td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function handle_save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Geen toegang', 'configurator-links' ) );
        }
        check_admin_referer( 'cl_save_settings', 'cl_nonce' );

        $new = [
            'sender_name' => sanitize_text_field( wp_unslash( $_POST['sender_name'] ?? '' ) ),
            'sender_email' => sanitize_email( wp_unslash( $_POST['sender_email'] ?? '' ) ),
            'admin_recipients' => sanitize_text_field( wp_unslash( $_POST['admin_recipients'] ?? '' ) ),
            'public_page_id' => (int) ( $_POST['public_page_id'] ?? 0 ),
            'invite_subject' => sanitize_text_field( wp_unslash( $_POST['invite_subject'] ?? '' ) ),
            'invite_body' => wp_kses_post( wp_unslash( $_POST['invite_body'] ?? '' ) ),
            'submission_subject' => sanitize_text_field( wp_unslash( $_POST['submission_subject'] ?? '' ) ),
            'submission_body' => wp_kses_post( wp_unslash( $_POST['submission_body'] ?? '' ) ),
            'remove_data_on_uninstall' => CL_Helpers::sanitize_bool( $_POST['remove_data_on_uninstall'] ?? 0 ),
        ];

        CL_Helpers::update_settings( $new );
        wp_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=configurator_links_settings' ) ) );
        exit;
    }
}
