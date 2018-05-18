<?php

namespace TPenaranda\BCoin\Models;

use ReflectionObject;

abstract class Model
{
    abstract function getDataFromAPI();

    public function __construct($model_data)
    {
        if (is_object($model_data)) {
            $this->hydrate(json_encode($model_data));
        } elseif (empty(json_decode($model_data))) {
            $this->hash = $model_data;
            $this->refresh();
        } else {
            $this->hydrate($model_data);
        }
    }

    public function refresh(): Model
    {
        return $this->hydrate($this->getDataFromAPI());
    }

    public function hydrate(string $api_response): Model
    {
        $reflection = new ReflectionObject($this);

        foreach (json_decode($api_response) ?? [] as $key => $value) {
            if (!$reflection->hasProperty($key)) {
                $this->$key = $value;
            } else {
                $property = $reflection->getProperty($key);
                if ($property->isPublic()) {
                    $this->$key = $value;
                } else {
                    $property->setAccessible(true);
                    $property->setValue($this, $value);
                }
            }
        }

        return $this;
    }
}
