<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Загрузка .env
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
        putenv(trim($k) . '=' . trim($v));
    }
}

// Автозагрузка классов из src/
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file)) require_once $file;
});

use Controllers\{
    HomeController, AboutController, ServicesController,
    CatalogController, ProductController, BasketController,
    AuthController, ProfileController, OrderController,
    AdminController, VerificationController, ErrorController
};

$resource = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$resource = preg_replace('/[^a-zA-Z0-9\-_\/]/', '', $resource);

// Авторизация
if ($resource === 'register') { echo (new AuthController())->register(); exit; }
if ($resource === 'register/process') { (new AuthController())->processRegister(); exit; }
if ($resource === 'login') { echo (new AuthController())->login(); exit; }
if ($resource === 'login/process') { (new AuthController())->processLogin(); exit; }
if ($resource === 'logout') { (new AuthController())->logout(); exit; }

// Верификация
if ($resource === 'verify') { (new VerificationController())->verify(); exit; }
if ($resource === 'verification/send') { (new VerificationController())->sendCode(); exit; }

// Профиль
if ($resource === 'profile') { echo (new ProfileController())->get(); exit; }
if ($resource === 'profile/phone') { (new ProfileController())->updatePhone(); exit; }
if ($resource === 'profile/password') { (new ProfileController())->updatePassword(); exit; }
if ($resource === 'profile/card') { (new ProfileController())->updateCard(); exit; }

// Корзина
if ($resource === 'cart') { echo (new BasketController())->get(); exit; }
if ($resource === 'cart/add') { (new BasketController())->add(); exit; }
if ($resource === 'cart/remove') { (new BasketController())->remove(); exit; }
if ($resource === 'cart/clear') { (new BasketController())->clear(); exit; }
if ($resource === 'cart/update') { (new BasketController())->update(); exit; }
if ($resource === 'cart/setStorage') { (new BasketController())->setStorage(); exit; }

// Заказ
if ($resource === 'checkout') { header("Location: /order"); exit; }
if ($resource === 'order') {
    $c = new OrderController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') { $c->create(); exit; }
    echo $c->get(); exit;
}

// Товары
if (preg_match('/^product\/(\d+)$/', $resource, $m)) {
    echo (new ProductController())->get((int)$m[1]); exit;
}
if ($resource === 'products') { echo (new CatalogController())->get(); exit; }

// Админка
if ($resource === 'admin') { echo (new AdminController())->dashboard(); exit; }
if ($resource === 'admin/product/edit') { (new AdminController())->editProduct(); exit; }
if ($resource === 'admin/product/add') { (new AdminController())->addProduct(); exit; }
if ($resource === 'admin/user/delete') { (new AdminController())->deleteUser(); exit; }
if ($resource === 'admin/role') { (new AdminController())->setRole(); exit; }
if ($resource === 'admin/activate') { (new AdminController())->activate(); exit; }
if ($resource === 'admin/logs') { (new AdminController())->logs(); exit; }
if ($resource === 'admin/logs/clear') { (new AdminController())->clearLogs(); exit; }

// Страницы
switch ($resource) {
    case '': case 'home': echo (new HomeController())->get(); break;
    case 'about': echo (new AboutController())->get(); break;
    case 'services': echo (new ServicesController())->get(); break;
    default: http_response_code(404); echo (new ErrorController())->get();
}