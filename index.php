<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$active_form = 'login';
$login_error = '';
$register_success = '';

// Check/Add Password column if missing from original SQL
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM master_employee LIKE 'PASSWORD'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE master_employee ADD PASSWORD VARCHAR(255) NOT NULL AFTER EMAIL_ID");
}

// --- BACKEND: LOGIN ---
if (isset($_POST['login_submit'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'EMAIL_ID' : 'MOBILE_NO';
    
    $stmt = mysqli_prepare($conn, "SELECT ID, FULL_NAME, ROLE, PASSWORD FROM master_employee WHERE $field = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['PASSWORD']) || $password === $user['PASSWORD']) {
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['full_name'] = $user['FULL_NAME'];
            $_SESSION['role'] = $user['ROLE'];
            header("Location: dashboard.php");
            exit();
        } else { $login_error = 'Invalid password.'; }
    } else { $login_error = 'User not found.'; }
}

// --- BACKEND: REGISTER ---
if (isset($_POST['register_submit'])) {
    $name = $_POST['full_name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $desig = $_POST['designation'];
    $role = $_POST['role'];

    $stmt = mysqli_prepare($conn, "INSERT INTO master_employee (FULL_NAME, MOBILE_NO, EMAIL_ID, PASSWORD, DESGINATION, ROLE, REG_AT) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
    mysqli_stmt_bind_param($stmt, "ssssss", $name, $mobile, $email, $pass, $desig, $role);
    
    if (mysqli_stmt_execute($stmt)) {
        $register_success = 'Registration successful! Please login.';
        $active_form = 'login';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NORA INFOTECH | Authentication</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --nora-teal: #004d4d;
            --nora-darkgreen: #003333;
            --nora-light: #90f6d4;
            --nora-gradient: linear-gradient(135deg, #004d4d 0%, #059669 100%);
            --error: #dc2626;
            --bg: linear-gradient(135deg, #004d4d 0%, #059669 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background-color: #004d4d;
            /* background-image: radial-gradient(at 0% 0%, hsla(161,71%,95%,1) 0, transparent 50%), 
                              radial-gradient(at 50% 0%, hsla(180,100%,96%,1) 0, transparent 50%); */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-card {
            width: 100%;
            max-width: 500px;
            background: #b2f3db;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            border: 1px solid rgba(0, 77, 77, 0.1);
        }

        .auth-header {
            background: var(--nora-darkgreen);
            padding: 20px 10px;
            text-align: center;
            color: white;
        }

        .logo-img {
            width: 150px; /* Adjust based on your actual logo size */
            height: auto;
            margin-bottom: 5px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
        }

        .auth-header h1 { font-size: 24px; letter-spacing: 1px; font-weight: 800; }

        .auth-body { padding: 20px; }

        .tabs {
            display: flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            border-radius: 10px;
            font-weight: 600;
            color: #64748b;
            transition: 0.3s;
        }

        .tab.active {
            background: white;
            color: var(--nora-teal);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .form-group { margin-bottom: 18px; position: relative; }
        
        /* Advanced Verification Error Styling */
        .error-hint {
            color: var(--error);
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 4px;
            display: none; /* Hidden by default */
            text-transform: uppercase;
        }

        .input-ctrl {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .input-ctrl:focus {
            outline: none;
            border-color: var(--nora-green);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .input-ctrl.invalid { border-color: var(--error); background-color: #fff1f2; }

        .btn-nora {
            width: 100%;
            padding: 14px;
            background: var(--nora-darkgreen);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-nora:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 77, 77, 0.3); }
        .btn-nora:disabled { opacity: 0.5; cursor: not-allowed; }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }

        .form-content { display: none; }
        .form-content.active { display: block; animation: fadeInUp 0.4s ease; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 480px) { .row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="auth-header">
        <img src="data/logo.png" alt="Nora Infotech" class="logo-img" onerror="this.src='https://via.placeholder.com/120x60?text=LOGO'">
        <h1>NORA INFOTECH</h1>
    </div>

    <div class="auth-body">
        <div class="tabs">
            <div class="tab <?php echo $active_form == 'login' ? 'active' : ''; ?>" onclick="switchTab('login')">Login</div>
            <div class="tab <?php echo $active_form == 'register' ? 'active' : ''; ?>" onclick="switchTab('register')">Register</div>
        </div>

        <?php if($login_error): ?> <div class="alert alert-error"><?php echo $login_error; ?></div> <?php endif; ?>
        <?php if($register_success): ?> <div class="alert alert-success"><?php echo $register_success; ?></div> <?php endif; ?>

        <form id="login-form" class="form-content <?php echo $active_form == 'login' ? 'active' : ''; ?>" method="POST">
            <div class="form-group">
                <input type="text" name="username" class="input-ctrl" placeholder="Email or Mobile" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="input-ctrl" placeholder="Password" required>
            </div>
            <button type="submit" name="login_submit" class="btn-nora">Login to System</button>
        </form>

        <form id="register-form" class="form-content <?php echo $active_form == 'register' ? 'active' : ''; ?>" method="POST">
            <div class="form-group">
                <div class="error-hint" id="err-name">Name is too short (min 3)</div>
                <input type="text" name="full_name" id="reg_name" class="input-ctrl" placeholder="Full Name" required>
            </div>

            <div class="row">
                <div class="form-group">
                    <div class="error-hint" id="err-mobile">10-Digit Mobile Required</div>
                    <input type="text" name="mobile" id="reg_mobile" class="input-ctrl" placeholder="Mobile" maxlength="10" required>
                </div>
                <div class="form-group">
                    <div class="error-hint" id="err-email">Invalid Email Address</div>
                    <input type="email" name="email" id="reg_email" class="input-ctrl" placeholder="Email ID" required>
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <div class="error-hint" id="err-desig">Designation Required</div>
                    <input type="text" name="designation" id="reg_desig" class="input-ctrl" placeholder="Designation" required>
                </div>
                <div class="form-group">
                    <select name="role" class="input-ctrl" required>
                        <option value="">Select Role</option>
                        <option value="Employee">Employee</option>
                        <option value="HR">HR</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <div class="error-hint" id="err-pass">Minimum 6 characters</div>
                    <input type="password" name="password" id="reg_pass" class="input-ctrl" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <div class="error-hint" id="err-confirm">Passwords mismatch</div>
                    <input type="password" id="reg_confirm" class="input-ctrl" placeholder="Confirm Password" required>
                </div>
            </div>

            <button type="submit" name="register_submit" id="reg-btn" class="btn-nora" disabled>Register Account</button>
        </form>
    </div>
</div>

<script>
    function switchTab(type) {
        document.querySelectorAll('.form-content').forEach(f => f.classList.remove('active'));
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.getElementById(type + '-form').classList.add('active');
        event.target.classList.add('active');
    }

    // Advance Verification Logic
    const fields = {
        name: { el: 'reg_name', err: 'err-name', check: v => v.length >= 3 },
        mobile: { el: 'reg_mobile', err: 'err-mobile', check: v => /^[0-9]{10}$/.test(v) },
        email: { el: 'reg_email', err: 'err-email', check: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) },
        desig: { el: 'reg_desig', err: 'err-desig', check: v => v.trim() !== "" },
        pass: { el: 'reg_pass', err: 'err-pass', check: v => v.length >= 6 },
        confirm: { el: 'reg_confirm', err: 'err-confirm', check: v => v === document.getElementById('reg_pass').value }
    };

    function validate() {
        let allValid = true;
        Object.keys(fields).forEach(key => {
            const f = fields[key];
            const input = document.getElementById(f.el);
            const error = document.getElementById(f.err);
            const isValid = f.check(input.value);

            if (input.value !== "") {
                if (!isValid) {
                    input.classList.add('invalid');
                    error.style.display = 'block';
                    allValid = false;
                } else {
                    input.classList.remove('invalid');
                    error.style.display = 'none';
                }
            } else {
                allValid = false;
                error.style.display = 'none';
                input.classList.remove('invalid');
            }
        });
        document.getElementById('reg-btn').disabled = !allValid;
    }

    // Attach listeners to all registration inputs
    Object.values(fields).forEach(f => {
        document.getElementById(f.el).addEventListener('input', validate);
    });
</script>

</body>
</html>