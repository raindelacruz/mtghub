<?php

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';
        $content = ob_get_clean();

        $content = preg_replace_callback(
            '/<form\b([^>]*)method=["\']post["\']([^>]*)>/i',
            static fn (array $matches): string => $matches[0] . csrf_field(),
            $content
        ) ?? $content;

        require APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'main.php';
    }
}
