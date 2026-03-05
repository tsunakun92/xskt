<?php

namespace Modules\Admin\Tests\Unit\Models;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Admin\Models\Municipality;
use Modules\Admin\Models\Prefecture;

class PrefectureTest extends TestCase {
    use RefreshDatabase;

    protected Prefecture $prefecture;

    protected function setUp(): void {
        parent::setUp();

        $this->prefecture = Prefecture::create([
            'name'   => 'Test Prefecture',
            'status' => Prefecture::STATUS_ACTIVE,
        ]);
    }

    /**
     * Test that prefecture uses correct table name.
     *
     * @return void
     */
    #[Test]
    public function it_uses_correct_table_name(): void {
        $this->assertEquals('prefectures', $this->prefecture->getTable());
    }

    /**
     * Test that prefecture has correct fillable attributes.
     *
     * @return void
     */
    #[Test]
    public function it_has_correct_fillable_attributes(): void {
        $fillable = [
            'name',
            'status',
        ];

        $this->assertEquals($fillable, $this->prefecture->getFillable());
    }

    /**
     * Test that prefecture has correct datatable columns.
     *
     * @return void
     */
    #[Test]
    public function it_has_correct_datatable_columns(): void {
        $expectedColumns = [
            'id',
            'name',
            'status',
            'action',
        ];

        $this->assertEquals($expectedColumns, Prefecture::getDatatableColumns());
    }

    /**
     * Test that prefecture has many municipalities.
     *
     * @return void
     */
    #[Test]
    public function it_has_many_municipalities(): void {
        $municipality1 = Municipality::create([
            'name'          => 'Municipality 1',
            'prefecture_id' => $this->prefecture->id,
            'status'        => Municipality::STATUS_ACTIVE,
        ]);

        $municipality2 = Municipality::create([
            'name'          => 'Municipality 2',
            'prefecture_id' => $this->prefecture->id,
            'status'        => Municipality::STATUS_ACTIVE,
        ]);

        $this->assertCount(2, $this->prefecture->rMunicipalities);
        $this->assertTrue($this->prefecture->rMunicipalities->contains($municipality1));
        $this->assertTrue($this->prefecture->rMunicipalities->contains($municipality2));
    }

    /**
     * Test that prefecture cannot be deleted if has municipalities.
     *
     * @return void
     */
    #[Test]
    public function it_cannot_be_deleted_if_has_municipalities(): void {
        Municipality::create([
            'name'          => 'Municipality 1',
            'prefecture_id' => $this->prefecture->id,
            'status'        => Municipality::STATUS_ACTIVE,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(__('admin::crud.delete_has_relationship_error', [
            'name'     => __('admin::crud.prefectures.title'),
            'relation' => __('admin::crud.municipalities.title'),
        ]));

        $this->prefecture->delete();
    }

    /**
     * Test that prefecture can be deleted if has no municipalities.
     *
     * @return void
     */
    #[Test]
    public function it_can_be_deleted_if_has_no_municipalities(): void {
        $prefectureWithoutMunicipalities = Prefecture::create([
            'name'   => 'No Municipalities Prefecture',
            'status' => Prefecture::STATUS_ACTIVE,
        ]);

        $prefectureWithoutMunicipalities->delete();

        $this->assertDatabaseMissing('prefectures', [
            'id' => $prefectureWithoutMunicipalities->id,
        ]);
    }

    /**
     * Test that prefecture can have multiple municipalities.
     *
     * @return void
     */
    #[Test]
    public function it_can_have_multiple_municipalities(): void {
        for ($i = 1; $i <= 5; $i++) {
            Municipality::create([
                'name'          => "Municipality {$i}",
                'prefecture_id' => $this->prefecture->id,
                'status'        => Municipality::STATUS_ACTIVE,
            ]);
        }

        $this->assertCount(5, $this->prefecture->rMunicipalities);
    }

    /**
     * Test that prefecture stores name correctly.
     *
     * @return void
     */
    #[Test]
    public function it_stores_name_correctly(): void {
        $this->assertEquals('Test Prefecture', $this->prefecture->name);

        $updatedPrefecture = Prefecture::create([
            'name'   => 'Updated Prefecture',
            'status' => Prefecture::STATUS_ACTIVE,
        ]);

        $this->assertEquals('Updated Prefecture', $updatedPrefecture->name);
    }

    /**
     * Test that prefecture stores status correctly.
     *
     * @return void
     */
    #[Test]
    public function it_stores_status_correctly(): void {
        $this->assertEquals(Prefecture::STATUS_ACTIVE, $this->prefecture->status);

        $inactivePrefecture = Prefecture::create([
            'name'   => 'Inactive Prefecture',
            'status' => Prefecture::STATUS_INACTIVE,
        ]);

        $this->assertEquals(Prefecture::STATUS_INACTIVE, $inactivePrefecture->status);
    }

    /**
     * Test that prefecture can be retrieved by id.
     *
     * @return void
     */
    #[Test]
    public function it_can_be_retrieved_by_id(): void {
        $retrieved = Prefecture::find($this->prefecture->id);

        $this->assertInstanceOf(Prefecture::class, $retrieved);
        $this->assertEquals($this->prefecture->id, $retrieved->id);
        $this->assertEquals($this->prefecture->name, $retrieved->name);
    }

    /**
     * Test that prefecture can get form fields.
     *
     * @return void
     */
    #[Test]
    public function it_can_get_form_fields(): void {
        $fields = Prefecture::getFormFields('prefectures');

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('status', $fields);

        $this->assertEquals('select', $fields['status']['type']);
        $this->assertIsArray($fields['status']['options']);
    }

    /**
     * Test that prefecture can get filter fields.
     *
     * @return void
     */
    #[Test]
    public function it_can_get_filter_fields(): void {
        $fields = Prefecture::getFilterFields('prefectures');

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('status', $fields);

        $this->assertEquals('select', $fields['status']['type']);
    }
}
