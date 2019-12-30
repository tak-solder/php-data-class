<?php

namespace TakSolder\DataClass;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use ArrayAccess;

/**
 * Class Request
 * @package Source\Contracts
 */
abstract class DataObject implements Arrayable, ArrayAccess, Jsonable
{
    const READ_ONLY = false;
    private array $_keys = [];

    public function __construct(array $data = [])
    {
        $this->_keys = array_filter(array_keys(get_class_vars(static::class)), fn($key) => strpos($key, '_') !== 0);
        $this->_fill($data);
    }

    /**
     * @return array
     */
    public function keys()
    {
        return $this->_keys;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function fill(array $params)
    {
        if (static::READ_ONLY) {
            throw new \Error(static::class . ' is read only');
        }
        $this->_fill($params);

        return $this;
    }

    /**
     * @param array $params
     */
    private function _fill(array $params)
    {
        foreach ($params as $key => $value) {
            if (!in_array($key, $this->_keys)) {
                continue;
            }

            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            } else {
                $this->$key = $value;
            }
        }
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $results = [];
        foreach ($this->_keys as $key) {
            $value = $this->$key;
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }
            $results[$key] = $value;
        }

        return $results;
    }

    /**
     * @param int $options
     * @return false|string
     * @throws \Exception
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->toArray(), $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \Exception(json_last_error_msg());
        }

        return $json;
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return in_array($offset, $this->_keys) && isset($this->$offset);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (in_array($offset, $this->_keys)) {
            return  $this->$offset;
        }

        return null;
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->_fill([$offset => $value]);
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->_fill([$offset => null]);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
}
