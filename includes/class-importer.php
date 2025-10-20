<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CL_Importer {
    public static function init() {
        add_action( 'admin_post_cl_import_zip', [ __CLASS__, 'handle_import' ] );
        add_action( 'admin_post_cl_delete_all_data', [ __CLASS__, 'handle_delete_all' ] );
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // Start session for preview data
        if ( ! session_id() ) {
            session_start();
        }

        $step = sanitize_text_field( wp_unslash( $_GET['step'] ?? 'upload' ) );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'ZIP Import (Nieuwe Structuur)', 'configurator-links' ) . '</h1>';

        // Show success message
        if ( ! empty( $_GET['imported'] ) ) {
            $groups = (int) $_GET['imported'];
            $groups_updated = (int) ( $_GET['groups_updated'] ?? 0 );
            $options = (int) ( $_GET['options'] ?? 0 );
            $options_updated = (int) ( $_GET['options_updated'] ?? 0 );
            $rules = (int) ( $_GET['rules'] ?? 0 );
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html( sprintf(
                __( '✓ Succesvol geïmporteerd: %d groep(en) aangemaakt, %d groep(en) bijgewerkt, %d optie(s) aangemaakt, %d optie(s) bijgewerkt, %d regel(s) aangemaakt', 'configurator-links' ),
                $groups,
                $groups_updated,
                $options,
                $options_updated,
                $rules
            ) );
            echo '</p></div>';
        }

        // Show delete success message
        if ( ! empty( $_GET['deleted'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html__( '✓ Alle groepen, opties en regels zijn succesvol verwijderd', 'configurator-links' );
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
        <div class="card" style="max-width: 900px;">
            <h2><?php esc_html_e( 'Upload ZIP bestand', 'configurator-links' ); ?></h2>
            <p><?php esc_html_e( 'Upload een ZIP bestand met de volgende structuur:', 'configurator-links' ); ?></p>

            <h3><?php esc_html_e( 'Vereiste structuur:', 'configurator-links' ); ?></h3>
            <pre style="background: #f5f5f5; padding: 15px; border-left: 3px solid #0073aa; overflow-x: auto;">
configurator.zip
├── configurable_catalog/
│   ├── groups.csv      (group_id, product_id, group_name, group_type, sort_order, active, group_description)
│   ├── options.csv     (option_id, group_id, option_name, code, option_description, image, sort_order, active)
│   ├── rules.csv       (rule_id, type, if_group, if_option, then_group, then_option, effect)
│   └── media.csv       (media_id, scope, ref_id, path, alt)
└── images/
    ├── image1.png
    ├── image2.png
    └── ...
            </pre>

            <h3><?php esc_html_e( 'CSV Formaten:', 'configurator-links' ); ?></h3>

            <h4>groups.csv</h4>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>group_id</strong> - Unieke ID voor de groep (bijvoorbeeld: g_album_type)</li>
                <li><strong>product_id</strong> - Product ID (optioneel, bijvoorbeeld: album_001)</li>
                <li><strong>group_name</strong> - Naam van de groep</li>
                <li><strong>group_type</strong> - Type: "single" of "multi"</li>
                <li><strong>sort_order</strong> - Sorteervolgorde</li>
                <li><strong>active</strong> - Actief (1 of 0)</li>
                <li><strong>group_description</strong> - Omschrijving</li>
            </ul>

            <h4>options.csv</h4>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>option_id</strong> - Unieke ID voor de optie (bijvoorbeeld: opt_type_acrylic)</li>
                <li><strong>group_id</strong> - Groep ID (refereert naar groups.csv)</li>
                <li><strong>option_name</strong> - Naam van de optie</li>
                <li><strong>code</strong> - Optionele code</li>
                <li><strong>option_description</strong> - Omschrijving</li>
                <li><strong>image</strong> - Afbeelding (niet gebruikt, zie media.csv)</li>
                <li><strong>sort_order</strong> - Sorteervolgorde</li>
                <li><strong>active</strong> - Actief (1 of 0)</li>
            </ul>

            <h4>rules.csv</h4>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>rule_id</strong> - Unieke ID voor de regel</li>
                <li><strong>type</strong> - Type: "show" of "hide"</li>
                <li><strong>if_group</strong> - Groep ID van de conditie</li>
                <li><strong>if_option</strong> - Optie ID van de conditie</li>
                <li><strong>then_group</strong> - Groep ID van de actie (optioneel)</li>
                <li><strong>then_option</strong> - Optie ID van de actie (optioneel)</li>
                <li><strong>effect</strong> - Effect: "show_group", "hide_group", "show_option", "hide_option"</li>
            </ul>

            <h4>media.csv</h4>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>media_id</strong> - Unieke ID voor media</li>
                <li><strong>scope</strong> - Type: "group", "option", of "product"</li>
                <li><strong>ref_id</strong> - Referentie ID (group_id of option_id)</li>
                <li><strong>path</strong> - Pad naar afbeelding in ZIP (bijvoorbeeld: /mnt/data/assets/images/image.png)</li>
                <li><strong>alt</strong> - Alt tekst voor afbeelding</li>
            </ul>

            <hr style="margin: 20px 0;">

            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'cl_import_zip', 'cl_nonce' ); ?>
                <input type="hidden" name="action" value="cl_import_zip" />
                <input type="hidden" name="import_step" value="preview" />

                <table class="form-table">
                    <tr>
                        <th><label for="zip_file"><?php esc_html_e( 'ZIP Bestand', 'configurator-links' ); ?></label></th>
                        <td>
                            <input type="file" name="zip_file" id="zip_file" accept=".zip" required />
                            <p class="description"><?php esc_html_e( 'Selecteer een ZIP bestand (maximaal 50MB)', 'configurator-links' ); ?></p>
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

            <hr style="margin: 40px 0;">

            <!-- Delete All Data Section -->
            <div style="background: #fee; padding: 20px; border-left: 4px solid #d00;">
                <h3 style="margin-top: 0; color: #d00;"><?php esc_html_e( '⚠ Gevaarzone: Alles Verwijderen', 'configurator-links' ); ?></h3>
                <p><?php esc_html_e( 'Verwijder alle groepen, opties en regels uit de database. Dit kan NIET ongedaan worden gemaakt!', 'configurator-links' ); ?></p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Weet je zeker dat je ALLE groepen, opties en regels wilt verwijderen? Dit kan NIET ongedaan worden gemaakt!');">
                    <?php wp_nonce_field( 'cl_delete_all_data', 'cl_delete_nonce' ); ?>
                    <input type="hidden" name="action" value="cl_delete_all_data" />
                    <button type="submit" class="button button-large" style="background: #d00; border-color: #a00; color: #fff; font-weight: bold;">
                        <?php esc_html_e( '🗑 Verwijder Alles', 'configurator-links' ); ?>
                    </button>
                </form>
            </div>
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
                __( 'Er worden %d groep(en) met %d optie(s) en %d regel(s) geïmporteerd. %d afbeeldingen worden geüpload.', 'configurator-links' ),
                count( $preview['groups'] ),
                $preview['total_options'],
                count( $preview['rules'] ),
                count( $preview['media_files'] )
            ) );
            echo '</p></div>';

            // Show preview table - Groups
            echo '<h3>' . esc_html__( 'Groepen:', 'configurator-links' ) . '</h3>';
            echo '<table class="widefat fixed striped" style="margin-top:12px;">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__( 'Group ID', 'configurator-links' ) . '</th>';
            echo '<th>' . esc_html__( 'Naam', 'configurator-links' ) . '</th>';
            echo '<th>' . esc_html__( 'Type', 'configurator-links' ) . '</th>';
            echo '<th>' . esc_html__( 'Opties', 'configurator-links' ) . '</th>';
            echo '<th>' . esc_html__( 'Actief', 'configurator-links' ) . '</th>';
            echo '</tr></thead><tbody>';

            foreach ( $preview['groups'] as $group ) {
                echo '<tr>';
                echo '<td><code>' . esc_html( $group['group_id'] ) . '</code></td>';
                echo '<td><strong>' . esc_html( $group['name'] ) . '</strong>';
                if ( ! empty( $group['description'] ) ) {
                    echo '<br><small>' . esc_html( $group['description'] ) . '</small>';
                }
                echo '</td>';
                echo '<td>' . esc_html( ucfirst( $group['type'] ) ) . '</td>';
                echo '<td>' . count( $group['options'] ) . '</td>';
                echo '<td>' . ( $group['active'] ? '✓' : '✗' ) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';

            // Show preview table - Rules
            if ( ! empty( $preview['rules'] ) ) {
                echo '<h3 style="margin-top: 30px;">' . esc_html__( 'Regels:', 'configurator-links' ) . '</h3>';
                echo '<table class="widefat fixed striped" style="margin-top:12px;">';
                echo '<thead><tr>';
                echo '<th>' . esc_html__( 'Rule ID', 'configurator-links' ) . '</th>';
                echo '<th>' . esc_html__( 'Als', 'configurator-links' ) . '</th>';
                echo '<th>' . esc_html__( 'Dan', 'configurator-links' ) . '</th>';
                echo '<th>' . esc_html__( 'Effect', 'configurator-links' ) . '</th>';
                echo '</tr></thead><tbody>';

                foreach ( $preview['rules'] as $rule ) {
                    echo '<tr>';
                    echo '<td><code>' . esc_html( $rule['rule_id'] ) . '</code></td>';
                    echo '<td>' . esc_html( $rule['if_group'] . ' → ' . $rule['if_option'] ) . '</td>';
                    echo '<td>' . esc_html( $rule['then_group'] ?: '-' ) . '</td>';
                    echo '<td>' . esc_html( $rule['effect'] ) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
            }

            if ( $dry_run ) {
                echo '<p style="margin-top:20px;">';
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;">';
                wp_nonce_field( 'cl_import_zip', 'cl_nonce' );
                echo '<input type="hidden" name="action" value="cl_import_zip" />';
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
        check_admin_referer( 'cl_import_zip', 'cl_nonce' );

        $import_step = sanitize_text_field( wp_unslash( $_POST['import_step'] ?? 'preview' ) );

        if ( 'preview' === $import_step ) {
            self::handle_preview();
        } elseif ( 'execute' === $import_step ) {
            self::handle_execute();
        }
    }

    public static function handle_delete_all() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Geen toegang', 'configurator-links' ) );
        }
        check_admin_referer( 'cl_delete_all_data', 'cl_delete_nonce' );

        // Delete all rules
        CL_Rules::delete_all();

        // Delete all options
        global $wpdb;
        $options_table = CL_Options::table();
        $wpdb->query( "TRUNCATE TABLE $options_table" );

        // Delete all groups
        $groups_table = CL_Groups::table();
        $wpdb->query( "TRUNCATE TABLE $groups_table" );

        // Redirect with success message
        $redirect = add_query_arg( [
            'page' => 'configurator_links_import',
            'deleted' => 1,
        ], admin_url( 'admin.php' ) );

        wp_redirect( $redirect );
        exit;
    }

    private static function handle_preview() {
        // Start session for storing preview data
        if ( ! session_id() ) {
            session_start();
        }

        $dry_run = ! empty( $_POST['dry_run'] );

        // Validate file upload
        if ( empty( $_FILES['zip_file'] ) ) {
            wp_die( esc_html__( 'Geen bestand geüpload', 'configurator-links' ) );
        }

        $uploaded_file = $_FILES['zip_file'];

        // Check file type
        $file_type = wp_check_filetype( $uploaded_file['name'] );
        if ( $file_type['ext'] !== 'zip' ) {
            wp_die( esc_html__( 'Alleen ZIP bestanden zijn toegestaan', 'configurator-links' ) );
        }

        // Extract ZIP
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/cl_import_temp_' . time();

        WP_Filesystem();
        global $wp_filesystem;

        $unzip_result = unzip_file( $uploaded_file['tmp_name'], $temp_dir );

        if ( is_wp_error( $unzip_result ) ) {
            wp_die( esc_html__( 'Fout bij uitpakken ZIP: ', 'configurator-links' ) . $unzip_result->get_error_message() );
        }

        // Parse CSV files
        $result = self::parse_zip_contents( $temp_dir );
        $result['dry_run'] = $dry_run;
        $result['temp_dir'] = $temp_dir; // Store for later cleanup

        // Store in session
        $_SESSION['cl_import_preview'] = $result;

        // If not dry-run and no errors, execute immediately
        if ( ! $dry_run && empty( $result['errors'] ) ) {
            $imported = self::execute_import( $result );
            unset( $_SESSION['cl_import_preview'] );

            // Cleanup temp directory
            self::cleanup_temp_dir( $temp_dir );

            $redirect = add_query_arg( [
                'page' => 'configurator_links_import',
                'imported' => $imported['groups'],
                'groups_updated' => $imported['groups_updated'],
                'options' => $imported['options'],
                'options_updated' => $imported['options_updated'],
                'rules' => $imported['rules'],
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
        $imported = self::execute_import( $preview );

        // Cleanup temp directory
        if ( ! empty( $preview['temp_dir'] ) ) {
            self::cleanup_temp_dir( $preview['temp_dir'] );
        }

        // Clear session
        unset( $_SESSION['cl_import_preview'] );

        // Redirect with success message
        $redirect = add_query_arg( [
            'page' => 'configurator_links_import',
            'imported' => $imported['groups'],
            'groups_updated' => $imported['groups_updated'],
            'options' => $imported['options'],
            'options_updated' => $imported['options_updated'],
            'rules' => $imported['rules'],
        ], admin_url( 'admin.php' ) );

        wp_redirect( $redirect );
        exit;
    }

    private static function parse_zip_contents( $temp_dir ) {
        $errors = [];
        $warnings = [];
        $groups = [];
        $options = [];
        $rules = [];
        $media_files = [];

        // Look for configurable_catalog directory
        $catalog_dir = $temp_dir . '/configurable_catalog';
        if ( ! is_dir( $catalog_dir ) ) {
            $catalog_dir = $temp_dir . '/configurable_product_catalog/configurable_catalog';
        }

        if ( ! is_dir( $catalog_dir ) ) {
            $errors[] = __( 'configurable_catalog directory niet gevonden in ZIP', 'configurator-links' );
            return [ 'errors' => $errors, 'warnings' => $warnings, 'groups' => [], 'options' => [], 'rules' => [], 'media_files' => [], 'total_options' => 0 ];
        }

        // Parse groups.csv
        $groups_csv = $catalog_dir . '/groups.csv';
        if ( ! file_exists( $groups_csv ) ) {
            $errors[] = __( 'groups.csv niet gevonden', 'configurator-links' );
        } else {
            $groups_data = self::read_csv_file( $groups_csv );
            if ( ! empty( $groups_data['data'] ) ) {
                foreach ( $groups_data['data'] as $row ) {
                    $groups[ $row['group_id'] ] = [
                        'group_id' => $row['group_id'],
                        'product_id' => $row['product_id'] ?? '',
                        'name' => $row['group_name'],
                        'type' => $row['group_type'],
                        'sort_order' => (int) ( $row['sort_order'] ?? 0 ),
                        'active' => (int) ( $row['active'] ?? 1 ),
                        'description' => $row['group_description'] ?? '',
                        'options' => [],
                        'gallery' => [],
                    ];
                }
            }
        }

        // Parse options.csv
        $options_csv = $catalog_dir . '/options.csv';
        if ( ! file_exists( $options_csv ) ) {
            $errors[] = __( 'options.csv niet gevonden', 'configurator-links' );
        } else {
            $options_data = self::read_csv_file( $options_csv );
            if ( ! empty( $options_data['data'] ) ) {
                foreach ( $options_data['data'] as $row ) {
                    $option_id = $row['option_id'];
                    $group_id = $row['group_id'];

                    $options[ $option_id ] = [
                        'option_id' => $option_id,
                        'group_id' => $group_id,
                        'name' => $row['option_name'],
                        'code' => $row['code'] ?? '',
                        'description' => $row['option_description'] ?? '',
                        'image_url' => '',
                        'sort_order' => (int) ( $row['sort_order'] ?? 0 ),
                        'active' => (int) ( $row['active'] ?? 1 ),
                    ];

                    // Add to parent group
                    if ( isset( $groups[ $group_id ] ) ) {
                        $groups[ $group_id ]['options'][] = $option_id;
                    }
                }
            }
        }

        // Parse rules.csv
        $rules_csv = $catalog_dir . '/rules.csv';
        if ( file_exists( $rules_csv ) ) {
            $rules_data = self::read_csv_file( $rules_csv );
            if ( ! empty( $rules_data['data'] ) ) {
                foreach ( $rules_data['data'] as $row ) {
                    $rules[] = [
                        'rule_id' => $row['rule_id'],
                        'type' => $row['type'],
                        'if_group' => $row['if_group'],
                        'if_option' => $row['if_option'] ?? '',
                        'then_group' => $row['then_group'] ?? '',
                        'then_option' => $row['then_option'] ?? '',
                        'effect' => $row['effect'],
                    ];
                }
            }
        }

        // Parse media.csv
        $media_csv = $catalog_dir . '/media.csv';
        if ( file_exists( $media_csv ) ) {
            $media_data = self::read_csv_file( $media_csv );
            if ( ! empty( $media_data['data'] ) ) {
                foreach ( $media_data['data'] as $row ) {
                    $media_files[] = [
                        'media_id' => $row['media_id'],
                        'scope' => $row['scope'],
                        'ref_id' => $row['ref_id'],
                        'path' => $row['path'],
                        'alt' => $row['alt'] ?? '',
                    ];

                    // Map media to groups/options
                    if ( $row['scope'] === 'group' && isset( $groups[ $row['ref_id'] ] ) ) {
                        $groups[ $row['ref_id'] ]['gallery'][] = $row;
                    }
                    // Options kunnen ook images hebben via media
                    if ( $row['scope'] === 'option' && isset( $options[ $row['ref_id'] ] ) ) {
                        // Voor nu slaan we de eerste image op
                        if ( empty( $options[ $row['ref_id'] ]['media_path'] ) ) {
                            $options[ $row['ref_id'] ]['media_path'] = $row['path'];
                        }
                    }
                }
            }
        }

        $total_options = count( $options );

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'groups' => array_values( $groups ),
            'options' => $options,
            'rules' => $rules,
            'media_files' => $media_files,
            'total_options' => $total_options,
        ];
    }

    private static function read_csv_file( $file_path ) {
        $data = [];
        $header = [];

        if ( ( $handle = fopen( $file_path, 'r' ) ) !== false ) {
            // Read header
            $header = fgetcsv( $handle, 0, ',' );

            // Read data rows
            while ( ( $row = fgetcsv( $handle, 0, ',' ) ) !== false ) {
                if ( count( $row ) === count( $header ) ) {
                    $data[] = array_combine( $header, $row );
                }
            }
            fclose( $handle );
        }

        return [ 'header' => $header, 'data' => $data ];
    }

    private static function execute_import( $data ) {
        $groups_created = 0;
        $groups_updated = 0;
        $options_created = 0;
        $options_updated = 0;
        $rules_created = 0;

        $group_id_map = []; // Map group_id (from CSV) to database ID
        $option_id_map = []; // Map option_id (from CSV) to database ID

        // Import groups
        foreach ( $data['groups'] as $group_data ) {
            // Check if group exists by name
            $existing_group = CL_Groups::get_by_name( $group_data['name'], $group_data['product_id'] );

            // Upload gallery images
            $gallery_urls = [];
            if ( ! empty( $group_data['gallery'] ) && ! empty( $data['temp_dir'] ) ) {
                foreach ( $group_data['gallery'] as $media ) {
                    $uploaded_url = self::upload_media_to_library( $media['path'], $data['temp_dir'], $media['alt'] );
                    if ( $uploaded_url ) {
                        $gallery_urls[] = $uploaded_url;
                    }
                }
            }

            $group_insert_data = [
                'name' => $group_data['name'],
                'description' => $group_data['description'],
                'collection' => $group_data['product_id'], // Use product_id as collection
                'type' => $group_data['type'],
                'sort_order' => $group_data['sort_order'],
                'active' => $group_data['active'],
                'gallery' => ! empty( $gallery_urls ) ? wp_json_encode( $gallery_urls ) : null,
            ];

            if ( $existing_group ) {
                // Update existing group
                CL_Groups::update( $existing_group->id, $group_insert_data );
                $db_group_id = $existing_group->id;
                $groups_updated++;
            } else {
                // Create new group
                $db_group_id = CL_Groups::create( $group_insert_data );
                $groups_created++;
            }

            $group_id_map[ $group_data['group_id'] ] = $db_group_id;
        }

        // Import options
        foreach ( $data['options'] as $option_data ) {
            $group_id = $group_id_map[ $option_data['group_id'] ] ?? null;
            if ( ! $group_id ) {
                continue; // Skip if group not found
            }

            // Check if option exists by name and group_id
            $existing_option = CL_Options::get_by_name( $option_data['name'], $group_id );

            // Upload image if exists
            $image_url = '';
            if ( ! empty( $option_data['media_path'] ) && ! empty( $data['temp_dir'] ) ) {
                $image_url = self::upload_media_to_library( $option_data['media_path'], $data['temp_dir'], $option_data['name'] );
            }

            $option_insert_data = [
                'group_id' => $group_id,
                'name' => $option_data['name'],
                'description' => $option_data['description'],
                'image_url' => $image_url,
                'sort_order' => $option_data['sort_order'],
                'active' => $option_data['active'],
            ];

            if ( $existing_option ) {
                // Update existing option
                CL_Options::update( $existing_option->id, $option_insert_data );
                $db_option_id = $existing_option->id;
                $options_updated++;
            } else {
                // Create new option
                $db_option_id = CL_Options::create( $option_insert_data );
                $options_created++;
            }

            $option_id_map[ $option_data['option_id'] ] = $db_option_id;
        }

        // Import rules
        foreach ( $data['rules'] as $rule_data ) {
            $if_group_id = $group_id_map[ $rule_data['if_group'] ] ?? null;
            $if_option_id = ! empty( $rule_data['if_option'] ) ? ( $option_id_map[ $rule_data['if_option'] ] ?? null ) : null;
            $then_group_id = ! empty( $rule_data['then_group'] ) ? ( $group_id_map[ $rule_data['then_group'] ] ?? null ) : null;
            $then_option_id = ! empty( $rule_data['then_option'] ) ? ( $option_id_map[ $rule_data['then_option'] ] ?? null ) : null;

            if ( ! $if_group_id ) {
                continue; // Skip if if_group not found
            }

            CL_Rules::create( [
                'rule_id' => $rule_data['rule_id'],
                'type' => $rule_data['type'],
                'if_group_id' => $if_group_id,
                'if_option_id' => $if_option_id,
                'then_group_id' => $then_group_id,
                'then_option_id' => $then_option_id,
                'effect' => $rule_data['effect'],
            ] );

            $rules_created++;
        }

        return [
            'groups' => $groups_created,
            'groups_updated' => $groups_updated,
            'options' => $options_created,
            'options_updated' => $options_updated,
            'rules' => $rules_created,
        ];
    }

    private static function upload_media_to_library( $path, $temp_dir, $alt_text = '' ) {
        // Extract filename from path
        // Path format: /mnt/data/assets/images/filename.png
        // We want to find: images/filename.png in the ZIP

        $filename = basename( $path );

        // Try different possible locations in ZIP
        $possible_paths = [
            $temp_dir . '/images/' . $filename,
            $temp_dir . '/configurable_product_catalog/images/' . $filename,
            $temp_dir . '/assets/images/' . $filename,
        ];

        $file_path = null;
        foreach ( $possible_paths as $possible_path ) {
            if ( file_exists( $possible_path ) ) {
                $file_path = $possible_path;
                break;
            }
        }

        if ( ! $file_path ) {
            return ''; // File not found
        }

        // Upload to WordPress media library
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload_dir = wp_upload_dir();
        $target_path = $upload_dir['path'] . '/' . $filename;

        // Copy file to uploads directory
        copy( $file_path, $target_path );

        // Create attachment
        $attachment = [
            'post_mime_type' => mime_content_type( $target_path ),
            'post_title' => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attach_id = wp_insert_attachment( $attachment, $target_path );

        if ( ! is_wp_error( $attach_id ) ) {
            // Generate metadata
            $attach_data = wp_generate_attachment_metadata( $attach_id, $target_path );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            if ( $alt_text ) {
                update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt_text );
            }

            return wp_get_attachment_url( $attach_id );
        }

        return '';
    }

    private static function cleanup_temp_dir( $temp_dir ) {
        if ( ! $temp_dir || ! is_dir( $temp_dir ) ) {
            return;
        }

        WP_Filesystem();
        global $wp_filesystem;

        $wp_filesystem->delete( $temp_dir, true );
    }
}
