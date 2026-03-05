<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Utils\CommonProcess;
use App\Utils\DomainConst;

class CommonProcessTest extends TestCase {
    /**
     * Test that getArrayGender returns expected mapping.
     *
     * @return void
     */
    #[Test]
    public function it_returns_gender_mapping_array(): void {
        $result = CommonProcess::getArrayGender();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(DomainConst::GENDER_MALE, $result);
        $this->assertArrayHasKey(DomainConst::GENDER_FEMALE, $result);
        $this->assertArrayHasKey(DomainConst::GENDER_OTHER, $result);
    }
}
