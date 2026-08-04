<?php
namespace App\Services;
final readonly class GatewayResult { public function __construct(public bool $success, public string $id, public string $status, public array $payload=[]) {} public function toArray(): array { return get_object_vars($this); } }
