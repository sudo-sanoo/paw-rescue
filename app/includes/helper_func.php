<?php
function getInitials($full_name) {
    // Regular expression to match the first character of each word boundary
    preg_match_all('/\b\w/', $full_name, $matches);

    // $matches[0] will contain an array of the first letters (e.g., ['j', 'd', 's'])
    $initials = implode('', $matches[0]);

    // Convert the resulting initials string to uppercase
    return strtoupper($initials);
}

// --- HELPER: Time Ago ---
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculate weeks manually
    // $diff->d is the days remaining after months are calculated.
    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    // Map the values to variables we can use
    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'min',
        's' => 'sec',
    );

    // Create a mapping of the actual values
    $values = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s
    ];

    foreach ($string as $k => &$v) {
        if ($values[$k]) {
            $v = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) : 'just now';
}
?>