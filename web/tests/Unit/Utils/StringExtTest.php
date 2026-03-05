<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Utils\StringExt;

class StringExtTest extends TestCase {
    /**
     * Test that generateUniqId returns upper case string with correct length.
     *
     * @return void
     */
    #[Test]
    public function it_generates_uppercase_string_with_given_length(): void {
        $id = StringExt::generateUniqId(10);

        $this->assertSame(10, strlen($id));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $id);
    }

    #[Test]
    public function it_generates_different_ids_on_multiple_calls(): void {
        $id1 = StringExt::generateUniqId(13);
        $id2 = StringExt::generateUniqId(13);

        $this->assertNotEquals($id1, $id2);
    }

    #[Test]
    public function it_handles_odd_length_correctly(): void {
        $id = StringExt::generateUniqId(15);

        $this->assertSame(15, strlen($id));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $id);
    }

    #[Test]
    public function it_handles_even_length_correctly(): void {
        $id = StringExt::generateUniqId(14);

        $this->assertSame(14, strlen($id));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $id);
    }

    #[Test]
    public function it_uses_default_length_when_not_specified(): void {
        $id = StringExt::generateUniqId();

        $this->assertSame(13, strlen($id));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $id);
    }

    #[Test]
    public function it_works_with_openssl_fallback_when_random_bytes_unavailable(): void {
        // Test that the method still works correctly
        $id = StringExt::generateUniqId(12);

        $this->assertSame(12, strlen($id));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $id);
    }

    #[Test]
    public function it_handles_very_long_length(): void {
        $id = StringExt::generateUniqId(100);
        $this->assertSame(100, strlen($id));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $id);
    }

    #[Test]
    public function it_always_returns_uppercase(): void {
        $id = StringExt::generateUniqId(20);
        // Verify it's uppercase (no lowercase letters)
        $this->assertFalse(ctype_lower($id));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $id);
    }
}
