<?php

namespace TPenaranda\BCoin\Models;

use TPenaranda\BCoin\BCoin;

class Transaction extends Model
{
    protected $hash;

    public function getDataFromAPI(): string
    {
        return BCoin::getFromAPI("/tx/{$this->hash}");
    }

    public function getHash()
    {
        return $this->hash;
    }
}
