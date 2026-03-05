<?php

namespace Tests\Unit\Utils;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Utils\PolicyHelper;

class PolicyHelperTest extends TestCase {
    #[Test]
    public function it_normalizes_language_codes_and_maps_setting_keys(): void {
        $this->assertSame('en', PolicyHelper::normalizeLanguageCode('en-US'));
        $this->assertSame('ja', PolicyHelper::normalizeLanguageCode('ja-JP'));
        $this->assertSame('vi', PolicyHelper::normalizeLanguageCode('vn'));
        $this->assertSame('en', PolicyHelper::normalizeLanguageCode('unknown'));

        $this->assertSame('policy_en', PolicyHelper::getSettingKey('en'));
        $this->assertSame('policy_vn', PolicyHelper::getSettingKey('vi'));
        $this->assertSame('policy_en', PolicyHelper::getSettingKey('xx'));

        $map = PolicyHelper::getLanguageSettingMap();
        $this->assertArrayHasKey('en', $map);
        $this->assertArrayHasKey('ja', $map);
    }

    #[Test]
    public function it_returns_language_names_policy_titles_and_last_updated_texts(): void {
        $this->assertSame('English', PolicyHelper::getLanguageName('en'));
        $this->assertSame('日本語', PolicyHelper::getLanguageName('ja'));
        $this->assertSame('Tiếng Việt', PolicyHelper::getLanguageName('vn'));
        $this->assertSame('xx', PolicyHelper::getLanguageName('xx'));

        $this->assertSame('Privacy Policy', PolicyHelper::getPolicyTitle('en'));
        $this->assertSame('プライバシーポリシー', PolicyHelper::getPolicyTitle('ja'));
        $this->assertSame('Chính Sách Bảo Mật', PolicyHelper::getPolicyTitle('vi'));

        $this->assertSame('Last updated', PolicyHelper::getLastUpdatedText('en'));
        $this->assertSame('最終更新', PolicyHelper::getLastUpdatedText('ja'));
        $this->assertSame('Cập nhật lần cuối', PolicyHelper::getLastUpdatedText('vn'));
    }

    #[Test]
    public function it_formats_dates_based_on_language(): void {
        $date = Carbon::create(2026, 2, 4, 0, 0, 0);

        $this->assertSame('February 04, 2026', PolicyHelper::formatDate($date, 'en'));
        $this->assertSame('2026年02月04日', PolicyHelper::formatDate($date, 'ja'));
        $this->assertSame('04/02/2026', PolicyHelper::formatDate($date, 'vn'));

        $formatted = PolicyHelper::formatDate(null, 'en');
        $this->assertNotEmpty($formatted);

        $supported = PolicyHelper::getSupportedLanguages();
        $this->assertContains('en', $supported);
        $this->assertContains('ja', $supported);
    }
}
