<?php

declare(strict_types=1);

namespace PrintBridge\Controllers;

use PrintBridge\Repositories\QueueRepository;
use PrintBridge\Services\AdminAuth;
use PrintBridge\Support\Http;
use PrintBridge\Support\PayloadPreview;
use PrintBridge\Support\View;

final class QueueController
{
    public static function index(): void
    {
        AdminAuth::requireLogin();
        View::render('queue/index', [
            'waiting' => QueueRepository::waiting(),
            'archived' => QueueRepository::archived(),
        ]);
    }

    public static function show(int $id): void
    {
        AdminAuth::requireLogin();
        $job = QueueRepository::find($id);

        if ($job === null) {
            Http::notFound();
            return;
        }

        $preview = PayloadPreview::detect((string) $job['payload'], (string) $job['content_type']);
        $previewText = null;
        $previewTruncated = false;

        if ($preview['type'] === 'text') {
            $payload = (string) $job['payload'];
            $previewTruncated = strlen($payload) > 20000;
            $previewText = $previewTruncated ? substr($payload, 0, 20000) : $payload;
        }

        View::render('queue/show', [
            'job' => $job,
            'preview' => $preview,
            'previewText' => $previewText,
            'previewTruncated' => $previewTruncated,
        ]);
    }

    public static function payload(int $id): void
    {
        AdminAuth::requireLogin();
        $job = QueueRepository::find($id);

        if ($job === null) {
            Http::notFound();
            return;
        }

        $payload = (string) $job['payload'];
        $preview = PayloadPreview::detect($payload, (string) $job['content_type']);
        $disposition = $preview['type'] === 'binary' ? 'attachment' : 'inline';

        http_response_code(200);
        header('Content-Type: ' . $preview['mime']);
        header('Content-Length: ' . strlen($payload));
        header('Content-Disposition: ' . $disposition . '; filename="job-' . $id . '"');
        header('X-Content-Type-Options: nosniff');
        echo $payload;
    }

    public static function delete(int $id): void
    {
        AdminAuth::requireLogin();
        QueueRepository::delete($id);
        Http::redirect('/queue');
    }
}
