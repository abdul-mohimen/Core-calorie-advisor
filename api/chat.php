<?php
/* ============================================================
   CCA ASSISTANT — context-aware chatbot endpoint (JSON).
   Rule/keyword knowledge base about Core Calorie Advisor. Aware of the
   visitor's login state + role so answers are personalised.
   (Swap the resolve() body for a Claude API call later if desired.)
   ============================================================ */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok' => false, 'error' => 'POST only']); exit; }
$token = $_POST['csrf'] ?? '';
if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
    http_response_code(419); echo json_encode(['ok' => false, 'error' => 'CSRF invalid']); exit;
}

$msg  = strtolower(trim((string)($_POST['message'] ?? '')));
$u    = current_user();
$role = $u['role'] ?? null;
$name = $u ? explode(' ', $u['name'])[0] : null;
$B    = BASE_URL;

if ($msg === '') { echo json_encode(['ok' => true, 'reply' => 'Type a question and I\'ll guide you through Core Calorie Advisor 🔥']); exit; }

/* helper: does the message contain ANY of these keywords? */
$has = fn(array $kw) => array_reduce($kw, fn($c, $k) => $c || str_contains($msg, $k), false);

$reply = null;
$links = [];

if ($has(['hi', 'hello', 'hey', 'salam', 'assalam', 'yo '])) {
    $reply = $u
        ? "Hey $name! 👋 Welcome back to Core Calorie Advisor. Ask me about your dashboard, AI scanners, workouts, or trainers."
        : "Welcome to Core Calorie Advisor! 🔥 I can explain our workouts, AI scanners, pricing, trainers and how to get started. What would you like to know?";
}
elseif ($has(['body scan', 'body scanner', 'scan my body', 'physique'])) {
    if ($role === 'member') {
        $reply = "The AI Body Scanner uses your camera (Google-Lens style) to estimate weight, body-fat %, BMI and muscle mass, then recommends whether to gain or lose, suggests a trainer, and flags a doctor consult if it spots a health anomaly. It needs a Pro plan.";
        $links[] = ['Open Body Scanner', "$B/pages/scanner-body.php"];
    } else {
        $reply = "The AI Body Scanner is a Member-Portal Pro tool — it live-scans your physique and builds an exact report. Log in as a member with a Pro plan to use it.";
        $links[] = ['Go Pro', "$B/pages/pricing.php"];
    }
}
elseif ($has(['food scan', 'food scanner', 'scan food', 'calorie', 'macros', 'nutrition value'])) {
    if ($role === 'member') {
        $reply = "The AI Food Scanner reads any meal from your camera and returns exact calories, protein, carbs and fats — then you can hit 'Add to Daily Log' to save it. Pro members only.";
        $links[] = ['Open Food Scanner', "$B/pages/scanner-food.php"];
    } else {
        $reply = "The AI Food Scanner instantly reads calories and macros from a photo of your meal. It's a Pro tool inside the Member Portal.";
        $links[] = ['See Plans', "$B/pages/pricing.php"];
    }
}
elseif ($has(['workout', 'exercise', 'training', 'program', 'gym plan'])) {
    $reply = "Core Calorie Advisor has a full library of 3D-animated workout programs — free and Pro. Each one runs in our workout player with a live 3D titan, rest timers and a calorie report saved to your log.";
    $links[] = ['Browse Workouts', "$B/pages/workouts.php"];
}
elseif ($has(['nutrition', 'diet', 'food database', 'meal'])) {
    $reply = "Our Nutrition Database lets you search foods with exact calories and macros. Pro members can also scan meals with the AI Food Scanner.";
    $links[] = ['Open Nutrition', "$B/pages/nutrition.php"];
}
elseif ($has(['trainer', 'coach', 'book a session', 'appointment'])) {
    $reply = "You can browse elite trainers and doctors, see ratings, and book a session. Members and patients book right from the Trainers page; trainers accept or reject from their portal.";
    $links[] = ['Meet Trainers', "$B/pages/trainers.php"];
}
elseif ($has(['doctor', 'disease', 'injury', 'knee', 'heart', 'diabetes', 'safe plan', 'medical'])) {
    $reply = "For health conditions, our Doctors approve disease-safe workout plans (heart, diabetes, BP, knee pain and more). Patients get doctor-monitored safe workouts and reminders in the Patient Portal.";
    $links[] = ['Consult a Doctor', "$B/pages/trainers.php"];
}
elseif ($has(['price', 'pricing', 'cost', 'plan', 'subscription', 'pro', 'elite', 'upgrade'])) {
    $reply = "Three tiers: Recruit (Free) — workouts + calculators; CCA Pro (\$9.99/mo) — unlocks both AI Scanners + all Pro workouts; CCA Elite (\$19.99/mo) — adds doctor consults + custom plans.";
    $links[] = ['View Pricing', "$B/pages/pricing.php"];
}
elseif ($has(['calculator', 'bmi', 'bmr', 'water', 'macro split'])) {
    $reply = "The Calculators page covers BMI, daily-calorie BMR (Mifflin-St Jeor), water intake and macro split — free for everyone.";
    $links[] = ['Open Calculators', "$B/pages/calculators.php"];
}
elseif ($has(['login', 'log in', 'sign in', 'register', 'sign up', 'account'])) {
    $reply = $u
        ? "You're already logged in as $name ($role). Head to your dashboard anytime."
        : "You can log in or register from the auth page. Demo accounts use password 'cca123' (member@, trainer@, doctor@, patient@, admin@ corecalorieadvisor.com).";
    $links[] = $u ? ['My Dashboard', "$B/portals/$role.php"] : ['Login / Register', "$B/auth/login.php"];
}
elseif ($has(['dashboard', 'portal', 'my account', 'profile'])) {
    if ($u) { $reply = "Your $role portal has your stats, activity and tools. Jump in below."; $links[] = ['Open Dashboard', "$B/portals/$role.php"]; }
    else    { $reply = "Portals are private dashboards for each role — log in to see yours."; $links[] = ['Login', "$B/auth/login.php"]; }
}
elseif ($has(['privacy', 'data', 'terms', 'policy', 'secure', 'security'])) {
    $reply = "Your data is protected with bcrypt passwords, CSRF tokens, prepared statements and role-based access. Read the full details in our legal pages.";
    $links[] = ['Privacy Policy', "$B/pages/privacy-policy.php"];
    $links[] = ['Terms', "$B/pages/terms-and-conditions.php"];
}
elseif ($has(['thank', 'thanks', 'shukriya', 'great', 'awesome', 'cool'])) {
    $reply = "Anytime! Stay strong and keep forging 💪🔥";
}
elseif ($has(['who are you', 'what are you', 'your name', 'help', 'what can you do'])) {
    $reply = "I'm the CCA Assistant — your guide to Core Calorie Advisor. Ask me about workouts, the AI Body/Food scanners, trainers, doctors, pricing, calculators or your portal.";
}
else {
    $reply = "I can help with workouts, the AI Body &amp; Food scanners, trainers, doctors, pricing, calculators and your portal. Try asking, for example, \"How does the body scanner work?\" or \"Show me pricing.\"";
    $links[] = ['Browse Workouts', "$B/pages/workouts.php"];
    $links[] = ['View Pricing', "$B/pages/pricing.php"];
}

echo json_encode(['ok' => true, 'reply' => $reply, 'links' => $links]);
