<?php
/**
 * Avatar Color Utility
 * Generates deterministic, high-contrast colors for user avatars
 */

/**
 * Generate a deterministic color for a user based on their name
 * Returns a hex color code that has high contrast with dark text
 * 
 * @param string $name User name
 * @return string Hex color code (e.g., "#FF6B6B")
 */
function getUserAvatarColor($name) {
    // Color palette: vibrant, distinct colors with good contrast on dark backgrounds
    $colors = [
        '#FF6B6B', // Red
        '#4ECDC4', // Teal
        '#45B7D1', // Blue
        '#FFA07A', // Light Salmon
        '#98D8C8', // Mint
        '#F7DC6F', // Yellow
        '#BB8FCE', // Purple
        '#85C1E2', // Sky Blue
        '#F8B88B', // Peach
        '#82E0AA', // Green
        '#F1948A', // Pink
        '#AED6F1', // Light Blue
        '#FAD7A0', // Tan
        '#D7BCCB', // Mauve
        '#A9CCE3', // Periwinkle
        '#F9E79F', // Light Gold
    ];
    
    // Generate a hash from the name to deterministically select a color
    $hash = crc32(strtolower(trim($name)));
    $index = abs($hash) % count($colors);
    
    return $colors[$index];
}

/**
 * Get the text color (white or black) that provides best contrast with the given background
 * 
 * @param string $hexColor Hex color code (with or without #)
 * @return string Either 'white' or 'black'
 */
function getContrastingTextColor($hexColor) {
    // Remove # if present
    $hex = ltrim($hexColor, '#');
    
    // Convert hex to RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Calculate luminance using relative luminance formula
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    // Return white for dark backgrounds, black for light backgrounds
    return $luminance > 0.5 ? '#000000' : '#FFFFFF';
}

/**
 * Generate inline style for avatar with color
 * 
 * @param string $name User name
 * @return string CSS style attribute value
 */
function getAvatarStyle($name) {
    $bgColor = getUserAvatarColor($name);
    $textColor = getContrastingTextColor($bgColor);
    
    return "background-color: {$bgColor}; color: {$textColor};";
}
