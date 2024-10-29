<?php
    if ( ! defined( 'ABSPATH' ) ) exit;

    class githubauthvideo_GithubAPIService {
        private static $githubUsernameRegex = '(/^[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}$/i)';
        protected $TOKEN;
        protected $TOKEN_TYPE;
        protected $GRAPH_API_URL;
        protected $IGNORE_SPONSORSHIP = FALSE;

        public function __construct(string $graphApiUrl){
            $Cookies = githubauthvideo_GithubAuthCookies::getCookiesInstance();
            $this->TOKEN = $Cookies->get_token();
            $this->TOKEN_TYPE = $Cookies->get_token_type();
            $this->GRAPH_API_URL = $graphApiUrl;
            $main_settings_options = get_option( 'githubauthvideo_main_settings' ); // Array of All Options
            if($main_settings_options && array_key_exists("ignore_sponsorship_4", $main_settings_options)){
                $this->IGNORE_SPONSORSHIP = $main_settings_options['ignore_sponsorship_4']; // Whether to track if the user is sponsoring the organization or not for video access
            }
        }

        public function get_auth_header(){
            return 'Authorization: ' . $this->TOKEN_TYPE . ' ' . $this->TOKEN;
        }

        protected function execute_graphql(string $ql){
            $returnObject = array('error' => FALSE, 'message' => '', 'data' => array());
            $data = array('query' => $ql);
            // use key 'http' even if you send the request to https://...
            $options = array(
                'http' => array(
                    'header'  => array('Content-type: application/json',
                        'Accept: application/json',
                        'User-Agent: PHP',
                        $this->get_auth_header()
                    ),
                    'method'  => 'POST',
                    'content' => json_encode($data)
                )
            );
            $context  = stream_context_create($options);
            $result = json_decode(@file_get_contents($this->GRAPH_API_URL, false, $context), true);
            if ($result == FALSE || !array_key_exists('data', $result)) {
                $returnObject['error'] = TRUE;
                $returnObject['message'] = 'Token may be expired. Try clearing cache and authenticating again.';
                $returnObject['data'] = NULL;
            } else if (array_key_exists('errors', $result)) {
                $concatErr = "";
                for($i = 0; $i < count($result['errors']); $i++){
                    $concatErr .= '<br>' . $result['errors'][$i]['message'];
                }
                $returnObject['error'] = TRUE;
                $returnObject['message'] = 'Gtihub API Error: ' . $concatErr;
                $returnObject['data'] = NULL;
            } else {
                //We have our data.
                $returnObject['data'] = $result['data'];
            }

            return $returnObject;
        }

        public function is_token_valid(){
            $options = array(
                'http' => array(
                    'header'  => array('Content-type: application/json',
                        'Accept: application/json',
                        'User-Agent: PHP',
                        $this->get_auth_header()
                    ),
                    'method'  => 'POST'
                )
            );
            $context  = stream_context_create($options);
            $result = json_decode(@file_get_contents($this->GRAPH_API_URL, false, $context), true);
            if ($result == FALSE || array_key_exists('message', $result)) {
                //Token is likely expired. need to auth again.
                return FALSE;
            } else {
                return TRUE;
            }
        }

        /**
         * Returns string with message on error, otherwise returns boolean value indicating sponsor status of viewer
         */
        public function is_viewer_sponsor_of_video(int $videoId){
            if($this->IGNORE_SPONSORSHIP){
                return $this->is_token_valid();
            }

            $login = get_post_meta( $videoId, 'githubauthvideo_github-organization-slug', true );
            $errors = '';
            //TODO: Once sponsorship tiers exist in Github, need to update this to query properly
            //Likely need to use code to pagination through results and compare tier ID's.... Uhg
            $orgQl = <<<EOT
                query {
                        organization(login: "$login") {
                            viewerIsSponsoring
                        }
                }
            EOT;
            $result = $this->execute_graphql($orgQl);
            
            if($result['error']){
                $errors .= $result['message'];
                $userQl = <<<EOT
                    query {
                            user(login: "$login") {
                                viewerIsSponsoring
                            }
                    }
                EOT;
                $result = $this->execute_graphql($userQl);
            }
            //if we don't find it, it will return with 'errors' not just error
            if($result['error']){
                return $errors . '<br>' . $result['message'];
            } else {
                if($result['data']){
                    if (array_key_exists('organization', $result['data'])){
                        return $result['data']['organization']['viewerIsSponsoring'];
                    } else if (array_key_exists('user', $result['data'])){
                        return $result['data']['user']['viewerIsSponsoring'];
                    }
                }
                
            }
        }
    }
?>