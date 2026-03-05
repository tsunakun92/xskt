<?php

namespace App\Datatables\Services;

use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

use Modules\Logging\Utils\LogHandler;

/**
 * Centralized error handling service for datatable components
 */
class ErrorHandlingService {
    /**
     * Handle render operation with error management
     */
    public function handleRender(callable $renderCallback, array $context = []): View {
        try {
            return $renderCallback();
        } catch (Throwable $e) {
            $this->logError($e, $context);

            return $this->createErrorView($e, $context);
        }
    }

    /**
     * Handle data fetch operation with error management
     */
    public function handleDataFetch(callable $fetchCallback, array $context = []): mixed {
        try {
            return $fetchCallback();
        } catch (Throwable $e) {
            $this->logError($e, $context);

            return $this->createEmptyResult($context);
        }
    }

    /**
     * Log error with context information
     */
    public function logError(Throwable $e, array $context = []): void {
        LogHandler::error('DataTables error: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'message'   => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
            'context'   => $context,
        ]);
    }

    /**
     * Create error view for render failures
     */
    protected function createErrorView(Throwable $e, array $context = []): View {
        $errorMessage = $this->getErrorMessage($e);

        return view('datatables::components.datatables.index', [
            'columnFilterValues' => [],
            'hasErrors'          => true,
            'errorMessage'       => $errorMessage,
            'data'               => collect([]), // Empty collection
        ]);
    }

    /**
     * Create empty result for data fetch failures
     */
    protected function createEmptyResult(array $context = []): mixed {
        // Return empty paginator if context suggests pagination is expected
        if (isset($context['perPage'])) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                $context['perPage'] ?? 10,
                1,
                ['path' => request()->url()]
            );
        }

        return collect([]);
    }

    /**
     * Get user-friendly error message
     */
    protected function getErrorMessage(Throwable $e): string {
        if (app()->isProduction()) {
            return __('datatables::datatables.error.generic');
        }

        return match (get_class($e)) {
            InvalidArgumentException::class                             => __('datatables::datatables.error.invalid_config') . ': ' . $e->getMessage(),
            \Illuminate\Database\QueryException::class                  => __('datatables::datatables.error.database'),
            \Illuminate\Database\Eloquent\ModelNotFoundException::class => __('datatables::datatables.error.model_not_found'),
            default                                                     => __('datatables::datatables.error.render_failed') . ($e->getMessage() ? ': ' . $e->getMessage() : ''),
        };
    }

    /**
     * Check if error is recoverable
     */
    public function isRecoverableError(Throwable $e): bool {
        $recoverableExceptions = [
            \Illuminate\Database\QueryException::class,
            \Illuminate\Http\Client\RequestException::class,
        ];

        return in_array(get_class($e), $recoverableExceptions);
    }

    /**
     * Get error recovery suggestions
     */
    public function getRecoverySuggestions(Throwable $e): array {
        return match (get_class($e)) {
            \Illuminate\Database\QueryException::class => [
                'Check database connection',
                'Verify table structure',
                'Review query parameters',
            ],
            InvalidArgumentException::class            => [
                'Check component configuration',
                'Verify required parameters',
                'Review data types',
            ],
            default                                    => [
                'Try refreshing the page',
                'Clear browser cache',
                'Contact support if issue persists',
            ],
        };
    }
}
