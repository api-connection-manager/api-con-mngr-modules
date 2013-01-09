<?php
/*
  Plugin Name: Twitter
  Plugin URI: https://github.com/david-coombes/api-con-mngr-modules
  Description: Twitter service module for API Connection Manager
  Version: 0.1
  Author: Daithi Coombes
  Author URI: http://david-coombes.com
 */

/**
 * Code flow for oauth1 is:
 * request_token
 * user_auth
 * access_token 
 */

require_once('twitteroauth/config.php');

if (!class_exists('API_Con_Twitter')):

	class API_Con_Twitter extends API_Con_Mngr_Module{

		public $user_id="";
	
		/**
		 * construct params
		 */
		function __construct() {
			
			//set params
			$this->protocol = 'oauth1';
			$this->use_nonce = false;
			$this->url_access_token = "https://api.twitter.com/oauth/access_token";
			$this->url_authorize = "https://api.twitter.com/oauth/authorize";
			$this->url_request_token = "https://api.twitter.com/oauth/request_token";
			
			//construct parent
			$this->consumer_key = CONSUMER_KEY;
			$this->consumer_secret = CONSUMER_SECRET;
			$this->callback_url = 'http://david-coombes.com/wp-admin/admin-ajax.php?action=api_con_mngr';
			parent::__construct();
			
			$this->get_params();
		}

		function check_error( array $response ){
			
			//get response
			$res = json_decode($response['body']);
			$errs = array();
			
			//check http code
			if($response['response']['code'] != '200')
				$errs[] = "{$response['response']['code']}: {$response['response']['message']}";
			
			//check for errors
			if(@$res->errors)
				foreach($res->errors as $err)
					$errs[] = "{$err->code}: {$err->message}";
					
			//if errors return WP_Error
			if(count($errs))
				return $this->error(implode("\n", $errs));
			
			return true;
		}
		
		/**
		 * Get twitter user_id after successfull login
		 * @todo add this method to the parent class
		 * @see API_Con_Mngr_Module::do_login() 
		 */
		function do_login( $dto ){
			
			//this call will return user_id
			$request = $this->request( $this->url_access_token, 'GET', array(
				'oauth_verifier' => $dto->response['oauth_verifier']
			));
			
			//save params
			$token = OAuthUtil::parse_parameters($request['body']);
			$this->set_params( $token );
		}
		
		/**
		 * Override request method. 
		 * 
		 * @see API_Con_Mngr_Module::request()
		 * @see API_Con_Mngr_Module::request()
		 * @param string $uri The full endpoint url.
		 * @param string $method Default GET. The http method to user.
		 * @param array $parameters Optional. An array of parameters in key
		 * value pairs
		 * @return array Returns the response array in the WP_HTTP format. 
		 */
		function request( $uri, $method, $parameters=array() ){
			
			//make sure parameters are loaded
			$this->get_params();
			
			//sign request
			$method = strtoupper($method);
			$request = $this->oauth_sign_request( $uri, $method, $parameters);
			if($method=='POST')
				$url = $request->get_normalized_http_url ();
			else
				$url = $request->to_url();
			
			//send and return result
			return parent::request( $url, $method, $parameters );
		}
		
	}
	endif;

$oauth1 = new API_Con_Twitter();
