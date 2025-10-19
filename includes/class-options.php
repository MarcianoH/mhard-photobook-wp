<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CL_Options {
    public static function init() {
        add_action( 'admin_post_cl_save_option', [ __CLASS__, 'handle_save_option' ] );
        add_action( 'admin_post_cl_delete_option', [ __CLASS__, 'handle_delete_option' ] );
    }

    public static function table() { global $wpdb; return $wpdb->prefix . 'configurator_options'; }

    public static function get_all( $args = [] ) {
        global $wpdb; $t = self::table();
        $where = 'WHERE 1=1';
        if ( ! empty( $args['group_id'] ) ) {
            $where .= $wpdb->prepare( ' AND group_id = %d', (int) $args['group_id'] );
        }
        if ( isset( $args['active'] ) ) {
            $where .= ' AND active = ' . ( CL_Helpers::sanitize_bool( $args['active'] ) ? '1' : '0' );
        }
        $sql = "SELECT * FROM $t $where ORDER BY sort_order ASC, id ASC";
        return $wpdb->get_results( $sql );
    }

    public static function get( $id ) {
        global $wpdb; $t = self::table();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", (int) $id ) );
    }

    public static function get_by_name( $name, $group_id ) {
        global $wpdb; $t = self::table();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE name = %s AND group_id = %d", $name, (int) $group_id ) );
    }

    public static function create( $data ) {
        global $wpdb; $t = self::table();
        $now = CL_Helpers::now();
        $wpdb->insert( $t, [
            'group_id' => (int) $data['group_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'image_id' => (int) ( $data['image_id'] ?? 0 ),
            'image_url' => $data['image_url'] ?? '',
            'sort_order' => (int) ( $data['sort_order'] ?? 0 ),
            'active' => CL_Helpers::sanitize_bool( $data['active'] ?? 1 ),
            'created_at' => $now,
            'updated_at' => $now,
        ], [ '%d','%s','%s','%d','%s','%d','%d','%s','%s' ] );
        return $wpdb->insert_id;
    }

    public static function update( $id, $data ) {
        global $wpdb; $t = self::table();
        $wpdb->update( $t, [
            'group_id' => (int) $data['group_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'image_id' => (int) ( $data['image_id'] ?? 0 ),
            'image_url' => $data['image_url'] ?? '',
            'sort_order' => (int) ( $data['sort_order'] ?? 0 ),
            'active' => CL_Helpers::sanitize_bool( $data['active'] ?? 1 ),
            'updated_at' => CL_Helpers::now(),
        ], [ 'id' => (int) $id ], [ '%d','%s','%s','%d','%s','%d','%d','%s' ], [ '%d' ] );
    }

    public static function delete( $id ) { global $wpdb; $t = self::table(); $wpdb->delete( $t, [ 'id' => (int) $id ], [ '%d' ] ); }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) );
        $filter_group = isset( $_GET['group'] ) ? (int) $_GET['group'] : 0; // phpcs:ignore
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Opties', 'configurator-links' ) . '</h1>';

        if ( 'edit' === $action || 'new' === $action ) {
            $id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0; // phpcs:ignore
            $item = $id ? self::get( $id ) : null;
            $groups = CL_Groups::get_all();
            $current_group_id = $item ? (int) $item->group_id : (int) $filter_group;
            $name_val = $item ? $item->name : '';
            $desc_val = $item ? $item->description : '';
            $img_id = $item ? (int) $item->image_id : 0;
            $img_url = $item ? (string) $item->image_url : '';
            $sort_val = $item ? (int) $item->sort_order : 0;
            $active_val = $item ? (int) $item->active : 1;
            ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'cl_save_option', 'cl_nonce' ); ?>
                <input type="hidden" name="action" value="cl_save_option" />
                <input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>" />

                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="group_id"><?php esc_html_e( 'Groep', 'configurator-links' ); ?></label></th>
                        <td>
                            <select name="group_id" id="group_id" required>
                                <option value="">--</option>
                                <?php foreach ( (array) $groups as $g ) : ?>
                                    <option value="<?php echo (int) $g->id; ?>" <?php selected( $current_group_id, (int) $g->id ); ?>><?php echo esc_html( $g->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="name"><?php esc_html_e( 'Naam', 'configurator-links' ); ?></label></th>
                        <td><input name="name" id="name" type="text" class="regular-text" value="<?php echo esc_attr( $name_val ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php esc_html_e( 'Omschrijving', 'configurator-links' ); ?></label></th>
                        <td><textarea name="description" id="description" class="large-text" rows="3"><?php echo esc_textarea( $desc_val ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Afbeelding', 'configurator-links' ); ?></th>
                        <td>
                            <div data-media-wrap>
                                <img class="cl-img-thumb" src="<?php echo esc_url( $img_url ); ?>" style="<?php echo empty( $img_url ) ? 'display:none' : ''; ?>" alt="" />
                                <input type="hidden" name="image_id" value="<?php echo esc_attr( $img_id ); ?>" data-media-id>
                                <input type="text" name="image_url" value="<?php echo esc_attr( $img_url ); ?>" class="regular-text" data-media-url>
                                <button class="button cl-media-select"><?php esc_html_e( 'Selecteer', 'configurator-links' ); ?></button>
                                <button class="button cl-media-clear"><?php esc_html_e( 'Leegmaken', 'configurator-links' ); ?></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sort_order"><?php esc_html_e( 'Volgorde', 'configurator-links' ); ?></label></th>
                        <td><input name="sort_order" id="sort_order" type="number" value="<?php echo esc_attr( $sort_val ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="active"><?php esc_html_e( 'Actief', 'configurator-links' ); ?></label></th>
                        <td><label><input type="checkbox" name="active" value="1" <?php checked( $active_val, 1 ); ?> /> <?php esc_html_e( 'Actief', 'configurator-links' ); ?></label></td>
                    </tr>
                </table>
                <?php submit_button( $id ? __( 'Opslaan', 'configurator-links' ) : __( 'Aanmaken', 'configurator-links' ) ); ?>
            </form>
            <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=configurator_links_options' . ( $filter_group ? '&group=' . (int) $filter_group : '' ) ) ); ?>">&larr; <?php esc_html_e( 'Terug naar lijst', 'configurator-links' ); ?></a></p>
            <?php
            echo '</div>';
            return;
        }

        echo '<p>';
        echo '<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=configurator_links_options&action=new' . ( $filter_group ? '&group=' . (int) $filter_group : '' ) ) ) . '">' . esc_html__( 'Nieuwe optie', 'configurator-links' ) . '</a> ';
        echo '</p>';

        // Filter by group
        $groups = CL_Groups::get_all();
        echo '<form method="get" style="margin:12px 0">';
        echo '<input type="hidden" name="page" value="configurator_links_options" />';
        echo '<label for="group_filter">' . esc_html__( 'Filter groep:', 'configurator-links' ) . ' </label>';
        echo '<select name="group" id="group_filter" onchange="this.form.submit()">';
        echo '<option value="0">' . esc_html__( 'Alle', 'configurator-links' ) . '</option>';
        foreach ( (array) $groups as $g ) {
            echo '<option value="' . (int) $g->id . '"' . selected( $filter_group, (int) $g->id, false ) . '>' . esc_html( $g->name ) . '</option>';
        }
        echo '</select>';
        echo '</form>';

        $items = self::get_all( [ 'group_id' => $filter_group ] );
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__( 'Groep', 'configurator-links' ) . '</th><th>' . esc_html__( 'Naam', 'configurator-links' ) . '</th><th>' . esc_html__( 'Afbeelding', 'configurator-links' ) . '</th><th>' . esc_html__( 'Volgorde', 'configurator-links' ) . '</th><th>' . esc_html__( 'Actief', 'configurator-links' ) . '</th><th></th></tr></thead><tbody>';
        if ( $items ) {
            $group_map = [];
            foreach ( (array) $groups as $g ) { $group_map[ $g->id ] = $g; }
            foreach ( $items as $it ) {
                $edit = admin_url( 'admin.php?page=configurator_links_options&action=edit&id=' . (int) $it->id . ( $filter_group ? '&group=' . (int) $filter_group : '' ) );
                $del  = wp_nonce_url( admin_url( 'admin-post.php?action=cl_delete_option&id=' . (int) $it->id . ( $filter_group ? '&group=' . (int) $filter_group : '' ) ), 'cl_delete_option' );
                echo '<tr>';
                echo '<td>' . (int) $it->id . '</td>';
                $gname = isset( $group_map[ $it->group_id ] ) ? $group_map[ $it->group_id ]->name : ('#' . (int) $it->group_id);
                echo '<td>' . esc_html( $gname ) . '</td>';
                echo '<td>' . esc_html( $it->name ) . '</td>';
                echo '<td>' . ( $it->image_url ? '<img class="cl-img-thumb" src="' . esc_url( $it->image_url ) . '" alt="" />' : '' ) . '</td>';
                echo '<td>' . (int) $it->sort_order . '</td>';
                echo '<td>' . ( (int) $it->active ? esc_html__( 'Ja', 'configurator-links' ) : esc_html__( 'Nee', 'configurator-links' ) ) . '</td>';
                echo '<td><a class="button" href="' . esc_url( $edit ) . '">' . esc_html__( 'Bewerken', 'configurator-links' ) . '</a> ';
                echo '<a class="button button-link-delete" href="' . esc_url( $del ) . '" onclick="return confirm(\'' . esc_js( __( 'Weet je zeker dat je deze optie wilt verwijderen?', 'configurator-links' ) ) . '\');">' . esc_html__( 'Verwijderen', 'configurator-links' ) . '</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">' . esc_html__( 'Geen opties gevonden.', 'configurator-links' ) . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public static function handle_save_option() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Geen toegang', 'configurator-links' ) );
        check_admin_referer( 'cl_save_option', 'cl_nonce' );
        $id = (int) ( $_POST['id'] ?? 0 );
        $data = [
            'group_id' => (int) ( $_POST['group_id'] ?? 0 ),
            'name' => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
            'description' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
            'image_id' => (int) ( $_POST['image_id'] ?? 0 ),
            'image_url' => esc_url_raw( wp_unslash( $_POST['image_url'] ?? '' ) ),
            'sort_order' => (int) ( $_POST['sort_order'] ?? 0 ),
            'active' => CL_Helpers::sanitize_bool( $_POST['active'] ?? 0 ),
        ];
        if ( $id ) { self::update( $id, $data ); } else { $id = self::create( $data ); }
        $redirect = admin_url( 'admin.php?page=configurator_links_options' );
        if ( ! empty( $data['group_id'] ) ) { $redirect = add_query_arg( 'group', (int) $data['group_id'], $redirect ); }
        wp_redirect( $redirect );
        exit;
    }

    public static function handle_delete_option() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Geen toegang', 'configurator-links' ) );
        check_admin_referer( 'cl_delete_option' );
        $id = (int) ( $_GET['id'] ?? 0 ); // phpcs:ignore
        $group = isset( $_GET['group'] ) ? (int) $_GET['group'] : 0; // phpcs:ignore
        if ( $id ) self::delete( $id );
        $redirect = admin_url( 'admin.php?page=configurator_links_options' );
        if ( $group ) { $redirect = add_query_arg( 'group', (int) $group, $redirect ); }
        wp_redirect( $redirect );
        exit;
    }
}
