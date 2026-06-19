<?php

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->normalize($_GET['url'] ?? '/');

        if ($method === 'POST' && !csrf_is_valid($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo '403 - Security token expired or invalid. Go back, refresh the page, and try again.';
            return;
        }

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '404 - Page not found';
            return;
        }

        [$controllerName, $action] = $handler;
        require APP_PATH . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $controllerName . '.php';

        $controller = new $controllerName();
        $controller->$action();
    }

    private function normalize(string $path): string
    {
        $path = trim($path, '/');
        return $path === '' ? '/' : $path;
    }
}
