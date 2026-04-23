<form method="POST" action="<?= baseUrl('auth/login') ?>">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    
    <div class="mb-3">
        <label for="username" class="form-label">
            <i class="fas fa-user me-2"></i>Username or Email
        </label>
        <input type="text" class="form-control" id="username" name="username" 
               value="<?= htmlspecialchars($old_input['username'] ?? '') ?>" 
               placeholder="Enter your username or email" required>
        <?php if (isset($errors['username'])): ?>
            <div class="text-danger small mt-1">
                <i class="fas fa-exclamation-circle me-1"></i>
                <?= htmlspecialchars($errors['username']) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="mb-3">
        <label for="password" class="form-label">
            <i class="fas fa-lock me-2"></i>Password
        </label>
        <div class="input-group">
            <input type="password" class="form-control" id="password" name="password" 
                   placeholder="Enter your password" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        <?php if (isset($errors['password'])): ?>
            <div class="text-danger small mt-1">
                <i class="fas fa-exclamation-circle me-1"></i>
                <?= htmlspecialchars($errors['password']) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember" 
               <?= isset($old_input['remember']) && $old_input['remember'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="remember">
            Remember me for 30 days
        </label>
    </div>
    
    <div class="d-grid">
        <button type="submit" class="btn btn-login">
            <i class="fas fa-sign-in-alt me-2"></i>
            Sign In
        </button>
    </div>
    
    <div class="text-center mt-3">
        <a href="<?= baseUrl('auth/forgot-password') ?>" class="text-decoration-none">
            <small>Forgot your password?</small>
        </a>
    </div>
</form>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>
