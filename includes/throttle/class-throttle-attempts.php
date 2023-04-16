<?php

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

/**
 * Password_Protected_Throttle_Attempts
 */
class Password_Protected_Throttle_Attempts {
    
    /**
     * attempts_allowed
     *
     * @var mixed
     */
    private $attempts_allowed;

    /**
     * lockdown_time
     *
     * @var mixed
     */
    private $lockdown_time;
    
    /**
     * attempt
     *
     * @var mixed
     */
    private $attempt;
    
    /**
     * ip
     *
     * @var mixed
     */
    private $ip;
    
    /**
     * now
     *
     * @var mixed
     */
    private $now;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct( $attempts_allowed, $lockdown_time ) {

        $this->now              = current_time( 'timestamp' );

        //TODO fix ip
        $this->ip               = '123';//md5( Password_Protected_Helpers::password_protected_get_client_ip() );

        $this->attempts_allowed = ( int ) $attempts_allowed;

        $this->lockdown_time    =  ( $lockdown_time ) ? $lockdown_time : 10;

        $this->attempt          = Password_Protected_Throttle::get_item_by_ip( $this->ip );

        add_action( 'password_protected_failure_login_attempt', array( $this, 'password_protected_login_attempt' ), 11 );

        add_action( 'password_protected_success_login_attempt', array( $this, 'password_protected_reset_attempt' ), 11 );
        
        add_filter( 'password_protected_check_for_throttling', array( $this, 'password_protected_lockdown_login_attempt' ) );

        add_filter( 'password_protected_throttling_error_messages', array( $this, 'password_protected_hide_default_error' ) );
        
    }
    
    /**
     * set_attempt
     *
     * @return void
     */
    public function set_attempt() {
        $this->attempt = array(
            'client_ip'         => $this->ip,
            'password_attempts' => 1,
            'attempt_at'        => $this->now,
            'locked_at'         => ( $this->attempts_allowed === 1 ) ? $this->now : 0
        );
    }
    
    /**
     * get_attempt
     *
     * @return void
     */
    public function get_attempt() {
        return $this->attempt;
    }

    /**
     * password_protected_login_attempt
     *
     * @return void
     */
    public function password_protected_login_attempt() {

        if( is_null( $this->attempt ) ) {
            $this->set_attempt();
            Password_Protected_Throttle::add_item( $this->get_attempt() );
        
            if( $this->attempt['password_attempts'] != $this->attempts_allowed ) {
                $this->throttle_error_messages( 'remaining_attempts' );
            } else {
                $this->throttle_error_messages( 'site_lockdown' );
            }
            
        } else {
            $this->increment_password_attempt();
            $this->is_limit_reached();
            
            $now            = date_create( date( "d-m-y H:i:s", current_time( "timestamp" ) ) );
            $locked_time    = date_create( date( "d-m-y H:i:s", $this->attempt['locked_at'] ) );
            
            if( ! is_a( $locked_time, 'DateTime' ) ) {
                $this->increment_lockdown_time();
            } else {
                $difference     = $locked_time->diff($now);
                $locked_time    = ( property_exists( $difference, "i" )  ? $difference->i : 0 );

                if( $locked_time >= $this->lockdown_time ) {
                    $this->reset_attempts();
                }
            }
        }
    }

    /**
     * is_limit_reached
     *
     * @return void
     */
    public function is_limit_reached() {
       
        if( $this->attempt['password_attempts'] >= $this->attempts_allowed ) {
            $this->throttle_error_messages( 'site_lockdown' );
        }
        
        if( $this->attempt['password_attempts'] < $this->attempts_allowed ) {
            $this->throttle_error_messages( 'remaining_attempts' );
        }

    }
    
    /**
     * increment_password_attempt
     *
     * @return void
     */
    private function increment_password_attempt() {
        $this->attempt['password_attempts'] += 1;
        Password_Protected_Throttle::update_item( $this->attempt );
    }
    
    /**
     * increment_lockdown_time
     *
     * @return void
     */
    private function increment_lockdown_time() {
         if( $this->attempt['password_attempts'] >= $this->attempts_allowed ) {
            $this->attempt['locked_at'] = $this->now;
            Password_Protected_Throttle::update_item( $this->attempt );
        }
    }
    
    /**
     * reset_attempts
     *
     * @return void
     */
    private function reset_attempts() {
        $this->attempt['password_attempts'] = 1;
        $this->attempt['attempt_at'] = $this->now;
        $this->attempt['locked_at'] = ( $this->attempts_allowed === 1 ) ? $this->now : 0;
        
        Password_Protected_Throttle::update_item( $this->attempt );
        $this->attempt = Password_Protected_Throttle::get_item_by_ip( $this->ip );
    }
    
    /**
     * throttle_error_messages
     *
     * @param  mixed $error_type
     * @return void
     */
    public function throttle_error_messages( $error_type = null ) {
        global $Password_Protected;

        if( $error_type === 'remaining_attempts' ) {
            $Password_Protected->errors->add( 
                "incorrect_password", 
                sprintf( __( "Incorrect Password: <strong>%s</strong> Attempts Remaining!",
                'password-protected-pro' ),
                $this->attempts_allowed - $this->attempt['password_attempts'] )
            );
        } else {
            $Password_Protected->errors->add( 
                "incorrect_password", 
                sprintf( __( "The maximum number of login attempts has been reached. Please try again in <strong>%s</strong> minutes",
                'password-protected-pro' ),
                $this->lockdown_time )
            );
        }
    }
    
    /**
     * password_protected_lockdown_login_attempt
     *
     * @param  mixed $bool
     * @return void
     */
    public function password_protected_lockdown_login_attempt( $bool ) {
        $now            = date_create( date( "d-m-y H:i:s", current_time( "timestamp" ) ) );
        
        if( strlen( $this->attempt['locked_at'] ) > 5 ) {
            $locked_time    = date_create( date( "d-m-y H:i:s", $this->attempt['locked_at'] ) );
            $difference     = $locked_time->diff($now);
            $locked_time    = ( property_exists( $difference, "i" )  ? $difference->i : 0 );
          
            if( !is_null( $this->attempt ) && $locked_time <= $this->lockdown_time ) {
                $this->increment_password_attempt();
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * password_protected_reset_attempt
     *
     * @return void
     */
    public function password_protected_reset_attempt() {
        if( !is_null( $this->attempt ) ) {
            $this->reset_attempts();
        }
    }
    
    /**
     * password_protected_hide_default_error
     *
     * @return void
     */
    public function password_protected_hide_default_error() {
        return false;
    }
}


