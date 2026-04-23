<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #1a3d1a 0%, #49772a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        .login-sidebar {
            background: linear-gradient(135deg, #1a3d1a 0%, #49772a 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-form {
            padding: 3rem;
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #1a3d1a;
            box-shadow: 0 0 0 0.2rem rgba(26, 61, 26, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #1a3d1a 0%, #49772a 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 61, 26, 0.3);
        }
        .logo {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
        }
        .feature-list i {
            margin-right: 0.5rem;
            color: #4ade80;
        }
        @media (max-width: 768px) {
            .login-sidebar {
                display: none;
            }
            .login-form {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-md-5 login-sidebar">
                <div class="logo">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Parrot Admin
                </div>
                <h3 class="mb-4">Welcome Back!</h3>
                <p class="mb-4">Manage your website content with ease. Access powerful tools to control every aspect of your site.</p>
                
                <ul class="feature-list">
                    <li>
                        <i class="fas fa-check-circle"></i>
                        Full Content Management
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        Gallery Management
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        Menu Customization
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        Real-time Updates
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        Secure Authentication
                    </li>
                </ul>
                
                <div class="mt-auto">
                    <p class="small mb-0">
                        <i class="fas fa-shield-alt me-1"></i>
                        Secure & Protected
                    </p>
                </div>
            </div>
            
            <!-- Login Form -->
            <div class="col-md-7 login-form">
                <div class="text-center mb-4">
                    <h2><?= $title ?></h2>
                </div>
                
                <!-- Flash Messages -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?= $content ?>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <small>
                            Need help? Contact support at 
                            <a href="mailto:admin@parrotvisa.com" class="text-decoration-none">admin@parrotvisa.com</a>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Add loading state to form submission
        $('form').on('submit', function() {
            var $btn = $(this).find('button[type="submit"]');
            var originalText = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...').prop('disabled', true);
            
            // Re-enable after 5 seconds in case of issues
            setTimeout(function() {
                $btn.html(originalText).prop('disabled', false);
            }, 5000);
        });
    </script>
</body>
</html>
