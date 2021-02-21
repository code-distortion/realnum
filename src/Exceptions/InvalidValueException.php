<?php

namespace CodeDistortion\RealNum\Exceptions;

/**
 * Exception for when invalid arguments are passed.
 */
class InvalidValueException extends RealNumException
{
    use ExceptionTrait;

    /**
     * Return a new instance when no comparison values were passed.
     *
     * @return static
     */
    public static function noComparisonValues(): self
    {
        return new static('No comparison values were passed');
    }

    /**
     * Return a new instance when the given value is not numeric.
     *
     * @param mixed $value The non-numeric value.
     * @return static
     */
    public static function notNumeric($value): self
    {
        return new static('The given value \'' . $value . '\' is not numeric');
    }

    /**
     * Return a new instance when an object of the given class is incompatible.
     *
     * @param string $class The incompatible class.
     * @return static
     */
    public static function incompatibleObject(string $class): self
    {
        return new static(
            'Object of type ' . $class . ' is not compatible for operations with ' . static::getCallingClass()
        );
    }
}
