<?php

namespace Modules\Admin\Tests\Unit\Models;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Admin\Models\Municipality;
use Modules\Admin\Models\PostNumber;
use Modules\Admin\Models\Prefecture;

class MunicipalityTest extends TestCase {
    use RefreshDatabase;

    protected Municipality $municipality;

    protected Prefecture $prefecture;

    protected function setUp(): void {
        parent::setUp();

        $this->prefecture = Prefecture::create([
            'name'   => 'Test Prefecture',
            'status' => Prefecture::STATUS_ACTIVE,
        ]);

        $this->municipality = Municipality::create([
            'name'          => 'Test Municipality',
            'prefecture_id' => $this->prefecture->id,
            'status'        => Municipality::STATUS_ACTIVE,
        ]);
    }

    /**
     * Test that municipality uses correct table name.
     *
     * @return void
     */
    #[Test]
    public function it_uses_correct_table_name(): void {
        $this->assertEquals('municipalities', $this->municipality->getTable());
    }

    /**
     * Test that municipality has correct fillable attributes.
     *
     * @return void
     */
    #[Test]
    public function it_has_correct_fillable_attributes(): void {
        $fillable = [
            'name',
            'prefecture_id',
            'status',
        ];

        $this->assertEquals($fillable, $this->municipality->getFillable());
    }

    /**
     * Test that municipality has correct datatable columns.
     *
     * @return void
     */
    #[Test]
    public function it_has_correct_datatable_columns(): void {
        $expectedColumns = [
            'id',
            'name',
            'prefecture_name',
            'status',
            'action',
        ];

        $this->assertEquals($expectedColumns, Municipality::getDatatableColumns());
    }

    /**
     * Test that municipality belongs to prefecture.
     *
     * @return void
     */
    #[Test]
    public function it_belongs_to_prefecture(): void {
        $this->assertInstanceOf(Prefecture::class, $this->municipality->rPrefecture);
        $this->assertEquals($this->prefecture->id, $this->municipality->prefecture_id);
    }

    /**
     * Test that municipality has many post numbers.
     *
     * @return void
     */
    #[Test]
    public function it_has_many_post_numbers(): void {
        $postNumber1 = PostNumber::create([
            'post_number'     => '100-0001',
            'name'            => 'Chiyoda Ward',
            'municipality_id' => $this->municipality->id,
            'status'          => PostNumber::STATUS_ACTIVE,
        ]);

        $postNumber2 = PostNumber::create([
            'post_number'     => '100-0002',
            'name'            => 'Chiyoda Ward 2',
            'municipality_id' => $this->municipality->id,
            'status'          => PostNumber::STATUS_ACTIVE,
        ]);

        // Reload the municipality to refresh the relationship
        $this->municipality->refresh();

        $this->assertCount(2, $this->municipality->rPostNumbers);
        $postNumbers = $this->municipality->rPostNumbers->pluck('post_number')->toArray();
        $this->assertContains('100-0001', $postNumbers);
        $this->assertContains('100-0002', $postNumbers);
    }

    /**
     * Test that municipality cannot be deleted if has post numbers.
     *
     * @return void
     */
    #[Test]
    public function it_cannot_be_deleted_if_has_post_numbers(): void {
        PostNumber::create([
            'post_number'     => '100-0001',
            'name'            => 'Chiyoda Ward',
            'municipality_id' => $this->municipality->id,
            'status'          => PostNumber::STATUS_ACTIVE,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(__('admin::crud.delete_has_relationship_error', [
            'name'     => __('admin::crud.municipalities.title'),
            'relation' => __('admin::crud.post-numbers.title'),
        ]));

        $this->municipality->delete();
    }

    /**
     * Test that municipality can be deleted if has no post numbers.
     *
     * @return void
     */
    #[Test]
    public function it_can_be_deleted_if_has_no_post_numbers(): void {
        $municipalityWithoutPostNumbers = Municipality::create([
            'name'          => 'No Post Numbers Municipality',
            'prefecture_id' => $this->prefecture->id,
            'status'        => Municipality::STATUS_ACTIVE,
        ]);

        $municipalityWithoutPostNumbers->delete();

        $this->assertDatabaseMissing('municipalities', [
            'id' => $municipalityWithoutPostNumbers->id,
        ]);
    }

    /**
     * Test that municipality can get form fields.
     *
     * @return void
     */
    #[Test]
    public function it_can_get_form_fields(): void {
        $fields = Municipality::getFormFields('municipalities');

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('prefecture_id', $fields);
        $this->assertArrayHasKey('status', $fields);

        $this->assertEquals('select', $fields['prefecture_id']['type']);
        $this->assertIsArray($fields['prefecture_id']['options']);

        $this->assertEquals('select', $fields['status']['type']);
        $this->assertIsArray($fields['status']['options']);
    }

    /**
     * Test that municipality can get filter fields.
     *
     * @return void
     */
    #[Test]
    public function it_can_get_filter_fields(): void {
        $fields = Municipality::getFilterFields('municipalities');

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('prefecture_id', $fields);
        $this->assertArrayHasKey('status', $fields);

        $this->assertEquals('select', $fields['prefecture_id']['type']);
        $this->assertEquals('select', $fields['status']['type']);
    }
}
