<?php
declare(strict_types=1);

namespace App;

/**
 * CORS e Response Headers Manager
 * 
 * Garante que os headers CORS sejam configurados corretamente
 * e que nenhum output seja enviado antes dos headers.
 */
final class ResponseManager
{
    private bool $headersSent = false;

    public function setCORSHeaders(): void
    {
        if ($this->headersSent) {
            return;
        }

        header('Access-Control-Allow-Origin: *', true);
        header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS', true);
        header('Access-Control-Allow-Headers: Content-Type, Authorization', true);
        header('Access-Control-Max-Age: 3600', true);

        $this->headersSent = true;
    }

    public function setJsonContentType(): void
    {
        if ($this->headersSent && function_exists('headers_list')) {
            // Verificar se Content-Type já foi definido
            $headers = headers_list();
            foreach ($headers as $header) {
                if (stripos($header, 'content-type') === 0) {
                    return; // Já definido
                }
            }
        }

        header('Content-Type: application/json; charset=utf-8', true);
    }

    public function handlePreflight(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
            return false;
        }

        $this->setCORSHeaders();
        http_response_code(204);
        return true;
    }
}
