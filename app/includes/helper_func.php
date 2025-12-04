<?php
function getInitials($full_name) {
    // Regular expression to match the first character of each word boundary
    preg_match_all('/\b\w/', $full_name, $matches);

    // $matches[0] will contain an array of the first letters (e.g., ['j', 'd', 's'])
    $initials = implode('', $matches[0]);

    // Convert the resulting initials string to uppercase
    return strtoupper($initials);
}
?>