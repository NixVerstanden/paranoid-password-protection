<?php
/**
 * Password_Protected_Activity_Logs_Table
 */

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Password_Protected_Activity_Logs_Table extends WP_List_Table {

    /**
     * Method __construct
     *
     * @return void
     */
    public function __construct() {

        parent::__construct(); // invoke parent constructor

        $this->handle_delete_activity_log();

    }
        
    /**
     * prepare_items
     *
     * @return void
     */
    public function prepare_items() {

        $activity_logs = $this->password_protected_get_activity_logs();

        $per_page = $this->get_items_per_page('password_protected_activity_logs_per_page');
        $current_page = $this->get_pagenum();
        $total_items = count( $activity_logs );

        $this->set_pagination_args(
            array(
                'total_items'   => $total_items,
                'per_page'      => $per_page
            )
        );

        $this->items = array_slice( $activity_logs, ( ( $current_page - 1 ) * $per_page ), $per_page );
        $columns = $this->get_columns();

        $this->_column_headers = array( $columns );

    }
    
    /**
     * get_columns
     *
     * @return void
     */
    public function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'ip' => __( 'IP', 'password-protected-pro' ),
            'browser' => __( 'Browser', 'password-protected-pro' ), 
            'status' => __( 'Status', 'password-protected-pro' ),
            'created_at' => __( 'Date Time', 'password-protected-pro' ) 
        );
        return $columns;
    }
    
    /**
     * column_cb
     *
     * @param  mixed $item
     * @return void
     */
    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="activity_logs[]" value="%s" />', $item['id'] );
    }
    
    /**
     * column_default
     *
     * @param  mixed $item
     * @param  mixed $columns_name
     * @return void
     */
    public function column_default( $item, $columns_name ) {
        switch( $columns_name ) {
            case 'ip':
            case 'status':
            case 'browser':
                return $item[$columns_name];
            break;
                
            case 'created_at':
                return date("F j, Y, g:i a", $item[$columns_name]);
            break;

            default:
                return __( "Undefined Column", 'password-protected-pro' );
            break;

        }
    }
    
    /**
     * column_ip
     *
     * @param  mixed $item
     * @return void
     */
    public function column_ip( $item ) {
        $nonce = wp_create_nonce( 'activity-log-row-action' );
        $delete_link = sprintf( 
            '?page=%s&tab=%s&action=%s&log_id=%s&_wpnonce=%s', 
            sanitize_text_field( $_GET['page'] ), 
            sanitize_text_field( $_GET['tab'] ), 
            'delete', 
            $item['id'],
            $nonce
        );
        $action = array(
            'delete' => '<a href="'.$delete_link.'" class="pp_confirmation">' . __( 'Delete', 'password-protected-pro' ) . '</a>'
        );
        
        return sprintf( '%1$s %2$s', $item['ip'], $this->row_actions( $action ) );
    }
    
    /**
     * password_protected_display_activity_logs_table
     *
     * @return void
     */
    public static function password_protected_display_activity_logs_table() {
        $activity_logs = new Password_Protected_Activity_Logs_Table();

        $activity_logs->prepare_items();
        $activity_logs->get_table_header( $activity_logs );
        $activity_logs->filter_logs();
        echo '<form method="post">';
            wp_nonce_field( 'password_protected_activity_logs_table', 'activity_logs_table_nonce' );
            $activity_logs->display();
        echo '</form>';
    }
    
    /**
     * password_protected_get_activity_logs
     *
     * @return void
     */
    public function password_protected_get_activity_logs() {
        return Password_Protected_Activity_Logs::get_items();
    }
    
    /**
     * get_bulk_actions
     *
     * @return void
     */
    public function get_bulk_actions() {

        $actions = array(
            'delete' => __( 'Delete', 'password-protected-pro'),
            'all_delete' => __( 'Delete all logs', 'password-protected-pro' )
        );

        return $actions;

    }
    
    /**
     * get_table_header
     *
     * @param  mixed $table
     * @return void
     */
    public function get_table_header( $table ) {
        echo "<form 
                method ='post' 
                name='pp_search_activity_log_form' 
                action='" . esc_attr( $_SERVER['PHP_SELF'] ) . "?page=password-protected&tab=activity_logs' " . ( !empty( $_GET['show_logs'] ) ? "&show_logs=".esc_attr( sanitize_text_field( $_GET['show_logs'] ) ) : "" ) .  " >";
            wp_nonce_field( 'password_protected_search_activity_logs', 'search_activity_logs_nonce' );
            $table->search_box( __( "Search", 'password-protected-pro' ), "search_pp_activity_log" );
        echo "</form>";
    }
    
    /**
     * handle_delete_activity_log
     *
     * @return void
     */
    public function handle_delete_activity_log() {
        // bulk delete items
        $this->bulk_delete();
        // truncate table
        $this->truncate_table();
        // delete single item
        $this->row_delete();
    }

    public function bulk_delete() {
        if( isset( $_POST['activity_logs'] ) && isset( $_POST['action'] ) && isset( $_POST['action2'] ) ) {
            if ( ! isset( $_POST['activity_logs_table_nonce'] )  || ! wp_verify_nonce( $_POST['activity_logs_table_nonce'], 'password_protected_activity_logs_table' )  ) {
                wp_die( __('Sorry, your nonce did not verify.', 'password-protected-pro' ) );
            }
            $logs = $_POST['activity_logs'];
            $ids = implode( ',', array_map( 'absint', (array) $logs ) );
            $action = sanitize_text_field( $_POST['action'] );
            if( $action == 'delete' && $action == sanitize_text_field( $_POST['action2'] ) ) {
                Password_Protected_Activity_Logs::delete_items( $ids );
            }
        }
    }
    
    /**
     * truncate_table
     *
     * @return void
     */
    public function truncate_table() {
        if( isset( $_POST['action'] ) && sanitize_text_field( $_POST['action'] ) == 'all_delete' && sanitize_text_field( $_POST['action'] ) == sanitize_text_field( $_POST['action2'] ) ) {
            if ( ! isset( $_POST['activity_logs_table_nonce'] )  || ! wp_verify_nonce( $_POST['activity_logs_table_nonce'], 'password_protected_activity_logs_table' )  ) {
                wp_die( __( 'Sorry, your nonce did not verify.', 'password-protected-pro' ) );
            }
            Password_Protected_Activity_Logs::delete_all_items();
        }
    }
        
    /**
     * row_delete
     *
     * @return void
     */
    public function row_delete() {
        if( isset( $_GET['page'] ) && isset( $_GET['tab'] ) && isset( $_GET['action'] ) && isset( $_GET['log_id'] )  ) {
            
            $nonce  = $_REQUEST['_wpnonce'];
            $page   = sanitize_text_field( $_GET['page'] );
            $tab    = sanitize_text_field( $_GET['tab'] );
            $action = sanitize_text_field( $_GET['action'] );
            $log_id = sanitize_text_field( $_GET['log_id'] );

            if( $page == 'password-protected' && $tab == 'activity_logs' && $action == 'delete' && !empty( $log_id ) ) {
                if( ! wp_verify_nonce( $nonce, 'activity-log-row-action' ) ) {
                    wp_die( __( 'Security check: Your nonce did not verify!', 'password-protected-pro' ) ); 
                } else { 
                    Password_Protected_Activity_Logs::delete_item( $log_id );
                }
            }
        }
    }
    /**
     * add_screen_option
     *
     * @return void
     */
    public static function add_screen_option() {
        add_screen_option( 
            'per_page', 
            array(
                'option' => 'password_protected_activity_logs_per_page' , 
                'label' => __( 'Activity Logs Per Page' , 'password-protected-pro' ) , 
                'default' => 10 
            ) 
        );
    }
    
    /**
     * filter_logs
     *
     * @return void
     */
    public function filter_logs() {
        $current = ( isset( $_GET['show_logs'] ) && sanitize_text_field( $_GET['show_logs'] ) ) ? sanitize_text_field( $_GET['show_logs'] ) : '';
        $nonce = wp_create_nonce( 'activity-logs-filter' );
        $url = admin_url('admin.php?page=password-protected&tab=activity_logs&_wpnonce='.$nonce);
        ?>
        <form method="post">
            <ul class="subsubsub">
                <li class="">
                    <a href="<?php echo $url; ?>" class="<?php echo ($current == '') ? 'current' : ''; ?>"><?php _e( 'All', 'password-protected-pro' ); ?></a> |
                </li>
                <li class="today">
                    <a href="<?php echo $url; ?>&show_logs=today" class="<?php echo ($current == 'today') ? 'current' : ''; ?>"><?php _e( 'Today', 'password-protected-pro' ); ?></a> |
                </li>
                <li class="yesterday">
                    <a href="<?php echo $url; ?>&show_logs=yesterday" class="<?php echo ($current == 'yesterday') ? 'current' : ''; ?>"><?php _e( 'Yesterday', 'password-protected-pro' ); ?></a> |
                </li>
                <li class="thisweek">
                    <a href="<?php echo $url; ?>&show_logs=thisweek" class="<?php echo ($current == 'thisweek') ? 'current' : ''; ?>"><?php _e( 'This Week', 'password-protected-pro' ); ?></a> |
                </li>
                <li class="thismonth">
                    <a href="<?php echo $url; ?>&show_logs=thismonth" class="<?php echo ($current == 'thismonth') ? 'current' : ''; ?>"><?php _e( 'This Month', 'password-protected-pro' ); ?></a>
                </li>
            </ul>
        </form>
        <?php
    } 
        
    /**
     * no_items
     *
     * @return void
     */
    public function no_items() {
        _e( 'No Activity Logs found.', 'password-protected-pro' );
    }
}