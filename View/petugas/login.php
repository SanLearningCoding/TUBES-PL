<?php
include __DIR__ . '/../../Config/Path.php';
include Path::template('auth_header.php');
?>

<style>
/* Login hero inspired by reference image, using project color tokens */
.login-hero {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.login-container {
    width: 100%;
    max-width: 900px;
    background: #ffffff;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    display: grid;
    grid-template-columns: 1fr 1fr;
}

.login-left {
    background: linear-gradient(135deg, #c62828 0%, #8b1a1a 100%);
    color: rgba(255,255,255,0.98);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    position: relative;
    overflow: hidden;
    min-height: 400px;
}

.login-left::before {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,0.08);
    border-radius: 50%;
    top: -150px;
    right: -100px;
    animation: float 8s ease-in-out infinite;
}

.login-left::after {
    content: '';
    position: absolute;
    width: 250px;
    height: 250px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
    bottom: -80px;
    left: -80px;
    animation: float 10s ease-in-out infinite reverse;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-30px); }
}

.login-left-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 280px;
}

.login-left h2 {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 1rem;
    line-height: 1.3;
    letter-spacing: -0.5px;
    animation: slideInLeft 0.8s ease-out;
}

.login-left p {
    color: rgba(255,255,255,0.88);
    font-size: 0.9rem;
    margin: 0;
    animation: slideInLeft 0.8s ease-out 0.2s backwards;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.login-right {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    background: #ffffff;
    position: relative;
}

.login-right::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--pmi-red);
}

.login-panel {
    width: 100%;
    max-width: 100%;
    background: transparent;
    border-radius: 0;
    padding: 0;
    box-shadow: none;
}

.login-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.login-panel-header h3 {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--pmi-text-muted);
    margin: 0;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.login-panel-header .pmi-badge {
    background: var(--pmi-red);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.login-panel h2 {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    color: var(--pmi-text-main);
}

.login-panel .form-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--pmi-text-muted);
    margin-bottom: 0.5rem;
    text-transform: capitalize;
    display: block;
}

.login-panel .form-control {
    border: none;
    border-bottom: 2px solid #e0e0e0;
    border-radius: 0;
    padding: 10px 0;
    font-size: 0.9rem;
    background: transparent;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    width: 100%;
}

.login-panel .form-control:focus {
    border-bottom-color: var(--pmi-red);
    box-shadow: none;
    background: transparent;
    outline: none;
}

.login-panel .form-control::placeholder {
    color: #ccc;
}

.form-group {
    margin-bottom: 1.5rem !important;
    position: relative;
}

.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-toggle {
    border: none !important;
    background: transparent !important;
    color: var(--pmi-text-muted);
    padding: 0 !important;
    position: absolute;
    right: 0;
    bottom: 10px;
    cursor: pointer;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: var(--pmi-red);
}

.login-panel .btn-pmi {
    background: var(--pmi-red);
    border: none;
    color: #fff;
    font-weight: 700;
    font-size: 0.95rem;
    padding: 12px 24px;
    border-radius: 8px;
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.login-panel .btn-pmi:hover {
    background: var(--pmi-red-dark);
    transform: translateY(-3px);
    box-shadow: 0 12px 24px rgba(198, 40, 40, 0.4);
}

.login-panel .btn-pmi:active {
    transform: translateY(-1px);
}

@media (max-width: 992px) {
    .login-container {
        grid-template-columns: 1fr;
    }
    
    .login-left {
        min-height: 300px;
        padding: 2rem;
    }
    
    .login-left h2 {
        font-size: 1.6rem;
    }
}

@media (max-width: 576px) {
    .login-hero {
        padding: 15px;
    }
    
    .login-right {
        padding: 2rem;
    }
    
    .login-panel h2 {
        font-size: 1.5rem;
    }
    
    .login-left h2 {
        font-size: 1.4rem;
    }
    
    .login-left {
        padding: 1.5rem;
        min-height: auto;
    }
}
</style>

<div class="login-hero">
    <div class="login-container">
        <!-- Left: animated red section with headline -->
        <div class="login-left">
            <div class="login-left-content">
                <h2>We Save Lives Through Blood Donation</h2>
            </div>
        </div>

        <!-- Right: login form -->
        <div class="login-right">
            <div class="login-panel">
                <div class="login-panel-header">
                    <span class="pmi-badge">PMI</span>
                </div>

                <?php if (session_status() == PHP_SESSION_NONE) session_start(); ?>
                <?php if (isset($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
                    <div class="alert alert-<?= htmlspecialchars($f['type'] ?? 'info') ?>" role="alert">
                        <?= htmlspecialchars($f['message'] ?? '') ?>
                    </div>
                <?php endif; ?>

                <form action="?action=authenticate" method="POST" novalidate>
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="nama@example.com" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Kata sandi" required>
                            <button type="button" class="btn btn-sm password-toggle" aria-label="Toggle password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-pmi">Masuk</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
// Password toggle functionality
document.querySelectorAll('.password-toggle').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const input = this.closest('.position-relative').querySelector('input[type="password"], input[type="text"]');
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});
</script>

<?php include Path::template('auth_footer.php'); ?>
