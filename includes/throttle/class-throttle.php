<?php

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

/**
 * Password_Protected_Throttle
 */
class Password_Protected_Throttle {
            
    /**
     * now
     *
     * @var undefined
     */
    private static $now =   0;
    
    /**
     * table
     *
     * @var mixed
     */
    private static $table;
    /**
     * __construct
     *
     * @return void
     */
    public function __construct() {
        self::$now          = current_time( 'timestamp' );
        self::$table        = 'pp_limit_password';
    }
        
    /**
     * add_item
     *
     * @param  mixed $request
     * @return boolean
     */
    public static function add_item( $request ) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table;
        $format = array( '%s', '%s', '%s', '%s', );
 
        return $wpdb->insert( $table_name, $request, $format );
    }
    
    public static function remove_old_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table;
        $sql = "DELETE FROM " . $table_name . " WHERE attempt_at < TIMESTAMPADD(DAY, -14, NOW());";
        $wpdb->query( $sql );
    }
        
    /**
     * update_item
     *
     * @param  mixed $request
     * @return void
     */
    public static function update_item( $request ) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table;

        $data = array(
            'client_ip'         => $request['client_ip'],
            'password_attempts' => $request['password_attempts'], 
            'attempt_at'        => self::$now,
            'locked_at'         => $request['locked_at'],
        );
        $format = array( '%s', '%s', '%s', '%s', '%s', '%s' );
        $where = array( 'id' => $request['id'], 'client_ip' => $request['client_ip'] );
        $wpdb->update( $table_name, $data, $where, $format );
    }

    /**
     * get_item_by_ip
     *
     * @param  mixed $hashed_ip
     * @return array|false
     */
    public static function get_item_by_ip( $hashed_ip ) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table;

        $prepare_statment = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE `client_ip` = %s ORDER BY `id` DESC LIMIT 1",
            $hashed_ip
        );

        $response = $wpdb->get_results( $prepare_statment, ARRAY_A );

        if( count( $response ) > 0 ) {
            return reset( $response );
        }

        return null;
    }

}

( new Password_Protected_Throttle() );