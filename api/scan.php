<?php
/* ============================================================
   AI SCAN ENDPOINT — Google-Lens-style Body / Food scanner.
   Configure OPENROUTER_API_KEY in .env for live vision analysis through the
   free vision router. The endpoint never fabricates body measurements or
   medical claims from a photograph.
   ============================================================ */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');

if (!is_logged_in() || !is_pro()) { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'Pro subscription required']); exit; }
if (($_SESSION['user']['role'] ?? '') !== 'member') { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'Member Portal only']); exit; }

$token = $_POST['csrf'] ?? '';
if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) { http_response_code(419); echo json_encode(['ok' => false, 'error' => 'CSRF invalid']); exit; }

$type = ($_POST['type'] ?? 'body') === 'food' ? 'food' : 'body';
$goal = in_array($_POST['goal'] ?? '', ['gain', 'cut', 'recomp'], true) ? $_POST['goal'] : 'recomp';
$ext  = validate_image_upload($_FILES['photo'] ?? []);
if ($ext === null) { echo json_encode(['ok' => false, 'error' => 'Invalid image — JPG/PNG/WebP, max 5MB.']); exit; }

/* Goal-tailored muscle development guide — used by the reference preview and merged
   into live results when the provider omits its own breakdown. Honest general
   coaching, not photo-derived measurements. */
function tf_muscle_focus(string $goal): array {
    if ($goal === 'gain') return [
        ['muscle' => 'Chest',     'priority' => 'High',   'action' => 'Push-ups / bench press — 3-4 sets, progressive overload each week'],
        ['muscle' => 'Back',      'priority' => 'High',   'action' => 'Rows and pull-ups — focus on lat width and posture'],
        ['muscle' => 'Legs',      'priority' => 'High',   'action' => 'Squats and lunges — builds overall foundation and mass'],
        ['muscle' => 'Shoulders', 'priority' => 'Medium', 'action' => 'Overhead press — add lateral raises for complete shoulder development'],
        ['muscle' => 'Arms',      'priority' => 'Medium', 'action' => 'Curls and dips — 2 focused sessions per week'],
        ['muscle' => 'Core',      'priority' => 'Medium', 'action' => 'Planks and leg raises — core stability for compound lifts'],
    ];
    if ($goal === 'cut') return [
        ['muscle' => 'Full Body', 'priority' => 'High',   'action' => 'HIIT circuits — burpees, mountain climbers, jumping jacks'],
        ['muscle' => 'Core',      'priority' => 'High',   'action' => 'Planks, crunches, Russian twists — muscle definition and tone'],
        ['muscle' => 'Legs',      'priority' => 'High',   'action' => 'Squat jumps and high knees — maximum calorie expenditure'],
        ['muscle' => 'Chest',     'priority' => 'Medium', 'action' => 'Push-up variations — preserve upper body lean mass'],
        ['muscle' => 'Back',      'priority' => 'Medium', 'action' => 'Rows and pulls — support spinal alignment and metabolic rate'],
    ];
    return [
        ['muscle' => 'Chest',     'priority' => 'High',   'action' => 'Push-ups — controlled tempo, 3 sets near technical fatigue'],
        ['muscle' => 'Back',      'priority' => 'High',   'action' => 'Rows / pull-ups — balance pushing and pulling volume'],
        ['muscle' => 'Legs',      'priority' => 'High',   'action' => 'Squats — 2 sessions per week, perfect form before increasing weight'],
        ['muscle' => 'Core',      'priority' => 'Medium', 'action' => 'Planks and rotational twists — finish each workout session'],
        ['muscle' => 'Shoulders', 'priority' => 'Low',    'action' => 'Overhead press — supported well by heavy compound movements'],
    ];
}
function tf_next_steps(string $goal): array {
    if ($goal === 'gain') return [
        'Daily protein intake: 1.6 - 2.2g per kg of bodyweight',
        'Moderate calorie surplus of +300 - 500 kcal for clean lean mass',
        'Progressive overload: track and increase weight or reps weekly',
        'Prioritize 7-9 hours of restful sleep for recovery and growth',
    ];
    if ($goal === 'cut') return [
        'Maintain a sustainable calorie deficit of -400 - 500 kcal',
        'Keep protein high to protect and retain lean muscle tissue',
        'Complete 3-4 HIIT workouts per week plus 8,000-10,000 daily steps',
        'Log all daily meals with the AI Food Scanner',
    ];
    return [
        'Eat at maintenance calories with 1.8g protein per kg',
        'Focus on heavy compound lifts to build strength and burn fat',
        'Schedule 3-4 strength workouts and 1-2 cardio sessions weekly',
        'Compare your physique progress photo every 2 weeks',
    ];
}

$imgData = base64_encode((string)file_get_contents($_FILES['photo']['tmp_name']));
$mime    = mime_content_type($_FILES['photo']['tmp_name']);

/* Privacy by default: raw scan photos are sent to the configured provider in-memory
   and are not retained on this server. Only the user-facing scan summary is saved. */
$prompt = $type === 'food'
  ? 'Analyze the food photo. Return ONLY JSON with: name, serving, kcal, protein, carbs, fats, verdict. Values are estimates for the visible serving. If the meal is unclear, say so in verdict and use null for uncertain numbers.'
  : 'Analyze only visible fitness presentation in this body photo. The user\'s training goal is: ' . $goal . ' (gain = build muscle, cut = lose fat, recomp = recomposition). Return ONLY JSON with: body_type, verdict, advice, training_focus, muscle_focus (array of {muscle, priority: High|Medium|Low, action} for which visible muscle groups to develop toward the goal), next_steps (array of 3-4 short actionable strings). Never infer or invent weight, BMI, body-fat percentage, muscle mass, a medical condition, age, gender, or a diagnosis from an image. State uncertainty clearly. If no human body is clearly visible in the photo, return {"no_body": true} only.';

function tf_scan_json(string $text): ?array {
    $text = trim((string)(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? ''));
    $decoded = json_decode($text, true);
    if (is_array($decoded)) return $decoded;
    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start === false || $end === false || $end <= $start) return null;
    $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
    return is_array($decoded) ? $decoded : null;
}

/* OpenRouter's `openrouter/free` router selects an available free model that
   accepts image input. A key is still mandatory and stays server-side in .env.
   Local image data is sent only for this request and is never written to disk. */
$openRouterKey = env('OPENROUTER_API_KEY');
if ($openRouterKey !== '' && !str_starts_with($openRouterKey, 'your_') && function_exists('curl_init')) {
    $payload = [
        'model' => env('OPENROUTER_MODEL', 'openrouter/free'),
        'temperature' => 0.15,
        /* Free-router models occasionally spend part of the response budget on
           internal reasoning. Leave enough room for the requested JSON report. */
        'max_tokens' => 1100,
        'messages' => [[
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $mime . ';base64,' . $imgData]],
            ],
        ]],
    ];
    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openRouterKey,
            'HTTP-Referer: ' . (defined('APP_URL') ? APP_URL : 'http://localhost'),
            'X-Title: Core Calorie Advisor',
        ],
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $provider = json_decode((string)$raw, true);
    $providerText = trim((string)($provider['choices'][0]['message']['content'] ?? ''));
    $providerResult = tf_scan_json($providerText);
    if ($code >= 200 && $code < 300 && is_array($providerResult)) {
        if ($type === 'food') {
            foreach (['kcal', 'protein', 'carbs', 'fats'] as $metric) {
                if (isset($providerResult[$metric]) && !is_numeric($providerResult[$metric])) $providerResult[$metric] = null;
            }
        } else {
            if (!empty($providerResult['no_body'])) {
                echo json_encode(['ok' => false, 'error' => 'Full body is not clearly visible in the photo — please retake a clear full-body photo in good lighting.']);
                exit;
            }
            $providerResult['weight_kg'] = null;
            $providerResult['body_fat_pct'] = null;
            $providerResult['bmi'] = null;
            $providerResult['muscle_mass_kg'] = null;
            $providerResult['health_flag'] = false;
            $providerResult['health_note'] = null;
            $providerResult['trainer'] = 'CCA coach matching';
            $providerResult['program'] = 'Browse a suitable workout';
            $providerResult['program_id'] = 0;
            if (empty($providerResult['muscle_focus']) || !is_array($providerResult['muscle_focus'])) $providerResult['muscle_focus'] = tf_muscle_focus($goal);
            if (empty($providerResult['next_steps']) || !is_array($providerResult['next_steps'])) $providerResult['next_steps'] = tf_next_steps($goal);
            db()->prepare('INSERT INTO body_scans (user_id, body_type, verdict, health_flag) VALUES (?,?,?,0)')
                ->execute([$_SESSION['user']['id'], mb_substr((string)($providerResult['body_type'] ?? 'Photo-only profile'), 0, 40), mb_substr((string)($providerResult['verdict'] ?? 'Training guidance'), 0, 60)]);
            $providerResult['saved'] = true;
        }
        echo json_encode(['ok' => true, 'demo' => false, 'type' => $type, 'result' => $providerResult]);
        exit;
    }
    $providerMessage = trim((string)($provider['error']['message'] ?? 'The free AI vision service did not return a usable result.'));
    http_response_code($code >= 400 ? $code : 502);
    echo json_encode(['ok' => false, 'error' => $providerMessage]);
    exit;
}

/* Optional paid live vision provider. Configure ANTHROPIC_API_KEY (+ optionally ANTHROPIC_MODEL)
   in .env. Invalid/empty keys safely fall through to the clearly-labelled preview. */
$apiKey = env('ANTHROPIC_API_KEY');
if ($apiKey !== '' && !str_starts_with($apiKey, 'your_') && function_exists('curl_init')) {
    $payload = [
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
        'max_tokens' => 500,
        'messages' => [[
            'role' => 'user',
            'content' => [
                ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $imgData]],
                ['type' => 'text', 'text' => $prompt],
            ],
        ]],
    ];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01'],
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $provider = json_decode((string)$raw, true);
    $providerText = trim((string)($provider['content'][0]['text'] ?? ''));
    $providerResult = tf_scan_json($providerText);
    if ($code >= 200 && $code < 300 && is_array($providerResult)) {
        if ($type === 'food') {
            foreach (['kcal', 'protein', 'carbs', 'fats'] as $metric) {
                if (isset($providerResult[$metric]) && !is_numeric($providerResult[$metric])) $providerResult[$metric] = null;
            }
        } else {
            if (!empty($providerResult['no_body'])) {
                echo json_encode(['ok' => false, 'error' => 'Full body is not clearly visible in the photo — please retake a clear full-body photo in good lighting.']);
                exit;
            }
            $providerResult['weight_kg'] = null;
            $providerResult['body_fat_pct'] = null;
            $providerResult['bmi'] = null;
            $providerResult['muscle_mass_kg'] = null;
            $providerResult['health_flag'] = false;
            $providerResult['health_note'] = null;
            $providerResult['trainer'] = 'CCA coach matching';
            $providerResult['program'] = 'Browse a suitable workout';
            $providerResult['program_id'] = 0;
            if (empty($providerResult['muscle_focus']) || !is_array($providerResult['muscle_focus'])) $providerResult['muscle_focus'] = tf_muscle_focus($goal);
            if (empty($providerResult['next_steps']) || !is_array($providerResult['next_steps'])) $providerResult['next_steps'] = tf_next_steps($goal);
            db()->prepare('INSERT INTO body_scans (user_id, body_type, verdict, health_flag) VALUES (?,?,?,0)')
                ->execute([$_SESSION['user']['id'], mb_substr((string)($providerResult['body_type'] ?? 'Photo-only profile'), 0, 40), mb_substr((string)($providerResult['verdict'] ?? 'Training guidance'), 0, 60)]);
            $providerResult['saved'] = true;
        }
        echo json_encode(['ok' => true, 'demo' => false, 'type' => $type, 'result' => $providerResult]);
        exit;
    }
}

/* Do not turn uploaded photos into a random reference result. A live provider
   must be configured before scans are accepted. */
http_response_code(503);
echo json_encode(['ok' => false, 'error' => 'Live AI scanner is not configured. Add OPENROUTER_API_KEY to .env to enable free vision scans.']);
exit;

/* ---------- Legacy reference preview (intentionally unreachable) ---------- */
if ($type === 'food') {
    $food = db()->query('SELECT * FROM foods ORDER BY RAND() LIMIT 1')->fetch();
    if (!$food) { http_response_code(500); echo json_encode(['ok' => false, 'error' => 'Food reference data is not installed.']); exit; }
    $protein = (float)$food['protein'];
    $carbs   = (float)$food['carbs'];
    $fats    = (float)$food['fats'];
    $verdict = $protein >= 20 ? 'High protein — excellent for muscle gain ✔'
             : ($carbs >= 25 ? 'Carb-rich — great pre-workout fuel ⚡'
             : 'Balanced macros — solid everyday choice 👍');
    $result = [
        'name'    => $food['name'],
        'image'   => $food['image'],
        'serving' => $food['serving'],
        'kcal'    => (int)$food['kcal'],
        'protein' => $protein,
        'carbs'   => $carbs,
        'fats'    => $fats,
        'verdict' => $verdict,
    ];
} else {
    /* No provider key: return an honest GOAL-BASED coaching plan (clearly labelled in
       the UI) — no photo-derived measurements are invented. */
    $goalCategories = $goal === 'cut' ? ['hiit', 'cardio'] : ['strength', 'weights'];
    $goalVerdicts = ['gain' => 'Muscle Gain Plan', 'cut' => 'Fat Loss Plan', 'recomp' => 'Recomposition Plan'];
    $goalAdvice = [
        'gain'   => 'Your primary goal is muscle gain — prioritize progressive overload across compound movements (bench, rows, squats) with high protein intake.',
        'cut'    => 'Your primary goal is fat loss — combine high-intensity circuits with a moderate calorie deficit while keeping protein high to protect lean mass.',
        'recomp' => 'Your goal is body recomposition — lift heavy with progressive overload at maintenance calories to steadily build muscle and reduce body fat.',
    ];
    $progSt = db()->prepare('SELECT id, name FROM workouts WHERE category IN (?, ?) ORDER BY RAND() LIMIT 1');
    $progSt->execute($goalCategories);
    $program = $progSt->fetch() ?: db()->query('SELECT id, name FROM workouts ORDER BY RAND() LIMIT 1')->fetch();
    $trainer = db()->query("SELECT u.name, tp.photo FROM trainer_profiles tp JOIN users u ON u.id = tp.user_id WHERE u.role = 'trainer' ORDER BY RAND() LIMIT 1")->fetch();
    db()->prepare('INSERT INTO body_scans (user_id, body_type, verdict, health_flag) VALUES (?,?,?,0)')
        ->execute([$_SESSION['user']['id'], 'Goal: ' . $goal, mb_substr($goalVerdicts[$goal], 0, 60)]);
    echo json_encode(['ok' => true, 'demo' => true, 'type' => 'body', 'result' => [
        'body_type' => 'Photo-only preview', 'weight_kg' => null, 'body_fat_pct' => null,
        'bmi' => null, 'muscle_mass_kg' => null, 'verdict' => $goalVerdicts[$goal],
        'advice' => $goalAdvice[$goal],
        'muscle_focus' => tf_muscle_focus($goal), 'next_steps' => tf_next_steps($goal),
        'health_flag' => false, 'health_note' => null, 'doctor' => null, 'doctor_url' => null,
        'trainer' => $trainer['name'] ?? 'CCA coach matching', 'trainer_photo' => $trainer['photo'] ?? null,
        'program' => $program['name'] ?? 'Browse workouts', 'program_id' => (int)($program['id'] ?? 0), 'saved' => true,
    ]]);
    exit;
}

echo json_encode(['ok' => true, 'demo' => true, 'type' => $type, 'result' => $result]);
