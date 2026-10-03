<?php
/**
 * auth.php
 * -------------------------------------------------------
 * Stubbed out for Firebase Web SDK migration.
 * Authentication is now handled client-side via Firebase.
 * -------------------------------------------------------
 */

function authenticate_member($pdo, string $identifier, string $password, ?string &$authError = null): bool
{
    return true;
}

function authenticate_staff($pdo, string $username, string $password, ?string &$authError = null): bool
{
    return true;
}

function logout_user(): void
{
    // Handled client-side
}

function is_logged_in(): bool
{
    return true;
}

function is_member(): bool
{
    return true;
}

function is_staff(): bool
{
    return true;
}

function is_admin(): bool
{
    return true;
}

function require_member(): void
{
    // Handled client-side by Firebase
}

function require_staff(?string $mustBeRole = null): void
{
    // Handled client-side by Firebase
}

function current_user_id(): ?int
{
    return 1; // Dummy ID
}

