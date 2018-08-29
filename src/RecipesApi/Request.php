<?php

namespace RecipesApi;

use Exception;

interface RequestInterface
{
    public function getToken();

    public function getContentType();

    public function getParameters();
}

// client's request
class Request implements RequestInterface
{
    const VERSION = '1.0'; // HTTP response version

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';

    const HTTP = 'http';
    const HTTPS = 'https';

    protected $uri = null;
    protected $method = self::METHOD_GET;
    protected $methodsAllowed = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    protected $httpResponseCode = 0;
    protected $contentType;
    protected $acceptLanguage = 'en';
    protected $https = 0;
    protected $userAgent = '';
    protected $headers = [];

    public $query; // _GET parameters
    public $request; // _POST parameters
    public $token; // token from request
    public $put;

    public function __construct(
        $query = [],
        $request = [],
        $method = self::METHOD_GET,
        $uri = '/',
        $contentType = 'json',
        $acceptLanguage = 'en',
        $https = '',
        $userAgent = ''
    ) {
        $this->query = $query;
        $this->request = $request;
        $this->method = $method;
        $this->uri = $uri;
        $this->contentType = $contentType;
        $this->acceptLanguage = $acceptLanguage;
        $this->https = empty($https);
        $this->userAgent = $userAgent;
    }

    // get all http parameters from request
    public function getParameters()
    {
        switch ($this->method) {
            case self::METHOD_GET :
                return $this->query;
                break;
            case self::METHOD_POST :
                return $this->request;
                break;
            default:
                return [];
        }
    }

    public function setPutParams($params)
    {
        $this->put = $params;
    }

    public function getPutParam($name)
    {
        if (isset($this->put[$name])) {
            return $this->put[$name];
        } else {
            return null;
        }
    }

    public function setGetParam($name, $value)
    {
        $this->query[$name] = $value;
    }

    public function getGetParam($name)
    {
        if (isset($this->query[$name])) {
            return $this->query[$name];
        } else {
            return null;
        }
    }

    public function getPostParam($name)
    {
        if (isset($this->request[$name])) {
            return $this->request[$name];
        } else {
            return null;
        }
    }

    public function getMethodType()
    {
        return $this->method;
    }

    public function isMethodAllowed()
    {
        return in_array($this->method, $this->methodsAllowed);
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function getToken()
    {
        return $this->getBearerToken();
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    // auth
    // header( "Authorization: Bearer 1234567890abcdef”);
    // content negotiation — lib http://ptlis.net/source/php/content-negotiation/
    // Accept-Language: en; q=1.0, de; q=0.5, ru; q=0.3
    // Accept: text/html; q=1.0, text/*; q=0.8, image/gif; q=0.6, image/jpeg; q=0.6, image/*; q=0.5, */*; q=0.1
    // User-agents can request data in specified formats from web services or web APIs, such as application/json or application/xml.
    // Accept-Charset: iso-8859-1,*;q=0.5,utf-8;q=0.8

    /**
     * Get hearder Authorization
     * */
    public function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
                $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
            } elseif (function_exists('apache_request_headers')) {
                $requestHeaders = apache_request_headers();
                // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
                $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)),
                    array_values($requestHeaders));
                if (isset($requestHeaders['Authorization'])) {
                    $headers = trim($requestHeaders['Authorization']);
                }
            }
        }
        return $headers;
    }

    /**
     * get access token from header
     * */
    public function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    public function getHeaders()
    {
        $this->headers = [
            "method" => $this->method,
            "uri" => $this->uri,
            "contentType" => $this->contentType,
            "acceptLanguage" => $this->acceptLanguage,
            "https" => empty($this->https),
            "userAgent" => $this->userAgent,
        ];
        return $this->headers;
    }
}

?>