<?php

namespace Minz;

use AppTest\forms;
use AppTest\models;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function testConstructWithDefaultValues(): void
    {
        $form = new forms\Rabbit([
            'name' => 'Bugs',
        ]);

        $this->assertSame('Bugs', $form->name);
    }

    public function testConstructWithBindedModel(): void
    {
        $rabbit = new models\Rabbit();
        $rabbit->name = 'Bugs';
        $form = new forms\Rabbit([
            'name' => 'Clémentine',
        ], model: $rabbit);

        $this->assertSame('Bugs', $form->name);
    }

    public function testHandleRequest(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'name' => ' Bugs ',
            'friend_id' => 1,
        ]);
        $form = new forms\Rabbit();

        $form->handleRequest($request);

        $this->assertSame('Bugs', $form->name);
        $this->assertSame(1, $form->friend_id);
    }

    public function testHandleRequestWithBindedModel(): void
    {
        $rabbit = new models\Rabbit();
        $request = new \Minz\Request('GET', '/', [
            'name' => ' Bugs ',
            'friend_id' => 1,
        ]);
        $form = new forms\Rabbit(model: $rabbit);

        $form->handleRequest($request);

        $rabbit = $form->getModel();
        $this->assertSame('Bugs', $rabbit->name);
        $this->assertSame(1, $rabbit->friend_id);
    }

    public function testHandleRequestWithMissingBoolParam(): void
    {
        $request = new \Minz\Request('GET', '/', []);
        $form = new forms\FormWithBool();

        $form->handleRequest($request);

        $this->assertFalse($form->param_bool);
    }

    public function testFormatWithDatetime(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'param_datetime' => '2024-03-07',
        ]);
        $form = new forms\FormWithDatetime();
        $form->handleRequest($request);

        $formatted_datetime = $form->format('param_datetime');

        $this->assertInstanceOf(\DateTimeImmutable::class, $form->param_datetime);
        $this->assertSame('2024-03-07', $formatted_datetime);
    }

    public function testValidate(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'name' => ' Bugs ',
            'friend_id' => 1,
            'csrf' => 'not the token',
        ]);
        $form = new forms\Rabbit();
        $form->handleRequest($request);

        $result = $form->validate();

        $this->assertFalse($result);
        $this->assertSame(
            'The security token is invalid. Please try to submit the form again.',
            $form->getError('@global')
        );
    }

    public function testValidateWithCustomCheck(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'name' => 'Clémentine',
        ]);
        $form = new forms\FormWithCheck();
        $form->handleRequest($request);

        $result = $form->validate();

        $this->assertFalse($result);
        $this->assertSame('Name must be equal to "Bugs"', $form->getError('name'));
    }
}
