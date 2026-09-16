<?php
/**
 * validation.php
 * -------------------------------------------------------
 * Small, composable validators. Each function returns a
 * string error message, or null when the value is valid.
 * Combine them in a module's form handler like this:
 *
 *     $errors = [];
 *     if ($err = validate_required($_POST['firstname'] ?? '', 'First name')) {
 *         $errors[] = $err;
 *     }
 *     if ($err = validate_contact_number($_POST['contact_number'] ?? '')) {
 *         $errors[] = $err;
 *     }
 *     if (empty($errors)) { // save to DB }
 * -------------------------------------------------------
 */

function validate_required(string $value, string $fieldLabel): ?string
{
    return trim($value) === '' ? "{$fieldLabel} is required." : null;
}

function validate_max_length(string $value, int $max, string $fieldLabel): ?string
{
    return mb_strlen($value) > $max ? "{$fieldLabel} must be {$max} characters or fewer." : null;
}

/** Philippine-style mobile/landline numbers, digits/spaces/+/- only, 7-20 chars. */
function validate_contact_number(string $value): ?string
{
    if ($value === '') {
        return null; // optional field
    }
    return preg_match('/^[0-9+\-\s]{7,20}$/', $value)
        ? null
        : 'Contact number may only contain digits, spaces, + and -.';
}

function validate_date(string $value, string $fieldLabel): ?string
{
    if ($value === '') {
        return "{$fieldLabel} is required.";
    }
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return ($d && $d->format('Y-m-d') === $value) ? null : "{$fieldLabel} must be a valid date (YYYY-MM-DD).";
}

/**
 * Validates that a birth date is a valid calendar date, not in the future,
 * and within the valid contemporary century range (from 1900-01-01 to today).
 */
function validate_birthdate(string $value, string $fieldLabel = 'Birthday', bool $required = true): ?string
{
    if ($value === '') {
        return $required ? "{$fieldLabel} is required." : null;
    }
    $d = DateTime::createFromFormat('Y-m-d', $value);
    if (!$d || $d->format('Y-m-d') !== $value) {
        return "{$fieldLabel} must be a valid date (YYYY-MM-DD).";
    }
    $today = date('Y-m-d');
    if ($value > $today) {
        return "{$fieldLabel} cannot be a future date.";
    }
    if ($value < '1900-01-01') {
        return "{$fieldLabel} must be after January 1, 1900.";
    }
    return null;
}

function validate_civil_status(string $value): ?string
{
    $allowed = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];
    return in_array($value, $allowed, true) ? null : 'Please select a valid civil status.';
}

/** Confirms a value only contains letters, spaces, hyphens and apostrophes (names and text fields). */
function validate_name(string $value, string $fieldLabel, bool $required = true): ?string
{
    if ($value === '') {
        return $required ? "{$fieldLabel} is required." : null;
    }
    return preg_match("/^[a-zA-Z\s\-'\.]+$/", $value)
        ? null
        : "{$fieldLabel} may only contain text (letters, spaces, hyphens, periods, and apostrophes).";
}

/** Validates text-only fields (e.g. occupation, employer name, etc.) when provided */
function validate_text_only(?string $value, string $fieldLabel, bool $required = false): ?string
{
    if ($value === null || trim($value) === '') {
        return $required ? "{$fieldLabel} is required." : null;
    }
    return preg_match("/^[a-zA-Z0-9\s\-'\.,&()\/]+$/", $value)
        ? null
        : "{$fieldLabel} contains invalid characters.";
}

/**
 * Runs a list of [callable, ...args] validators and returns
 * all resulting error messages (empty array = all valid).
 * Example:
 *   $errors = run_validators([
 *       [fn() => validate_required($firstname, 'First name')],
 *       [fn() => validate_contact_number($contact)],
 *   ]);
 */
function run_validators(array $validatorCalls): array
{
    $errors = [];
    foreach ($validatorCalls as $call) {
        $result = $call[0]();
        if ($result !== null) {
            $errors[] = $result;
        }
    }
    return $errors;
}
