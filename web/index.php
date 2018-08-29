<?php
namespace RecipesApi;
require_once('../vendor/autoload.php');

// classes autoloader
spl_autoload_register(function ($class_name) {

    $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace('\\',
            DIRECTORY_SEPARATOR, $class_name) . '.php';
    //echo "\n autoload $path \n";
    if (file_exists($file = $path)) {
        require $file;
    }
});

// make request to api
// docker exec -it php_7ab6cecf5cfb /bin/bash
// auth get request
// curl -i -d "login=mkulygin&password=tevakeku" http://localhost/auth
// read the jwt token

// make POST request with jwt token to $auth to create a new recipe
// curl -i -d "status=1&name=roastbeef&prep%20time=30&difficulty=3&vegeterian=0" -H "Content-Type: application/json" -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.IntcInVzZXJJZFwiOlwiYjA4Zjg2YWYtMzVkYS00OGYyLThmYWItY2VmMzkwNDY2MGJkXCIsXCJleHBcIjoxNTMzMjMyODc1LFwiaWF0XCI6MTUzMzIzMTA3NX0i.2t2Q8PE85yxHeFtu477hcR_XX1qE5_dDqITqs7xu9q7HHDhKOJeyRtsDAycyzBviIq33nZ3lv-rDU_EjT1XH-g" http://localhost/recipes
// error log: tail -f /var/log/nginx/error.log

// new request from a client - create an object from HTTP-request

// configurate HTTPS http://equinox.one/blog/2016/11/03/set-https-in-nginx-running-in-docker-container-and-update-certs-from-jenkins/
// http://scmquest.com/nginx-docker-container-with-https-protocol/

// api request with valid auth token
$request = new Request($_GET, $_POST, $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], isset($_SERVER['HTTP_CONTENT_TYPE'])?$_SERVER['HTTP_CONTENT_TYPE']:'', isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])?$_SERVER['HTTP_ACCEPT_LANGUAGE']:'en', isset($_SERVER['HTTPS'])?$_SERVER['HTTPS']:'', isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'');

$response = new Response($request->getParameters(), 200, $request->getHeaders());
$api = new ApiResponse($request, $response); // api generate response
echo $api->getResponse(); // sever send response to the client (check authorization if needed)

unset($request);
unset($api);
unset($response);

?>