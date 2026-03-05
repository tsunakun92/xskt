<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Parsedown;

use App\Http\Controllers\Controller;
use App\Utils\AjaxHandle;
use App\Utils\DateTimeExt;
use Modules\Logging\Utils\LogHandler;

/**
 * Handles changelog display and management
 */
class ChangelogController extends Controller {
    /**
     * Get cache duration based on environment and type.
     * - Local: no cache
     * - List: 1 hour
     * - Content: 10 years
     *
     * @param  string  $type  Cache type: 'list' or 'content'
     * @return Carbon|null Cache duration or null for no cache
     */
    private function getCacheDuration(string $type = 'content') {
        if (App::environment('local')) {
            return null;
        }

        return $type === 'list' ? Carbon::now()->addHour() : Carbon::now()->addYears(10);
    }

    /**
     * Display changelog index with version list.
     * Route name: changelog.index
     * Url: /admin/changelog
     *
     * @return ViewContract
     */
    public function index(): ViewContract {
        $files = $this->getCacheDuration('list') === null
        ? $this->getFilesList()
        : Cache::remember('changelog:index', $this->getCacheDuration('list'), fn() => $this->getFilesList());

        return View::make('admin::changelog.index', compact('files'));
    }

    /**
     * Get sorted list of changelog files with version and date.
     *
     * @return Collection Collection of files with version and date
     */
    private function getFilesList() {
        return collect(File::files(base_path('changelog')))
            ->map(fn($file) => [
                'version' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                'date'    => date(DateTimeExt::DATE_FORMAT_4, $file->getMTime()),
            ])
            ->sortByDesc('version');
    }

    /**
     * Get content of specific changelog version by ajax.
     * Route name: ajax-get-changelog-by-version
     * Url: /admin/changelog/ajax-get-changelog-by-version/{version}
     *
     * @param  string  $version  Changelog version
     * @return JsonResponse
     */
    public function ajaxGetChangelogByVersion(string $version): JsonResponse {
        $content = $this->getCacheDuration('content') === null
        ? $this->getChangelogContent($version)
        : Cache::remember(
            "changelog:content:{$version}",
            $this->getCacheDuration('content'),
            fn() => $this->getChangelogContent($version)
        );

        if ($content === null) {
            LogHandler::warning('Changelog version not found', [
                'version' => $version,
            ]);

            return AjaxHandle::error('Changelog not found', null, [], 404);
        }

        LogHandler::debug('Successfully retrieved changelog content', [
            'version' => $version,
        ]);

        return AjaxHandle::success('Changelog retrieved successfully', ['content' => $content]);
    }

    /**
     * Parse and return markdown content of changelog file.
     *
     * @param  string  $version  Changelog version
     * @return string|null Parsed HTML content or null if file not found
     */
    private function getChangelogContent(string $version) {
        $file = base_path("changelog/{$version}.md");

        return File::exists($file)
        ? (new Parsedown)->text(File::get($file))
        : null;
    }
}
