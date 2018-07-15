<?php

namespace TPenaranda\BCoin\Models;

use TPenaranda\BCoin\BCoin;

class Server extends Model
{
    public function __construct()
    {
        return parent::__construct($this->getDataFromAPI());
    }

    public function getDataFromAPI(): string
    {
        return BCoin::getFromServerAPI('/');
    }
}
