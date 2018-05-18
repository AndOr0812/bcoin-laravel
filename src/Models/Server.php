<?php

namespace TPenaranda\BCoin\Models;

use TPenaranda\BCoin\BCoin;

class Server extends Model
{
    public function getDataFromAPI(): string
    {
        return BCoin::getFromAPI('/');
    }
}
