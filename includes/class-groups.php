<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CL_Groups {
    public static function init() {
        add_action( 'admin_post_cl_save_group', [ __CLASS__, 'handle_save_group' ] );
        add_action( 'admin_post_cl_delete_group', [ __CLASS__, 'handle_delete_group' ] );
    }

    public static function table() {
        global $wpdb; return $wpdb->prefix . 'configurator_groups';
    }

    public static function get_all( $args = [] ) {
        global $wpdb; $t = self::table();
        $where = 'WHERE 1=1';
        $order = 'ORDER BY sort_order ASC, id ASC';
        if ( isset( $args['active'] ) ) {
            $where .= ' AND active = ' . ( CL_Helpers::sanitize_bool( $args['active'] ) ? '1' : '0' );
        }
        $sql = "SELECT * FROM $t $where $order";
        return $wpdb->get_results( $sql );
    }

    public static function get( $id ) {
        global $wpdb; $t = self::table();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $id ) );
    }

    public static function create( $data ) {
        global $wpdb; $t = self::table();
        $now = CL_Helpers::now();
        $wpdb->insert( $t, [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'collection' => $data['collection'] ?? '',
            'type' => in_array( $data['type'], [ 'single', 'multi' ], true ) ? $data['type'] : 'single',
            'sort_order' => (int) ( $data['sort_order'] ?? 0 ),
            'active' => CL_Helpers::sanitize_bool( $data['active'] ?? 1 ),
            'created_at' => $now,
            'updated_at' => $now,
        ], [ '%s','%s','%s','%s','%d','%d','%s','%s' ] );
        return $wpdb->insert_id;
    }

    public static function update( $id, $data ) {
        global $wpdb; $t = self::table();
        $wpdb->update( $t, [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'collection' => $data['collection'] ?? '',
            'type' => in_array( $data['type'], [ 'single', 'multi' ], true ) ? $data['type'] : 'single',
            'sort_order' => (int) ( $data['sort_order'] ?? 0 ),
            'active' => CL_Helpers::sanitize_bool( $data['active'] ?? 1 ),
            'updated_at' => CL_Helpers::now(),
        ], [ 'id' => (int) $id ], [ '%s','%s','%s','%s','%d','%d','%s' ], [ '%d' ] );
    }

    public static function delete( $id ) {
        global $wpdb; $t = self::table();
        $wpdb->delete( $t, [ 'id' => (int) $id ], [ '%d' ] );
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Groepen', 'configurator-links' ) . '</h1>';

        if ( 'edit' === $action || 'new' === $action ) {
            $id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0; // phpcs:ignore
            $item = $id ? self::get( $id ) : null;
            ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'cl_save_group', 'cl_nonce' ); ?>
                <input type="hidden" name="action" value="cl_save_group" />
                <input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="name"><?php esc_html_e( 'Naam', 'configurator-links' ); ?></label></th>
                        <td><input name="name" id="name" type="text" class="regular-text" value="<?php echo esc_attr( $item->name ?? '' ); ?>" required /></td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php esc_html_e( 'Omschrijving', 'configurator-links' ); ?></label></th>
                        <td><textarea name="description" id="description" class="large-text" rows="3"><?php echo esc_textarea( $item->description ?? '' ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="collection"><?php esc_html_e( 'Collectie', 'configurator-links' ); ?></label></th>
                        <td><input name="collection" id="collection" type="text" class="regular-text" value="<?php echo esc_attr( $item->collection ?? '' ); ?>" placeholder="Bijv: DreambooksPRO, Bold Collection 150" /></td>
                    </tr>
                    <tr>
                        <th><label for="type"><?php esc_html_e( 'Type', 'configurator-links' ); ?></label></th>
                        <td>
                            <select name="type" id="type">
                                <option value="single" <?php selected( ( $item->type ?? 'single' ), 'single' ); ?>><?php esc_html_e( 'Radio (enkel)', 'configurator-links' ); ?></option>
                                <option value="multi" <?php selected( ( $item->type ?? '' ), 'multi' ); ?>><?php esc_html_e( 'Checkbox (meerdere)', 'configurator-links' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sort_order"><?php esc_html_e( 'Volgorde', 'configurator-links' ); ?></label></th>
                        <td><input name="sort_order" id="sort_order" type="number" value="<?php echo esc_attr( (int) ( $item->sort_order ?? 0 ) ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="active"><?php esc_html_e( 'Actief', 'configurator-links' ); ?></label></th>
                        <td><label><input type="checkbox" name="active" value="1" <?php checked( (int) ( $item->active ?? 1 ), 1 ); ?> /> <?php esc_html_e( 'Actief', 'configurator-links' ); ?></label></td>
                    </tr>
                </table>
                <?php submit_button( $id ? __( 'Opslaan', 'configurator-links' ) : __( 'Aanmaken', 'configurator-links' ) ); ?>
            </form>
            <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=configurator_links_groups' ) ); ?>">&larr; <?php esc_html_e( 'Terug naar lijst', 'configurator-links' ); ?></a></p>
            <?php
            echo '</div>';
            return;
        }

        echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=configurator_links_groups&action=new' ) ) . '">' . esc_html__( 'Nieuwe groep', 'configurator-links' ) . '</a></p>';
        $items = self::get_all();
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__( 'Naam', 'configurator-links' ) . '</th><th>' . esc_html__( 'Collectie', 'configurator-links' ) . '</th><th>' . esc_html__( 'Type', 'configurator-links' ) . '</th><th>' . esc_html__( 'Volgorde', 'configurator-links' ) . '</th><th>' . esc_html__( 'Actief', 'configurator-links' ) . '</th><th></th></tr></thead><tbody>';
        if ( $items ) {
            foreach ( $items as $it ) {
                $edit = admin_url( 'admin.php?page=configurator_links_groups&action=edit&id=' . (int) $it->id );
                $del  = wp_nonce_url( admin_url( 'admin-post.php?action=cl_delete_group&id=' . (int) $it->id ), 'cl_delete_group' );
                echo '<tr>';
                echo '<td>' . (int) $it->id . '</td>';
                echo '<td>' . esc_html( $it->name ) . '</td>';
                echo '<td>' . esc_html( $it->collection ?? '-' ) . '</td>';
                echo '<td>' . esc_html( $it->type ) . '</td>';
                echo '<td>' . (int) $it->sort_order . '</td>';
                echo '<td>' . ( (int) $it->active ? esc_html__( 'Ja', 'configurator-links' ) : esc_html__( 'Nee', 'configurator-links' ) ) . '</td>';
                echo '<td><a class="button" href="' . esc_url( $edit ) . '">' . esc_html__( 'Bewerken', 'configurator-links' ) . '</a> ';
                echo '<a class="button button-link-delete" href="' . esc_url( $del ) . '" onclick="return confirm(\'' . esc_js( __( 'Weet je zeker dat je deze groep wilt verwijderen?', 'configurator-links' ) ) . '\');">' . esc_html__( 'Verwijderen', 'configurator-links' ) . '</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">' . esc_html__( 'Geen groepen gevonden.', 'configurator-links' ) . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public static function handle_save_group() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Geen toegang', 'configurator-links' ) );
        check_admin_referer( 'cl_save_group', 'cl_nonce' );
        $id = (int) ( $_POST['id'] ?? 0 );
        $data = [
            'name' => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
            'description' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
            'collection' => sanitize_text_field( wp_unslash( $_POST['collection'] ?? '' ) ),
            'type' => sanitize_text_field( wp_unslash( $_POST['type'] ?? 'single' ) ),
            'sort_order' => (int) ( $_POST['sort_order'] ?? 0 ),
            'active' => CL_Helpers::sanitize_bool( $_POST['active'] ?? 0 ),
        ];
        if ( $id ) { self::update( $id, $data ); } else { $id = self::create( $data ); }
        wp_redirect( admin_url( 'admin.php?page=configurator_links_groups' ) );
        exit;
    }

    public static function handle_delete_group() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Geen toegang', 'configurator-links' ) );
        check_admin_referer( 'cl_delete_group' );
        $id = (int) ( $_GET['id'] ?? 0 ); // phpcs:ignore
        if ( $id ) self::delete( $id );
        wp_redirect( admin_url( 'admin.php?page=configurator_links_groups' ) );
        exit;
    }
}
