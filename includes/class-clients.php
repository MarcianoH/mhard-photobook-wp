<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CL_Clients {
    public static function init() {
        add_action( 'admin_post_cl_save_client', [ __CLASS__, 'handle_save_client' ] );
        add_action( 'admin_post_cl_delete_client', [ __CLASS__, 'handle_delete_client' ] );
        add_action( 'admin_post_cl_invite_client', [ __CLASS__, 'handle_invite_client' ] );
    }

    public static function table() { global $wpdb; return $wpdb->prefix . 'configurator_clients'; }

    public static function get_all( $args = [] ) {
        global $wpdb; $t = self::table();
        $where = 'WHERE 1=1';
        if ( isset( $args['active'] ) ) {
            $where .= ' AND active = ' . ( CL_Helpers::sanitize_bool( $args['active'] ) ? '1' : '0' );
        }
        $sql = "SELECT * FROM $t $where ORDER BY id DESC";
        return $wpdb->get_results( $sql );
    }

    public static function get( $id ) { global $wpdb; $t = self::table(); return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id=%d", (int) $id ) ); }
    public static function get_by_token( $token ) { global $wpdb; $t = self::table(); return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE token=%s", $token ) ); }

    public static function ensure_token( $maybe = '' ) {
        $token = $maybe ?: CL_Helpers::generate_token();
        // Uniqueness loop (rare)
        for ( $i = 0; $i < 3; $i++ ) {
            if ( ! self::get_by_token( $token ) ) { return $token; }
            $token = CL_Helpers::generate_token();
        }
        return $token;
    }

    public static function create( $data ) {
        global $wpdb; $t = self::table();
        $now = CL_Helpers::now();
        $token = self::ensure_token( $data['token'] ?? '' );
        $wpdb->insert( $t, [
            'name' => $data['name'],
            'email' => $data['email'],
            'token' => $token,
            'active' => CL_Helpers::sanitize_bool( $data['active'] ?? 1 ),
            'created_at' => $now,
            'updated_at' => $now,
        ], [ '%s','%s','%s','%d','%s','%s' ] );
        return $wpdb->insert_id;
    }

    public static function update( $id, $data ) {
        global $wpdb; $t = self::table();
        $fields = [
            'name' => $data['name'],
            'email' => $data['email'],
            'active' => CL_Helpers::sanitize_bool( $data['active'] ?? 1 ),
            'updated_at' => CL_Helpers::now(),
        ];
        if ( ! empty( $data['token'] ) ) {
            $fields['token'] = self::ensure_token( $data['token'] );
        }
        // Build formats to match fields count
        $formats = [ '%s','%s','%d','%s' ]; // name, email, active, updated_at
        if ( isset( $fields['token'] ) ) { $formats[] = '%s'; }
        $wpdb->update( $t, $fields, [ 'id' => (int) $id ], $formats, [ '%d' ] );
    }

    public static function touch( $id, $fields ) {
        global $wpdb; $t = self::table();
        $fields['updated_at'] = CL_Helpers::now();
        $wpdb->update( $t, $fields, [ 'id' => (int) $id ] );
    }

    public static function delete( $id ) { global $wpdb; $t = self::table(); $wpdb->delete( $t, [ 'id' => (int) $id ], [ '%d' ] ); }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Klanten', 'configurator-links' ) . '</h1>';

        if ( 'edit' === $action || 'new' === $action ) {
            $id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0; // phpcs:ignore
            $item = $id ? self::get( $id ) : null;
            $token = $item->token ?? CL_Helpers::generate_token();
            $public_link = CL_Helpers::get_public_link( $token );
            ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'cl_save_client', 'cl_nonce' ); ?>
                <input type="hidden" name="action" value="cl_save_client" />
                <input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="name"><?php esc_html_e( 'Naam', 'configurator-links' ); ?></label></th>
                        <td><input name="name" id="name" type="text" class="regular-text" value="<?php echo esc_attr( $item->name ?? '' ); ?>" required /></td>
                    </tr>
                    <tr>
                        <th><label for="email"><?php esc_html_e( 'E-mail', 'configurator-links' ); ?></label></th>
                        <td><input name="email" id="email" type="email" class="regular-text" value="<?php echo esc_attr( $item->email ?? '' ); ?>" required /></td>
                    </tr>
                    <tr>
                        <th><label for="token"><?php esc_html_e( 'Token', 'configurator-links' ); ?></label></th>
                        <td><input name="token" id="token" type="text" class="regular-text" value="<?php echo esc_attr( $token ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Unieke link', 'configurator-links' ); ?></th>
                        <td><code><?php echo esc_html( $public_link ); ?></code></td>
                    </tr>
                    <tr>
                        <th><label for="active"><?php esc_html_e( 'Actief', 'configurator-links' ); ?></label></th>
                        <td><label><input type="checkbox" name="active" value="1" <?php checked( (int) ( $item->active ?? 1 ), 1 ); ?> /> <?php esc_html_e( 'Actief', 'configurator-links' ); ?></label></td>
                    </tr>
                </table>
                <?php submit_button( $id ? __( 'Opslaan', 'configurator-links' ) : __( 'Aanmaken', 'configurator-links' ) ); ?>
            </form>
            <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=configurator_links_clients' ) ); ?>">&larr; <?php esc_html_e( 'Terug naar lijst', 'configurator-links' ); ?></a></p>
            <?php
            echo '</div>';
            return;
        }

        echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=configurator_links_clients&action=new' ) ) . '">' . esc_html__( 'Nieuwe klant', 'configurator-links' ) . '</a></p>';

        $items = self::get_all();
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__( 'Naam', 'configurator-links' ) . '</th><th>' . esc_html__( 'E-mail', 'configurator-links' ) . '</th><th>' . esc_html__( 'Link', 'configurator-links' ) . '</th><th>' . esc_html__( 'Laatst uitgenodigd', 'configurator-links' ) . '</th><th>' . esc_html__( 'Laatste inzending', 'configurator-links' ) . '</th><th>' . esc_html__( 'Actief', 'configurator-links' ) . '</th><th></th></tr></thead><tbody>';
        if ( $items ) {
            foreach ( $items as $it ) {
                $public_link = CL_Helpers::get_public_link( $it->token );
                $edit = admin_url( 'admin.php?page=configurator_links_clients&action=edit&id=' . (int) $it->id );
                $del  = wp_nonce_url( admin_url( 'admin-post.php?action=cl_delete_client&id=' . (int) $it->id ), 'cl_delete_client' );
                $invite = wp_nonce_url( admin_url( 'admin-post.php?action=cl_invite_client&id=' . (int) $it->id ), 'cl_invite_client' );
                echo '<tr>';
                echo '<td>' . (int) $it->id . '</td>';
                echo '<td>' . esc_html( $it->name ) . '</td>';
                echo '<td><a href="mailto:' . esc_attr( $it->email ) . '">' . esc_html( $it->email ) . '</a></td>';
                echo '<td><a href="' . esc_url( $public_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open link', 'configurator-links' ) . '</a></td>';
                echo '<td>' . ( $it->invited_at ? esc_html( $it->invited_at ) : '-' ) . '</td>';
                echo '<td>' . ( $it->last_submitted ? esc_html( $it->last_submitted ) : '-' ) . '</td>';
                echo '<td>' . ( (int) $it->active ? esc_html__( 'Ja', 'configurator-links' ) : esc_html__( 'Nee', 'configurator-links' ) ) . '</td>';
                echo '<td><a class="button" href="' . esc_url( $edit ) . '">' . esc_html__( 'Bewerken', 'configurator-links' ) . '</a> ';
                echo '<a class="button" href="' . esc_url( $invite ) . '" onclick="return confirm(\'' . esc_js( __( 'Uitnodiging naar klant verzenden?', 'configurator-links' ) ) . '\');">' . esc_html__( 'Nodig uit', 'configurator-links' ) . '</a> ';
                echo '<a class="button button-link-delete" href="' . esc_url( $del ) . '" onclick="return confirm(\'' . esc_js( __( 'Weet je zeker dat je deze klant wilt verwijderen?', 'configurator-links' ) ) . '\');">' . esc_html__( 'Verwijderen', 'configurator-links' ) . '</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8">' . esc_html__( 'Geen klanten gevonden.', 'configurator-links' ) . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public static function handle_save_client() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Geen toegang', 'configurator-links' ) );
        check_admin_referer( 'cl_save_client', 'cl_nonce' );
        $id = (int) ( $_POST['id'] ?? 0 );
        $data = [
            'name' => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
            'email' => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
            'token' => sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) ),
            'active' => CL_Helpers::sanitize_bool( $_POST['active'] ?? 0 ),
        ];
        if ( $id ) { self::update( $id, $data ); } else { $id = self::create( $data ); }
        wp_redirect( admin_url( 'admin.php?page=configurator_links_clients' ) );
        exit;
    }

    public static function handle_delete_client() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Geen toegang', 'configurator-links' ) );
        check_admin_referer( 'cl_delete_client' );
        $id = (int) ( $_GET['id'] ?? 0 ); // phpcs:ignore
        if ( $id ) self::delete( $id );
        wp_redirect( admin_url( 'admin.php?page=configurator_links_clients' ) );
        exit;
    }

    public static function handle_invite_client() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Geen toegang', 'configurator-links' ) );
        check_admin_referer( 'cl_invite_client' );
        $id = (int) ( $_GET['id'] ?? 0 ); // phpcs:ignore
        $client = $id ? self::get( $id ) : null;
        if ( $client && (int) $client->active === 1 ) {
            CL_Emails::send_invitation( $client );
            self::touch( $client->id, [ 'invited_at' => CL_Helpers::now() ] );
        }
        wp_redirect( admin_url( 'admin.php?page=configurator_links_clients' ) );
        exit;
    }
}
