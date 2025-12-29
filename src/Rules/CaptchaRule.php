<?php

namespace AronLabs\Captcha\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Session;

class CaptchaRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $sessionKey = 'aron_captcha_code';
        $correctCode = Session::get($sessionKey);

        if (!$correctCode || strtolower((string)$value) !== strtolower((string)$correctCode)) {

            Session::forget($sessionKey);

            $fail('aronlabs-captcha::validation.incorrect')->translate();

            return;
        }


        Session::forget($sessionKey);
    }
}