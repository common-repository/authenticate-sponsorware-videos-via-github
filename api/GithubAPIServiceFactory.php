<?php
    if ( ! defined( 'ABSPATH' ) ) exit;

    class githubauthvideo_GithubAPIServiceFactory {
        private static $singleton = NULL;

        private function __construct(){
        }

        public static function getGithubAPIServiceInstance(){
            return githubauthvideo_GithubAPIServiceFactory::$singleton;
        }

        public static function registerGithubAPIService(githubauthvideo_GithubAPIService $service){
            if(isset($service)){
                githubauthvideo_GithubAPIServiceFactory::$singleton = $service;   
            }
        }
    }

?>