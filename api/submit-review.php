<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST required', 405);
}

csrf_verify_json();

$trainerId = (int)post('trainer_id');
$rating    = (int)post('rating');
$comment   = trim(post('comment'));
$uid       = (int)$_SESSION['user']['id'];

if ($trainerId <= 0 || $rating < 1 || $rating > 5) {
    json_error('Invalid review details (provider ID or rating scale 1-5).');
}

try {
    // Check if review already exists
    $st = db()->prepare("SELECT id FROM reviews WHERE user_id = ? AND trainer_id = ?");
    $st->execute([$uid, $trainerId]);
    if ($st->fetch()) {
        json_error('You have already submitted a review for this provider.');
    }

    // Insert review with pending status for admin moderation if configured
    $ins = db()->prepare("INSERT INTO reviews (user_id, trainer_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')");
    $ins->execute([$uid, $trainerId, $rating, $comment]);

    // Recalculate average rating on provider profile
    $avgSt = db()->prepare("SELECT AVG(rating) r FROM reviews WHERE trainer_id = ? AND status = 'approved'");
    $avgSt->execute([$trainerId]);
    $avg = (float)$avgSt->fetchColumn();

    if ($avg > 0) {
        db()->prepare("UPDATE trainer_profiles SET rating = ? WHERE user_id = ?")->execute([$avg, $trainerId]);
    }

    notify($trainerId, 'New Review Received', "A new $rating-star review was submitted for your profile.", 'system', BASE_URL . '/trainer/reviews-ratings.php');

    json_response(['success' => true, 'message' => 'Thank you! Your review has been submitted for moderation.']);
} catch (Throwable $e) {
    json_error('Failed to submit review: ' . $e->getMessage());
}
