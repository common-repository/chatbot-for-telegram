<?php

$helper = new stdClass();
/**
 * Find Response by Keyword
 *
 * @return void
 */
function wpbot_tg_find_response_by_keyword( $keyword, $language ) {

    global $wpdb;
	$table = $wpdb->prefix.'wpbot_response';
	

	$response_result = array();

	$status = array( 'status' => 'fail', 'multiple' => false );
	

	if ( empty( $response_result ) ) {
		$results = $wpdb->get_results( "SELECT `query`, `response`, `custom` FROM `$table` WHERE 1 and `query` = '".$keyword."'" );
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				
				$response_result[] = array( 'query' => $result->query, 'response' => $result->response, 'followup' => $result->custom, 'score' => 1 );
				
			}
		}
	}
	

	if ( empty( $response_result ) ) {

		$results = $wpdb->get_results( "SELECT * FROM `$table` WHERE 1 and ( CONCAT(',', keyword, ',') like '%,". $keyword .",%' or CONCAT(',', keyword, ',') like '%, ". $keyword .",%' or CONCAT(',', keyword, ',') like '%". $keyword .",%' or CONCAT(',', keyword, ',') like '%, ". $keyword ."%' or CONCAT(',', keyword, ',') like '%,". $keyword ."%' ) " );
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				
				$response_result[] = array( 'query' => $result->query, 'response'=> $result->response, 'followup' => $result->custom, 'score' => 1 );
				
			}
		}
	}
	
	if ( empty( $response_result ) ) {
		$results = $wpdb->get_results( "SELECT * FROM `$table` WHERE `query` REGEXP '".$keyword."' " );
		$weight = get_option( 'qc_bot_str_weight' ) != '' ? get_option( 'qc_bot_str_weight' ) : '0.4';
		
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
			
				$response_result[] = array( 'query' => $result->query, 'response' => $result->response, 'followup' => $result->custom, 'score' => 1 );
				
			}
		}
	}

	if ( class_exists( 'Qcld_str_pro' ) ) {
		if ( get_option( 'qc_bot_str_remove_stopwords' ) && get_option( 'qc_bot_str_remove_stopwords' ) == 1 ) {
			$keyword = qc_strpro_remove_stopwords( $keyword );
		}
	}
	
	
	if ( empty( $response_result ) ) {
		$keyword = qc_strpro_remove_stopwords( $keyword );
		$fields = get_option( 'qc_bot_str_fields' );
		if ( $fields && ! empty( $fields ) && class_exists( 'Qcld_str_pro' ) ) {
			$qfields = implode( ', ', $fields );
		} else {
			$qfields = '`query`,`keyword`';
		}

		$results = $wpdb->get_results( "SELECT `query`, `response`, `custom`, MATCH($qfields) AGAINST('".$keyword."' IN NATURAL LANGUAGE MODE) as score FROM $table WHERE MATCH($qfields) AGAINST('".$keyword."' IN NATURAL LANGUAGE MODE) order by score desc limit 15" );

		$weight = get_option( 'qc_bot_str_weight' ) != '' ? get_option( 'qc_bot_str_weight' ) : '0.4';
		//$weight = 0;
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( $result->score >= $weight ) {
					$response_result[] = array( 'query' => $result->query, 'response' => $result->response, 'followup' => $result->custom, 'score' => $result->score );
				}
			}

			if ( empty( $response_result ) ) {
				if ( ! empty( $results ) ) {
					foreach ( $results as $result ) {
	
						$score_array = str_split( $result->score );
						$score_int = 0;
						foreach ( $score_array as $score ) {
							
							if ( $score != '.' && $score != '0' ) {
								$score_int = (int)$score;
								break;
							}
						}
						$main_score = $result->score;
						if ( $score_int > 0 ) {
							$main_score = '0.'.$score_int;
						}
						if ( $main_score >= $weight ) {
							$response_result[] = array( 'query' => $result->query, 'response' => $result->response, 'followup' => $result->custom, 'score' => $result->score);
						}
					}
				}
	
			}


		}
		
	}


	if ( ! empty( $response_result ) ) {

		if ( count( $response_result ) > 1 ) {
			$status = array( 'status' => 'success', 'multiple' => true, 'data' => $response_result );
		} else {
			$status = array( 'status' => 'success', 'multiple' => false, 'data' => $response_result );
		}

	}
	
    return $status;
}

/**
 * find conversational form ID
 *
 * @param [type] $ccommands
 * @param [type] $cforms
 * @param [type] $cformid
 * @param [type] $message
 * @return void
 */
function qcld_tg_findformid( $ccommands, $cforms, $cformid, $message ) {
	
	$gformid = 'None';
	
	if(!empty($ccommands)){
		
		foreach($ccommands as $key=>$val){
			
			if (in_array($message, $val)){
				return $cformid[$key];
			}
			
		}
		
	}
	if(!empty($cforms)){
		
		foreach($cforms as $key=>$val){
			if($val==$message){
				return $cformid[$key];
			}
		}
		
	}
	return $gformid;
}

/**
 * str to lower recursively
 *
 * @param string $value
 * @return string
 */
function qcld_tg_nestedLowercase($value) {
    if (is_array($value)) {
        return array_map('qcld_tg_nestedLowercase', $value);
    }
    return strtolower($value);
}

/**
 * Get form first field by id
 *
 * @param int $formid
 * @param int $chatid
 * @return void
 */
function qcwpbot_tg_get_form($formid, $sender){
    global $wpdb;

    $formid = sanitize_text_field($formid);
    $session = sanitize_text_field($sender);

    $result = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix."wfb_forms where form_id='".$formid."' and type='primary'");

    $form = maybe_unserialize($result->config);
    qcbot_conv_cookies_data_delete_tg( $formid.'_'.$session.'_data' );
    qcbot_conv_cookies_tg( $formid.'_'.$session, json_encode( $result ) );
    $fields = $form['fields'];
    //print_r($form['layout_grid']['fields']);exit;
    if(isset($form['layout_grid']['fields']) && !empty($form['layout_grid']['fields'])){
        
        $firstfield = qc_get_first_field($form['layout_grid']['fields']);
        $field = $fields[$firstfield];
        return $field;
    }
    
}


function qcld_get_details_by_fieldid_tg($form, $fieldid){

    $fields = $form['fields'];
    if(isset($fields[$fieldid])){
        return $fields[$fieldid];
    }else{
        return array();
    }

}


function qcwpbot_capture_form_value_tg($formid, $fieldid, $answer, $entry, $helper){
    global $wpdb;

    $formid = sanitize_text_field($formid);
    $fieldid = sanitize_text_field($fieldid);
    $answer = $answer;
    $entry = sanitize_text_field($entry);

    $result = qcbot_conv_cookies_get_tg( $formid.'_'.$helper->request->chatID, $helper );
	
    if( ! $result || empty( $result ) ){
        $result = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix."wfb_forms where form_id='".$formid."' and type='primary'");
    } else {
		$result = json_decode( $result );
	}
	
    $form = maybe_unserialize( $result->config );
    $processors = (isset($form['processors'])?$form['processors']:array());
    
    $mailer = (isset($form['mailer'])?$form['mailer']:array());
    
    $variables = isset($form['variables'])?$form['variables']:array();

    $fieldetails = qcld_get_details_by_fieldid_tg($form, $fieldid);
    
    if($answer!=''){
        $data = array();
        if($fieldetails['type']=='file'){
            
            $answers = explode(',', $answer);
            
            foreach($answers as $answer){
                $data[] = array(
                    'entry_id'  => $entry,
                    'field_id'   => $fieldid,
                    'slug'	=> (isset($fieldetails['slug'])?$fieldetails['slug']:''),
                    'value'	=> stripslashes($answer)
                );
            }
            qcbot_conv_cookies_data_set_tg( $formid.'_'.$helper->request->chatID.'_data', $data, $helper );
        }else{
            $data[] = array(
                'entry_id'  => $entry,
                'field_id'   => $fieldid,
                'slug'	=> (isset($fieldetails['slug'])?$fieldetails['slug']:''),
                'value'	=> stripslashes($answer)
            );
			
            qcbot_conv_cookies_data_set_tg( $formid.'_'.$helper->request->chatID.'_data', $data, $helper );
        }
        
    }
    $fields = $form['fields'];
    $conditions = array();
    if(isset($form['conditional_groups']['conditions'])){
        $conditions = $form['conditional_groups']['conditions'];
    }
    
    
    if(isset($form['layout_grid']['fields']) && !empty($form['layout_grid']['fields'])){
        
        $nextfield = qc_get_next_field($form, $fieldid, $entry, $helper->request->chatID);
        
        if($nextfield!='none' && $nextfield!='' && !empty($fields[$nextfield])){
            
            $field = $fields[$nextfield];
            $field = qcld_check_field_variables($form, $field, $variables, $entry, $helper->request->chatID);
            $field['entry'] = $entry;
            $field['status'] = 'incomplete';
            if($field['type']=='calculation'){
                $field = qcld_formbuilder_do_calculation($field, $entry, $form, $helper->request->chatID);
            }else if( $field['type']=='html' ){
                $field['config']['default'] =  $field['config']['default'];
            }
            
            return $field;

        }else{
            
            if(isset($mailer['on_insert']) && $mailer['on_insert']==1){
                $answers = qc_form_answer($form, $fields, $entry, $helper->request->chatID);
                if( !empty( $answers ) ){
                    qcld_wb_chatbot_send_form_query($answers, $mailer, $formid , $helper->request->chatID);
                }
                
            }
            
            if(!empty($processors) && isset($processors[qcld_array_key_first($processors)]['runtimes'])){
                $entrydetails = qcld_form_entry_details($form, $fields, $entry, $helper->request->chatID);
                qcld_wb_chatbot_send_autoresponse($entrydetails, $processors, $formid, $helper->request->chatID);
            }
            
            $wpdb->insert(
                $wpdb->prefix."wfb_form_entries",
                array(
                    'datestamp'  => current_time( 'mysql', 1 ),
                    'user_id'   => 0,
                    'form_id'	=> $formid,
                    'status'	=> 'active'
                )
            );

            $entry = $wpdb->insert_id;

            $all_answers = qcbot_conv_cookies_data_get_tg( $formid.'_'.$helper->request->chatID.'_data' );
            
            if( $all_answers && ! empty( $all_answers ) ){
                foreach( $all_answers as $answer ){
                    
                    $table      = $wpdb->prefix . 'wfb_form_entry_values';
                    $valuecheck = $wpdb->get_results( "SELECT * FROM `$table` WHERE 1 and `entry_id` = '" . $entry . "' and `field_id` = '" . $answer->field_id . "'" );
                    if( empty( $valuecheck ) ){
                        $wpdb->insert(
                            $table,
                            array(
                                'entry_id' => $entry,
                                'field_id' => $answer->field_id,
                                'slug'     => ( $answer->slug ),
                                'value'    => stripslashes( $answer->value ),
                            )
                        );
                    }else{
                        $data = array('value'=> stripslashes( $answer->value ));
                        $where = array('entry_id'=>$entry, 'field_id'=> $answer->field_id);
                        $whereformat = array('%d', '%s');
                        $format = array('%s');
                        $wpdb->update( $table, $data, $where, $format, $whereformat );
                        
                    }

                }
            }
            
            return array('status'=>'complete');
        }
        
    }else{

        if(isset($mailer['on_insert']) && $mailer['on_insert']==1){
            $answers = qc_form_answer($form, $fields, $entry, $helper->request->chatID);
            qcld_wb_chatbot_send_form_query($answers, $mailer, $formid, $helper->request->chatID);
        }

        if(!empty($processors) && isset($processors[qcld_array_key_first($processors)]['runtimes'])){
            $entrydetails = qcld_form_entry_details($form, $fields, $entry, $helper->request->chatID);
            qcld_wb_chatbot_send_autoresponse($entrydetails, $processors, $formid, $helper->request->chatID);
        }
        
        $wpdb->insert(
            $wpdb->prefix."wfb_form_entries",
            array(
                'datestamp'  => current_time( 'mysql', 1 ),
                'user_id'   => 0,
                'form_id'	=> $formid,
                'status'	=> 'active'
            )
        );

        $entry = $wpdb->insert_id;
        $all_answers = qcbot_conv_cookies_data_get_tg( $formid.'_'.$helper->request->chatID.'_data' );
        if( $all_answers && ! empty( $all_answers ) ){
            foreach( $all_answers as $answer ){
                
                $wpdb->insert(
                    $wpdb->prefix."wfb_form_entry_values",
                    array(
                        'entry_id'  => $entry,
                        'field_id'   => $answer->field_id,
                        'slug'	=> ($answer->slug),
                        'value'	=> stripslashes($answer->value)
                    )
                );

            }
        }
        qcbot_conv_cookies_data_delete_tg( $formid.'_'.$helper->request->chatID.'_data' );
        qcbot_conv_cookies_data_delete_tg( $formid.'_'.$helper->request->chatID );
        
        return array('status'=>'complete');
    }
    
    die();
}


/**
 * Handle next field
 *
 * @param string $answer
 * @param object $helper
 * @return void
 */
function qcld_tg_handle_cfb_next( $answer='', $helper ) {
	
	$formid = $helper->get_transient( '_tg_conversational_form_id' );
	$fieldid = $helper->get_transient( '_tg_conversational_field_id' );
	$entry = $helper->get_transient( '_tg_conversational_field_entry' );
	
	$formresponse = qcwpbot_capture_form_value_tg( $formid, $fieldid, $answer, $entry, $helper );
	
	if( $answer != '' ){
		$ccommands = array_map( 'qcld_tg_nestedLowercase', qcld_get_formbuilder_form_commands() );
		$cformid = qcld_get_formbuilder_form_ids();
		$cforms = array_map('qcld_tg_nestedLowercase', qcld_get_formbuilder_forms());
		$get_formidby_keyword = qcld_tg_findformid($ccommands, $cforms, $cformid, strtolower($answer));
		if(!empty($cformid) && in_array($get_formidby_keyword, $cformid)){
			$helper->delete_transient( '_tg_conversational_field_entry' );
			$helper->delete_transient( '_tg_conversational_field_id' );
			$helper->delete_transient( '_tg_conversational_form_id' );
			$helper->delete_transient( '_tg_conversational_form' );
            $helper->handle_formbuilder_response( $get_formidby_keyword );
			exit;
		}
	}

	if($formresponse['status']=='incomplete'){

		$helper->set_transient( '_tg_conversational_field_entry', $formresponse['entry']);
		$helper->set_transient( '_tg_conversational_field_id', $formresponse['ID']);
		
		$formtype = $formresponse['type'];
		$formlabel = $formresponse['label'];
		
		if($formtype=='dropdown' || $formtype=='checkbox'){			
			$fieldoptions = $formresponse['config']['option'];
			$all_faqs = array();
			foreach($fieldoptions as $fieldoption){
				$all_faqs[] = $fieldoption['value'];
			}
			
			$encodedKeyboard = $helper->buttons( $all_faqs );
            // text message only
            $parameters = array(
                'chat_id' => $helper->request->chatID,
                'reply_markup' => $encodedKeyboard,
                'text' => $formlabel,
                "parse_mode" => "html"
            );
            $helper->send( 'sendMessage', $parameters );
            exit;
			
			
		}elseif($formtype=='html'){
			$formlabel = $formresponse['config']['default'];
			$parameters = array(
                "chat_id" => $helper->request->chatID,
                "text" => $formlabel,
                "parse_mode" => "html"
            );
            $helper->send( 'sendMessage', $parameters );
            qcld_tg_handle_cfb_next( '', $helper );

			
			
		}elseif($formtype=='calculation'){
			
			$formlabel = $formresponse['calresult'];
			$parameters = array(
                "chat_id" => $helper->request->chatID,
                "text" => $formlabel,
                "parse_mode" => "html"
            );
            $helper->send( 'sendMessage', $parameters );
            qcld_tg_handle_cfb_next( $formresponse['calvalue'], $helper );
			
		}elseif($formtype=='hidden'){
            qcld_tg_handle_cfb_next( $formresponse['config']['default'], $helper );
			
		}else{
			
            $parameters = array(
                "chat_id" => $helper->request->chatID,
                "text" => $formlabel,
                "parse_mode" => "html"
            );
            $helper->send( 'sendMessage', $parameters );
			exit;
		}
		
		
	}else{
		$helper->delete_transient( '_tg_conversational_field_entry');
		$helper->delete_transient( '_tg_conversational_field_id');
		$helper->delete_transient( '_tg_conversational_form_id');
		$helper->delete_transient( '_tg_conversational_form');
		exit;
	}
	
}

function qcpd_remove_tg_stopwords($query, $stopwords){
	return preg_replace('/\b('.implode('|',$stopwords).')\b/','',$query);
}

function qcld_df_v2_api_tg($query, $helper ){
	
	$session_id = 'asd2342sde';
    $language = $helper->get_option('qlcd_wp_chatbot_dialogflow_agent_language');
    //project ID
    $project_ID = $helper->get_option('qlcd_wp_chatbot_dialogflow_project_id');
    // Service Account Key json file
    $JsonFileContents = $helper->get_option('qlcd_wp_chatbot_dialogflow_project_key');
    if($project_ID==''){
        return json_encode(array('error'=>'Project ID is empty'));
    }
    if($JsonFileContents==''){
        return json_encode(array('error'=>'Key is empty'));
    }
    if( $query==''){
        return json_encode(array('error'=>'Query text is not added!'));
    }
    $query = sanitize_text_field($query);
    if(isset($_POST['sessionid']) && $_POST['sessionid']!=''){
        $session_id = sanitize_text_field($_POST['sessionid']);
    }
    

    if(file_exists(QCLD_wpCHATBOT_GC_DIRNAME.'/autoload.php')){

        require(QCLD_wpCHATBOT_GC_DIRNAME.'/autoload.php');

        $client = new \Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->setScopes (['https://www.googleapis.com/auth/dialogflow']);
        // Convert to array 
        $array = json_decode($JsonFileContents, true);
        $client->setAuthConfig($array);

        try {
            $httpClient = $client->authorize();
            $apiUrl = "https://dialogflow.googleapis.com/v2/projects/{$project_ID}/agent/sessions/{$session_id}:detectIntent";

            $response = $httpClient->request('POST', $apiUrl, [
                'json' => ['queryInput' => ['text' => ['text' => $query, 'languageCode' => $language]],
                    'queryParams' => ['timeZone' => '']]
            ]);
            
            $contents = $response->getBody()->getContents();
            return $contents;

        }catch(Exception $e) {
            return json_encode(array('error'=>$e->getMessage()));
        }

    }else{
        return json_encode(array('error'=>'API client not found'));
    }
	
}
if ( ! function_exists( 'qcld_get_formbuilder_form_commands' ) ) {
    function qcld_get_formbuilder_form_commands(){
        global $wpdb;
        $command = array();
        if(class_exists('Qcformbuilder_Forms_Admin')){
            $results = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix."wfb_forms where 1 and type='primary'");
            if(!empty($results)){
                foreach($results as $result){
                    $form = maybe_unserialize($result->config);
                    
                    if(isset($form['command'])){
                        $command[] = array_map('trim', explode(',', strtolower($form['command'])));
                    } 
                    
                }
                return $command;
            }else{
                return array();   
            }
        }else{
            return array();
        }
    }
}

if ( ! function_exists( 'qcld_get_formbuilder_form_ids' ) ) {
    function qcld_get_formbuilder_form_ids(){
        global $wpdb;
        $forms = array();
        if(class_exists('Qcformbuilder_Forms_Admin')){
            $results = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix."wfb_forms where 1 and type='primary'");
            if(!empty($results)){
                foreach($results as $result){
                    $form = maybe_unserialize($result->config);
                    $forms[] = trim($form['ID']);
                }
                return $forms;
            }else{
                return array();   
            }
        }else{
            return array();
        }
    }
}

if ( ! function_exists( 'qcld_get_formbuilder_forms' ) ) {
    function qcld_get_formbuilder_forms(){
        global $wpdb;
        $forms = array();
        if(class_exists('Qcformbuilder_Forms_Admin')){
            $results = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix."wfb_forms where 1 and type='primary'");
            if(!empty($results)){
                foreach($results as $result){
                    $form = maybe_unserialize($result->config);
                    $forms[] = trim($form['name']);
                }
                return $forms;
            }else{
                return array();   
            }
        }else{
            return array();
        }
    }
}

if ( ! function_exists( 'qcbot_conv_cookies_get_tg' ) ) {
    function qcbot_conv_cookies_get_tg( $form_id, $helper ){
        return $helper->get_transient( $form_id );
    }
}

if ( ! function_exists( 'qcbot_conv_cookies_data_set_tg' ) ) {
    function qcbot_conv_cookies_data_set_tg( $form_id, $data, $helper ){
        $existing_data = $helper->get_transient( $form_id );
        if( $existing_data && ! empty( $existing_data ) && is_array( $existing_data ) ){
            $data = array_merge( $data, $existing_data );
        }
        $helper->set_transient( $form_id, $data );
    }
}

if ( ! function_exists( 'qcbot_conv_cookies_data_get_tg' ) ) {
    function qcbot_conv_cookies_data_get_tg( $form_id ){
        global $helper;
        return $helper->get_transient( $form_id );
    }
}

if ( ! function_exists( 'qcbot_conv_cookies_data_delete_tg' ) ) {
    function qcbot_conv_cookies_data_delete_tg( $form_id ){
        global $helper;
        $helper->delete_transient( $form_id );
    }
}

if ( ! function_exists( 'qcbot_conv_cookies_tg' ) ) {
    function qcbot_conv_cookies_tg( $form_id, $data ) {
        global $helper;
        $helper->set_transient( $form_id, $data );
    }
}



if(!function_exists('qcld_check_field_variables')){
	function qcld_check_field_variables($form, $field, $variables, $entry, $session){
		global $wpdb;
		$formid = $form['ID'];
		if(isset($variables['keys'])){
			
			if($field['type']=='html'){

				foreach($variables['keys'] as $key=>$val){
					if (strpos($field['config']['default'], '%'.$val.'%') !== false) {
		
						$repval = trim(str_replace('%','', $variables['values'][$key]));
		
						//$result = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix."wfb_form_entry_values where entry_id='".$entry."' and slug='".$repval."'");

						$result = array();
						$all_answers = qcbot_conv_cookies_data_get_tg( $formid.'_'.$session.'_data' );
						if( $all_answers && ! empty( $all_answers ) ){
							foreach( $all_answers as $answer ){
								if( $answer->slug == $repval ){
									$result = ($answer);
									break;
								}
							}
						}


						if(!empty($result)){
							$field['config']['default'] = str_replace('%'.$val.'%', $result->value, $field['config']['default']);
						}
					}
				}

			}else{
				foreach($variables['keys'] as $key=>$val){
					if (strpos($field['label'], '%'.$val.'%') !== false) {
						
						$repval = trim(str_replace('%','', $variables['values'][$key]));
		
						//$result = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix."wfb_form_entry_values where entry_id='".$entry."' and slug='".$repval."'");
						
						$result = array();
						$all_answers = qcbot_conv_cookies_data_get_tg( $formid.'_'.$session.'_data' );
						
						if( $all_answers && ! empty( $all_answers ) ){
							
							foreach( $all_answers as $answer ){
								
								if( $answer->slug == $repval ){
									
									$result = $answer;
									break;
								}
							}
							
						}

						if(!empty($result)){
							$field['label'] = str_replace('%'.$val.'%', $result->value, $field['label']);
						}
					}
				}
			}



		}

		return $field;

	}
}


if(!function_exists('qcld_formbuilder_do_calculation')){
	function qcld_formbuilder_do_calculation($field, $entry, $form, $session){
		global $wpdb;
		
		
		$formid = $form['ID'];
		$calfieldids = array();
		$calgroups = $field['config']['config']['group'];
		$formular = $field['config']['formular'];
		
		foreach($calgroups as $calgroup){
			
			if(isset($calgroup['lines']) && !empty($calgroup['lines'])){
				
				foreach($calgroup['lines'] as $line){
					if(isset($line['field']) && $line['field']!=''){
						$calfieldids[] = $line['field'];
					}					
				}
				
			}
			
		}
		
		
		if(!empty($calfieldids)){
			
			$results = array();
			$all_answers = qcbot_conv_cookies_data_get_tg( $formid.'_'.$session.'_data' );
			if( $all_answers && ! empty( $all_answers ) ){
				if( !empty( $calfieldids ) ){
					foreach( $calfieldids as $calfieldid ){
						foreach( $all_answers as $answer ){
							if( $answer->field_id == $calfieldid ){
								$results[] = ($answer);
							}
						}
					}
				}
				
			}

			$keyvalue = array();
			if(!empty($results)){
				foreach($results as $result){
					
					$fieldetails = qcld_get_details_by_fieldid($form, $result->field_id);
					
					$iscal_value = qcld_is_cal_value($result->value, $fieldetails);
					if($iscal_value>0){
						$keyvalue[$result->field_id] = $iscal_value;
					}else{
						$keyvalue[$result->field_id] = $result->value;
					}
					
				}
				
				$formulafields = array_keys($keyvalue);
				$formulavalue = array_values($keyvalue);
				$formular = preg_replace('/\s+/', '', str_replace($formulafields, $formulavalue, $formular));
				$Cal = new Qcld_Tg_Field_calculate();
				$calresult = $Cal->calculate($formular);
				$field['calresult'] = $field['config']['before'].' '.ceil($calresult).' '.$field['config']['after'];
				$field['calvalue'] = ceil( $calresult );

				return $field;
				
			}else{
				$field['calresult'] = $field['config']['before'].' 0 '.$field['config']['after'];
				$field['calvalue'] = 0;
				return $field;
			}
			
		}else{
			
			$field['calresult'] = $field['config']['before'].' 0 '.$field['config']['after'];
			$field['calvalue'] = 0;
			return $field;
			
		}
		
	}
}

if(!function_exists('qcld_is_cal_value')){
    function qcld_is_cal_value($value, $fieldetails){
	
        $returnval = 0;
        
        if($fieldetails['type']=='dropdown'){
            
            $options = $fieldetails['config']['option'];
            
            
            foreach($options as $option){
                
                if($option['value']==$value && isset($option['calc_value']) && $option['calc_value']!=''){
                    $returnval = $option['calc_value'];
                }
            }
            
        }elseif($fieldetails['type']=='checkbox'){
            
            $value = explode(',', $value);
            
            
            
            $options = $fieldetails['config']['option'];
            foreach($options as $option){
                if(in_array($option['value'], $value) && isset($option['calc_value']) && $option['calc_value']!=''){
                    $returnval += $option['calc_value'];
                }
            }
            
            
            
        }
        
        
        return $returnval;
    }
}


if(!function_exists('qcld_get_details_by_fieldid')){
	function qcld_get_details_by_fieldid($form, $fieldid){

		$fields = $form['fields'];
		if(isset($fields[$fieldid])){
			return $fields[$fieldid];
		}else{
			return array();
		}

	}
}

if(!class_exists('Qcld_Tg_Field_calculate')){
	class Qcld_Tg_Field_calculate {
		const PATTERN = '/(?:\-?\d+(?:\.?\d+)?[\+\-\*\/])+\-?\d+(?:\.?\d+)?/';

		const PARENTHESIS_DEPTH = 10;

		public function calculate($input){
			if(strpos($input, '+') != null || strpos($input, '-') != null || strpos($input, '/') != null || strpos($input, '*') != null){
				//  Remove white spaces and invalid math chars
				$input = str_replace(',', '.', $input);
				$input = preg_replace('[^0-9\.\+\-\*\/\(\)]', '', $input);

				//  Calculate each of the parenthesis from the top
				$i = 0;
				while(strpos($input, '(') || strpos($input, ')')){
					$input = preg_replace_callback('/\(([^\(\)]+)\)/', 'self::callback', $input);

					$i++;
					if($i > self::PARENTHESIS_DEPTH){
						break;
					}
				}

				//  Calculate the result
				if(preg_match(self::PATTERN, $input, $match)){
					return $this->compute($match[0]);
				}
				// To handle the special case of expressions surrounded by global parenthesis like "(1+1)"
				if(is_numeric($input)){
					return $input;
				}

				return 0;
			}

			return $input;
		}

		private function compute($input){
			$compute = create_function('', 'return '.$input.';');

			return 0 + $compute();
		}

		private function callback($input){
			if(is_numeric($input[1])){
				return $input[1];
			}
			elseif(preg_match(self::PATTERN, $input[1], $match)){
				return $this->compute($match[0]);
			}

			return 0;
		}
	}
}

if (!function_exists('qcld_array_key_first')) {
    function qcld_array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}

if(!function_exists('qcld_form_entry_details')){
	function qcld_form_entry_details($form, $fields, $entry, $session){
		global $wpdb;
		$data = array();
		$formid = $form['ID'];
		foreach($fields as $key=>$field){
			$fieldid = $field['ID'];
			$question = '%'.$field['slug'].'%';
			$result = array();
			$all_answers = qcbot_conv_cookies_data_get_tg( $formid.'_'.$session.'_data' );
			if( $all_answers && ! empty( $all_answers ) ){
				foreach( $all_answers as $answer ){
					if( $answer->field_id == $fieldid ){
						$result = ($answer);
						break;
					}
				}
			}

			$answer = '';
			if(!empty($result)){
				$answer = $result->value;
			}
			if($answer!=''){
				$data[$question] = $answer;
			}
		}
		return $data;
	}
}

if(!function_exists('qcld_wb_chatbot_send_autoresponse')){
	
	function qcld_wb_chatbot_send_autoresponse($entrydetails, $processors, $formid, $session){

		foreach ( $processors as $key => $processor ) {

			if( ! isset( $processor['runtimes'] ) ){
				continue;
			}

			$config = $processor['config'];
		
			$url = get_site_url();
			$url = parse_url($url);
			$domain = $url['host'];
			
			$sender_name = (isset($entrydetails[$config['sender_name']])?$entrydetails[$config['sender_name']]:$config['sender_name']);
			
			$sender_email = (isset($config['sender_email'])?$config['sender_email']:"wordpress@".$domain);
			
			$subject = (isset($entrydetails[$config['subject']])?$entrydetails[$config['subject']]:$config['subject']);
			$subject = str_replace(array_keys($entrydetails), array_values($entrydetails), $subject);
			$recipient_name = (isset($entrydetails[$config['recipient_name']])?$entrydetails[$config['recipient_name']]:$config['recipient_name']);
			$recipient_email = (isset($entrydetails[$config['recipient_email']])?$entrydetails[$config['recipient_email']]:$config['recipient_email']);
			$message = str_replace(array_keys($entrydetails), array_values($entrydetails), $config['message']);
			$message = str_replace( '%recipient_name%', $recipient_name, $message );
			$headers = array();
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
			$headers[] = 'From: ' . esc_html($sender_name) . ' <' . esc_html($sender_email) . '>';

			$conversationdata = qcbot_conv_cookies_data_get_tg( $formid.'_'.$session.'_conversation' );
			krsort($conversationdata);
			$message = apply_filters( 'qc_conv_autoresponder_content_filter', $message, $conversationdata );

			if(isset($config['enable_condition']) && $config['enable_condition']==1){
				
				if(isset($config['condition_field']) && $config['condition_field']!=''){
					
					$condition_field = isset($entrydetails[$config['condition_field']])?$entrydetails[$config['condition_field']]:'';
					$con_status = false;
					if($config['condition']=='contain'){
						
						if (strpos($condition_field, $config['condition_value']) !== false) {
							$con_status = true;
						}
						
					}elseif($config['condition']=='is'){
						if($condition_field==$config['condition_value']){
							$con_status = true;
						}
					}
					
					if($con_status==true){
						@wp_mail(trim($recipient_email), $subject, $message, $headers);
					}
					
				}
				
			}else{
				@wp_mail(trim($recipient_email), $subject, $message, $headers);
			}
		}
		
	}
	
}