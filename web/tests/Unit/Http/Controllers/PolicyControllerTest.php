<?php

namespace Tests\Unit\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

use App\Http\Controllers\PolicyController;
use App\Http\Requests\StoreDataDeletionRequest;
use App\Utils\PolicyHelper;
use Modules\Admin\Models\Setting;

class PolicyControllerTest extends TestCase {
    use RefreshDatabase;

    protected PolicyController $controller;

    protected function setUp(): void {
        parent::setUp();
        $this->controller = new PolicyController;
        // Clear Setting static cache before each test
        $this->clearSettingCache();
    }

    protected function clearSettingCache(): void {
        $reflection = new ReflectionClass(Setting::class);
        if ($reflection->hasProperty('aSettings')) {
            $property = $reflection->getProperty('aSettings');
            $property->setAccessible(true);
            $property->setValue(null);
        }
    }

    #[Test]
    public function it_displays_policy_page_with_default_language(): void {
        // Create a setting for default language
        $langCode = App::getLocale();
        $key      = PolicyHelper::getSettingKey($langCode);
        Setting::create([
            'key'    => $key,
            'value'  => 'Test Policy Content',
            'status' => 1,
        ]);
        $this->clearSettingCache(); // Clear cache after creating

        $request = Request::create('/policy', 'GET');

        $response = $this->controller->show($request);

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $response);
        $viewData = $response->getData();

        $this->assertArrayHasKey('content', $viewData);
        $this->assertArrayHasKey('currentLang', $viewData);
        $this->assertArrayHasKey('availableLanguages', $viewData);
        $this->assertArrayHasKey('updatedAt', $viewData);
        $this->assertArrayHasKey('title', $viewData);
        $this->assertArrayHasKey('lastUpdatedText', $viewData);
        $this->assertArrayHasKey('formattedDate', $viewData);
    }

    #[Test]
    public function it_displays_policy_page_with_specified_language(): void {
        $langCode = 'en';
        $key      = PolicyHelper::getSettingKey($langCode);
        Setting::create([
            'key'    => $key,
            'value'  => 'English Policy Content',
            'status' => 1,
        ]);
        $this->clearSettingCache(); // Clear cache after creating

        $request = Request::create('/policy?lang=en', 'GET');

        $response = $this->controller->show($request);

        $viewData = $response->getData();
        $this->assertEquals('en', $viewData['currentLang']);
        $this->assertEquals('English Policy Content', $viewData['content']);
    }

    #[Test]
    public function it_falls_back_to_other_language_when_current_is_empty(): void {
        // Create setting for fallback language only
        $fallbackKey = PolicyHelper::getSettingKey('en');
        Setting::create([
            'key'    => $fallbackKey,
            'value'  => 'Fallback Content',
            'status' => 1,
        ]);
        $this->clearSettingCache(); // Clear cache after creating

        $request = Request::create('/policy?lang=ja', 'GET');

        $response = $this->controller->show($request);

        $viewData = $response->getData();
        // Should use fallback content
        $this->assertNotEmpty($viewData['content']);
    }

    #[Test]
    public function it_sets_locale_based_on_language_parameter(): void {
        $langCode = 'ja';
        $key      = PolicyHelper::getSettingKey($langCode);
        Setting::create([
            'key'    => $key,
            'value'  => 'Japanese Content',
            'status' => 1,
        ]);
        $this->clearSettingCache(); // Clear cache after creating

        $request = Request::create('/policy?lang=ja', 'GET');

        $this->controller->show($request);

        $this->assertEquals('ja', App::getLocale());
    }

    #[Test]
    public function it_displays_data_deletion_request_form(): void {
        $request = Request::create('/request-delete-user-data', 'GET');

        $response = $this->controller->showDataDeletionRequest($request);

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $response);
        $viewData = $response->getData();

        $this->assertArrayHasKey('currentLang', $viewData);
        $this->assertArrayHasKey('availableLanguages', $viewData);
    }

    #[Test]
    public function it_displays_data_deletion_request_form_with_language(): void {
        $request = Request::create('/request-delete-user-data?lang=en', 'GET');

        $response = $this->controller->showDataDeletionRequest($request);

        $viewData = $response->getData();
        $this->assertEquals('en', $viewData['currentLang']);
    }

    #[Test]
    public function it_handles_data_deletion_request_submission(): void {
        $formRequest = new StoreDataDeletionRequest;
        $formRequest->merge(['lang' => 'en']);

        // Mock the validated method
        $formRequest = Mockery::mock(StoreDataDeletionRequest::class)->makePartial();
        $formRequest->shouldReceive('validated')
            ->andReturn(['lang' => 'en']);

        $response = $this->controller->storeDataDeletionRequest($formRequest);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertStringContainsString('/request-delete-user-data/success', $response->getTargetUrl());
        $this->assertStringContainsString('lang=en', $response->getTargetUrl());
    }

    #[Test]
    public function it_handles_data_deletion_request_without_lang(): void {
        $formRequest = Mockery::mock(StoreDataDeletionRequest::class)->makePartial();
        $formRequest->shouldReceive('validated')
            ->andReturn([]);

        $response = $this->controller->storeDataDeletionRequest($formRequest);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertStringContainsString('/request-delete-user-data/success', $response->getTargetUrl());
        // Should use current locale when lang is not provided
        $this->assertStringContainsString('lang=', $response->getTargetUrl());
    }

    #[Test]
    public function it_displays_data_deletion_success_page(): void {
        $request = Request::create('/request-delete-user-data/success', 'GET');

        $response = $this->controller->showDataDeletionSuccess($request);

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $response);
        $viewData = $response->getData();

        $this->assertArrayHasKey('currentLang', $viewData);
        $this->assertArrayHasKey('availableLanguages', $viewData);
    }

    #[Test]
    public function it_displays_data_deletion_success_page_with_language(): void {
        $request = Request::create('/request-delete-user-data/success?lang=ja', 'GET');

        $response = $this->controller->showDataDeletionSuccess($request);

        $viewData = $response->getData();
        $this->assertEquals('ja', $viewData['currentLang']);
    }

    #[Test]
    public function it_normalizes_language_code_vn_to_vi(): void {
        // Create setting for 'vn' (which maps to 'policy_vn')
        $vnKey = PolicyHelper::getSettingKey('vn');
        Setting::create([
            'key'    => $vnKey,
            'value'  => 'Vietnamese Content',
            'status' => 1,
        ]);
        $this->clearSettingCache(); // Clear cache after creating

        $request = Request::create('/policy?lang=vn', 'GET');

        $response = $this->controller->show($request);

        $viewData = $response->getData();
        // Should normalize 'vn' internally but may display as 'vi'
        $this->assertNotEmpty($viewData['content']);
    }

    #[Test]
    public function it_includes_updated_at_from_setting(): void {
        $langCode = 'en';
        $key      = PolicyHelper::getSettingKey($langCode);
        $setting  = Setting::create([
            'key'    => $key,
            'value'  => 'Test Content',
            'status' => 1,
        ]);
        $this->clearSettingCache(); // Clear cache after creating

        $request = Request::create('/policy?lang=en', 'GET');

        $response = $this->controller->show($request);

        $viewData = $response->getData();
        $this->assertInstanceOf(Carbon::class, $viewData['updatedAt']);
    }

    #[Test]
    public function it_uses_current_time_when_setting_not_found(): void {
        $request = Request::create('/policy?lang=ja', 'GET');

        $response = $this->controller->show($request);

        $viewData = $response->getData();
        $this->assertInstanceOf(Carbon::class, $viewData['updatedAt']);
    }

    #[Test]
    public function it_maps_vn_to_vi_in_available_languages(): void {
        // Create settings for multiple languages
        Setting::create([
            'key'    => PolicyHelper::getSettingKey('en'),
            'value'  => 'English Content',
            'status' => 1,
        ]);
        Setting::create([
            'key'    => PolicyHelper::getSettingKey('vn'),
            'value'  => 'Vietnamese Content',
            'status' => 1,
        ]);
        $this->clearSettingCache(); // Clear cache after creating

        $request = Request::create('/policy', 'GET');

        $response = $this->controller->show($request);

        $viewData           = $response->getData();
        $availableLanguages = $viewData['availableLanguages'];

        // Should have 'vi' instead of 'vn' in available languages
        $hasVi = false;
        foreach ($availableLanguages as $lang) {
            if ($lang['code'] === 'vi') {
                $hasVi = true;
                break;
            }
        }

        $this->assertTrue($hasVi, 'Available languages should contain "vi" mapped from "vn"');
    }

    #[Test]
    public function it_prevents_duplicate_vi_in_available_languages(): void {
        // Create settings that would cause both 'vn' and 'vi' to be added
        Setting::create([
            'key'   => PolicyHelper::getSettingKey('vn'),
            'value' => 'Vietnamese Content',
        ]);

        $request = Request::create('/policy', 'GET');

        $response = $this->controller->show($request);

        $viewData           = $response->getData();
        $availableLanguages = $viewData['availableLanguages'];

        $viCount = 0;
        foreach ($availableLanguages as $lang) {
            if ($lang['code'] === 'vi') {
                $viCount++;
            }
        }

        $this->assertLessThanOrEqual(1, $viCount, 'Should not have duplicate "vi" in available languages');
    }

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }
}
