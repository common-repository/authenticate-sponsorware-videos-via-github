<?php
    if ( ! defined( 'ABSPATH' ) ) exit;
    
    class githubauthvideo_GithubAuthCookies {
        private $domain;
        private $token_key;
        private $token_type_key;
        private $cookie_domain;
        private $secure_cookie;
        private static $singleton = NULL;

        private function __construct(){
            $this->domain = str_replace( '.', '', SERVER_HOST );
            $this->token_key = 'githubauthvideo_' . $this->domain . '_token';
            $this->token_type_key = 'githubauthvideo_' . $this->domain . '_token_type';
            $this->cookie_domain = SERVER_SCHEME . '://' . SERVER_HOST;
            $main_settings_options = get_option( 'githubauthvideo_main_settings' ); // Array of All Options
            $this->secure_cookie = TRUE;
            $do_not_enforce_https = FALSE;
            if($main_settings_options && array_key_exists('do_not_enforce_https_5', $main_settings_options)){
                $do_not_enforce_https = $main_settings_options['do_not_enforce_https_5'];
            }

            if($do_not_enforce_https && SERVER_SCHEME != 'https'){
                //Only have an insecure cookie if we aren't enforcing https AND the request scheme is not https
                $this->secure_cookie = FALSE;
            }

            githubauthvideo_GithubAuthCookies::$singleton = $this;
        }

        public static function getCookiesInstance(){
            if(isset(githubauthvideo_GithubAuthCookies::$singleton)){
                return githubauthvideo_GithubAuthCookies::$singleton;
            } else {
                return new githubauthvideo_GithubAuthCookies;
            }
        }

        public function is_token_valid($token){
            //Removed verification for token since Github can (and does) change the token scheme
            if(isset($token)){
                return true;
            } else {
                return false;
            }
        }

        public function set_auth_cookies(string $token, string $token_type = 'bearer'){
            setcookie($this->token_key, $token, 
                time()+60*60*24*1, '/', '', $this->secure_cookie);
            setcookie($this->token_type_key, $token_type, 
                time()+60*60*24*1, '/', '', $this->secure_cookie);
        }
    
        public function void_auth_cookies(){
            setcookie($this->token_key, NULL, 
                time() - 3600, '/', '', $this->secure_cookie);
            setcookie($this->token_type_key, NULL, 
                time() - 3600, '/', '', $this->secure_cookie);
        }

        public function get_token(){
            if (array_key_exists($this->token_key, $_COOKIE)){
                $tokenCookieValue = sanitize_text_field($_COOKIE[$this->token_key]);
                if($this->is_token_valid($tokenCookieValue)){
                    return $tokenCookieValue;
                }
            }

            return NULL;
        }

        public function get_token_type(){
            if (array_key_exists($this->token_type_key, $_COOKIE)){
                return sanitize_text_field($_COOKIE[$this->token_type_key]);
            } else {
                return 'bearer';
            }
        } 

        public function get_token_type_key(){
            return $this->token_type_key;
        }

        public function get_token_key(){
            return $this->token_key;
        }
    }
?>