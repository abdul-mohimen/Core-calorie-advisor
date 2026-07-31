<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST required', 405);
}

csrf_verify_json();

$reviewId = (int)post('review_id');
$action   = post('mod_action'); // 'approved' or 'rejected'

if ($reviewId <= 0 || !in_array($action, ['approved', 'rejected'], true)) {
    json_error('Invalid review ID or action.');
}

try {
    $st = db()->prepare("SELECT * FROM reviews WHERE id = ?");
    $st->execute([$reviewId]);
    $review = $st->fetch();

    if (!$review) {
        json_error('Review not found.');
    }

    db()->prepare("UPDATE reviews SET status = ? WHERE id = ?")->execute([$action, $reviewId]);

    // Recalculate provider average rating
    $avgSt = db()->prepare("SELECT AVG(rating) r FROM reviews WHERE trainer_id = ? AND status = 'approved'");
    $avgSt->execute([$review['trainer_id']]);
    $avg = (float)($avgSt->fetchColumn() ?: 5.0);

    db()->prepare("UPDATE trainer_profiles SET rating = ? WHERE user_id = ?")->execute([$avg, $review['trainer_id']]);

    json_response(['success' => true, 'message' => "Review #$reviewId marked as $action."]);
} catch (Throwable $e) {
    json_error('Failed to moderate review: ' . $e->getMessage());
}
