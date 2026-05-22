<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS is set once in .htaccess (mod_headers). Do not duplicate here.

// Handle preflight requests (Apache may still route OPTIONS here)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once __DIR__ . '/app/config/config.php';

// Include required files
require_once __DIR__ . '/app/controllers/BaseController.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__ . '/app/controllers/GalleryController.php';
require_once __DIR__ . '/app/controllers/EligibleProgramsController.php';

// Simple router
class Router {
    private $routes = [];
    private $controllerNamespace = '';

    public function addRoute($method, $path, $controller, $action) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove query string
        $requestPath = strtok($requestPath, '?');
        
        // Remove base directory if present
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/') {
            $requestPath = str_replace($basePath, '', $requestPath);
        }
        
        // Remove index.php if present (for direct access)
        if (strpos($requestPath, '/index.php') === 0) {
            $requestPath = substr($requestPath, 10); // Remove '/index.php'
        }
        
        // Debug output
        header('X-Debug-Request-Path: ' . $requestPath);
        header('X-Debug-Base-Path: ' . $basePath);
        header('X-Debug-Script-Name: ' . $_SERVER['SCRIPT_NAME']);
        header('X-Debug-Request-URI: ' . $_SERVER['REQUEST_URI']);
        
        // Default route
        if (empty($requestPath) || $requestPath === '/') {
            $requestPath = '/dashboard';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestPath, $params)) {
                $controllerClass = $route['controller'];
                
                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    
                    if (method_exists($controller, $route['action'])) {
                        // Call the action with parameters
                        call_user_func_array([$controller, $route['action']], $params);
                        return;
                    }
                } else {
                    // Debug: Show which controller class doesn't exist
                    http_response_code(500);
                    echo "<h1>Controller Not Found</h1>";
                    echo "<p>Controller class '$controllerClass' does not exist.</p>";
                    echo "<p>Available controllers:</p>";
                    echo "<pre>";
                    print_r(get_declared_classes());
                    echo "</pre>";
                    return;
                }
            }
        }

        // 404 - Route not found
        http_response_code(404);
        echo '<h1>404 - Page Not Found</h1>';
        echo '<p>The requested page could not be found.</p>';
        echo '<h3>Debug Info:</h3>';
        echo '<p>Request Path: ' . htmlspecialchars($requestPath) . '</p>';
        echo '<p>Request Method: ' . htmlspecialchars($requestMethod) . '</p>';
        echo '<p>Available Routes:</p>';
        echo '<pre>';
        foreach ($this->routes as $route) {
            echo $route['method'] . ' ' . $route['path'] . ' -> ' . $route['controller'] . '::' . $route['action'] . "\n";
        }
        echo '</pre>';
    }

    private function matchPath($routePath, $requestPath, &$params) {
        $routePath = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $routePath = '#^' . $routePath . '$#';
        
        if (preg_match($routePath, $requestPath, $matches)) {
            array_shift($matches); // Remove full match
            $params = $matches;
            return true;
        }
        
        return false;
    }
}

// Create router instance
$router = new Router();

// Define routes
$router->addRoute('GET', '/', 'DashboardController', 'index');
$router->addRoute('GET', '/dashboard', 'DashboardController', 'index');
$router->addRoute('GET', '/test', 'DashboardController', 'test');
$router->addRoute('GET', '/dashboard/profile', 'DashboardController', 'profile');
$router->addRoute('POST', '/dashboard/profile', 'DashboardController', 'profile');
$router->addRoute('GET', '/dashboard/change-password', 'DashboardController', 'changePassword');
$router->addRoute('POST', '/dashboard/change-password', 'DashboardController', 'changePassword');
$router->addRoute('GET', '/dashboard/settings', 'DashboardController', 'settings');
$router->addRoute('POST', '/dashboard/settings', 'DashboardController', 'settings');
$router->addRoute('GET', '/dashboard/api/stats', 'DashboardController', 'apiStats');
$router->addRoute('POST', '/dashboard/api/clear-cache', 'DashboardController', 'clearCache');

// Authentication routes
$router->addRoute('GET', '/auth/login', 'AuthController', 'login');
$router->addRoute('POST', '/auth/login', 'AuthController', 'login');
$router->addRoute('POST', '/auth/api/login', 'AuthController', 'apiLogin');
$router->addRoute('GET', '/auth/logout', 'AuthController', 'logout');
$router->addRoute('GET', '/auth/forgot-password', 'AuthController', 'forgotPassword');
$router->addRoute('POST', '/auth/forgot-password', 'AuthController', 'forgotPassword');
$router->addRoute('GET', '/auth/reset-password', 'AuthController', 'resetPassword');
$router->addRoute('POST', '/auth/reset-password', 'AuthController', 'resetPassword');

// Gallery routes
$router->addRoute('GET', '/gallery', 'GalleryController', 'index');
$router->addRoute('GET', '/gallery/create', 'GalleryController', 'create');
$router->addRoute('POST', '/gallery/create', 'GalleryController', 'create');
$router->addRoute('GET', '/gallery/edit/{id}', 'GalleryController', 'edit');
$router->addRoute('POST', '/gallery/edit/{id}', 'GalleryController', 'edit');
$router->addRoute('GET', '/gallery/delete/{id}', 'GalleryController', 'delete');
$router->addRoute('POST', '/gallery/delete/{id}', 'GalleryController', 'delete');
$router->addRoute('GET', '/gallery/view/{id}', 'GalleryController', 'viewImage');
$router->addRoute('POST', '/gallery/toggle-status/{id}', 'GalleryController', 'toggleStatus');
$router->addRoute('POST', '/gallery/reorder', 'GalleryController', 'reorder');
$router->addRoute('POST', '/gallery/bulk-actions', 'GalleryController', 'bulkActions');
$router->addRoute('POST', '/gallery/upload', 'GalleryController', 'upload');

// Menu Management routes (to be created)
$router->addRoute('GET', '/menus', 'MenuController', 'index');
$router->addRoute('GET', '/menus/create', 'MenuController', 'create');
$router->addRoute('POST', '/menus/create', 'MenuController', 'create');
$router->addRoute('GET', '/menus/edit/{id}', 'MenuController', 'edit');
$router->addRoute('POST', '/menus/edit/{id}', 'MenuController', 'edit');
$router->addRoute('POST', '/menus/delete/{id}', 'MenuController', 'delete');
$router->addRoute('POST', '/menus/reorder', 'MenuController', 'reorder');

// Content Management routes (to be created)
$router->addRoute('GET', '/content', 'ContentController', 'index');
$router->addRoute('GET', '/content/edit/{section}', 'ContentController', 'edit');
$router->addRoute('POST', '/content/edit/{section}', 'ContentController', 'edit');

// Settings routes (to be created)
$router->addRoute('GET', '/settings', 'SettingsController', 'index');
$router->addRoute('POST', '/settings', 'SettingsController', 'update');

// API routes for frontend
$router->addRoute('GET', '/api/menu', 'ApiController', 'getMenu');
$router->addRoute('GET', '/api/gallery', 'ApiController', 'getGallery');
$router->addRoute('GET', '/api/content/{section}', 'ApiController', 'getContent');
$router->addRoute('GET', '/api/settings', 'ApiController', 'getSettings');

// Eligible Programs (Smart Brochures sync from parrot_mis)
$router->addRoute('GET',  '/eligible-programs',                  'EligibleProgramsController', 'index');
$router->addRoute('POST', '/eligible-programs/toggle-hidden',    'EligibleProgramsController', 'toggleHidden');
$router->addRoute('POST', '/eligible-programs/toggle-featured',  'EligibleProgramsController', 'toggleFeatured');
$router->addRoute('POST', '/eligible-programs/update-label',     'EligibleProgramsController', 'updateLabel');
$router->addRoute('GET',  '/api/eligible-programs',              'EligibleProgramsController', 'apiList');

// Dispatch the request
$router->dispatch();
?>
