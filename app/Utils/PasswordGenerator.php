<?php

namespace App\Utils;

class PasswordGenerator
{
    public static function generate($length = 12): string
    {
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()-_+=<>?';

        $password =
            $upper[random_int(0, strlen($upper) - 1)] .
            $lower[random_int(0, strlen($lower) - 1)] .
            $numbers[random_int(0, strlen($numbers) - 1)] .
            $special[random_int(0, strlen($special) - 1)];

        $allCharacters = $upper . $lower . $numbers . $special;
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allCharacters[random_int(0, strlen($allCharacters) - 1)];
        }

        return str_shuffle($password);
    }

    public static function validate(string $password): bool
    {
        return self::validateWithFeedback($password)['valid'];
    }

    public static function validateWithFeedback(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Debe contener al menos una letra mayúscula.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Debe contener al menos una letra minúscula.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Debe contener al menos un número.';
        }

        if (!preg_match('/[!@#$%^&*()\-_+=<>?]/', $password)) {
            $errors[] = 'Debe contener al menos un carácter especial.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
