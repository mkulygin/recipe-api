<?php

namespace RecipesApi;

use \Doctrine\Common\ClassLoader;

class Model implements ModelInterface
{
    protected $config = null;
    protected $conn = null;

    public function getId(Recipe $recipe)
    {
    }

    public function __destruct()
    {
        if (isset($this->config)) {
            unset($this->config);
        }
        if (isset($this->conn)) {
            unset($this->conn);
        }
    }

}

// psql -h postgres -U hellofresh -p 5432 -W hellofresh
// SELECT * FROM users;
// INSERT INTO users (id, login, password) VALUES (1, 'mkulygin', 'cd779868d1558b852b8f172153b382c9');
// INSERT INTO rating (id, recipeId, rate) VALUES (1, '5b69487f7af19c32f62da886', 5);
class ModelPostgres extends Model
{
    public function __construct()
    {
        $this->config = new \Doctrine\DBAL\Configuration();
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection(array(
            'dbname' => 'hellofresh',
            'user' => 'hellofresh',
            'password' => 'hellofresh',
            'host' => 'postgres',
            'driver' => 'pdo_pgsql',
            'charset' => 'utf8'
        ), $config = $this->config);
    }

    public function getId(Recipe $recipe)
    {
    }

    public function checkLogin($login, $password)
    {
        $md5pass = md5($password);

        $sql = "SELECT id FROM users WHERE login = ? AND password = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $login);
        $stmt->bindValue(2, $md5pass);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function rate(Recipe $recipe)
    {
        $sql = "INSERT INTO rating (recipeid, rating) VALUES  (?, ?);";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $recipe->getId());
        $stmt->bindValue(2, $recipe->getRate());
        $stmt->execute();

        return $stmt->rowCount();
    }
}

class ModelRedis extends Model
{
    public function __construct()
    {
        /*$redis = new Redis();
        $redis->connect('redis_host', 6379);

        $cacheDriver = new \Doctrine\Common\Cache\RedisCache();
        $cacheDriver->setRedis($redis);
        $cacheDriver->save('cache_id', 'my_data');
        */
    }

    public function getId(Recipe $recipe)
    {

    }
}