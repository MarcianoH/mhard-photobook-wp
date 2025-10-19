<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CL_Importer {
    public static function init() {
        add_action( 'admin_post_cl_import_csv', [ __CLASS__, 'handle_import' ] );
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        
        // Start session for preview data
        if ( ! session_id() ) {
            session_start();
        }
        
        $step = sanitize_text_field( wp_unslash( $_GET['step'] ?? 'upload' ) );
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'CSV Import', 'configurator-links' ) . '</h1>';
        
        // Show success message
        if ( ! empty( $_GET['imported'] ) ) {
            $groups = (int) $_GET['imported'];
            $groups_updated = (int) ( $_GET['groups_updated'] ?? 0 );
            $options = (int) ( $_GET['options'] ?? 0 );
            $options_updated = (int) ( $_GET['options_updated'] ?? 0 );
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html( sprintf( 
                __( '✓ Succesvol geïmporteerd: %d groep(en) aangemaakt, %d groep(en) bijgewerkt, %d optie(s) aangemaakt, %d optie(s) bijgewerkt', 'configurator-links' ),
                $groups,
                $groups_updated,
                $options,
                $options_updated
            ) );
            echo '</p></div>';
        }
        
        switch ( $step ) {
            case 'preview':
                self::render_preview_step();
                break;
            case 'upload':
            default:
                self::render_upload_step();
                break;
        }
        
        echo '</div>';
    }
    
    private static function render_upload_step() {
        ?>
        <div class="card" style="max-width: 800px;">
            <h2><?php esc_html_e( 'Upload CSV bestand', 'configurator-links' ); ?></h2>
            <p><?php esc_html_e( 'Upload een CSV bestand met groepen en opties. Het bestand moet de volgende kolommen bevatten:', 'configurator-links' ); ?></p>
            
            <h3><?php esc_html_e( 'Vereiste kolommen:', 'configurator-links' ); ?></h3>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>group_name</strong> - <?php esc_html_e( 'Naam van de groep', 'configurator-links' ); ?></li>
                <li><strong>group_type</strong> - <?php esc_html_e( 'Type: "single" of "multi"', 'configurator-links' ); ?></li>
                <li><strong>option_name</strong> - <?php esc_html_e( 'Naam van de optie', 'configurator-links' ); ?></li>
            </ul>
            
            <h3><?php esc_html_e( 'Optionele kolommen:', 'configurator-links' ); ?></h3>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>collection</strong> - <?php esc_html_e( 'Collectie naam (bijv: DreambooksPRO, Bold Collection 150)', 'configurator-links' ); ?></li>
                <li><strong>group_description</strong> - <?php esc_html_e( 'Omschrijving van de groep', 'configurator-links' ); ?></li>
                <li><strong>group_sort_order</strong> - <?php esc_html_e( 'Sorteervolgorde groep (nummer)', 'configurator-links' ); ?></li>
                <li><strong>group_active</strong> - <?php esc_html_e( 'Actief (1 of 0)', 'configurator-links' ); ?></li>
                <li><strong>option_description</strong> - <?php esc_html_e( 'Omschrijving van de optie', 'configurator-links' ); ?></li>
                <li><strong>option_image_url</strong> - <?php esc_html_e( 'URL naar afbeelding', 'configurator-links' ); ?></li>
                <li><strong>option_sort_order</strong> - <?php esc_html_e( 'Sorteervolgorde optie (nummer)', 'configurator-links' ); ?></li>
                <li><strong>option_active</strong> - <?php esc_html_e( 'Actief (1 of 0)', 'configurator-links' ); ?></li>
            </ul>
            
            <p>
                <a href="<?php echo esc_url( CL_PLUGIN_URL . 'assets/examples/combined.csv' ); ?>" class="button">
                    <?php esc_html_e( '⬇ Download voorbeeld CSV', 'configurator-links' ); ?>
                </a>
            </p>
            
            <hr style="margin: 20px 0;">
            
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'cl_import_csv', 'cl_nonce' ); ?>
                <input type="hidden" name="action" value="cl_import_csv" />
                <input type="hidden" name="import_step" value="preview" />
                
                <table class="form-table">
                    <tr>
                        <th><label for="csv_file"><?php esc_html_e( 'CSV Bestand', 'configurator-links' ); ?></label></th>
                        <td>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required />
                            <p class="description"><?php esc_html_e( 'Selecteer een CSV bestand (maximaal 2MB)', 'configurator-links' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="dry_run"><?php esc_html_e( 'Modus', 'configurator-links' ); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="dry_run" id="dry_run" value="1" checked />
                                <?php esc_html_e( 'Dry-run (alleen preview, niet importeren)', 'configurator-links' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button( __( 'Preview', 'configurator-links' ) ); ?>
            </form>
        </div>
        <?php
    }
    
    private static function render_preview_step() {
        if ( empty( $_SESSION['cl_import_preview'] ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Geen preview data gevonden. Upload eerst een bestand.', 'configurator-links' ) . '</p></div>';
            echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=configurator_links_import' ) ) . '" class="button">&larr; ' . esc_html__( 'Terug', 'configurator-links' ) . '</a></p>';
            return;
        }
        
        $preview = $_SESSION['cl_import_preview'];
        $dry_run = ! empty( $preview['dry_run'] );
        
        echo '<h2>' . esc_html__( 'Import Preview', 'configurator-links' ) . '</h2>';
        
        if ( ! empty( $preview['errors'] ) ) {
            echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Fouten gevonden:', 'configurator-links' ) . '</strong></p><ul style="list-style:disc;margin-left:20px;">';
            foreach ( $preview['errors'] as $err ) {
                echo '<li>' . esc_html( $err ) . '</li>';
            }
            echo '</ul></div>';
        }
        
        if ( ! empty( $preview['warnings'] ) ) {
            echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Waarschuwingen:', 'configurator-links' ) . '</strong></p><ul style="list-style:disc;margin-left:20px;">';
            foreach ( $preview['warnings'] as $warn ) {
                echo '<li>' . esc_html( $warn ) . '</li>';
            }
            echo '</ul></div>';
        }
        
        if ( empty( $preview['errors'] ) ) {
            echo '<div class="notice notice-success"><p>';
            echo esc_html( sprintf( 
                __( 'Er worden %d groep(en) met in totaal %d optie(s) geïmporteerd.', 'configurator-links' ),
                count( $preview['groups'] ),
                $preview['total_options']
            ) );
            echo '</p></div>';
            
            // Show preview table
            echo '<h3>' . esc_html__( 'Preview:', 'configurator-links' ) . '</h3>';
            echo '<table class="widefat fixed striped" style="margin-top:12px;">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__( 'Collectie', 'configurator-links' ) . '</th>';
            echo '<th>' . esc_html__( 'Groep', 'configurator-links' ) . '</th>';
            echo '<th>' . esc_html__( 'Type', 'configurator-links' ) . '</th>';
            echo '<th>' . esc_html__( 'Opties', 'configurator-links' ) . '</th>';
            echo '<th>' . esc_html__( 'Actief', 'configurator-links' ) . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ( $preview['groups'] as $group ) {
                echo '<tr>';
                echo '<td>' . esc_html( $group['collection'] ?: '-' ) . '</td>';
                echo '<td><strong>' . esc_html( $group['name'] ) . '</strong>';
                if ( ! empty( $group['description'] ) ) {
                    echo '<br><small>' . esc_html( $group['description'] ) . '</small>';
                }
                echo '</td>';
                echo '<td>' . esc_html( ucfirst( $group['type'] ) ) . '</td>';
                echo '<td>';
                $opts = [];
                foreach ( $group['options'] as $opt ) {
                    $opts[] = esc_html( $opt['name'] ) . ( ! empty( $opt['image_url'] ) ? ' 🖼️' : '' );
                }
                echo implode( '<br>', $opts );
                echo '</td>';
                echo '<td>' . ( $group['active'] ? esc_html__( 'Ja', 'configurator-links' ) : esc_html__( 'Nee', 'configurator-links' ) ) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            
            if ( $dry_run ) {
                echo '<p style="margin-top:20px;">';
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;">';
                wp_nonce_field( 'cl_import_csv', 'cl_nonce' );
                echo '<input type="hidden" name="action" value="cl_import_csv" />';
                echo '<input type="hidden" name="import_step" value="execute" />';
                submit_button( __( '✓ Importeren', 'configurator-links' ), 'primary', 'submit', false );
                echo '</form> ';
                echo '<a href="' . esc_url( admin_url( 'admin.php?page=configurator_links_import' ) ) . '" class="button">' . esc_html__( 'Annuleren', 'configurator-links' ) . '</a>';
                echo '</p>';
            }
        } else {
            echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=configurator_links_import' ) ) . '" class="button">&larr; ' . esc_html__( 'Probeer opnieuw', 'configurator-links' ) . '</a></p>';
        }
    }
    
    public static function handle_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Geen toegang', 'configurator-links' ) );
        }
        check_admin_referer( 'cl_import_csv', 'cl_nonce' );
        
        $import_step = sanitize_text_field( wp_unslash( $_POST['import_step'] ?? 'preview' ) );
        
        if ( 'preview' === $import_step ) {
            self::handle_preview();
        } elseif ( 'execute' === $import_step ) {
            self::handle_execute();
        }
    }
    
    private static function handle_preview() {
        // Start session for storing preview data
        if ( ! session_id() ) {
            session_start();
        }

        $dry_run = ! empty( $_POST['dry_run'] );

        // Validate file upload
        if ( empty( $_FILES['csv_file'] ) ) {
            wp_die( esc_html__( 'Geen bestand geüpload', 'configurator-links' ) );
        }

        $csv_data = CL_Helpers::csv_read_uploaded_file( $_FILES['csv_file'] );

        if ( empty( $csv_data['header'] ) ) {
            wp_die( esc_html__( 'Ongeldig CSV bestand', 'configurator-links' ) );
        }

        // Parse and validate
        $result = self::parse_csv( $csv_data['header'], $csv_data['rows'] );
        $result['dry_run'] = $dry_run;

        // Store in session
        $_SESSION['cl_import_preview'] = $result;

        // If not dry-run and no errors, execute immediately
        if ( ! $dry_run && empty( $result['errors'] ) ) {
            $imported = self::execute_import( $result['groups'] );
            unset( $_SESSION['cl_import_preview'] );

            $redirect = add_query_arg( [
                'page' => 'configurator_links_import',
                'imported' => $imported['groups'],
                'groups_updated' => $imported['groups_updated'],
                'options' => $imported['options'],
                'options_updated' => $imported['options_updated'],
            ], admin_url( 'admin.php' ) );

            wp_redirect( $redirect );
            exit;
        }

        // Redirect to preview (for dry-run or if errors)
        wp_redirect( admin_url( 'admin.php?page=configurator_links_import&step=preview' ) );
        exit;
    }
    
    private static function handle_execute() {
        if ( ! session_id() ) {
            session_start();
        }
        
        if ( empty( $_SESSION['cl_import_preview'] ) ) {
            wp_die( esc_html__( 'Geen preview data gevonden', 'configurator-links' ) );
        }
        
        $preview = $_SESSION['cl_import_preview'];
        
        if ( ! empty( $preview['errors'] ) ) {
            wp_die( esc_html__( 'Kan niet importeren vanwege fouten in preview', 'configurator-links' ) );
        }
        
        // Execute import
        $imported = self::execute_import( $preview['groups'] );
        
        // Clear session
        unset( $_SESSION['cl_import_preview'] );
        
        // Redirect with success message
        $redirect = add_query_arg( [
            'page' => 'configurator_links_import',
            'imported' => $imported['groups'],
            'groups_updated' => $imported['groups_updated'],
            'options' => $imported['options'],
            'options_updated' => $imported['options_updated'],
        ], admin_url( 'admin.php' ) );
        
        wp_redirect( $redirect );
        exit;
    }
    
    private static function parse_csv( $header, $rows ) {
        $errors = [];
        $warnings = [];
        $groups = [];
        
        // Validate required columns
        $required_cols = [ 'group_name', 'group_type', 'option_name' ];
        foreach ( $required_cols as $col ) {
            if ( ! in_array( $col, $header, true ) ) {
                $errors[] = sprintf( __( 'Verplichte kolom ontbreekt: %s', 'configurator-links' ), $col );
            }
        }
        
        if ( ! empty( $errors ) ) {
            return [ 'errors' => $errors, 'warnings' => $warnings, 'groups' => [], 'total_options' => 0 ];
        }
        
        // Build column index map
        $col_map = array_flip( $header );
        
        // Parse rows and group by group_name
        $grouped_data = [];
        $line_num = 1; // Start at 1 (header is 0)
        
        foreach ( $rows as $row ) {
            $line_num++;
            
            // Skip empty rows
            if ( empty( array_filter( $row ) ) ) {
                continue;
            }
            
            $group_name = isset( $col_map['group_name'] ) ? trim( $row[ $col_map['group_name'] ] ?? '' ) : '';
            $option_name = isset( $col_map['option_name'] ) ? trim( $row[ $col_map['option_name'] ] ?? '' ) : '';
            
            if ( empty( $group_name ) ) {
                $warnings[] = sprintf( __( 'Regel %d: group_name is leeg, rij wordt overgeslagen', 'configurator-links' ), $line_num );
                continue;
            }
            
            if ( empty( $option_name ) ) {
                $warnings[] = sprintf( __( 'Regel %d: option_name is leeg, rij wordt overgeslagen', 'configurator-links' ), $line_num );
                continue;
            }
            
            // Initialize group if first occurrence
            if ( ! isset( $grouped_data[ $group_name ] ) ) {
                $group_type = isset( $col_map['group_type'] ) ? trim( $row[ $col_map['group_type'] ] ?? 'single' ) : 'single';
                
                if ( ! in_array( $group_type, [ 'single', 'multi' ], true ) ) {
                    $warnings[] = sprintf( __( 'Regel %d: ongeldig group_type "%s", wordt "single"', 'configurator-links' ), $line_num, $group_type );
                    $group_type = 'single';
                }
                
                $grouped_data[ $group_name ] = [
                    'name' => $group_name,
                    'description' => isset( $col_map['group_description'] ) ? trim( $row[ $col_map['group_description'] ] ?? '' ) : '',
                    'collection' => isset( $col_map['collection'] ) ? trim( $row[ $col_map['collection'] ] ?? '' ) : '',
                    'type' => $group_type,
                    'sort_order' => isset( $col_map['group_sort_order'] ) ? (int) ( $row[ $col_map['group_sort_order'] ] ?? 0 ) : 0,
                    'active' => isset( $col_map['group_active'] ) ? CL_Helpers::sanitize_bool( $row[ $col_map['group_active'] ] ?? 1 ) : 1,
                    'options' => [],
                ];
            }
            
            // Add option to group
            $grouped_data[ $group_name ]['options'][] = [
                'name' => $option_name,
                'description' => isset( $col_map['option_description'] ) ? trim( $row[ $col_map['option_description'] ] ?? '' ) : '',
                'image_url' => isset( $col_map['option_image_url'] ) ? trim( $row[ $col_map['option_image_url'] ] ?? '' ) : '',
                'sort_order' => isset( $col_map['option_sort_order'] ) ? (int) ( $row[ $col_map['option_sort_order'] ] ?? 0 ) : 0,
                'active' => isset( $col_map['option_active'] ) ? CL_Helpers::sanitize_bool( $row[ $col_map['option_active'] ] ?? 1 ) : 1,
            ];
        }
        
        // Convert to indexed array
        $groups = array_values( $grouped_data );
        $total_options = 0;
        foreach ( $groups as $g ) {
            $total_options += count( $g['options'] );
        }
        
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'groups' => $groups,
            'total_options' => $total_options,
        ];
    }
    
    private static function execute_import( $groups ) {
        $groups_created = 0;
        $groups_updated = 0;
        $options_created = 0;
        $options_updated = 0;
        
        foreach ( $groups as $group_data ) {
            // Check if group exists by name and collection
            $existing_group = CL_Groups::get_by_name( $group_data['name'], $group_data['collection'] );
            
            if ( $existing_group ) {
                // Update existing group
                CL_Groups::update( $existing_group->id, [
                    'name' => $group_data['name'],
                    'description' => $group_data['description'],
                    'collection' => $group_data['collection'],
                    'type' => $group_data['type'],
                    'sort_order' => $group_data['sort_order'],
                    'active' => $group_data['active'],
                ] );
                $group_id = $existing_group->id;
                $groups_updated++;
            } else {
                // Create new group
                $group_id = CL_Groups::create( [
                    'name' => $group_data['name'],
                    'description' => $group_data['description'],
                    'collection' => $group_data['collection'],
                    'type' => $group_data['type'],
                    'sort_order' => $group_data['sort_order'],
                    'active' => $group_data['active'],
                ] );
                $groups_created++;
            }
            
            // Process options for this group
            foreach ( $group_data['options'] as $option_data ) {
                // Check if option exists by name and group_id
                $existing_option = CL_Options::get_by_name( $option_data['name'], $group_id );
                
                if ( $existing_option ) {
                    // Update existing option
                    CL_Options::update( $existing_option->id, [
                        'group_id' => $group_id,
                        'name' => $option_data['name'],
                        'description' => $option_data['description'],
                        'image_url' => $option_data['image_url'],
                        'sort_order' => $option_data['sort_order'],
                        'active' => $option_data['active'],
                    ] );
                    $options_updated++;
                } else {
                    // Create new option
                    CL_Options::create( [
                        'group_id' => $group_id,
                        'name' => $option_data['name'],
                        'description' => $option_data['description'],
                        'image_url' => $option_data['image_url'],
                        'sort_order' => $option_data['sort_order'],
                        'active' => $option_data['active'],
                    ] );
                    $options_created++;
                }
            }
        }
        
        return [
            'groups' => $groups_created,
            'groups_updated' => $groups_updated,
            'options' => $options_created,
            'options_updated' => $options_updated,
        ];
    }
}
