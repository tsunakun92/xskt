<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Lang;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransOrDefaultTest extends TestCase {
    #[Test]
    public function test_trans_or_default() {
        // Test with existing translation
        Lang::shouldReceive('has')
            ->with('existing.key')
            ->andReturn(true);
        Lang::shouldReceive('get')
            ->with('existing.key', [], null)
            ->andReturn('Translated Text');
        $this->assertEquals('Translated Text', transOrDefault('existing.key'));

        // Test with non-existing translation, no default
        Lang::shouldReceive('has')
            ->with('non.existing.key')
            ->andReturn(false);
        $this->assertEquals('non.existing.key', transOrDefault('non.existing.key'));

        // Test with non-existing translation, with default
        Lang::shouldReceive('has')
            ->with('another.key')
            ->andReturn(false);
        $this->assertEquals('Default Value', transOrDefault('another.key', 'Default Value'));
    }

    protected function setUp(): void {
        parent::setUp();
    }

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }
}
