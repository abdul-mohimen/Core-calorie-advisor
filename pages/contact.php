<?php
require_once dirname(__DIR__) . '/config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    // Honeypot check
    if (post('website_hp') !== '') {
        sleep(2);
        exit;
    }

    $name = trim(post('name'));
    $email = filter_var(post('email'), FILTER_VALIDATE_EMAIL);
    $category = post('category', 'general');
    $subject = trim(post('subject'));
    $message = trim(post('message'));

    if (mb_strlen($name) < 2) {
        $error = 'Please enter your name.';
    } elseif (!$email) {
        $error = 'Please enter a valid email address.';
    } elseif (mb_strlen($subject) < 3) {
        $error = 'Subject must be at least 3 characters.';
    } elseif (mb_strlen($message) < 10) {
        $error = 'Message must be at least 10 characters.';
    } else {
        // Map target category for issue_reports table
        $targetType = in_array($category, ['doctor', 'trainer'], true) ? $category : 'general';
        $reporterId = is_logged_in() ? (int)$_SESSION['user']['id'] : 1; // Default to admin/system if guest

        $st = db()->prepare('INSERT INTO issue_reports (reporter_id, target_type, subject, message, status) VALUES (?,?,?,?,?)');
        $st->execute([$reporterId, $targetType, '[' . strtoupper($category) . '] ' . $subject, "From: $name <$email>\n\n" . $message, 'open']);

        $success = 'Your message has been sent to our support and admin team. We will get back to you shortly!';
    }
}

$pageTitle = 'Contact Support & Help Desk';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="wrap support-page" style="padding-top:40px;padding-bottom:60px;max-width:900px">
    <div class="support-page__head" style="text-align:center;margin-bottom:40px">
        <span class="eyebrow" style="justify-content:center">CORE CALORIE ADVISOR SUPPORT</span>
        <h1 style="font-size:36px;margin-top:10px">Contact &amp; Help Desk</h1>
        <p style="color:var(--muted);max-width:600px;margin:12px auto 0">Have a question about 3D coaching, portal access, AI food scanners or medical safe plans? Our engineering and support team is here to assist.</p>
    </div>

    <?php if ($error): ?>
        <div class="flash flash-err" style="margin-bottom:24px"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="flash flash-ok" style="margin-bottom:24px"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="support-grid" style="display:grid;grid-template-columns:1fr 1.5fr;gap:30px">
        <!-- Support Details Column -->
        <div class="support-options" style="display:flex;flex-direction:column;gap:20px">
            <div class="card support-option" style="display:flex;align-items:flex-start;gap:14px">
                <div style="font-size:24px">⚡</div>
                <div>
                    <h4 style="font-size:16px;margin-bottom:4px">Instant AI Assistance</h4>
                    <p style="color:var(--muted);font-size:13px;line-height:1.5">Aap hamare 3D Coach Chatbot se instant assistance le sakte hain directly on the home page.</p>
                </div>
            </div>

            <div class="card support-option" style="display:flex;align-items:flex-start;gap:14px">
                <div style="font-size:24px">🏥</div>
                <div>
                    <h4 style="font-size:16px;margin-bottom:4px">Doctor &amp; Trainer Support</h4>
                    <p style="color:var(--muted);font-size:13px;line-height:1.5">For medical disease inquiries or workout plan modifications, select 'Doctor / Trainer' category.</p>
                </div>
            </div>

            <div class="card support-option" style="display:flex;align-items:flex-start;gap:14px">
                <div style="font-size:24px">🛡️</div>
                <div>
                    <h4 style="font-size:16px;margin-bottom:4px">Admin Verification</h4>
                    <p style="color:var(--muted);font-size:13px;line-height:1.5">Doctor & Trainer account verification requests are reviewed directly by the admin team.</p>
                </div>
            </div>
        </div>

        <!-- Contact Form Column -->
        <div class="card support-form-card">
            <h3 style="font-size:20px;margin-bottom:20px">Send Us a Message</h3>
            <form method="post">
                <?= csrf_field() ?>
                
                <!-- Honeypot -->
                <div style="display:none !important;visibility:hidden !important;opacity:0 !important;position:absolute !important;left:-9999px !important;">
                    <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                </div>

                <div class="field" style="margin-bottom:14px">
                    <label>Your Name</label>
                    <input type="text" name="name" required placeholder="Mohimen Khan" value="<?= is_logged_in() ? e($_SESSION['user']['name']) : e(post('name')) ?>">
                </div>

                <div class="field" style="margin-bottom:14px">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="name@example.com" value="<?= is_logged_in() ? e($_SESSION['user']['email']) : e(post('email')) ?>">
                </div>

                <div class="field" style="margin-bottom:14px">
                    <label>Category</label>
                    <select name="category">
                        <option value="general">General Inquiry</option>
                        <option value="technical">Technical Support / Bug</option>
                        <option value="doctor">Doctor Consultation Issue</option>
                        <option value="trainer">Trainer Plan Issue</option>
                        <option value="billing">Billing &amp; Subscription</option>
                    </select>
                </div>

                <div class="field" style="margin-bottom:14px">
                    <label>Subject</label>
                    <input type="text" name="subject" required placeholder="Brief title of your request" value="<?= e(post('subject')) ?>">
                </div>

                <div class="field" style="margin-bottom:20px">
                    <label>Message</label>
                    <textarea name="message" rows="5" required placeholder="Explain your query or issue in detail..." style="width:100%;padding:12px;background:var(--bg);border:1px solid var(--line);border-radius:8px;color:var(--text);font-family:inherit"><?= e(post('message')) ?></textarea>
                </div>

                <button class="btn btn-fire" type="submit" style="width:100%;justify-content:center">⚡ Submit Ticket to Support</button>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
