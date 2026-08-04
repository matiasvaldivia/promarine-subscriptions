<?php
namespace App\Services;
class MockIGSGateway implements IGSGatewayInterface { public function registerSale(array $data):GatewayResult{return new GatewayResult(true,'mock_igs_'.bin2hex(random_bytes(6)),'recorded',['is_mock'=>true]);} public function reverseCommission(array $data):GatewayResult{return new GatewayResult(true,'mock_igs_refund_'.bin2hex(random_bytes(6)),'reversed',['is_mock'=>true]);} }
