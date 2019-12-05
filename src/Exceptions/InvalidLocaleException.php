<?php

namespace CodeDistortion\RealNum\Exceptions;

/**
 * Exception for when invalid arguments are passed
 */
class InvalidLocaleException extends RealNumException
{
    /**
     * Return a new instance when a locale couldn't be resolved
     *
     * @param mixed $localeIdentifier The locale being resolved.
     * @return static
     */
    public static function unresolvableLocale($localeIdentifier): self
    {
        return new static('Locale "'.$localeIdentifier.'" could not be resolved');
    }
}
