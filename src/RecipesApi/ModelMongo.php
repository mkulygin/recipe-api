<?php

namespace RecipesApi;

/*
docker exec -it eb3aa662ff06 mongo admin
use demo
db.recipes.find()
 */

class Page
{
    public $page = 1;
    public $per_page = 10;
    public $skip = 0;
    public $next = 2;
    public $prev = 0;
    public $last = 0;
    public $last_page = 0;
    public $sort = array('name' => 1);

    public function __construct($page = null)
    {
        $this->limit = 20;
    }
}

class ModelMongo extends Model
{
    public function __construct()
    {
        $this->conn = new \MongoDB\Client("mongodb://mongodb:27017");
    }

    public function setId(Recipe $recipe)
    {
        $collection = $this->conn->demo->recipes;
        $result = $collection->insertOne([
            'name' => $recipe->getName(),
            'preptime' => $recipe->getPreptime(),
            'difficulty' => $recipe->getDifficulty(),
            'vegeterian' => $recipe->getVegeterian()
        ]);
        return $result->getInsertedId();
    }

    public function update(Recipe $recipe)
    {

        $collection = $this->conn->demo->recipes;

        if ($recipe->getId()) {
            $filter = ['_id' => new \MongoDB\BSON\ObjectID($recipe->getId())];

            $setArray = [];
            if ($recipe->getName()) {
                $setArray["name"] = $recipe->getName();
            }
            if ($recipe->getPreptime()) {
                $setArray["preptime"] = $recipe->getPreptime();
            }
            if ($recipe->getDifficulty()) {
                $setArray["difficulty"] = $recipe->getDifficulty();
            }
            if ($recipe->getVegeterian()) {
                $setArray["vegeterian"] = $recipe->getVegeterian();
            }

            $options = [];
            $item = $collection->findOne($filter);
            $result = $collection->updateOne(['_id' => new \MongoDB\BSON\ObjectID($recipe->getId())],
                ['$set' => $setArray]);
        }

    }

    public function getId(Recipe $recipe)
    {
        $collection = $this->conn->demo->recipes;

        if ($recipe->getId()) {
            $filter = ['_id' => new \MongoDB\BSON\ObjectID($recipe->getId())];
        }

        if ($recipe->getName()) {
            $filter = ['name' => $recipe->getName()];
        }

        $item = $collection->findOne($filter);

        if (!is_null($item)) {
            return $item->getArrayCopy();
        }

        return [];
    }

    public function delete(Recipe $recipe)
    {
        $collection = $this->conn->demo->recipes;
        $filter = ['_id' => new \MongoDB\BSON\ObjectID($recipe->getId())];
        $collection->deleteOne($filter);
    }

    public function getListing(Page $page, $filter = [])
    {
        $collection = $this->conn->demo->recipes;
        $options = [
            'limit' => $page->per_page,
            'skip' => $page->skip,
            'sort' => $page->sort
        ];

        $cursor = $collection->find($filter, $options);

        return $cursor->toArray();
    }
}