<?php

namespace Pterodactyl\Rules;

use Illuminate\Contracts\Validation\Rule;

class Username implements Rule
{
    public const VALIDATION_REGEX = '/^[a-z0-9]([\w\.-]+)[a-z0-9]$/';

    /**
     * Validate that a username contains only the allowed characters and starts/ends
     * with alpha-numeric characters.
     *
     * Allowed characters: a-z0-9_-.
     *
     * @param string $attribute
     * @param mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return preg_match(self::VALIDATION_REGEX, mb_strtolower($value));
    }

    /**
     * Return a validation message for use when this rule fails.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must start and end with alpha-numeric characters and
                contain only letters, numbers, dashes, underscores, and periods.';
    }
}
