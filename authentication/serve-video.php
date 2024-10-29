<?php
    if ( ! defined( 'ABSPATH' ) ) exit;

    if (!function_exists('getallheaders'))
    {
        //shim if not apache
        function getallheaders()
        {
           $headers = [];
           foreach ($_SERVER as $name => $value)
           {
               if (substr($name, 0, 5) == 'HTTP_')
               {
                   $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
               }
           }
           return $headers;
        }
    }
    
    function githubauthvideo_serve_video(){
        $Cookies = githubauthvideo_GithubAuthCookies::getCookiesInstance();

        $token = $Cookies->get_token();
    
        if(!isset($token)){
            header('HTTP/1.0 401 Unauthorized');
            echo 'Token not provided.';
            exit;
        }
    
        $tokenType = $Cookies->get_token_type();

        $videoId = intval(get_query_var( 'githubauthvideo_video' ));
    
        if(!isset($videoId) || !is_numeric($videoId) || $videoId < 1){
            header('HTTP/1.0 400 Bad Request');
            echo 'Video ID not provided.';
            exit;
        }
    
        if(null == get_post($videoId)){
            header('HTTP/1.0 404 Not Found');
            echo 'Video mapping entry not found.';
            exit;
        }

        $nonce = sanitize_text_field(get_query_var( 'nonce' ));
    
        if(!isset($nonce) || !is_string($nonce) || !wp_verify_nonce($nonce, 'githubauthvideo_serve_video_' . $videoId)){
            header('HTTP/1.0 400 Bad Request');
            echo '<div>Video nonce was invalid or absent.</div>';
            exit;
        }
    
        $GithubApi = githubauthvideo_GithubAPIServiceFactory::getGithubAPIServiceInstance();
    
        //use github api service methods, check here
        //TODO: Not great to keep checking the API for every HTTP request. Cache in JWT token?
        $result = $GithubApi->is_viewer_sponsor_of_video($videoId);
        if(gettype($result) === 'string'){
            header('HTTP/1.0 500 Internal Server Error');
            echo 'Error checking Github API for Sponsor status. See message(s) below:<br>';
            echo $result;
            exit;
        } else if (!$result){
            header('HTTP/1.0 401 Unauthorized');
            echo 'User is not sponsor of this video\'s Github organization';
            exit;
        } else {
            $location = get_post_meta( $videoId, 'githubauthvideo_video-location-uri', true );
            $mediaId = intval($location);
            $scheme = '';
            if(!$mediaId){
                $scheme = parse_url($location, PHP_URL_SCHEME);
            } else {
                //If we are using a locally hosted file, get the file path instead so we aren't using extra bandwidth
                $location = get_attached_file($mediaId);
                $scheme = 'file';
            }        
            //if it's a local file, do proper streaming
            if($scheme == 'file'){
                $stream = new githubauthvideo_VideoStream($location, githubauthvideo_get_video_mime_type($location));
                $stream->start();
            } else { //otherwise pass through from the URL
                $request_headers_for_server = array(
                    'method: GET'
                );
                $headers_from_client = getallheaders();
                foreach($headers_from_client as $header_name => $header_value){
                    switch(strtolower($header_name)){
                        case 'host':
                        case 'cookie':
                        break;
                        default:
                            array_push($request_headers_for_server, $header_name . ': ' . $header_value);
                        break;
                    }
                }

                $options = array(
                    'http' => array(
                        'header'  => $request_headers_for_server
                    )
                );
                $context  = stream_context_create($options);
                $server_headers = array_change_key_case(get_headers($location, TRUE, $context), CASE_LOWER);
                if(isset($server_headers) && is_array($server_headers)){
                    if(array_key_exists(0, $server_headers)){
                        header($server_headers[0]);
                        unset($server_headers[0]);
                    } else {
                        header('HTTP/1.0 200 OK'); 
                    }
                }

                foreach($server_headers as $header_name => $header_value){
                    if(is_array($header_value)){
                        foreach($header_value as $subheader){
                            header($header_name . ": " . $subheader);
                        }
                    } else {
                        header($header_name . ": " . $header_value);
                    }
                }
                $handle = fopen($location, 'rb', false, $context);
                if(!(isset($handle))){
                    header('HTTP/1.0 404 Not Found');
                    echo 'Video file could not be found.';
                    exit;
                }
                $bytesToRead = 8192;
                $data = TRUE;
                set_time_limit(5);
                while(!feof($handle) && $data) {
                    $data = fread($handle, $bytesToRead);
                    echo $data;
                    flush();
                    if (connection_aborted () != 0) {
                        fclose($handle);
                        die();
                    }
                }
                fclose($handle);
            }
        }
    }
    githubauthvideo_serve_video();
?>