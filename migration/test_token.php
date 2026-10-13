<?php
$serviceAccountPath = __DIR__ . '/../firebase-admin-key.json';
if (!file_exists($serviceAccountPath)) {
    die("Service account not found");
}

$serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
$privateKey = $serviceAccount['private_key'];
$clientEmail = $serviceAccount['client_email'];

$header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
$now = time();
$payload = json_encode([
    'iss' => $clientEmail,
    'sub' => $clientEmail,
    'aud' => 'https://oauth2.googleapis.com/token',
    'iat' => $now,
    'exp' => $now + 3600,
    'scope' => 'https://www.googleapis.com/auth/datastore'
]);

function base64UrlEncode($text) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
}

$base64UrlHeader = base64UrlEncode($header);
$base64UrlPayload = base64UrlEncode($payload);

openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $privateKey, "sha256WithRSAEncryption");
$base64UrlSignature = base64UrlEncode($signature);
$jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwt
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "Token response: \n";
echo $response;
