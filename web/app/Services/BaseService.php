<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

use App\Models\BaseModel;
use App\Services\Concerns\HandlesCrudOperations;
use Modules\Admin\Models\OneMany;
use Modules\Logging\Utils\LogHandler;

/**
 * Base service class for all application services.
 * Provides transaction handling, logging helpers, and CRUD operations.
 */
abstract class BaseService {
    use HandlesCrudOperations;

    /**
     * Execute operation within transaction.
     * Returns the actual result from callback instead of just bool.
     *
     * @param  callable  $callback
     * @return mixed
     */
    protected function handleTransaction(callable $callback): mixed {
        if (DB::transactionLevel() > 0) {
            try {
                $result = $callback();
                if ($result === false) {
                    throw new Exception('Transaction callback returned false');
                }

                return $result;
            } catch (Exception $e) {
                $this->logError('Transaction failed', [], $e);

                // store error message so callers can inspect even when nested
                Session::put('transaction_error', $e->getMessage());

                // do not rethrow so callers receive null as before
                return null;
            }
        }

        // otherwise we are not in a transaction; start one normally
        try {
            return DB::transaction(function () use ($callback) {
                $result = $callback();
                if ($result === false) {
                    throw new Exception('Transaction callback returned false');
                }

                return $result;
            });
        } catch (Exception $e) {
            $this->logError('Transaction failed', [], $e);
            // TODO (Session::put) do not use session here; callers only check boolean result
            Session::put('transaction_error', $e->getMessage());

            return null;
        }
    }

    /**
     * Log info message.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void {
        LogHandler::info($message, $context);
    }

    /**
     * Log error message.
     *
     * @param  string  $message
     * @param  array  $context
     * @param  Exception|null  $exception
     * @return void
     */
    protected function logError(string $message, array $context = [], ?Exception $exception = null): void {
        LogHandler::error($message, $context, $exception);
    }

    /**
     * Log warning message.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void {
        LogHandler::warning($message, $context);
    }

    /**
     * Sync one_many relations for a given one_id and type.
     * Updates existing records status and creates new records as needed.
     *
     * @param  int  $oneId
     * @param  array  $desiredManyIds
     * @param  int  $type
     * @param  array|null  $allowedManyIds
     * @param  array|null  $preservedManyIds
     * @return void
     */
    protected function syncOneManyRelations(
        int $oneId,
        array $desiredManyIds,
        int $type,
        ?array $allowedManyIds = null,
        ?array $preservedManyIds = null
    ): void {
        // Normalize and filter desired IDs
        $desiredManyIds = array_values(array_unique(array_map('intval', $desiredManyIds)));
        $desiredManyIds = array_filter($desiredManyIds, static fn($id) => $id > 0);

        // Filter by allowed IDs if provided
        if ($allowedManyIds !== null) {
            $allowedManyIds = array_map('intval', $allowedManyIds);
            $desiredManyIds = array_values(array_intersect($allowedManyIds, $desiredManyIds));
        }

        // Add preserved IDs (e.g., main role that must always be included)
        if ($preservedManyIds !== null) {
            $preservedManyIds = array_map('intval', $preservedManyIds);
            $preservedManyIds = array_filter($preservedManyIds, static fn($id) => $id > 0);
            $desiredManyIds   = array_values(array_unique(array_merge($desiredManyIds, $preservedManyIds)));
        }

        // Load existing records
        $existing = OneMany::query()
            ->where('one_id', $oneId)
            ->where('type', $type)
            ->get()
            ->keyBy('many_id');

        $desiredMap = array_fill_keys($desiredManyIds, true);

        // Prepare bulk update arrays
        $idsToActivate   = [];
        $idsToDeactivate = [];
        $idsToCreate     = [];

        // Process existing records
        foreach ($existing as $manyId => $record) {
            $manyIdInt = (int) $manyId;
            $isDesired = isset($desiredMap[$manyIdInt]);
            $isActive  = (int) $record->status === BaseModel::STATUS_ACTIVE;

            if ($isDesired && !$isActive) {
                $idsToActivate[] = $record->id;
            } elseif (!$isDesired && $isActive) {
                // Check if this ID is preserved (should not be deactivated)
                if ($preservedManyIds !== null && in_array($manyIdInt, $preservedManyIds, true)) {
                    // Preserved ID should remain active even if not in desired list
                    continue;
                }
                $idsToDeactivate[] = $record->id;
            }

            // Remove from desired map if it exists
            unset($desiredMap[$manyIdInt]);
        }

        // Remaining items in desiredMap need to be created
        $idsToCreate = array_keys($desiredMap);

        // Bulk update status for existing records
        if (!empty($idsToActivate)) {
            OneMany::query()
                ->whereIn('id', $idsToActivate)
                ->update(['status' => BaseModel::STATUS_ACTIVE]);
        }

        if (!empty($idsToDeactivate)) {
            OneMany::query()
                ->whereIn('id', $idsToDeactivate)
                ->update(['status' => BaseModel::STATUS_INACTIVE]);
        }

        // Bulk create new records with status set
        if (!empty($idsToCreate)) {
            $now  = now();
            $data = [];
            foreach ($idsToCreate as $manyId) {
                // Check if record already exists (shouldn't happen, but safety check)
                if (!OneMany::checkExist($oneId, $manyId, $type)) {
                    $data[] = [
                        'one_id'     => $oneId,
                        'many_id'    => $manyId,
                        'type'       => $type,
                        'status'     => BaseModel::STATUS_ACTIVE,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($data)) {
                OneMany::query()->insert($data);
            }
        }
    }
}
