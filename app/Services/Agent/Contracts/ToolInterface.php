<?php

namespace App\Services\Agent\Contracts;

interface ToolInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function getParameterSchema(): array;
    public function execute(array $parameters): array;
}
