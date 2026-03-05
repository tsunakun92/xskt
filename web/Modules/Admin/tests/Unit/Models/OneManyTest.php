<?php

namespace Modules\Admin\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Admin\Models\OneMany;

class OneManyTest extends TestCase {
    use RefreshDatabase;

    #[Test]
    public function it_has_correct_fillable_and_datatable_columns() {
        $model = new OneMany;

        $this->assertEquals([
            'one_id',
            'many_id',
            'type',
            'status',
        ], $model->getFillable());

        $this->assertEquals([
            'id',
            'one_id',
            'many_id',
            'type',
            'status',
            'action',
        ], OneMany::getDatatableColumns());
    }

    #[Test]
    public function it_can_check_exist_and_insert_one() {
        $oneId  = 1;
        $manyId = 10;
        $type   = OneMany::TYPE_WEEK_DAY_WORK_REGISTER;

        $this->assertFalse(OneMany::checkExist($oneId, $manyId, $type));

        $result = OneMany::insertOne($oneId, $manyId, $type);
        $this->assertTrue($result);
        $this->assertTrue(OneMany::checkExist($oneId, $manyId, $type));

        // Second insert with same data should return false (already exists)
        $this->assertFalse(OneMany::insertOne($oneId, $manyId, $type));
    }

    #[Test]
    public function it_can_insert_many_and_skip_existing_records() {
        $oneId   = 2;
        $type    = OneMany::TYPE_MONTH_DAY_WORK_REGISTER;
        $manyIds = [1, 2, 3];

        // First insert should create three records
        $result = OneMany::insertMany($oneId, $manyIds, $type);
        $this->assertTrue($result);
        $this->assertDatabaseCount('one_many', 3);

        // Insert again with overlapping ids; only new ids should be inserted
        $moreManyIds = [2, 3, 4, 5];
        $result      = OneMany::insertMany($oneId, $moreManyIds, $type);
        $this->assertTrue($result);

        // Now we should have records for many_id 1,2,3,4,5 (5 unique)
        $this->assertDatabaseCount('one_many', 5);

        // If all records already exist, insertMany should return false
        $this->assertFalse(OneMany::insertMany($oneId, [1, 2, 3, 4, 5], $type));
    }
}
