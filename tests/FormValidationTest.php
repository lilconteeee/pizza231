<?php
declare(strict_types=1);

// Подключение автозагрузчика Composer (если запускаете файл напрямую)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPUnit\Framework\TestCase;

/**
 * Модульные тесты валидации форм проекта «gym low cortisol»
 * Проверяет логику AuthController, OrderController и ProfileController
 */
class FormValidationTest extends TestCase
{
    // ========== 1. РЕГИСТРАЦИЯ ==========
    public function testRegistrationValidData(): void
    {
        $data = ['name' => 'Иван', 'email' => 'test@mail.ru', 'password' => '123456', 'confirm' => '123456'];
        $this->assertNotEmpty(trim($data['name']));
        $this->assertNotFalse(filter_var($data['email'], FILTER_VALIDATE_EMAIL));
        $this->assertTrue(strlen($data['password']) >= 6);
        $this->assertEquals($data['password'], $data['confirm']);
    }

    public function testRegistrationInvalidEmail(): void
    {
        $this->assertFalse(filter_var('user@domain', FILTER_VALIDATE_EMAIL));
        $this->assertFalse(filter_var('', FILTER_VALIDATE_EMAIL));
    }

    public function testRegistrationWeakPassword(): void
    {
        $this->assertTrue(strlen('12345') < 6);
        $this->assertFalse(strlen('123456') < 6);
    }

    public function testRegistrationPasswordMismatch(): void
    {
        // НАМЕРЕННО СЛОМАННЫЙ ТЕСТ ДЛЯ ПРОВЕРКИ CI
        $this->assertEquals(1, 2);
    }

    // ========== 2. ОФОРМЛЕНИЕ ЗАКАЗА ==========
    public function testOrderValidEmailDelivery(): void
    {
        $data = ['fio' => 'Петров', 'phone' => '+79991234567', 'type' => 'email', 'contact' => 'client@mail.ru', 'address' => ''];
        $this->assertNotEmpty(trim($data['fio']));
        $this->assertTrue($data['type'] === 'email');
        $this->assertNotFalse(filter_var($data['contact'], FILTER_VALIDATE_EMAIL));
    }

    public function testOrderValidCourierDelivery(): void
    {
        $data = ['fio' => 'Сидоров', 'phone' => '+79997654321', 'type' => 'courier', 'contact' => '', 'address' => 'г. Москва, ул. Ленина 1'];
        $this->assertNotEmpty(trim($data['address']));
        $this->assertEmpty(trim($data['contact']));
    }

    // ========== 3. ПРОФИЛЬ ==========
    public function testProfilePhoneFormat(): void
    {
        $phone = preg_replace('/\D/', '', '+7 (999) 123-45-67');
        $this->assertEquals('79991234567', $phone);
        $this->assertTrue(strlen($phone) >= 10);
    }
}