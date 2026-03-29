<?php header('Content-Type: text/plain; charset=utf-8');
$token = 'WPISZ_TUTAJ_TRUDNY_TOKEN';
if (!isset($_GET['token']) || $_GET['token'] !== $token) {
    http_response_code(403);
    exit("Brak dostepu\n");
}
require __DIR__ . '/wp-load.php';
global $wpdb;
$total = (int) $wpdb->get_var(" SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status NOT IN ('trash','auto-draft','inherit') ");
$rows = $wpdb->get_results(" SELECT LOWER(TRIM(post_title)) AS normalized_title, MIN(post_title) AS sample_title, COUNT(*) AS cnt, GROUP_CONCAT(ID ORDER BY ID SEPARATOR ',') AS ids, GROUP_CONCAT(DISTINCT post_status ORDER BY post_status SEPARATOR ',') AS statuses FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status NOT IN ('trash','auto-draft','inherit') GROUP BY normalized_title HAVING cnt > 1 ORDER BY cnt DESC, normalized_title ", ARRAY_A);
echo "TOTAL_PRODUCTS=" . $total . "\n";
echo "DUPLICATE_GROUPS=" . count($rows) . "\n\n";
foreach ($rows as $r) {
    echo $r['cnt'] . "x | " . $r['sample_title'] . " | statusy: " . $r['statuses'] . " | ID: " . $r['ids'] . "\n";
}
