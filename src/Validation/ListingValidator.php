<?php

declare(strict_types=1);

namespace App\Validation;

class ListingValidator
{
    public static function validate(array $data): array
    {
        $errors = [];

        if (
            !isset($data['category_id']) ||
            filter_var($data['category_id'], FILTER_VALIDATE_INT) === false ||
            (int) $data['category_id'] <= 0
        ) {
            $errors['category_id'] = 'Category ID must be a positive integer.';
        }

        if (!isset($data['title']) || trim((string) $data['title']) === '') {
            $errors['title'] = 'Title is required.';
        } elseif (mb_strlen(trim((string) $data['title'])) > 200) {
            $errors['title'] = 'Title must not exceed 200 characters.';
        }

        if (
            isset($data['description']) &&
            $data['description'] !== null &&
            mb_strlen((string) $data['description']) > 5000
        ) {
            $errors['description'] =
                'Description must not exceed 5000 characters.';
        }

        if (
            !isset($data['price']) ||
            !is_numeric($data['price']) ||
            (float) $data['price'] < 0
        ) {
            $errors['price'] =
                'Price must be a non-negative number.';
        } elseif (
            preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                (string) $data['price']
            ) !== 1
        ) {
            $errors['price'] =
                'Price must have no more than 2 decimal places.';
        }

        if (
            isset($data['currency']) &&
            $data['currency'] !== ''
        ) {
            $currency = strtoupper(trim((string) $data['currency']));

            if (
                preg_match('/^[A-Z]{3}$/', $currency) !== 1
            ) {
                $errors['currency'] =
                    'Currency must be a 3-letter currency code.';
            }
        }

        if (
            isset($data['condition']) &&
            $data['condition'] !== null &&
            $data['condition'] !== ''
        ) {
            $allowedConditions = [
                'new',
                'like_new',
                'good',
                'fair',
                'poor',
            ];

            if (
                !in_array(
                    $data['condition'],
                    $allowedConditions,
                    true
                )
            ) {
                $errors['condition'] =
                    'Condition must be one of: new, like_new, good, fair, poor.';
            }
        }

        return $errors;
    }
}