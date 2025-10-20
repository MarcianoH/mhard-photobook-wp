<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CL_Rules {
    public static function init() {
        // Hook for future AJAX or admin actions if needed
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'configurator_rules';
    }

    /**
     * Get all rules
     */
    public static function get_all( $args = array() ) {
        global $wpdb;
        $t = self::table();
        
        $sql = "SELECT * FROM $t ORDER BY id ASC";
        return $wpdb->get_results( $sql, ARRAY_A );
    }

    /**
     * Get rule by ID
     */
    public static function get( $id ) {
        global $wpdb;
        $t = self::table();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $id ), ARRAY_A );
    }

    /**
     * Get rules by if_group_id
     */
    public static function get_by_if_group( $group_id ) {
        global $wpdb;
        $t = self::table();
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE if_group_id = %d", $group_id ), ARRAY_A );
    }

    /**
     * Create a new rule
     */
    public static function create( $data ) {
        global $wpdb;
        $t = self::table();
        $now = current_time( 'mysql' );

        $wpdb->insert( $t, array(
            'rule_id'        => isset( $data['rule_id'] ) ? sanitize_text_field( $data['rule_id'] ) : '',
            'type'           => sanitize_text_field( $data['type'] ),
            'if_group_id'    => absint( $data['if_group_id'] ),
            'if_option_id'   => isset( $data['if_option_id'] ) ? absint( $data['if_option_id'] ) : null,
            'then_group_id'  => isset( $data['then_group_id'] ) ? absint( $data['then_group_id'] ) : null,
            'then_option_id' => isset( $data['then_option_id'] ) ? absint( $data['then_option_id'] ) : null,
            'effect'         => sanitize_text_field( $data['effect'] ),
            'created_at'     => $now,
            'updated_at'     => $now,
        ) );

        return $wpdb->insert_id;
    }

    /**
     * Update rule
     */
    public static function update( $id, $data ) {
        global $wpdb;
        $t = self::table();

        $update_data = array(
            'updated_at' => current_time( 'mysql' ),
        );

        if ( isset( $data['rule_id'] ) ) {
            $update_data['rule_id'] = sanitize_text_field( $data['rule_id'] );
        }
        if ( isset( $data['type'] ) ) {
            $update_data['type'] = sanitize_text_field( $data['type'] );
        }
        if ( isset( $data['if_group_id'] ) ) {
            $update_data['if_group_id'] = absint( $data['if_group_id'] );
        }
        if ( isset( $data['if_option_id'] ) ) {
            $update_data['if_option_id'] = absint( $data['if_option_id'] );
        }
        if ( isset( $data['then_group_id'] ) ) {
            $update_data['then_group_id'] = absint( $data['then_group_id'] );
        }
        if ( isset( $data['then_option_id'] ) ) {
            $update_data['then_option_id'] = absint( $data['then_option_id'] );
        }
        if ( isset( $data['effect'] ) ) {
            $update_data['effect'] = sanitize_text_field( $data['effect'] );
        }

        return $wpdb->update( $t, $update_data, array( 'id' => $id ) );
    }

    /**
     * Delete rule
     */
    public static function delete( $id ) {
        global $wpdb;
        $t = self::table();
        return $wpdb->delete( $t, array( 'id' => $id ) );
    }

    /**
     * Delete all rules
     */
    public static function delete_all() {
        global $wpdb;
        $t = self::table();
        return $wpdb->query( "TRUNCATE TABLE $t" );
    }

    /**
     * Get rules for frontend (for JavaScript)
     */
    public static function get_rules_for_js() {
        global $wpdb;
        $t = self::table();
        $gt = CL_Groups::table();
        $ot = CL_Options::table();

        $sql = "SELECT 
                    r.id,
                    r.type,
                    r.effect,
                    ig.id as if_group_db_id,
                    io.id as if_option_db_id,
                    tg.id as then_group_db_id,
                    to.id as then_option_db_id
                FROM $t r
                LEFT JOIN $gt ig ON r.if_group_id = ig.id
                LEFT JOIN $ot io ON r.if_option_id = io.id
                LEFT JOIN $gt tg ON r.then_group_id = tg.id
                LEFT JOIN $ot to ON r.then_option_id = to.id
                ORDER BY r.id ASC";

        return $wpdb->get_results( $sql, ARRAY_A );
    }
}
