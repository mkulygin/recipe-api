<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use \Firebase\JWT\ExpiredException;
use \RecipesApi\Auth;

require_once('vendor/autoload.php');
require_once('src/RecipesApi/ModelInterface.php');
require_once('src/RecipesApi/Model.php');
require_once('src/RecipesApi/Auth.php');

// ./vendor/bin/phpunit --testdox --bootstrap vendor/autoload.php ./tests/AuthTest

final class AuthTest extends TestCase
{
    public function testExpiredToken(): void
    {
        $this->expectException(ExpiredException::class);
        $auth = new Auth();

        $auth->DecodeToken('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.IntcInVzZXJJZFwiOlwiYjA4Zjg2YWYtMzVkYS00OGYyLThmYWItY2VmMzkwNDY2MGJkXCIsXCJleHBcIjoxNTMzNjQ2OTMyLFwiaWF0XCI6MTUzMzY0NTEzMn0i.j5IMBOosMT7ZROmYulhkJfhjPdFoK9XQnPscuCPD_7kowjnKP7NvzRLvEQTeEVjPoSwGaOKE3JWyhJpCgQieTQ');

        if(isset($auth))
            unset($auth);
    }
    public function testLoginAndValidToken(): void
    {
        $this->expectException(Exception::class);
        $auth = new Auth();
        $auth->login("mkulygin", "tevakeku");
        $token = $auth->getToken();

        $auth->DecodeToken();

        if(isset($auth))
            unset($auth);

    }
}