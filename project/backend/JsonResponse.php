<?php
declare(strict_types=1);

namespace App;

final class JsonResponse
{
    private static ?ResponseManager $responseManager = null;

    private function __construct()
    {
    }

    private static function getResponseManager(): ResponseManager
    {
        if (self::$responseManager === null) {
            self::$responseManager = new ResponseManager();
        }

        return self::$responseManager;
    }

    public static function send(int $statusCode, array $payload): void
    {
        $manager = self::getResponseManager();
        $manager->setCORSHeaders();
        $manager->setJsonContentType();

        http_response_code($statusCode);
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public static function success(array $data, string $message = '', int $statusCode = 200): void
    {
        $payload = ['success' => true, 'data' => $data];

        if ($message !== '') {
            $payload['message'] = $message;
        }

        self::send($statusCode, $payload);
    }

    public static function created(array $data, string $message = 'Recurso criado com sucesso.'): void
    {
        self::send(201, [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public static function error(string $message, int $statusCode = 400): void
    {
        self::send($statusCode, [
            'success' => false,
            'message' => $message,
        ]);
    }

    public static function notFound(string $message = 'Recurso não encontrado.'): void
    {
        self::error($message, 404);
    }

    public static function unprocessableEntity(string $message): void
    {
        self::error($message, 422);
    }

    public static function methodNotAllowed(string $message = 'Método não permitido.'): void
    {
        self::error($message, 405);
    }

    public static function internalServerError(string $message = 'Erro interno no servidor.'): void
    {
        self::error($message, 500);
    }
}
