<?php

function requireJsonContentTypeMiddleware(?callable $next = null) {
    if (php_sapi_name() === 'cli') {
        return $next ? $next() : null;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

    if (empty($contentType) && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $val) {
            if (strcasecmp($key, 'Content-Type') === 0) {
                $contentType = $val;
                break;
            }
        }
    }

    if (empty($contentType) || stripos($contentType, 'application/json') === false) {
        http_response_code(415);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => 'error',
            'code' => 415,
            'message' => 'Header Content-Type tidak valid atau tidak ditemukan. Harap sertakan "Content-Type: application/json".'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($next) {
        return $next();
    }
}
