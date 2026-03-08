<?php
/**
 * Simple Favicon Generator
 * Generates a 32x32 ICO file from SVG using ImageMagick
 */

$svgContent = file_get_contents('favicon.svg');

// For browsers that don't support SVG favicons, use ImageMagick if available
if (extension_loaded('imagick')) {
    $svg = new Imagick();
    $svg->readImageBlob($svgContent);
    $svg->setImageFormat('png');
    $svg->resizeImage(32, 32, Imagick::FILTER_LANCZOS, 1);
    
    // Save as ICO
    $svg->setImageFormat('ico');
    $svg->writeImage('favicon.ico');
    echo "favicon.ico generated successfully!\n";
} else {
    echo "ImageMagick not available. Using SVG favicon instead.\n";
    echo "For .ico file, install ImageMagick PHP extension or use an online converter.\n";
}
?>
