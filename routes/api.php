<?php

use Illuminate\Http\Request;
use App\Services\Teams\TeamsNotificationService;
use Illuminate\Support\Facades\Route;

Route::post('/teams-notify', function (Request $request, TeamsNotificationService $teams) {
    $validated = $request->validate([
        'title'       => 'required|string',
        'message'     => 'required|string',
        'url'         => 'nullable|url',
        'button_text' => 'nullable|string',
        'channels'    => 'nullable|array',
        'channels.*'  => 'string',
    ]);

    $results = $teams->send(
        title: $validated['title'],
        message: $validated['message'],
        url: $validated['url'] ?? null,
        buttonText: $validated['button_text'] ?? 'Review & Approve',
        channels: $validated['channels'] ?? ['default'],
    );

    // $results looks like ['default' => true, 'mondi' => true]
    $allSucceeded = !in_array(false, $results, true);

    return response()->json([
        'success' => $allSucceeded,
        'results' => $results,   // include per-channel breakdown, useful for debugging
        'message' => $allSucceeded
            ? 'Teams notification sent successfully!'
            : 'One or more Teams notifications failed to send.',
    ], $allSucceeded ? 200 : 500);
});