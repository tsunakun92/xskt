<?php

namespace Modules\Admin\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Admin\Models\Municipality;
use Modules\Admin\Models\PostNumber;
use Modules\Admin\Models\Prefecture;

class PostNumberTest extends TestCase {
    use RefreshDatabase;

    protected PostNumber $postNumber;

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

        $this->postNumber = PostNumber::create([
            'post_number'     => '100-0001',
            'name'            => 'Test Town',
            'municipality_id' => $this->municipality->id,
            'status'          => PostNumber::STATUS_ACTIVE,
        ]);
    }

    /**
     * Test that post number uses correct table name.
     *
     * @return void
     */
    #[Test]
    public function it_uses_correct_table_name(): void {
        $this->assertEquals('post_numbers', $this->postNumber->getTable());
    }

    /**
     * Test that post number has correct fillable attributes.
     *
     * @return void
     */
    #[Test]
    public function it_has_correct_fillable_attributes(): void {
        $fillable = [
            'post_number',
            'name',
            'municipality_id',
            'status',
        ];

        $this->assertEquals($fillable, $this->postNumber->getFillable());
    }

    /**
     * Test that post number has correct datatable columns.
     *
     * @return void
     */
    #[Test]
    public function it_has_correct_datatable_columns(): void {
        $expectedColumns = [
            'id',
            'post_number',
            'name',
            'municipality_name',
            'status',
            'action',
        ];

        $this->assertEquals($expectedColumns, PostNumber::getDatatableColumns());
    }

    /**
     * Test that post number belongs to municipality.
     *
     * @return void
     */
    #[Test]
    public function it_belongs_to_municipality(): void {
        $this->assertInstanceOf(Municipality::class, $this->postNumber->rMunicipality);
        $this->assertEquals($this->municipality->id, $this->postNumber->municipality_id);
    }

    /**
     * Test that post number has correct post number format.
     *
     * @return void
     */
    #[Test]
    public function it_stores_post_number_correctly(): void {
        $this->assertEquals('100-0001', $this->postNumber->post_number);
    }

    /**
     * Test that post number has correct name.
     *
     * @return void
     */
    #[Test]
    public function it_stores_name_correctly(): void {
        $this->assertEquals('Test Town', $this->postNumber->name);
    }

    /**
     * Test that post number with same post_number and municipality is unique.
     *
     * @return void
     */
    #[Test]
    public function it_can_create_multiple_post_numbers_with_different_post_numbers(): void {
        $postNumber2 = PostNumber::create([
            'post_number'     => '100-0002',
            'name'            => 'Another Town',
            'municipality_id' => $this->municipality->id,
            'status'          => PostNumber::STATUS_ACTIVE,
        ]);

        $this->assertNotEquals($this->postNumber->post_number, $postNumber2->post_number);
        $this->assertEquals(2, PostNumber::count());
    }

    /**
     * Test that post number can be created with different municipality.
     *
     * @return void
     */
    #[Test]
    public function it_can_create_post_number_for_different_municipality(): void {
        $municipality2 = Municipality::create([
            'name'          => 'Another Municipality',
            'prefecture_id' => $this->prefecture->id,
            'status'        => Municipality::STATUS_ACTIVE,
        ]);

        $postNumber2 = PostNumber::create([
            'post_number'     => '100-0001',
            'name'            => 'Test Town 2',
            'municipality_id' => $municipality2->id,
            'status'          => PostNumber::STATUS_ACTIVE,
        ]);

        $this->assertEquals(2, PostNumber::count());
        $this->assertNotEquals($this->postNumber->municipality_id, $postNumber2->municipality_id);
    }

    /**
     * Test that post number can be deleted.
     *
     * @return void
     */
    #[Test]
    public function it_can_be_deleted(): void {
        $postNumberId = $this->postNumber->id;
        $this->postNumber->delete();

        $this->assertDatabaseMissing('post_numbers', [
            'id' => $postNumberId,
        ]);
    }

    /**
     * Test that post number can get form fields.
     *
     * @return void
     */
    #[Test]
    public function it_can_get_form_fields(): void {
        $fields = PostNumber::getFormFields('post-numbers');

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('post_number', $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('municipality_id', $fields);
        $this->assertArrayHasKey('status', $fields);

        $this->assertEquals('select', $fields['municipality_id']['type']);
        $this->assertIsArray($fields['municipality_id']['options']);

        $this->assertEquals('select', $fields['status']['type']);
        $this->assertIsArray($fields['status']['options']);
    }

    /**
     * Test that post number can get filter fields.
     *
     * @return void
     */
    #[Test]
    public function it_can_get_filter_fields(): void {
        $fields = PostNumber::getFilterFields('post-numbers');

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('post_number', $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('municipality_id', $fields);
        $this->assertArrayHasKey('status', $fields);

        $this->assertEquals('select', $fields['municipality_id']['type']);
        $this->assertEquals('select', $fields['status']['type']);
    }
}
