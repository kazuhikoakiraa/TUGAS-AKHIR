<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NpwpRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Allow empty values if NPWP is optional
        }

        // Remove all non-numeric characters
        $cleanNpwp = preg_replace('/[^0-9]/', '', $value);

        // NPWP must be exactly 15 digits
        if (strlen($cleanNpwp) !== 15) {
            $fail('NPWP must be exactly 15 digits.');
            return;
        }

        // Check if it's not all zeros or same digit
        if (preg_match('/^0{15}$/', $cleanNpwp)) {
            $fail('NPWP cannot be all zeros.');
            return;
        }

        if (preg_match('/^(\d)\1{14}$/', $cleanNpwp)) {
            $fail('NPWP cannot be the same digit repeated.');
            return;
        }

        // Additional validation: Check basic NPWP structure
        // First 2 digits: should not be 00
        if (substr($cleanNpwp, 0, 2) === '00') {
            $fail('Invalid NPWP format.');
            return;
        }

        // 9th digit (check digit position): should be 1-9
        $checkDigit = substr($cleanNpwp, 8, 1);
        if (!in_array($checkDigit, ['1', '2', '3', '4', '5', '6', '7', '8', '9'])) {
            $fail('Invalid NPWP check digit.');
            return;
        }
    }
}
