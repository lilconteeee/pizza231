<?php
namespace Controllers;

use Models\Product;
use Models\Order;
use Models\Log;
use Views\OrderTemplate;

class OrderController
{
    public function get()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $model = new Product();
        $basketItems = $model->getBasketData();
        $total = 0;
        foreach ($basketItems as $item) {
            $total += $item['product']['price'] * $item['quantity'];
        }

        return OrderTemplate::render($basketItems, $total);
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $fio = trim($_POST['fio'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $deliveryType = $_POST['delivery_type'] ?? 'email';
        $email = '';
        $address = '';

        if ($deliveryType === 'email') {
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['flash'] = '⚠️ Укажите корректный email';
                $_SESSION['flash_type'] = 'error';
                header('Location: /order');
                exit;
            }
        } else {
            $address = trim($_POST['address'] ?? '');
            if (empty($address)) {
                $_SESSION['flash'] = '️ Укажите адрес доставки';
                $_SESSION['flash_type'] = 'error';
                header('Location: /order');
                exit;
            }
        }

        if (empty($fio) || empty($phone)) {
            $_SESSION['flash'] = '⚠️ Заполните все обязательные поля';
            $_SESSION['flash_type'] = 'error';
            header('Location: /order');
            exit;
        }

        $productModel = new Product();
        $products = $productModel->getBasketData();

        if (empty($products)) {
            $_SESSION['flash'] = '⚠️ Корзина пуста';
            $_SESSION['flash_type'] = 'error';
            header('Location: /cart');
            exit;
        }

        $allSum = 0;
        foreach ($products as $product) {
            $allSum += $product['product']['price'] * $product['quantity'];
        }

        $orderData = [
            'order_id' => 'ORD_' . uniqid(),
            'user_id' => $_SESSION['user_id'] ?? 0,
            'user_email' => $_SESSION['user_email'] ?? '',
            'fio' => htmlspecialchars($fio),
            'phone' => htmlspecialchars($phone),
            'delivery_type' => $deliveryType,
            'email' => $email,
            'address' => $address,
            'products' => $products,
            'total' => $allSum,
            'status' => 'new'
        ];

        // 🔹 СОХРАНЕНИЕ В SQL
        $orderModel = new Order();
        if (!$orderModel->saveOrder($orderData)) {
            $_SESSION['flash'] = '❌ Ошибка сохранения заказа. Попробуйте позже.';
            $_SESSION['flash_type'] = 'error';
            header('Location: /order');
            exit;
        }

        // 🔔 Логирование
        $logModel = new Log();
        $logModel->add('Создание заказа', $_SESSION['user_name'] ?? 'Гость', [
            'order_id' => $orderData['order_id'],
            'total' => $allSum,
            'items' => count($products)
        ]);

        // 📧 Отправка письма (через стандартный mail, без PHPMailer)
        if ($deliveryType === 'email' && !empty($email)) {
            $this->sendOrderEmail($email, $orderData);
        }

        // Очистка корзины и редирект
        $_SESSION['basket'] = [];
        $_SESSION['flash'] = '✅ Ваш заказ успешно создан!';
        $_SESSION['flash_type'] = 'success';
        header("Location: /order?success=1");
        exit;
    }

    /**
     * Отправка письма через стандартный mail()
     */
    private function sendOrderEmail(string $customerEmail, array $orderData): bool
    {
        $subject = 'Заказ №' . $orderData['order_id'] . ' оформлен';

        $body = "Здравствуйте, {$orderData['fio']}!\n\n";
        $body .= "Ваш заказ №{$orderData['order_id']} успешно оформлен.\n\n";
        $body .= "Состав заказа:\n";
        $body .= str_repeat('-', 50) . "\n";

        foreach ($orderData['products'] as $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];
            $subtotal = $product['price'] * $quantity;
            $body .= "{$product['name']} x {$quantity} = " . number_format($subtotal, 0, '.', ' ') . " ₽\n";
        }

        $body .= str_repeat('-', 50) . "\n";
        $body .= "Итого: " . number_format($orderData['total'], 0, '.', ' ') . " ₽\n";
        $body .= "Телефон: {$orderData['phone']}\n";

        if ($orderData['delivery_type'] === 'email') {
            $body .= "Доставка: на email ({$orderData['email']})\n";
        } else {
            $body .= "Доставка: курьером по адресу: {$orderData['address']}\n";
        }

        $body .= "\nСпасибо за покупку в Gym Low Cortisol!";

        $headers = "From: gym@gym.ru\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        // mail() может не работать на localhost без настроенного SMTP
        // поэтому просто логируем попытку
        $sent = @mail($customerEmail, $subject, $body, $headers);

        // Записываем в лог
        $logModel = new Log();
        $logModel->add('Отправка email о заказе', 'Система', [
            'to' => $customerEmail,
            'order_id' => $orderData['order_id'],
            'sent' => $sent ? 'да' : 'нет (localhost)'
        ]);

        return $sent;
    }
}