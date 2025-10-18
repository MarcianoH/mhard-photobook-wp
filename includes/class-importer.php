<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CL_Importer {
    public static function init() {}

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        echo '<div class="wrap"><h1>' . esc_html__( 'Import/Export', 'configurator-links' ) . '</h1>';
        echo '<p>' . esc_html__( 'CSV-import wizard wordt hier toegevoegd (dry-run, mapping, batch, fout-CSV).', 'configurator-links' ) . '</p>';
        echo '</div>';
    }
}
