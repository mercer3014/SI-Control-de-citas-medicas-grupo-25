<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResendService
{
    protected string $apiKey;

    protected string $from;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.resend.key') ?? '');
        $this->from = (string) (config('services.resend.from') ?: 'Clínica Vida Salud <onboarding@resend.dev>');
    }

    public function sendEmail(string $to, string $subject, string $htmlContent): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->post('https://api.resend.com/emails', [
                    'from' => $this->from,
                    'to' => [$to],
                    'subject' => $subject,
                    'html' => $htmlContent,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            Log::error('Resend API Error: '.$response->body());

            return ['success' => false, 'error' => $response->body()];
        } catch (\Throwable $e) {
            Log::error('Resend Exception: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
