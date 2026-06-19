<?php

class PolicyController extends Controller
{
    public function show(): void
    {
        $key = trim($_GET['policy'] ?? '');
        $policies = require APP_PATH . '/policies.php';
        if (!isset($policies[$key])) { http_response_code(404); echo '404 - Policy not found'; return; }
        $this->view('policies.show', ['title' => $policies[$key]['title'], 'policy' => $policies[$key]]);
    }
}
