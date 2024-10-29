<?php
    if ( ! defined( 'ABSPATH' ) ) exit;
    
    class githubauthvideo_PlayerHtmlRenderer {

        public function __construct(){
        }

        private function get_splash_image(int $videoId = -1){
            $splashUrl = esc_url( plugins_url( 'images/blur.png', __FILE__ ) );
            if($videoId > -1) {
                $metaSplash =  get_post_meta( $videoId, 'githubauthvideo_splash-screen', true );
                if(isset($metaSplash) && !empty($metaSplash)){
                    $splashUrl = $metaSplash;
                }
            }
        
            return $splashUrl;
        }

        public function get_auth_html(int $videoId = -1, string $returnPath = NULL){
            if(!isset($returnPath)){
                $returnPath = '/';
            }

            $authUrl = esc_url( '/?githubauthvideo_auth=1&return_path=' . urlencode($returnPath) );
            $splashUrl = $this->get_splash_image($videoId);
            $ghIconUrl = esc_url( plugins_url( '../images/github-icon.png', __FILE__ ) );
            $textContent = get_post_meta( $videoId, 'githubauthvideo_video-unauthenticated-description', true );
            return <<<EOT
            <div class="githubvideoauth-video-auth-splash-container">
            <img src="$splashUrl" class="githubvideoauth-video-auth-splash-image" />
                <div class="githubvideoauth-video-auth-splash-cover">
                    <a href="$authUrl">
                        <button>
                            <img class="github-icon" src="$ghIconUrl"> <span class="github-button-text">Authenticate with Github</span>
                        </button>
                    </a>
                </div>
            </div>
            <div class="githubvideoauth-video-text-content-container">
                $textContent
            </div>  
            EOT;
        }

        public function get_sponsor_html(int $videoId = -1, string $orgId = ''){
            $splashUrl = $this->get_splash_image($videoId);
            $ghIconUrl = esc_url( plugins_url( '../images/github-icon.png', __FILE__ ) );
            //TODO: Have reasonable default
            $sponsorUrl = esc_url('https://github.com/sponsors/' . $orgId);
            $textContent = get_post_meta( $videoId, 'githubauthvideo_video-unauthenticated-description', true );
            return <<<EOT
            <div class="githubvideoauth-video-auth-splash-container">
            <img src="$splashUrl" class="githubvideoauth-video-auth-splash-image" />
                <div class="githubvideoauth-video-auth-splash-cover">
                    <div class="githubvideoauth-sponsor-message-block">
                        <text>Only Github sponsors have access to this video.</text>
                    </div>
                    <br>
                    <a href="$sponsorUrl" target="_blank">
                        <button>
                            <img class="github-icon" src="$ghIconUrl"> <span class="github-button-text">Become a sponsor now!</span>
                        </button>
                    </a>
                </div>
            </div>
            <div class="githubvideoauth-video-text-content-container">
                $textContent
            </div>  
            EOT;
        }

        public function get_video_html(int $videoId = -1){

            $nonce = wp_create_nonce('githubauthvideo_serve_video_' . $videoId);
            $videoUrl = '/?githubauthvideo_video=' . $videoId . '&nonce=' . $nonce;
            $location = get_post_meta( $videoId, 'githubauthvideo_video-location-uri', true );
            $textContent = get_post_meta( $videoId, 'githubauthvideo_video-description', true );
            
            if(!isset($location) || $location == -1 || $location == ''){
                return <<<EOT
                <div class="githubvideoauth-video-text-content-container">
                    $textContent
                </div>
            EOT;
            }
            $mediaId = intval($location);
            if($mediaId){
                //If using a media ID, get the file path
                $location = get_attached_file($mediaId);
            }
            
            $title = '';
            $post = get_post($videoId);
            if($post){
                $title = $post->post_title;
            }
            $mimeType = githubauthvideo_get_video_mime_type($location);
            return <<<EOT
                <h5 class="githubvideoauth-video-title-container">$title</h5>
                <div class="githubvideoauth-video-container">
                    <video class="githubvideoauth-video"
                    title="$title"
                    alt="$videoId"
                    controls
                    preload="true"
                    >
                        <source src="$videoUrl" type="$mimeType"></source>
                    </video>
                </div>
                <div class="githubvideoauth-video-text-content-container">
                    $textContent
                </div>
            EOT;
        }
        //githubauthvideo_video-unauthenticated-description
        public function get_video_placeholder_html(int $videoId = -1, string $orgId = ''){
            $nonce = wp_create_nonce('githubauthvideo_render_html_' . $videoId);
            return <<<EOT
                <div class="githubvideoauth-video-placeholder">
                    <div class="loader">Loading...</div>
                    <input type="hidden" class="videoId" value="$videoId"/>
                    <input type="hidden" class="orgId" value="$orgId"/>
                    <input type="hidden" class="nonce" value="$nonce"/>
                </div>  
            EOT;
        }
    }
?>