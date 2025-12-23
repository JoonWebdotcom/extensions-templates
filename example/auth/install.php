<?php
// Check if we have embedded context stored
if (isset($_SESSION['embedded_context']) && empty($_GET['site'])) {
    $_GET['site'] = $_SESSION['embedded_context']['site'];
}

// Build OAuth URL with embedded context
$oauth_url = "https://accounts.joonweb.com/oauth/authorize?" . http_build_query([
    'client_id' => JOONWEB_CLIENT_ID,
    'scope' => JOONWEB_API_SCOPES,
    'redirect_uri' => JOONWEB_REDIRECT_URI,
    'state' => generateState(),
    'site' => $_GET['site'] ?? '',
    // Add embedded context to state so it persists
    'embedded' => isset($_SESSION['embedded_context']) ? '1' : '0'
]);