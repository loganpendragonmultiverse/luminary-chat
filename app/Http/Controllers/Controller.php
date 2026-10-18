<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function refreshCaptcha(string $key = 'captcha_answer'): string
    {
        $left = random_int(2, 9);
        $right = random_int(2, 9);
        session([$key => (string) ($left + $right)]);

        return "{$left} + {$right}";
    }

    protected function validateCaptcha(string $answer, string $key = 'captcha_answer'): bool
    {
        $expected = session()->pull($key);

        return $expected !== null && trim($answer) === (string) $expected;
    }
}
