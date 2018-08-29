<?php

namespace RecipesApi;

interface RecipeInterface
{
    public function getId();

    public function setId(string $guid);

    public function getName();

    public function setName(string $name);

    public function getPreptime();

    public function setPreptime(string $preptime);

    public function getDifficulty();

    public function setDifficulty(int $difficulty);

    public function getVegeterian();

    public function setVegeterian(bool $vegeterian);

    public function getRate();

    public function setRate(int $rate);
}

class Recipe implements RecipeInterface
{
    protected $guid;
    protected $name;
    protected $preptime;
    protected $difficulty;
    protected $vegeterian;
    protected $rate;

    public function getId()
    {
        return $this->guid;
    }

    public function setId(string $guid)
    {
        $this->guid = $guid;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getPreptime()
    {
        return $this->preptime;
    }

    public function setPreptime(string $preptime)
    {
        $this->preptime = $preptime;
    }

    public function getDifficulty()
    {
        return $this->difficulty;
    }

    public function setDifficulty(int $difficulty)
    {
        if ($difficulty > 3 || $difficulty < 1) {
            throw new \LengthException("Difficulty must be in 1-3 range.", 400);
        }
        $this->difficulty = $difficulty;
    }

    public function getVegeterian()
    {
        return $this->vegeterian;
    }

    public function setVegeterian(bool $vegeterian)
    {
        $this->vegeterian = $vegeterian;
    }

    public function getRate()
    {
        return $this->rate;
    }

    public function setRate(int $rate)
    {
        if ($rate > 5 || $rate < 1) {
            throw new \LengthException("Rate must be in 1-5 range.", 400);
        }
        $this->rate = $rate;
    }
}