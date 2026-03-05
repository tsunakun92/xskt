<?php

namespace App\Datatables\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Throwable;

use Modules\Logging\Utils\LogHandler;

/**
 * Service for managing filter states in session storage with UUID-based keys
 *
 * This service provides session-based storage for datatables filter states
 * to prevent URL length issues while maintaining full filter functionality.
 */
class FilterSessionService {
    /**
     * Session configuration
     */
    protected array $config;

    /**
     * Session key prefix for filter states
     */
    protected string $sessionPrefix = 'datatables_filter_';

    /**
     * Session metadata key for tracking active sessions
     */
    protected string $metadataKey = 'datatables_filter_metadata';

    public function __construct() {
        $this->config = config('datatables.session', [
            'enabled'           => true,
            'ttl'               => 3600,          // 1 hour default TTL
            'max_sessions'      => 50,       // Maximum sessions per user
            'cleanup_threshold' => 100, // Cleanup when metadata exceeds this
        ]);
    }

    /**
     * Generate a unique UUID-based session key for filter state
     */
    public function generateSessionKey(): string {
        return Str::uuid()->toString();
    }

    /**
     * Save filter state to session with UUID key
     *
     * @param  string  $sessionKey  UUID session key
     * @param  array  $filterData  Filter state data
     * @return bool Success status
     */
    public function saveFilterState(string $sessionKey, array $filterData): bool {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $sessionData = [
                'filters'             => $filterData['filters'] ?? [],
                'sortBy'              => $filterData['sortBy'] ?? 'id',
                'sortDirection'       => $filterData['sortDirection'] ?? 'asc',
                'perPage'             => $filterData['perPage'] ?? 10,
                'collapsedGroups'     => $filterData['collapsedGroups'] ?? [],
                'filterSectionStates' => $filterData['filterSectionStates'] ?? [],
                'created_at'          => now()->timestamp,
                'expires_at'          => now()->addSeconds($this->getSessionTTL())->timestamp,
                'updated_at'          => now()->timestamp,
            ];

            // Store the filter state
            Session::put($this->sessionPrefix . $sessionKey, $sessionData);

            // Update metadata to track active sessions
            $this->updateSessionMetadata($sessionKey);

            // Cleanup old sessions if needed
            $this->cleanupExpiredSessionsIfNeeded();

            return true;
        } catch (Throwable $e) {
            LogHandler::warning('Filter session save failed', [
                'session_key' => $sessionKey,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Load filter state from session using UUID key
     *
     * @param  string  $sessionKey  UUID session key
     * @return array Filter state data or empty array if not found/expired
     */
    public function loadFilterState(string $sessionKey): array {
        if (!$this->isEnabled() || empty($sessionKey)) {
            return $this->getDefaultFilterState();
        }

        try {
            $sessionData = Session::get($this->sessionPrefix . $sessionKey);

            if (!$sessionData) {
                return $this->getDefaultFilterState();
            }

            // Check if session has expired
            if ($this->isSessionExpired($sessionData)) {
                $this->deleteFilterState($sessionKey);

                return $this->getDefaultFilterState();
            }

            // Update last accessed time
            $sessionData['updated_at'] = now()->timestamp;
            Session::put($this->sessionPrefix . $sessionKey, $sessionData);

            return [
                'filters'             => $sessionData['filters'] ?? [],
                'sortBy'              => $sessionData['sortBy'] ?? 'id',
                'sortDirection'       => $sessionData['sortDirection'] ?? 'asc',
                'perPage'             => $sessionData['perPage'] ?? 10,
                'collapsedGroups'     => $sessionData['collapsedGroups'] ?? [],
                'filterSectionStates' => $sessionData['filterSectionStates'] ?? [],
            ];
        } catch (Throwable $e) {
            LogHandler::warning('Filter session load failed', [
                'session_key' => $sessionKey,
                'error'       => $e->getMessage(),
            ]);

            return $this->getDefaultFilterState();
        }
    }

    /**
     * Delete filter state from session
     *
     * @param  string  $sessionKey  UUID session key
     * @return bool Success status
     */
    public function deleteFilterState(string $sessionKey): bool {
        if (!$this->isEnabled() || empty($sessionKey)) {
            return false;
        }

        try {
            Session::forget($this->sessionPrefix . $sessionKey);
            $this->removeFromSessionMetadata($sessionKey);

            return true;
        } catch (Throwable $e) {
            LogHandler::warning('Filter session delete failed', [
                'session_key' => $sessionKey,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clean up expired filter sessions
     *
     * @return int Number of sessions cleaned up
     */
    public function cleanupExpiredSessions(): int {
        if (!$this->isEnabled()) {
            return 0;
        }

        try {
            $metadata     = $this->getSessionMetadata();
            $cleanedCount = 0;

            foreach ($metadata as $sessionKey => $sessionInfo) {
                if ($this->isSessionExpired($sessionInfo)) {
                    $this->deleteFilterState($sessionKey);
                    $cleanedCount++;
                }
            }

            if ($cleanedCount > 0) {
                LogHandler::info('Filter sessions cleaned up', [
                    'cleaned_count'   => $cleanedCount,
                    'remaining_count' => count($metadata) - $cleanedCount,
                ]);
            }

            return $cleanedCount;
        } catch (Throwable $e) {
            LogHandler::warning('Filter session cleanup failed', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Get session TTL in seconds
     */
    public function getSessionTTL(): int {
        return $this->config['ttl'] ?? 3600;
    }

    /**
     * Check if session storage is enabled
     */
    public function isEnabled(): bool {
        return $this->config['enabled'] ?? true;
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(): array {
        try {
            $metadata     = $this->getSessionMetadata();
            $activeCount  = 0;
            $expiredCount = 0;

            foreach ($metadata as $sessionInfo) {
                if ($this->isSessionExpired($sessionInfo)) {
                    $expiredCount++;
                } else {
                    $activeCount++;
                }
            }

            return [
                'enabled'          => $this->isEnabled(),
                'ttl'              => $this->getSessionTTL(),
                'active_sessions'  => $activeCount,
                'expired_sessions' => $expiredCount,
                'total_sessions'   => count($metadata),
                'max_sessions'     => $this->config['max_sessions'] ?? 50,
            ];
        } catch (Throwable $e) {
            return [
                'enabled' => $this->isEnabled(),
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Get default filter state
     */
    protected function getDefaultFilterState(): array {
        return [
            'filters'             => [],
            'sortBy'              => 'id',
            'sortDirection'       => 'asc',
            'perPage'             => 10,
            'collapsedGroups'     => [],
            'filterSectionStates' => [],
        ];
    }

    /**
     * Check if session data is expired
     */
    protected function isSessionExpired(array $sessionData): bool {
        $expiresAt = $sessionData['expires_at'] ?? 0;

        return $expiresAt < now()->timestamp;
    }

    /**
     * Update session metadata to track active sessions
     */
    protected function updateSessionMetadata(string $sessionKey): void {
        $metadata = $this->getSessionMetadata();

        $metadata[$sessionKey] = [
            'created_at' => now()->timestamp,
            'expires_at' => now()->addSeconds($this->getSessionTTL())->timestamp,
            'updated_at' => now()->timestamp,
        ];

        // Limit number of sessions per user
        if (count($metadata) > ($this->config['max_sessions'] ?? 50)) {
            $this->cleanupOldestSessions($metadata);
        }

        Session::put($this->metadataKey, $metadata);
    }

    /**
     * Remove session from metadata
     */
    protected function removeFromSessionMetadata(string $sessionKey): void {
        $metadata = $this->getSessionMetadata();
        unset($metadata[$sessionKey]);
        Session::put($this->metadataKey, $metadata);
    }

    /**
     * Get session metadata
     */
    protected function getSessionMetadata(): array {
        return Session::get($this->metadataKey, []);
    }

    /**
     * Cleanup oldest sessions when limit is exceeded
     */
    protected function cleanupOldestSessions(array &$metadata): void {
        // Sort by updated_at timestamp (oldest first)
        uasort($metadata, function ($a, $b) {
            return ($a['updated_at'] ?? 0) <=> ($b['updated_at'] ?? 0);
        });

        $maxSessions      = $this->config['max_sessions'] ?? 50;
        $sessionsToRemove = count($metadata) - $maxSessions;

        $sessionKeys = array_keys($metadata);
        for ($i = 0; $i < $sessionsToRemove; $i++) {
            $oldestKey = $sessionKeys[$i];
            $this->deleteFilterState($oldestKey);
            unset($metadata[$oldestKey]);
        }
    }

    /**
     * Cleanup expired sessions if metadata exceeds threshold
     */
    protected function cleanupExpiredSessionsIfNeeded(): void {
        $metadata  = $this->getSessionMetadata();
        $threshold = $this->config['cleanup_threshold'] ?? 100;

        if (count($metadata) > $threshold) {
            $this->cleanupExpiredSessions();
        }
    }

    /**
     * Validate session key format (UUID)
     */
    public function isValidSessionKey(string $sessionKey): bool {
        return !empty($sessionKey) && Str::isUuid($sessionKey);
    }

    /**
     * Get session key info for debugging
     */
    public function getSessionKeyInfo(string $sessionKey): array {
        if (!$this->isValidSessionKey($sessionKey)) {
            return [
                'valid' => false,
                'error' => 'Invalid session key format',
            ];
        }

        $sessionData = Session::get($this->sessionPrefix . $sessionKey);

        return [
            'valid'      => true,
            'exists'     => !is_null($sessionData),
            'expired'    => $sessionData ? $this->isSessionExpired($sessionData) : false,
            'created_at' => $sessionData['created_at'] ?? null,
            'expires_at' => $sessionData['expires_at'] ?? null,
            'updated_at' => $sessionData['updated_at'] ?? null,
        ];
    }
}
