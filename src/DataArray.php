<?php

namespace TakSolder\DataClass;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use ArrayAccess;
use Countable;

/**
 * Class Request
 * @package Source\Contracts
 */
class DataArray implements Arrayable, ArrayAccess, Jsonable, Countable
{
    const READ_ONLY = false;
    const SCALAR_TYPES = [
        'int',
        'float',
        'bool',
        'string',
    ];

    protected array $items = [];
    protected string $type = 'mixed';
    /**
     * @var callable
     */
    protected $validateFunc;

    /**
     * @param array $items
     * @param string|callable|null $type
     */
    public function __construct(array $items = [], $type = null)
    {
        if ($type && in_array($type, self::SCALAR_TYPES)) {
            $this->type = $type;
            $this->validateFunc = 'is_' . $type;
        } elseif (is_callable($type)) {
            $this->validateFunc = $type;
        } elseif (class_exists($type) || interface_exists($type) || trait_exists($type)) {
            $this->validateFunc = fn($value) => $value instanceof $this->type;
            $this->type = $type;
        } else {
            throw new \InvalidArgumentException('Unknown type');
        }

        array_walk($items, function ($item, $offset) {
            if (!$this->validate($item)) {
                throw new \InvalidArgumentException('mismatch type:' . $offset);
            }
        });
        $this->items = array_values($items);
    }

    /**
     * @param $item
     * @return bool
     */
    protected function validate($item): bool
    {
        return call_user_func($this->validateFunc, $item);
    }

    /**
     * @return array
     */
    public function items()
    {
        return $this->items;
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(fn($item) => ($item instanceof Arrayable ? $item->toArray() : $item), $this->items);
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
     * @return int
     */
    public function count()
    {
        return count($this->items());
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return  $this->items[$offset];
        }

        return null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws \Exception
     */
    public function offsetSet($offset, $value)
    {
        if (static::READ_ONLY) {
            throw new \Error(static::class . ' is read only');
        }

        $this->validate($value);
        if ($this->offsetExists($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (static::READ_ONLY) {
            throw new \Error(static::class . ' is read only');
        }

        if ($this->offsetExists($offset)) {
            unset($this->items[$offset]);
        }
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
     * @throws \Exception
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
