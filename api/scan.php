<?php
/* ============================================================
   AI SCAN ENDPOINT — Google-Lens-style Body / Food scanner.
   Configure ANTHROPIC_API_KEY (and optionally ANTHROPIC_MODEL) in .env
   for live vision analysis. Without it the UI is clearly labelled as a
   reference preview; it does not invent body or medical measurements.
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
        ['muscle' => 'Chest',     'priority' => 'High',   'action' => 'Push-ups / bench press — 3-4 sets, progressive overload har hafta'],
        ['muscle' => 'Back',      'priority' => 'High',   'action' => 'Rows aur pull-ups — width ke liye lats par focus karein'],
        ['muscle' => 'Legs',      'priority' => 'High',   'action' => 'Squats + lunges — sab se zyada mass yahan banta hai'],
        ['muscle' => 'Shoulders', 'priority' => 'Medium', 'action' => 'Overhead press — 3D delts ke liye lateral raises add karein'],
        ['muscle' => 'Arms',      'priority' => 'Medium', 'action' => 'Curls + dips — hafte mein 2 din kaafi hain'],
        ['muscle' => 'Core',      'priority' => 'Medium', 'action' => 'Planks + leg raises — heavy lifts ke liye base'],
    ];
    if ($goal === 'cut') return [
        ['muscle' => 'Full Body', 'priority' => 'High',   'action' => 'HIIT circuits — burpees, mountain climbers, jumping jacks'],
        ['muscle' => 'Core',      'priority' => 'High',   'action' => 'Planks, crunches, Russian twists — definition ke liye'],
        ['muscle' => 'Legs',      'priority' => 'High',   'action' => 'Squat jumps + high knees — sab se bara calorie burn'],
        ['muscle' => 'Chest',     'priority' => 'Medium', 'action' => 'Push-up variations — muscle retain karne ke liye'],
        ['muscle' => 'Back',      'priority' => 'Medium', 'action' => 'Rows — posture aur strength maintain karein'],
    ];
    return [
        ['muscle' => 'Chest',     'priority' => 'High',   'action' => 'Push-ups — controlled tempo, 3 sets to near-failure'],
        ['muscle' => 'Back',      'priority' => 'High',   'action' => 'Rows / pull-ups — pushing volume ke barabar rakhen'],
        ['muscle' => 'Legs',      'priority' => 'High',   'action' => 'Squats — hafte mein 2 din, form pehle weight baad'],
        ['muscle' => 'Core',      'priority' => 'Medium', 'action' => 'Planks + twists — har workout ke aakhir mein'],
        ['muscle' => 'Shoulders', 'priority' => 'Low',    'action' => 'Press — compound lifts ke sath cover ho jate hain'],
    ];
}
function tf_next_steps(string $goal): array {
    if ($goal === 'gain') return [
        'Protein target: 1.6-2.2g per kg bodyweight rozana',
        'Calorie surplus +300-500 kcal — clean bulk',
        'Har hafta weight ya reps barhao (progressive overload)',
        '7-9 ghante neend — muscle yahin banta hai',
    ];
    if ($goal === 'cut') return [
        'Moderate calorie deficit −400-500 kcal — crash diet nahi',
        'Protein high rakhein taake muscle na toote',
        'Hafte mein 3-4 HIIT sessions + 8-10k steps rozana',
        'AI Food Scanner se meals track karein',
    ];
    return [
        'Calories maintenance par, protein 1.8g/kg',
        'Heavy compound lifts — strength up, fat slowly down',
        'Hafte mein 3-4 strength + 1-2 cardio sessions',
        'Har 2 hafte baad progress photo se compare karein',
    ];
}

$imgData = base64_encode((string)file_get_contents($_FILES['photo']['tmp_name']));
$mime    = mime_content_type($_FILES['photo']['tmp_name']);

/* Privacy by default: raw scan photos are sent to the configured provider in-memory
   and are not retained on this server. Only the user-facing scan summary is saved. */
$prompt = $type === 'food'
  ? 'Analyze the food photo. Return ONLY JSON with: name, serving, kcal, protein, carbs, fats, verdict. Values are estimates for the visible serving. If the meal is unclear, say so in verdict and use null for uncertain numbers.'
  : 'Analyze only visible fitness presentation in this body photo. The user\'s training goal is: ' . $goal . ' (gain = build muscle, cut = lose fat, recomp = recomposition). Return ONLY JSON with: body_type, verdict, advice, training_focus, muscle_focus (array of {muscle, priority: High|Medium|Low, action} for which visible muscle groups to develop toward the goal), next_steps (array of 3-4 short actionable strings). Never infer or invent weight, BMI, body-fat percentage, muscle mass, a medical condition, age, gender, or a diagnosis from an image. State uncertainty clearly. If no human body is clearly visible in the photo, return {"no_body": true} only.';

/* Live vision provider. Configure ANTHROPIC_API_KEY (+ optionally ANTHROPIC_MODEL)
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
    $providerText = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $providerText) ?? '';
    $providerResult = json_decode($providerText, true);
    if ($code >= 200 && $code < 300 && is_array($providerResult)) {
        if ($type === 'food') {
            foreach (['kcal', 'protein', 'carbs', 'fats'] as $metric) {
                if (isset($providerResult[$metric]) && !is_numeric($providerResult[$metric])) $providerResult[$metric] = null;
            }
        } else {
            if (!empty($providerResult['no_body'])) {
                echo json_encode(['ok' => false, 'error' => 'Photo mein poora body clearly nazar nahi aa raha — full-body photo, acchi lighting mein dobara try karein.']);
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

/* ---------- REFERENCE PREVIEW (clearly labelled in the UI) ---------- */
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
    $goalCat = $goal === 'cut' ? "('hiit','cardio')" : "('strength','weights')";
    $goalVerdicts = ['gain' => 'Muscle Gain Plan', 'cut' => 'Fat Loss Plan', 'recomp' => 'Recomposition Plan'];
    $goalAdvice = [
        'gain'   => 'Aap ka goal muscle gain hai — neeche diye gaye muscle groups par progressive overload ke sath kaam karein. Compound lifts pehle, isolation baad mein.',
        'cut'    => 'Aap ka goal fat loss hai — HIIT circuits aur calorie deficit ka combo sab se tez kaam karta hai. Protein high rakhein taake muscle safe rahe.',
        'recomp' => 'Recomposition ke liye maintenance calories par heavy lifting karein — dheere dheere fat ghatta hai aur muscle barhta hai.',
    ];
    $program = db()->query("SELECT id, name FROM workouts WHERE category IN $goalCat ORDER BY RAND() LIMIT 1")->fetch()
             ?: db()->query('SELECT id, name FROM workouts ORDER BY RAND() LIMIT 1')->fetch();
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
