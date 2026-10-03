<?php
/**
 * functions.php
 * -------------------------------------------------------
 * Small, generic helper functions used across every module.
 * Nothing in here should know about a specific page — keep
 * page-specific logic in the module files themselves.
 * -------------------------------------------------------
 */

/**
 * Shortcut for htmlspecialchars() — use this every time you
 * print user-supplied data into HTML, to prevent XSS.
 *
 *     <p><?= e($member['firstname']) ?></p>
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirects to another page within the app and stops execution.
 * $path can be relative to BASE_URL, e.g. redirect('member/dashboard.php')
 */
function redirect(string $path): void
{
    // Allow absolute URLs to pass through untouched
    if (preg_match('#^https?://#', $path)) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    }
    exit;
}

/**
 * Trims and strips tags from raw input. Use this on every
 * $_POST / $_GET value before validating or saving it.
 */
function clean(?string $value): string
{
    return trim(strip_tags($value ?? ''));
}

/**
 * Calculates a person's current age from a birthdate.
 * Accepts 'YYYY-MM-DD' (matches the DATE columns in the schema).
 */
function calculate_age(?string $birthday): ?int
{
    if (empty($birthday) || $birthday === '0000-00-00') {
        return null;
    }
    try {
        $bday = new DateTime($birthday);
        $today = new DateTime('today');
        if ($bday > $today) {
            return null;
        }
        return (int) $bday->diff($today)->y;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Formats a DATE/DATETIME/TIMESTAMP column value for display.
 */
function format_date(?string $date, string $format = 'F j, Y'): string
{
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    try {
        return (new DateTime($date))->format($format);
    } catch (Exception $e) {
        return '—';
    }
}

/**
 * Generates a fresh CSRF token for a form and stores it in
 * the session. Call once per form render:
 *     <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies a submitted CSRF token matches the session's token.
 * Call at the top of every POST handler:
 *     verify_csrf($_POST['csrf_token'] ?? '');
 */
function verify_csrf(string $submittedToken): void
{
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        http_response_code(403);
        die('Invalid or expired form submission (CSRF check failed). Please go back and try again.');
    }
}

/**
 * Returns true if the submitted token matches the session CSRF token, false otherwise.
 */
function verify_csrf_token(?string $submittedToken): bool
{
    if (empty($_SESSION['csrf_token']) || empty($submittedToken)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submittedToken);
}

/**
 * Returns 'active' when $file matches the current script's
 * filename — used by the sidebar partials to highlight the
 * current page. e.g. nav_active('profile.php', $currentPage)
 */
function nav_active(string $file, string $currentPage): string
{
    return $file === $currentPage ? 'active' : '';
}


/**
 * Returns a standardized list of nationalities / demonyms for dropdown inputs.
 * Filipino is prioritized as the default standard option, followed by a comprehensive alphabetical list.
 *
 * @return string[] List of nationalities
 */
function get_nationalities_list(): array
{
    return [
        'Filipino',
        'Afghan',
        'Albanian',
        'Algerian',
        'American',
        'Andorran',
        'Angolan',
        'Argentine',
        'Armenian',
        'Australian',
        'Austrian',
        'Azerbaijani',
        'Bahamian',
        'Bahraini',
        'Bangladeshi',
        'Barbadian',
        'Belarusian',
        'Belgian',
        'Belizean',
        'Beninese',
        'Bhutanese',
        'Bolivian',
        'Bosnian',
        'Botswanan',
        'Brazilian',
        'British',
        'Bruneian',
        'Bulgarian',
        'Burkinabe',
        'Burmese',
        'Burundian',
        'Cambodian',
        'Cameroonian',
        'Canadian',
        'Cape Verdean',
        'Central African',
        'Chadian',
        'Chilean',
        'Chinese',
        'Colombian',
        'Comoran',
        'Congolese',
        'Costa Rican',
        'Croatian',
        'Cuban',
        'Cypriot',
        'Czech',
        'Danish',
        'Djiboutian',
        'Dominican',
        'Dutch',
        'East Timorese',
        'Ecuadorean',
        'Egyptian',
        'Emirati',
        'Equatorial Guinean',
        'Eritrean',
        'Estonian',
        'Ethiopian',
        'Fijian',
        'Finnish',
        'French',
        'Gabonese',
        'Gambian',
        'Georgian',
        'German',
        'Ghanaian',
        'Greek',
        'Grenadian',
        'Guatemalan',
        'Guinean',
        'Guyanese',
        'Haitian',
        'Honduran',
        'Hungarian',
        'Icelander',
        'Indian',
        'Indonesian',
        'Iranian',
        'Iraqi',
        'Irish',
        'Israeli',
        'Italian',
        'Ivorian',
        'Jamaican',
        'Japanese',
        'Jordanian',
        'Kazakh',
        'Kenyan',
        'Korean (South)',
        'Korean (North)',
        'Kuwaiti',
        'Kyrgyz',
        'Laotian',
        'Latvian',
        'Lebanese',
        'Liberian',
        'Libyan',
        'Liechtensteiner',
        'Lithuanian',
        'Luxembourgish',
        'Macedonian',
        'Malagasy',
        'Malawian',
        'Malaysian',
        'Maldivian',
        'Malian',
        'Maltese',
        'Mauritanian',
        'Mauritian',
        'Mexican',
        'Micronesian',
        'Moldovan',
        'Monacan',
        'Mongolian',
        'Montenegrin',
        'Moroccan',
        'Mozambican',
        'Namibian',
        'Nauruan',
        'Nepalese',
        'New Zealander',
        'Nicaraguan',
        'Nigerian',
        'Nigerien',
        'Norwegian',
        'Omani',
        'Pakistani',
        'Palauan',
        'Palestinian',
        'Panamanian',
        'Papua New Guinean',
        'Paraguayan',
        'Peruvian',
        'Polish',
        'Portuguese',
        'Qatari',
        'Romanian',
        'Russian',
        'Rwandan',
        'Saint Lucian',
        'Salvadoran',
        'Samoan',
        'Saudi Arabian',
        'Senegalese',
        'Serbian',
        'Seychellois',
        'Sierra Leonean',
        'Singaporean',
        'Slovak',
        'Slovenian',
        'Solomon Islander',
        'Somali',
        'South African',
        'Spanish',
        'Sri Lankan',
        'Sudanese',
        'Surinamese',
        'Swazi',
        'Swedish',
        'Swiss',
        'Syrian',
        'Taiwanese',
        'Tajik',
        'Tanzanian',
        'Thai',
        'Togolese',
        'Tongan',
        'Trinidadian',
        'Tunisian',
        'Turkish',
        'Turkmen',
        'Tuvaluan',
        'Ugandan',
        'Ukrainian',
        'Uruguayan',
        'Uzbek',
        'Vanuatu',
        'Venezuelan',
        'Vietnamese',
        'Yemeni',
        'Zambian',
        'Zimbabwean'
    ];
}

