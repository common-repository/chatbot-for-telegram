<?php

/**
 * Telegram Menu Class
 */
class Qcld_Tg_Menu {

    /**
     * Constructor
     * 
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct(){
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array($this, 'register_plugin_settings') );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_script' ) );
        add_action( 'admin_post_qc_tg_connect', array($this, 'connect') );
        
    }

    
    function enqueue_script(){

        wp_enqueue_script('jquery');
    
        // please create also an empty JS file in your theme directory and include it too
        wp_enqueue_script('wpbotml-admin-script', QCLD_TELEGRAM_PLUGIN_URL . 'js/admin-script.js', array( 'jquery', ), qcld_telegram()->version ); 
    
    }

    /**
     * Callback function for admin_menu hook
     * 
     * @since 1.0.0
     *
     * @return void
     */
    public function admin_menu(){
        add_menu_page( esc_html__( 'Bot - Telegram', 'wpbot-telegram' ), esc_html__('Bot - Telegram', 'wpbot-telegram' ), 'manage_options', 'wpbottelegram-settings-page', array( $this, 'render_settings' ), 'dashicons-networking', '9' );

    }

    /**
     * Register settings field
     * 
     * @since 1.0.0
     *
     * @return void
     */
    public function register_plugin_settings(){
        register_setting( 'qc-wpbottelegram-settings-group', 'tg_access_token' );
        register_setting( 'qc-wpbottelegram-settings-group', 'tg_language_command' );
        register_setting( 'qc-wpbottelegram-settings-group', 'tg_choose_language' );
        register_setting( 'qc-wpbottelegram-settings-group', 'tg_disable_start_menu' );

    }

    /**
     * Render settings page
     * 
     * @since 1.0.0
     *
     * @return void
     */
    public function render_settings(){
        include 'templates/settings.php';
    }

    public function webhookURL() {
        return 'https://7693-192-140-253-97.ngrok.io/wpbot-free/wp-json/wpbot/v2/telegram';
        //return home_url().'/wp-json/'.Qcld_Tg_Webhook::$namespace.Qcld_Tg_Webhook::$route;
    }
    /**
     * Check Webhook URL is connected with telegram or not
     *
     * @return void
     */
    public function tg_status(){
        if( get_option( 'tg_access_token' ) && get_option( 'tg_access_token' ) != '' ){
            $response = wp_remote_get( 'https://api.telegram.org/bot'.get_option( 'tg_access_token' ).'/getWebhookInfo' );
            $response = wp_remote_retrieve_body($response);
            $response = json_decode( $response );
            if( $response->ok == 1 && $response->result->url == $this->webhookURL() ){
                echo esc_html__( 'Connected!', 'wpbot-telegram' );
            }else{
                echo 'Not Connected'.' <a href="'.admin_url( 'admin-post.php?action=qc_tg_connect' ).'" >Click to Connect</a>';
            }
        }else{
            echo 'Not Connected'.' <a href="'.admin_url( 'admin-post.php?action=qc_tg_connect' ).'" >Click to Connect</a>';
        }
    }

    /**
     * Connect webhook to telegram
     *
     * @return void
     */
    public function connect(){
        $url = $this->webhookURL();
        $response = wp_remote_get( 'https://api.telegram.org/bot'.get_option( 'tg_access_token' ).'/setWebhook?url='.$url );
        $response = wp_remote_retrieve_body($response);
        $response = json_decode( $response );
        wp_redirect( admin_url( 'admin.php?page=wpbottelegram-settings-page' ) );
        
    }

}

new Qcld_Tg_Menu();
