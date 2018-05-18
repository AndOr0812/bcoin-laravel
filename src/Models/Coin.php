<?php

namespace TPenaranda\BCoin\Models;

use TPenaranda\BCoin\BCoin;

class Coin extends Model
{
    protected $hash;
    protected $index;

    public function getDataFromAPI(): string
    {
        return BCoin::getFromAPI("/coin/{$this->hash}/{$this->index}");
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function getIndex()
    {
        return $this->index;
    }
}
