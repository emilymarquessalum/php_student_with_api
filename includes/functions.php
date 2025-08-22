<?php
function sanitize_input($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function is_logged_in()
{
    return isset($_SESSION['prof_id']);
}

function redirect_if_not_logged_in()
{
    if (!is_logged_in()) {
        header("Location: ../login.php");
        exit();
    }
}

function api_request($endpoint, $method = 'GET', $data = [], $headers = [])
{
    // Get API base URL from environment variables
    $api_base_url = $_ENV['API_BASE_URL'] ?? 'http://localhost:8000';
    $url = $api_base_url . $endpoint;

    $ch = curl_init();

    // Set common headers
    $default_headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    // Merge with custom headers
    $all_headers = array_merge($default_headers, $headers);

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $all_headers);

    // Set method and data
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    // Execute request
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    // Handle response
    if ($error) {
        return [
            'success' => false,
            'error' => $error,
            'status_code' => $status_code
        ];
    }

    $response_data = json_decode($response, true);

    return [
        'success' => $status_code >= 200 && $status_code < 300,
        'data' => $response_data,
        'status_code' => $status_code
    ];
}
