<?php

declare(strict_types=1);

namespace PrintBridge\Controllers;

use PrintBridge\Repositories\QueueRepository;
use PrintBridge\Services\AdminAuth;
use PrintBridge\Support\Http;
use PrintBridge\Support\Pagination;
use PrintBridge\Support\PayloadPreview;
use PrintBridge\Support\View;

final class QueueController
{
    private const ARCHIVED_STATUSES = ['printed', 'cancelled'];

    public static function index(): void
    {
        AdminAuth::requireLogin();
        [$page, $perPage] = self::paginationParams();

        View::render('queue/index', [
            'jobs' => QueueRepository::waiting($perPage, self::offset($page, $perPage)),
            'total' => QueueRepository::countWaiting(),
            'page' => $page,
            'perPage' => $perPage,
            'basePath' => '/queue',
        ]);
    }

    public static function archive(): void
    {
        AdminAuth::requireLogin();
        [$page, $perPage] = self::paginationParams();

        View::render('queue/archive', [
            'jobs' => QueueRepository::archived($perPage, self::offset($page, $perPage)),
            'total' => QueueRepository::countArchived(),
            'page' => $page,
            'perPage' => $perPage,
            'basePath' => '/archive',
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
            'isArchived' => in_array($job['status'], self::ARCHIVED_STATUSES, true),
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
        $status = QueueRepository::statusOf($id);
        $isArchived = $status !== null && in_array($status, self::ARCHIVED_STATUSES, true);

        QueueRepository::delete($id);
        Http::redirect($isArchived ? '/archive' : '/queue');
    }

    public static function deleteSelectedWaiting(): void
    {
        AdminAuth::requireLogin();
        QueueRepository::deleteByIds(self::submittedIds());
        Http::redirect('/queue');
    }

    public static function deleteAllWaiting(): void
    {
        AdminAuth::requireLogin();
        QueueRepository::deleteAllWaiting();
        Http::redirect('/queue');
    }

    public static function deleteSelectedArchived(): void
    {
        AdminAuth::requireLogin();
        QueueRepository::deleteByIds(self::submittedIds());
        Http::redirect('/archive');
    }

    public static function deleteAllArchived(): void
    {
        AdminAuth::requireLogin();
        QueueRepository::deleteAllArchived();
        Http::redirect('/archive');
    }

    /**
     * @return array<int, int>
     */
    private static function submittedIds(): array
    {
        $ids = $_POST['ids'] ?? [];

        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    /**
     * @return array{0: int, 1: int|null}
     */
    private static function paginationParams(): array
    {
        $page = Pagination::resolvePage($_GET['page'] ?? null);
        $perPage = Pagination::resolvePerPage($_GET['per_page'] ?? null);

        return [$page, $perPage];
    }

    private static function offset(int $page, ?int $perPage): int
    {
        return $perPage === null ? 0 : ($page - 1) * $perPage;
    }
}
