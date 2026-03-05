<?php

namespace Tests\Unit\Console\Commands;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Console\Commands\ClearAll;

class ClearAllTest extends TestCase {
    #[Test]
    public function it_can_be_instantiated() {
        $command = new ClearAll;
        $this->assertEquals('clearall', $command->getName());
        $this->assertEquals('Clears routes, config, cache, views, compiled, and caches config.', $command->getDescription());
    }

    #[Test]
    public function it_clears_all_caches() {
        // Create a mock of the command
        $command = $this->getMockBuilder(ClearAll::class)
            ->onlyMethods(['call'])
            ->getMock();

        $calledCommands = [];

        // Set up expectations for each command (ClearAll also clears Lighthouse schema cache)
        $command->expects($this->atLeast(6))
            ->method('call')
            ->willReturnCallback(function ($cmd) use (&$calledCommands) {
                $calledCommands[] = $cmd;

                return 0;
            });

        // Run the command
        $command->handle();

        // Assert: first 5 commands are always called in order
        $this->assertSame([
            'route:clear',
            'config:clear',
            'cache:clear',
            'view:clear',
            'clear-compiled',
        ], array_slice($calledCommands, 0, 5));

        // Assert: Lighthouse cache clear is attempted (at least one of these)
        $this->assertContains('lighthouse:clear-cache', $calledCommands);
    }
}
