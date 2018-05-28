<?php

namespace TPenaranda\BCoin\Models;

use TPenaranda\BCoin\BCoinException;
use ReflectionObject;

abstract class Model
{
    abstract function getDataFromAPI();

    public function __construct($model_data)
    {
        if (is_object($model_data)) {
            $this->hydrate(json_encode($model_data));
        } elseif (is_array($model_data)) {
            foreach ($model_data as $key => $value) {
                $this->$key = $value;
            }
            $this->refresh();
        } elseif (json_decode($model_data)) {
            $this->hydrate($model_data);
        } else {
            throw new BCoinException("Can't construct BCoin Model with ${model_data} data. __construct method expects array, object or JSON encoded data.");
        }

        return true;
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

    protected function getMethodNameToHandleProperty(string $property)
    {
        if (method_exists($this, $method_name = 'add' . ucfirst(camel_case($property)) . 'Attribute')) {
            return $method_name;
        }

        return false;
    }

    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->$key;
        }

        if (is_string($key) && ($method_name = $this->getMethodNameToHandleProperty($key))) {
            return $this->$method_name();
        }

        return null;
    }

    public function __isset($key) {
        return property_exists($this, $key) || !empty($this->getMethodNameToHandleProperty($key));
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
