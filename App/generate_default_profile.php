<?php
// Create a 150x150 image
$image = imagecreatetruecolor(150, 150);

// Set background color (light gray)
$bgColor = imagecolorallocate($image, 240, 240, 240);
imagefill($image, 0, 0, $bgColor);

// Set text color (dark gray)
$textColor = imagecolorallocate($image, 100, 100, 100);

// Add text
$text = "?";
$fontSize = 5;
$font = 5; // Built-in font
$textWidth = imagefontwidth($font) * strlen($text);
$textHeight = imagefontheight($font);
$x = (150 - $textWidth) / 2;
$y = (150 - $textHeight) / 2;
imagestring($image, $font, $x, $y, $text, $textColor);

// Save the image
imagepng($image, 'assets/images/default-profile.png');

// Free up memory
imagedestroy($image);

echo "Default profile picture generated successfully!";
?> 