<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\Agent\Orchestrator\AgentOrchestrator;
use App\Services\Agent\Tools\DocumentSearchRagTool;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('agent:run {prompt}', function (string $prompt, AgentOrchestrator $orchestrator) {
    $this->info("Initializing Agent with prompt: '{$prompt}'");

    $orchestrator->registerTool(new DocumentSearchRagTool());
    $result = $orchestrator->run($prompt);

    $this->line('');
    $this->info('--- Agent Output ---');
    $this->line($result['response']);
    $this->info("Execution Status: {$result['status']}");
})->purpose('Executes the autonomous AI agent orchestrator with ReAct loop and RAG tool');
