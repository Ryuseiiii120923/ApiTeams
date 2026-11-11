<?php

namespace App\Services\Teams;

use Illuminate\Support\Facades\Http;

class TeamsNotificationService
{
    public function send(
        string $title,
        string $message,
        ?string $url = null,
        string $buttonText = 'Review & Approve',
        array $channels = ['default']   // ← accept multiple channels now
    ): array {
        $card = $this->buildCard($title, $message, $url, $buttonText);

        $payload = [
            'type' => 'message',
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'contentUrl' => null,
                    'content' => $card,
                ],
            ],
        ];

        $results = [];

        foreach ($channels as $channel) {
            $webhookUrl = $this->resolveWebhookUrl($channel);

            if (!$webhookUrl) {
                $results[$channel] = false;
                continue;
            }

            try {
                $response = Http::post($webhookUrl, $payload);
                $results[$channel] = $response->successful();
            } catch (\Throwable $e) {
                $results[$channel] = false;
            }
        }

        return $results; // e.g. ['default' => true, 'mondi' => false]
    }

    protected function buildCard(string $title, string $message, ?string $url, string $buttonText): array
    {
        $card = [
            'type' => 'AdaptiveCard',
            '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
            'version' => '1.2',
            'body' => [
                [
                    'type' => 'TextBlock',
                    'text' => $title,
                    'size' => 'Medium',
                    'weight' => 'Bolder',
                ],
                [
                    'type' => 'TextBlock',
                    'text' => $message,
                    'wrap' => true,
                ],
            ],
        ];

        if ($url) {
            $card['actions'] = [
                [
                    'type' => 'Action.OpenUrl',
                    'title' => $buttonText,
                    'url' => $url,
                ],
            ];
        }

        return $card;
    }

    protected function resolveWebhookUrl(string $channel): ?string
    {
        return match ($channel) {
            'mondi'   => config('services.teams.webhook_mondi'),
            'default' => config('services.teams.webhook_url'),
            'angel' => config('services.teams.webhook_angel'),
            'plant1' => config('services.teams.webhook_plant1'),
            'plant2' => config('services.teams.webhook_plant2'),
            default   => config('services.teams.webhook_url'),
        };
    }
}