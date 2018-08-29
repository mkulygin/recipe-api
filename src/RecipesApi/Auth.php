<?php
namespace RecipesApi;

require_once('Model.php');

use \Firebase\JWT\ExpiredException;
use DateTime;
use DateInterval;
use DateTimeZone;
use Exception;

// testing

// get auth with login and pass - 200
// get auth with login and pass with expired time 0 sec - 200
// try to create with expired token - 401
// try to list without token - 200

interface AuthInterface
{
    public function login($login, $pass);

    public function logout();
}

// authorization
class Auth implements AuthInterface
{
    protected $alg = "HS512";
    protected $secret = "32408320483294.871239817391.dsfoiuiewoiruer.13087219083";
    protected $userId = "b08f86af-35da-48f2-8fab-cef3904660bd";
    protected $loggedIn = false;
    protected $tokenLifeTimeSec = 1800;
    protected $authHeader = '';
    protected $allowedAuthMethods = ['Bearer'];
    protected $currentAuthMethod = 'Bearer';
    protected $defaultTimeZone = 'Europe/Berlin';
    protected $loginRateLimit = 50;
    protected $currentClientIp = '127.0.0.1';
    protected $loginRate = 0;
    protected $dbUsers = null;

    public function __construct()
    {
        $this->dbUsers = new ModelPostgres();
    }

    public function __destruct()
    {
        if (isset($this->dbUsers)) {
            unset($this->dbUsers);
        }
    }

    public function login($login, $pass)
    {

        // check rate limit of requests per minute for the IP and login
        $loginRate = 5; // $this->dbUsers->getLoginRate($this->currentClientIp);
        if ($loginRate > $this->loginRateLimit) {
            throw new Exception("Login rate limit exhausted. Try login later", 401);
        }

        // check login and pass if OK set loggedIn
        if ($this->dbUsers->checkLogin($login, $pass)) {
            $this->loggedIn = 1;
        } else {
            // db->auth->increaseLoginRate($this->currentClientIp);
            throw new Exception("Not logged in", 401);
        }

        return $this->loggedIn;
    }

    public function logout()
    {
        $this->loggedIn = false;
    }

    public function getAuthHeader()
    {
        return "Authorization: Bearer " . $this->JWTCreateToken();
    }

    public function getAuthHeaderName()
    {
        return "Authorization";
    }

    public function getAuthHeaderValue()
    {
        return $this->currentAuthMethod . " " . $this->CreateToken();
    }

    public function getToken()
    {
        if ($this->loggedIn) {
            return $this->CreateToken();
        } else {
            return '';
        }
    }

    public function checkToken($token)
    {
        return ($this->CreateToken($this->secret) === $token);
    }

    public function getAllowedAuth()
    {
        return implode(',', $this->allowedAuthMethods);
    }

    protected function CreateToken($tokenFileTimeInSec = null)
    {
        $tokenFileTimeInSec ? $this->tokenLifeTimeSec = $tokenFileTimeInSec : true;
        // use timestamp in default time zone
        $date = new DateTime('now', new DateTimeZone($this->defaultTimeZone));
        $nowts = $date->getTimestamp();
        $date->add(new DateInterval("PT" . $this->tokenLifeTimeSec . "S"));
        $expts = $date->getTimestamp();

        // JWT payload
        $payload = json_encode(
            [
                "userId" => $this->userId,
                "exp" => $expts,
                "iat" => $nowts
            ]);
        $token = \Firebase\JWT\JWT::encode($payload, $this->secret, $this->alg);
        /**
         * Save token on the user model
         */
        return $token;
    }

    /**
     * Check the JWT token
     * Return true if the token is correct
     *
     * @param null $jwt
     *
     */
    public function DecodeToken($jwt = null)
    {
        //decode the jwt using the key from config
        $date = new DateTime('now', new DateTimeZone($this->defaultTimeZone));
        \Firebase\JWT\JWT::$timestamp = $date->getTimestamp();
        $token = \Firebase\JWT\JWT::decode($jwt, $this->secret, array($this->alg)); // return string

        // {"userId":"b08f86af-35da-48f2-8fab-cef3904660bd","exp":1533227975,"iat":1533227975}
        $exp = strpos($token, '"exp":');
        $exp = (int)substr($token, $exp + strlen('"exp":'), 15);

        if (\Firebase\JWT\JWT::$timestamp > $exp) {
            throw new ExpiredException("Token expired", 401);
        }
    }
}

?>
