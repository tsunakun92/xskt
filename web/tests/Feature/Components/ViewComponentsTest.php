<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

use App\View\Components\Badge;
use App\View\Components\Button;
use App\View\Components\Form;
use App\View\Components\Input;
use App\View\Components\Select;
use App\View\Components\TableIndex;
use App\View\Components\Textarea;

class ViewComponentsTest extends TestCase {
    public function test_badge_component() {
        $badge = new Badge('success', 'Test Badge');
        $this->assertEquals('success', $badge->type);
        $this->assertEquals('Test Badge', $badge->label);

        $view = $badge->render();
        $this->assertIsObject($view);
    }

    public function test_button_component() {
        $button = new Button('submit', 'Test Button');
        $this->assertEquals('submit', $button->type);
        $this->assertEquals('Test Button', $button->label);

        $view = $button->render();
        $this->assertIsObject($view);
    }

    public function test_form_component() {
        $form = new Form('POST', '/test');
        $this->assertEquals('POST', $form->method);
        $this->assertEquals('/test', $form->action);

        $view = $form->render();
        $this->assertIsObject($view);
    }

    public function test_input_component() {
        $input = new Input('test_name', 'text', 'Test Label');
        $this->assertEquals('text', $input->type);
        $this->assertEquals('test_name', $input->name);
        $this->assertEquals('Test Label', $input->label);

        $view = $input->render();
        $this->assertIsObject($view);
    }

    public function test_select_component() {
        $options = ['1' => 'Option 1', '2' => 'Option 2'];
        $select  = new Select('test_select', $options, 'Test Select');
        $this->assertEquals('test_select', $select->name);
        $this->assertEquals('Test Select', $select->label);
        $this->assertEquals($options, $select->options);

        $view = $select->render();
        $this->assertIsObject($view);
    }

    public function test_table_index_component() {
        $headers = ['ID', 'Name'];
        $rows    = [['1', 'Test']];
        $table   = new TableIndex($headers, $rows);
        $this->assertEquals($headers, $table->headers);
        $this->assertEquals($rows, $table->rows);

        $view = $table->render();
        $this->assertIsObject($view);
    }

    public function test_textarea_component() {
        $textarea = new Textarea('test_area', 'Test Area');
        $this->assertEquals('test_area', $textarea->name);
        $this->assertEquals('Test Area', $textarea->label);

        $view = $textarea->render();
        $this->assertIsObject($view);
    }
}
