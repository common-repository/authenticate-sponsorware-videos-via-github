<?php 
    if ( ! defined( 'ABSPATH' ) ) exit;

    class githubauthvideo_PlayerHtmlRenderingFactory {
        private static $singleton = NULL;

        private function __construct(){
        }

        public static function getPlayerHtmlRenderingServiceServiceInstance(){
            return githubauthvideo_PlayerHtmlRenderingFactory::$singleton;
        }

        public static function registerPlayerHtmlRenderingService(githubauthvideo_PlayerHtmlRenderer $service){
            if(isset($service)){
                githubauthvideo_PlayerHtmlRenderingFactory::$singleton = $service;   
            }
        }
    }
?>