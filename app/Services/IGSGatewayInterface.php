<?php
namespace App\Services;
interface IGSGatewayInterface { public function registerSale(array $data): GatewayResult; public function reverseCommission(array $data): GatewayResult; }
