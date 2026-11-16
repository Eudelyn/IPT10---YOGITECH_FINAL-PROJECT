<?php
declare(strict_types=1);

namespace Tests;

use Core\Validator;

final class ValidatorTest extends TestCase
{
    public function testRequiredAndEmail(): void
    {
        $v = new Validator(['email' => 'not-an-email'], ['email' => 'required|email', 'name' => 'required']);
        $this->assertTrue($v->fails());
        $this->assertSame('Enter a valid email address.', $v->errors()['email']);
        $this->assertSame('Name is required.', $v->errors()['name']);
    }

    public function testNumericMinMaxAndStringLength(): void
    {
        $v = new Validator(['price' => '0', 'title' => 'ab'], ['price' => 'required|numeric|min:1', 'title' => 'min:3']);
        $this->assertSame('Price must be at least 1.', $v->errors()['price']);
        $this->assertSame('Title must be at least 3 characters.', $v->errors()['title']);
    }

    public function testConfirmedAndIn(): void
    {
        $v = new Validator(['password' => 'secret123', 'password_confirmation' => 'nope', 'role' => 'admin'],
            ['password' => 'confirmed', 'role' => 'in:buyer,seller']);
        $this->assertTrue(isset($v->errors()['password']));
        $this->assertTrue(isset($v->errors()['role']));
    }

    public function testPhilippineMobileNumber(): void
    {
        $ok = new Validator(['phone' => '0917 123 4567'], ['phone' => 'phone_ph']);
        $bad = new Validator(['phone' => '12345'], ['phone' => 'phone_ph']);
        $this->assertTrue($ok->passes());
        $this->assertTrue($bad->fails());
    }

    public function testNullableSkipsOtherRules(): void
    {
        $v = new Validator(['postal_code' => ''], ['postal_code' => 'nullable|regex:/^\d{4}$/']);
        $this->assertTrue($v->passes());
        $this->assertSame(null, $v->validated()['postal_code']);
    }

    public function testArrayRulesAllowPipesInRegex(): void
    {
        $rule = ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'];
        $this->assertTrue((new Validator(['exp' => '12/29'], ['exp' => $rule]))->passes());
        $this->assertTrue((new Validator(['exp' => '13/29'], ['exp' => $rule]))->fails());
    }
}
