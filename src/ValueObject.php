<?php

namespace TakSolder\DataClass;

/**
 * Class ValueObject
 * @package Source\Contracts
 */
abstract class ValueObject
{
    /**
     * @var mixed
     */
    protected $value;

    public function __construct($value)
    {
        if (!$this->validate($value)) {
            throw new \InvalidArgumentException('invalid value');
        }
        $this->value = $value;
    }

    protected function validate($value)
    {
        return true;
    }
}
