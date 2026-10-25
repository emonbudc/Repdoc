<?php

class CaptchaSolverClient {
    private string $serviceUrl;
    private int $timeout;

    public function __construct(string $serviceUrl = 'http://localhost:8001', int $timeout = 30) {
        $this->serviceUrl = rtrim($serviceUrl, '/');
        $this->timeout = $timeout;
    }

    public function solve(string $base64Image, string $mimeType = 'image/png'): array {
        $payload = json_encode([
            'image_base64' => $base64Image,
            'mime_type' => $mimeType
        ]);

        $ch = curl_init($this->serviceUrl . '/solve');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($error) {
            return ['ok' => false, 'error' => 'Service connection failed: ' . $error];
        }

        if ($httpCode !== 200) {
            return ['ok' => false, 'error' => "Service returned HTTP $httpCode"];
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['ok' => false, 'error' => 'Invalid JSON response from service'];
        }

        return $data;
    }

    public function healthCheck(): bool {
        $ch = curl_init($this->serviceUrl . '/health');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $httpCode === 200;
    }
}