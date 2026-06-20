<?php

namespace App\Services;

class CaptchaService
{
    /**
     * Generate a new math puzzle, store the answer in session, and return the inline SVG.
     */
    public static function generateCaptcha(): string
    {
        $num1 = random_int(1, 10);
        $num2 = random_int(1, 10);
        
        // Randomly pick addition or subtraction
        $op = random_int(0, 1) === 1 ? '+' : '-';
        if ($op === '-' && $num1 < $num2) {
            // Swap so we don't have negative numbers
            $temp = $num1;
            $num1 = $num2;
            $num2 = $temp;
        }

        $result = $op === '+' ? ($num1 + $num2) : ($num1 - $num2);
        
        session(['captcha_result' => $result]);

        $questionText = "{$num1} {$op} {$num2} = ?";

        // Generate distorted SVG to display to the user (anti-OCR protection)
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="130" height="42" style="border: 1px solid var(--glass-border); border-radius: 8px; vertical-align: middle;">';
        $svg .= '<rect width="100%" height="100%" fill="var(--glass-bg)" />';
        
        // Background noise lines
        for ($i = 0; $i < 6; $i++) {
            $x1 = random_int(0, 130);
            $y1 = random_int(0, 42);
            $x2 = random_int(0, 130);
            $y2 = random_int(0, 42);
            $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="var(--primary)" stroke-opacity="0.15" stroke-width="1.5"/>';
        }

        // Output character-by-character with slight rotations and offsets for noise
        $chars = str_split($questionText);
        $xOffset = 15;
        foreach ($chars as $char) {
            $yOffset = random_int(24, 30);
            $rotate = random_int(-15, 15);
            $svg .= '<text x="' . $xOffset . '" y="' . $yOffset . '" font-family="Courier New, Courier, monospace" font-size="18" font-weight="bold" fill="var(--primary)" transform="rotate(' . $rotate . ' ' . $xOffset . ' ' . $yOffset . ')">' . htmlspecialchars($char) . '</text>';
            $xOffset += 14;
        }

        $svg .= '</svg>';

        return $svg;
    }
}
