<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\EnrollmentController;
use App\Controllers\OfferController;
use App\Controllers\PaymentController;
use App\Controllers\ProfileController;
use App\Middleware\JwtAuthMiddleware;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Psr7\Response;

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$app = AppFactory::create();

// Middleware CORS
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Max-Age', '86400');
});

// Handle CORS preflight
$app->options('/{routes:.+}', function ($request, $response) {
    return $response->withStatus(200);
});

// Error handling
$app->addErrorMiddleware(true, true, true);

// JSON body parsing middleware (manual, handles charset variants)
$app->add(function ($request, $handler) {
    $contentType = $request->getHeaderLine('Content-Type');
    $method = $request->getMethod();

    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) && str_contains($contentType, 'application/json')) {
        $raw = (string)$request->getBody();
        if (!empty($raw)) {
            $data = json_decode($raw, true);
            if (is_array($data)) {
                $request = $request->withParsedBody($data);
            }
        }
    }
    return $handler->handle($request);
});

// Rutas públicas
$app->post('/v1/auth/login', [AuthController::class, 'login']);
$app->post('/v1/auth/refresh', [AuthController::class, 'refresh']);

// Rutas protegidas
$app->group('/v1', function (RouteCollectorProxy $group) {
    $group->get('/profile', [ProfileController::class, 'get']);
    $group->put('/profile', [ProfileController::class, 'update']);
    $group->post('/profile/photo', [ProfileController::class, 'uploadPhoto']);
    $group->get('/profile/photo', [ProfileController::class, 'getPhoto']);
    $group->put('/profile/password', [ProfileController::class, 'changePassword']);
    $group->get('/enrollments', [EnrollmentController::class, 'list']);
    $group->post('/enrollments/pre-register', [EnrollmentController::class, 'preRegister']);
    $group->get('/debts', [PaymentController::class, 'getDebts']);
    $group->get('/payments', [PaymentController::class, 'getPayments']);
    $group->post('/payments/report', [PaymentController::class, 'reportPayment']);
    $group->get('/payments/bancos', [PaymentController::class, 'getBancos']);
    $group->get('/payments/formas-pago', [PaymentController::class, 'getFormasPago']);
    $group->get('/offers', [OfferController::class, 'getAvailable']);
})->add(new JwtAuthMiddleware());

// Health check
$app->get('/health', function ($request, $response) {
    $payload = json_encode([
        'success' => true,
        'data' => [
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => date('c'),
        ],
    ]);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});

// 404 handler
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    $payload = json_encode([
        'success' => false,
        'message' => 'Ruta no encontrada',
        'code' => 'NOT_FOUND',
    ]);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(404);
});

// Base path stripping (added last = runs first, before routing)
$app->add(function ($request, $handler) {
    $basePath = '/api/public';
    $uri = $request->getUri();
    $path = $uri->getPath();
    if (str_starts_with($path, $basePath)) {
        $newPath = substr($path, strlen($basePath)) ?: '/';
        $uri = $uri->withPath($newPath);
        $request = $request->withUri($uri);
    }
    return $handler->handle($request);
});

$app->run();
