<?php

namespace App\Utils;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

use Modules\Logging\Utils\LogHandler;

/**
 * SQL transaction handler utility.
 *
 * @param  callable  $callback
 *
 * @throws Exception
 *
 * @return bool
 */
class SqlHandler {
    /**
     * Execute callback within database transaction.
     *
     * @param  callable  $callback
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function handleTransaction(callable $callback): bool {
        try {
            return DB::transaction(function () use ($callback) {
                $result = $callback();
                if ($result === false) {
                    throw new Exception('Transaction callback returned false');
                }

                return true;
            });
        } catch (Exception $e) {
            LogHandler::databaseError('Transaction failed', $e);
            // TODO (Session::put) do not use session here; callers only check boolean result
            Session::put('transaction_error', $e->getMessage());

            return false;
        }
    }
}
