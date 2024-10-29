<?php
    if ( ! defined( 'ABSPATH' ) ) exit;

    function githubauthvideo_serve_video_html(){
        //TODO: common code with authentication/serve-video.php
        //Create param parser utility function
        $json = file_get_contents('php://input');
        $request_data = json_decode($json, true);

        $videoId = NULL;

        if($request_data !== null && array_key_exists('video_id', $request_data) && isset($request_data['video_id'])){
            $videoId = intval($request_data['video_id']);
        } else if(array_key_exists('video_id', $_POST)){
            $videoId = intval($_POST['video_id']);
        } else if(array_key_exists('video_id', $_GET)){
            $videoId = intval($_GET['video_id']);
        }

        if(!isset($videoId) || !is_numeric($videoId) || $videoId < 1){
            header('HTTP/1.0 400 Bad Request');
            echo '<div>Video ID not provided.</div>';
            exit;
        }

        if(null == get_post($videoId)){
            header('HTTP/1.0 404 Not Found');
            echo '<div>Video mapping entry not found.</div>';
            exit;
        }

        $nonce = NULL;

        if($request_data !== null && array_key_exists('nonce', $request_data) && isset($request_data['nonce'])){
            $nonce = sanitize_text_field($request_data['nonce']);
        } else if(array_key_exists('nonce', $_POST)){
            $nonce = sanitize_text_field($_POST['nonce']);
        } else if(array_key_exists('nonce', $_GET)){
            $nonce = sanitize_text_field($_GET['nonce']);
        }

        if(!isset($nonce) || !is_string($nonce) || !wp_verify_nonce($nonce, 'githubauthvideo_render_html_' . $videoId)){
            header('HTTP/1.0 400 Bad Request');
            echo '<div>Render nonce was invalid or absent.</div>';
            exit;
        }

        $renderType = NULL;

        if($request_data !== null && array_key_exists('render_type', $request_data) && isset($request_data['render_type'])){
            $renderType = sanitize_text_field($request_data['render_type']);
        } else if(array_key_exists('render_type', $_POST)){
            $renderType = sanitize_text_field($_POST['render_type']);
        } else if(array_key_exists('render_type', $_GET)){
            $renderType = sanitize_text_field($_GET['render_type']);
        }

        if(!isset($renderType) || !is_string($renderType) || ($renderType != 'auth'
            && $renderType != 'sponsor' && $renderType != 'video')){
            header('HTTP/1.0 400 Bad Request');
            echo '<div>Render type not provided.</div>';
            exit;
        }

        $orgId = get_post_meta( $videoId, 'githubauthvideo_github-organization-slug', true );
        $returnPath = NULL;
        
        if($request_data !== null && array_key_exists('return_path', $request_data) && isset($request_data['return_path'])){
            $returnPath = sanitize_text_field($request_data['return_path']);
        } else if(array_key_exists('return_path', $_POST)){
            $returnPath = sanitize_text_field($_POST['return_path']);
        } else if(array_key_exists('return_path', $_GET)){
            $returnPath = sanitize_text_field($_GET['return_path']);
        }

        if($returnPath == NULL | $returnPath == ''){
            $returnPath = '/';
        }

        $returnPath = esc_url_raw($returnPath);

        $renderer = githubauthvideo_PlayerHtmlRenderingFactory::getPlayerHtmlRenderingServiceServiceInstance();
        switch($renderType){
            case 'auth':
                echo $renderer->get_auth_html($videoId, $returnPath);
                break;
            case 'sponsor':
                echo $renderer->get_sponsor_html($videoId, $orgId);
                break;
            case 'video':
                echo $renderer->get_video_html($videoId);
                break;
            default:
                header('HTTP/1.0 400 Bad Request');
                echo '<div>Valid render type not provided.</div>';
                exit;
                break;
        }
    }

    githubauthvideo_serve_video_html();
?>