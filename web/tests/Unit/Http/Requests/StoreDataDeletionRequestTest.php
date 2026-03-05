<?php

namespace Tests\Unit\Http\Requests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Http\Requests\StoreDataDeletionRequest;
use App\Utils\PolicyHelper;

class StoreDataDeletionRequestTest extends TestCase {
    use RefreshDatabase;

    protected StoreDataDeletionRequest $request;

    protected function setUp(): void {
        parent::setUp();
        $this->request = new StoreDataDeletionRequest;
    }

    #[Test]
    public function it_is_always_authorized(): void {
        $this->assertTrue($this->request->authorize());
    }

    #[Test]
    public function it_has_lang_validation_rule(): void {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('lang', $rules);
        $this->assertContains('nullable', $rules['lang']);
        $this->assertContains('string', $rules['lang']);
    }

    #[Test]
    public function it_validates_lang_must_be_in_supported_languages(): void {
        $supportedLanguages = PolicyHelper::getSupportedLanguages();

        $rules = $this->request->rules();

        $this->assertIsArray($rules['lang']);
        $this->assertContains('nullable', $rules['lang']);
        $this->assertContains('string', $rules['lang']);

        // Check that Rule::in is present (it's an object, so we check the array structure)
        $this->assertCount(3, $rules['lang']);
    }

    #[Test]
    public function it_passes_validation_with_valid_language_code(): void {
        $supportedLanguages = PolicyHelper::getSupportedLanguages();

        foreach ($supportedLanguages as $lang) {
            $validator = Validator::make(
                ['lang' => $lang],
                $this->request->rules(),
                $this->request->messages()
            );

            $this->assertTrue($validator->passes(), "Language code '{$lang}' should be valid");
        }
    }

    #[Test]
    public function it_passes_validation_with_null_lang(): void {
        $validator = Validator::make(
            ['lang' => null],
            $this->request->rules(),
            $this->request->messages()
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function it_fails_validation_with_invalid_language_code(): void {
        $validator = Validator::make(
            ['lang' => 'invalid_lang'],
            $this->request->rules(),
            $this->request->messages()
        );

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('lang', $validator->errors()->toArray());
    }

    #[Test]
    public function it_fails_validation_with_non_string_lang(): void {
        $validator = Validator::make(
            ['lang' => 123],
            $this->request->rules(),
            $this->request->messages()
        );

        $this->assertFalse($validator->passes());
    }

    #[Test]
    public function it_returns_custom_validation_messages(): void {
        $messages = $this->request->messages();

        $this->assertArrayHasKey('lang.in', $messages);
        $this->assertIsString($messages['lang.in']);
    }

    #[Test]
    public function it_validates_empty_string_as_valid(): void {
        $validator = Validator::make(
            ['lang' => ''],
            $this->request->rules(),
            $this->request->messages()
        );

        // Empty string should pass nullable rule
        $this->assertTrue($validator->passes());
    }
}
