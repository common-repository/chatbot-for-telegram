<?php

/**
 * Class Helper
 * @since 0.0.9
 * @package WPBot 
 */

class Qcld_Tg_Helper {
    
    /**
     * Request
     *
     * @var object Qcld_Tg_Request
     */
    public $request = '';

    function __construct( Qcld_Tg_Request $request ) {
        $this->request = $request;
    }

    /**
     * Handle faq responses
     *
     * @param [type] $key
     * @param array $all_faqs
     * @return void
     */
    public function faq( $key, $all_faqs = array() ) {
        if ( $key == 'show_question' ){
            $faqjson = '';
            $all_faqs = maybe_unserialize( $this->get_option( 'support_query' ));
            $welcomefaqs = maybe_unserialize($this->get_option( 'qlcd_wp_chatbot_support_welcome' ));
            $welcomefaq = $welcomefaqs[array_rand( $welcomefaqs )];
            $fararray = array();
            foreach ( $all_faqs as $faq ) {
                $fararray[] = array( 'text' => $faq, 'callback_data' => $faq );
            }
            $keyboard = array(
                'inline_keyboard' => array(        
                    $fararray
                )
            );
            $encodedKeyboard = json_encode($keyboard);
            // text message only
            $parameters = array(
                'chat_id' => $this->request->chatID,
                'reply_markup' => $encodedKeyboard,
                'text' => $welcomefaq,
                "parseMode" => "html"
            );
            return $parameters;
        } else {

            $faqkey = array_search ($this->request->event, $all_faqs);
            $faqans = maybe_unserialize( $this->get_option( 'support_ans' ) );

            $parameters = array(
                "chat_id" => $this->request->chatID,
                "text" => $faqans[ $faqkey ],
                "parseMode" => "html"
            );
            return $parameters;
        }
    }


    public function getMenuItems() {
        $faqjson = '';
        
        $phonetextarray = maybe_unserialize( $this->get_option( 'qlcd_wp_chatbot_support_phone' ) );
        $phonetxt = ( $this->get_option( 'qlcd_wp_chatbot_support_phone' ) != '' ? $this->get_option( 'qlcd_wp_chatbot_support_phone' ) : esc_html__('Leave your number. We will call you back!', 'wpbot-telegram' ) );

        $custom_intents_array = array_filter( maybe_unserialize( $this->get_option( 'qlcd_wp_custon_intent_label' ) ) );
        
        $all_faqs = array(
        
            ( $this->get_option( 'qlcd_wp_email_subscription' ) != '' ? $this->get_option( 'qlcd_wp_email_subscription' ) : esc_html__('Email Subscription', 'wpbot-telegram' ) ),
            ( $this->get_option( 'qlcd_wp_chatbot_sys_key_support' ) != '' ? strtoupper( $this->get_option( 'qlcd_wp_chatbot_sys_key_support' ) ) : esc_html__('FAQ', 'wpbot-telegram' ) ),
            ( $this->get_option( 'qlcd_wp_send_us_email' ) != '' ? $this->get_option( 'qlcd_wp_send_us_email' ) : esc_html__('Send Us Email', 'wpbot-telegram' ) ),
            ( $this->get_option( 'qlcd_wp_leave_feedback' ) != '' ? $this->get_option( 'qlcd_wp_leave_feedback' ) : esc_html__('Leave a Feedback', 'wpbot-telegram' ) ),
            ( $this->get_option( 'qlcd_wp_site_search' )!='' ? $this->get_option( 'qlcd_wp_site_search' ) : esc_html__('Site Search', 'wpbot-telegram' ) ),
            $phonetxt
        );
        
        $all_faqs = array_merge($all_faqs, $custom_intents_array);
        
        if ( $this->get_option( 'qc_wpbot_tg_menu_order' ) && $this->get_option( 'qc_wpbot_tg_menu_order' ) != '' ) {
            $startmenu = stripslashes( $this->get_option( 'qc_wpbot_tg_menu_order' ) );
            preg_match_all( "/\<\w[^<>]*?\>([^<>]+?\<\/\w+?\>)?|\<\/\w+?\>/i", $startmenu, $matches );
            $newArray = array_map( function( $v ){
                return trim( strip_tags( $v ) );
            }, $matches[0] );
            $newArray = array_filter( $newArray );
            if ( ! empty( $newArray ) ){
                $all_faqs = $newArray;
            }
        } else {
            if ( $this->get_option( 'qc_wpbot_menu_order' ) && $this->get_option( 'qc_wpbot_menu_order' ) != '' ) {
                $startmenu = stripslashes( $this->get_option( 'qc_wpbot_menu_order' ) );
                preg_match_all( "/\<\w[^<>]*?\>([^<>]+?\<\/\w+?\>)?|\<\/\w+?\>/i", $startmenu, $matches );
                $newArray = array_map( function( $v ) {
                    return trim( strip_tags( $v ) );
                }, $matches[0] );
                $newArray = array_filter( $newArray );
                if ( ! empty( $newArray ) ) {
                    $all_faqs = $newArray;
                }
            }
        }
        if( get_option( 'tg_language_command' ) && get_option( 'tg_language_command' ) != '' ){
            $all_faqs[] = get_option( 'tg_language_command' );
        }

        return array_unique( $all_faqs );
    }

    /**
     * Menu for Telegram chatbot
     *
     * @return array
     */
    public function menu() {
        
        $msgtextoutput = esc_html__('I am here to find what you need. What are you looking for?', 'wpbot-telegram' );
        
        $default_msgs = $this->get_option( 'qlcd_wp_chatbot_wildcard_msg' );

        if( $default_msgs != '' ){
            $default_msgs = array_filter( maybe_unserialize( $default_msgs ) );
            if ( ! empty( $default_msgs ) ) {
                $msgtextoutput = $default_msgs[array_rand( $default_msgs )];
                $msgtextoutput = str_replace('%%username%%', $this->getFullName() , $msgtextoutput);
            }
        }
        
        //qc_wpbot_menu_order
        $all_faqs = array();
        if( get_option( 'tg_disable_start_menu' ) != '1' ){
            $all_faqs = $this->getMenuItems();
        }else{

            if( get_option( 'tg_language_command' ) && get_option( 'tg_language_command' ) != '' ){
                $all_faqs[] = get_option( 'tg_language_command' );
            }

        }

        if ( ! empty( $all_faqs ) ) {
            $encodedKeyboard = $this->buttons( $all_faqs );
            // text message only
            $parameters = array(
                'chat_id' => $this->request->chatID,
                'reply_markup' => $encodedKeyboard,
                'text' => $msgtextoutput,
                "parseMode" => "html"
            );
        } else {
            $parameters = array(
                "chat_id" => $this->request->chatID,
                "text" => $msgtextoutput,
                "parseMode" => "html"
            );
        }

        return $parameters;
        
    }

    public function buttons( $all_faqs ) {

        $multiarray = array();
        if( count( $all_faqs ) > 1 ) {

            while ( ! empty( $all_faqs ) ) {

                if ( count( $all_faqs ) > 1 ) {

                    $multiarray[] = array_slice( $all_faqs, 0, 1 );
                    unset( $all_faqs[0] );
                    
                    $all_faqs = array_values( $all_faqs );

                } else {

                    if ( ! empty( $all_faqs ) ) {

                        $multiarray[] = $all_faqs;
                        unset( $all_faqs );
                        $all_faqs = array();

                    }
                    
                }

            }

            $allmenus = array();
            foreach ( $multiarray as $all_faqs ) {
                $fararray = array();
                foreach ( $all_faqs as $faq ) {
                    $fararray[] = array( 'text' => $faq, 'callback_data' => $faq );
                }
                $allmenus[] = $fararray;
            }

        } else {

            $allmenus = array();
            $fararray = array();
            foreach ( $all_faqs as $faq ) {

                $fararray[] = array( 'text' => $faq, 'callback_data' => $faq );

            }
            $allmenus[] = $fararray;

        }
        
        $keyboard = array(
            'inline_keyboard' => $allmenus
        );
        return $encodedKeyboard = json_encode( $keyboard );
        
    }

    /**
     * Leave your number. We will call you back! - Intent
     * Multi step function
     * 
     * @param int $step [1, 2]
     * @return array
     */
    public function callmeback( $step = 1 ) {
        if ( $step == 1 ) {
            $this->set_transient( '_tg_phone', 1 );
            $texts = maybe_unserialize( $this->get_option( 'qlcd_wp_chatbot_asking_phone' ) );
            $texts = $texts[array_rand($texts)];
            $parameters = array(
                "chat_id" => $this->request->chatID,
                "text" => $texts,
                "parseMode" => "html"
            );
            return $parameters;
        } elseif ( $step == 2 ) {
            $this->delete_transient( '_tg_phone' );
            $text = ( $this->get_option('qlcd_wp_chatbot_phone_sent' ) != '' ? $this->get_option('qlcd_wp_chatbot_phone_sent') : esc_html__('Thanks for your phone number. We will call you ASAP!', 'wpbot-telegram' ) );
            $parameters = array(
                "chat_id" => $this->request->chatID,
                "text" => $text,
                "parseMode" => "html"
            );
            return $parameters;
        }

    }

    /**
     * Send Us Email, Leave A Feedback intent
     *
     * @param integer $step
     * @return array $parameters
     */
    function sendUsEmail( $step = 1 ) {

        if ( $step == 1 ) {

            $this->set_transient( '_tg_feedback', 1 );
            $texts = maybe_unserialize( $this->get_option( 'qlcd_wp_chatbot_asking_email' ) );
            $text = $texts[array_rand( $texts )];

        } elseif ( $step == 2 ) {

            $this->set_transient( '_tg_feedback', 2);
            $texts = maybe_unserialize( $this->get_option( 'qlcd_wp_chatbot_asking_msg' ) );
            $text = $texts[array_rand( $texts )];

        } elseif ( $step == 3 ) {

            $this->delete_transient( '_tg_feedback' );
            $name                   = $this->getFullName();
            $email                  = $this->get_transient( '_tg_feedback_email' );
            $message                = $this->get_transient( '_tg_feedback_msg' );
            $subject                = esc_html__('Feedback from WPBot by Client', 'wpbot-telegram' );

            //Extract Domain
            $url                    = get_site_url();
            $url                    = parse_url( $url );
            $domain                 = $url['host'];
            $admin_email            = $this->get_option( 'admin_email' );
            $toEmail                = $this->get_option( 'qlcd_wp_chatbot_admin_email' ) != '' ? $this->get_option( 'qlcd_wp_chatbot_admin_email' ) : $admin_email;
            $fromEmail              = "wordpress@" . $domain;
            //Starting messaging and status.
            $response['status']     = 'fail';
            $response['message']    = esc_html( str_replace( '\\', '', $this->get_option( 'qlcd_wp_chatbot_email_fail' ) ) );
        
            //build email body
            $bodyContent            = "";
            $bodyContent           .= '<p><strong>' . esc_html__( 'Feedback Details', 'wpchatbot' ) . ':</strong></p><hr>';
            $bodyContent           .= '<p>' . esc_html__( 'Name', 'wpfb' ) . ' : ' . esc_html( $name ) . '</p>';
            $bodyContent           .= '<p>' . esc_html__( 'Email', 'wpfb' ) . ' : ' . esc_html( $email ) . '</p>';
            $bodyContent           .= '<p>' . esc_html__( 'Message', 'wpfb' ) . ' : ' . esc_html( $message ) . '</p>';
            
                
            $bodyContent           .= '<p>' . esc_html__( 'Mail Generated on', 'wpchatbot' ) . ': ' . date( 'F j, Y, g:i a' ) . '</p>';
            $to                     = $toEmail;
            $body                   = $bodyContent;
        
            $headers                = array();
            $headers[]              = 'Content-Type: text/html; charset=UTF-8';
            $headers[]              = 'From: ' . esc_html( $name ) . ' <' . esc_html( $fromEmail ) . '>';
        
            wp_mail( $to, $subject, $body, $headers );
            $this->delete_transient( '_tg_feedback_email' );
            $this->delete_transient( '_tg_feedback_msg' );
            $text = ( $this->get_option( 'qlcd_wp_chatbot_email_sent' ) != '' ? $this->get_option( 'qlcd_wp_chatbot_email_sent' ) : esc_html__('Your email was sent successfully. Thanks!', 'wpbot-telegram' ) );

        }

        $parameters = array(
            "chat_id" => $this->request->chatID,
            "text" => $text,
            "parseMode" => "html"
        );

        return $parameters;
    }

    public function findStrResponses() {

        $responses = wpbot_tg_find_response_by_keyword( $this->request->message, $this->currentLanguage() );
        if ( $responses['status'] == 'success' ) {
            $data = $responses['data'];
            if ( $responses['multiple'] ) {
                //multiple

                $responses = array();
                foreach ( $data as $response ) {
                    $responses[] = $response['query'];
                }
                $encodedKeyboard = $this->buttons( $responses );
                // text message only
                $parameters = array(
                    'chat_id' => $this->request->chatID,
                    'reply_markup' => $encodedKeyboard,
                    'text' => esc_html__( 'Please choose from below', 'wpbot-telegram' ),
                    "parse_mode" => "html"
                );
                $this->send( 'sendMessage', $parameters );

            } else {
                //single
                $parameters = array(
                    "chat_id" => $this->request->chatID,
                    "text" => $data[0]['response'],
                    "parse_mode" => "html"
                );
                $this->send( 'sendMessage', $parameters );
                if( $data[0]['followup'] != '' ){
                    $parameters = array(
                        "chat_id" => $this->request->chatID,
                        "text" => $data[0]['followup'],
                        "parse_mode" => "html"
                    );
                    $this->send( 'sendMessage', $parameters );
                }
                exit;
            }
        }
        
    }

    /**
     * Handle form builder response
     * 
     * @param int $formid
     *
     * @return void
     */
    public function handle_formbuilder_response( $get_formidby_keyword ) {
        
        $this->set_transient( '_tg_conversational_form', 'active' );
        $this->set_transient( '_tg_conversational_form_id', $get_formidby_keyword);
        
        $formresponse = qcwpbot_tg_get_form( $get_formidby_keyword, $this->request->chatID );
        
        $fieldid = $formresponse['ID'];
        $formtype = $formresponse['type'];
        $formlabel = $formresponse['label'];
        $this->set_transient( '_tg_conversational_field_id', $fieldid );
        $this->set_transient( '_tg_conversational_field_entry', 0 );
        
        if($formtype=='dropdown' || $formtype=='checkbox'){
            
            $fieldoptions = $formresponse['config']['option'];
            $all_faqs = array();
            foreach($fieldoptions as $fieldoption){
                $all_faqs[] = $fieldoption['value'];
            }
            
            $encodedKeyboard = $this->buttons( $all_faqs );
            // text message only
            $parameters = array(
                'chat_id' => $this->request->chatID,
                'reply_markup' => $encodedKeyboard,
                'text' => $formlabel,
                "parse_mode" => "html"
            );
            $this->send( 'sendMessage', $parameters );
            exit;

            
        }elseif($formtype=='html'){
            $formlabel = $formresponse['config']['default'];
            $parameters = array(
                "chat_id" => $this->request->chatID,
                "text" => $formlabel,
                "parse_mode" => "html"
            );
            $this->send( 'sendMessage', $parameters );
            qcld_tg_handle_cfb_next( '', $this );
            
        }else{
            $parameters = array(
                "chat_id" => $this->request->chatID,
                "text" => $formlabel,
                "parse_mode" => "html"
            );
            $this->send( 'sendMessage', $parameters );
            
        }

    }

    /**
     * Handle dialogflow responses
     *
     * @return void
     */
    public function handle_dialogflow() {

        $query = $this->request->message;
        
        if($query!=''){

            $result = qcld_df_v2_api_tg($query, $this);
    
            $result = json_decode($result, true);
            if(isset($result['queryResult']) && !empty($result['queryResult'])){
            
                $intent = $result['queryResult']['intent']['displayName'];
                if($intent=='Default Fallback Intent'){
                        
                        $all_faqs = $this->getMenuItems();
                        $encodedKeyboard = $this->buttons( $all_faqs );
                        // text message only
                        $parameters = array(
                            'chat_id' => $this->request->chatID,
                            'reply_markup' => $encodedKeyboard,
                            'text' => $this->get_option('qlcd_wp_chatbot_dialogflow_defualt_reply'),
                            "parseMode" => "html"
                        );

                        $this->send( 'sendMessage', $parameters );
                        exit;
    
                }elseif($intent=='faq'){

                    $parameters = $this->faq( 'show_question' );
                    $this->send( 'sendMessage', $parameters );
                    exit;

                }elseif($intent=='email'){

                    $parameters = $this->sendUsEmail();
                    $this->send( 'sendMessage', $parameters );
                    exit;

                }elseif($intent=='phone'){

                    $parameters = $this->callmeback();
                    $this->send( 'sendMessage', $parameters );
                    exit;

                }elseif(isset($result['queryResult']['fulfillmentMessages']) && !empty($result['queryResult']['fulfillmentMessages'])){
                    
                    $dfmessages = $result['queryResult']['fulfillmentMessages'];
                    foreach($dfmessages as $key => $message){
                        
                        if(isset($message['text'])){
                            //text response

                            $parameters = array(
                                "chat_id" => $this->request->chatID,
                                "text" => $message['text']['text'][0],
                                "parse_mode" => "html"
                            );
                            $this->send( 'sendMessage', $parameters );
                            
                        }elseif(isset($message['quickReplies'])){
                            //quick replies
                            
                            $title = strip_tags($message['quickReplies']['title']);
                            $replies = $message['quickReplies']['quickReplies'];
                            $encodedKeyboard = $this->buttons( $replies );
                            // text message only
                            $parameters = array(
                                'chat_id' => $this->request->chatID,
                                'reply_markup' => $encodedKeyboard,
                                'text' => $title,
                                "parseMode" => "html"
                            );
                            $this->send( 'sendMessage', $parameters );
                            
                        }elseif(isset($message['card'])){
                            
                            $parameters = array(
                                "chat_id" => $this->request->chatID,
                                "text" => esc_html__( 'Sorry, Card response not supported yet.', 'wpbot-telegram' ),
                                "parse_mode" => "html"
                            );
                            $this->send( 'sendMessage', $parameters );
                            
                        }
                        
                    }
    
                }
            }else{
    
                $all_faqs = $this->getMenuItems();
                $encodedKeyboard = $this->buttons( $all_faqs );
                // text message only
                $parameters = array(
                    'chat_id' => $this->request->chatID,
                    'reply_markup' => $encodedKeyboard,
                    'text' => $this->get_option('qlcd_wp_chatbot_dialogflow_defualt_reply'),
                    "parseMode" => "html"
                );
                $this->send( 'sendMessage', $parameters );
                exit;
                
            }
    
        }

    }

    /**
     * Get users Full Name
     *
     * @return string $fullname
     */
    public function getFullName() {
        return trim( $this->request->first_name.' '.$this->request->last_name );
    }

    /**
     * Send message to Telegram user
     *
     * @param string $method
     * @param array $data
     * @return string $output
     */
    public function send( $method, $data ) {
        $bot_token = get_option( 'tg_access_token' );
        $url = "https://api.telegram.org/bot$bot_token/$method";

        $response = wp_remote_post( $url, array(
            'body'    => $data,
            
        ) );
        return $response;

    }

    /**
     * Get current language
     *
     * @return string
     */
    public function currentLanguage() {
        if( $this->get_transient( '_tg_lang' ) && $this->get_transient( '_tg_lang' ) != '' ){
            $lancode = $this->get_transient( '_tg_lang' );
        } else {
            $lancode = function_exists( 'get_wpbot_locale' ) ? get_wpbot_locale() : 'en_US';
        }
        return $lancode;
    }

    /**
     * Get Option
     *
     * @param string $key
     * @return array|string $option
     */
    public function get_option($key) {
        $options = get_option( $key );
        
        if( $options ){
            $options = maybe_unserialize( $options );
            if( is_array( $options ) ){
                if ( isset( $options[$this->currentLanguage()] ) ) {
    
                    return $options[$this->currentLanguage()];
    
                } else {
                    return $options;
                }
            } else {
                return $options;
            }
    
        } else {
            return $options;
        }
    }

    /**
     * Delete transient
     *
     * @param string $key
     * @return void
     */
    public function delete_transient( $key ) {
        delete_transient( $this->request->chatID.$key );
    }

    /**
     * Get transient
     *
     * @param string $transient
     * @return void
     */
    public function get_transient( $key ) {
        return get_transient( $this->request->chatID.$key );
    }

    /**
     * Set Transient
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set_transient( $key, $value ) {
        set_transient( $this->request->chatID.$key, $value, 12 * HOUR_IN_SECONDS );
    }

    /**
     * Remove all transient that are being set during conversation
     *
     * @return void
     */
    public function clear_all_transient() {

        $this->delete_transient( '_tg_feedback' );
        $this->delete_transient( '_tg_feedback_email' );
        $this->delete_transient( '_tg_feedback_msg' );
        $this->delete_transient( '_tg_phone' );
        $this->delete_transient( '_tg_sitesearch' );
        $this->delete_transient( '_tg_subscription' );
        $this->delete_transient( '_tg_conversational_field_entry' );
        $this->delete_transient( '_tg_conversational_field_id' );
        $this->delete_transient( '_tg_conversational_form_id' );
        $this->delete_transient( '_tg_conversational_form' );

    }

}
