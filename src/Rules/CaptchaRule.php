<?php

namespace AronLabs\Captcha\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Lang;

class CaptchaRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $sessionKey = 'aron_captcha_code';
        $correctCode = Session::get($sessionKey);

        if (!$correctCode || strtolower((string)$value) !== strtolower((string)$correctCode)) {
            Session::forget($sessionKey);

            $errorMessage = Lang::get('aronlabs-captcha::validation.captcha');

            $fail($errorMessage);
            return;
        }

        Session::forget($sessionKey);
    }
}
