<?php

namespace AronLabs\Captcha;

use Illuminate\Session\Store as Session;
use GDImage;

class Captcha
{
    protected Session $session;
    protected array $config;

    public function __construct(Session $session, array $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * The main method for generating the code and image, and returning the Base64 representation.
     */
    public function generateBase64Image(): string
    {
        [$code, $display] = $this->generateCode();

        $this->session->put('aron_captcha_code', $code);

        return $this->createImage($display);
    }

    /**
     * Generates the CAPTCHA code (text or mathematical).
     */
    protected function generateCode(): array
    {
        $type = $this->config['type'] ?? 'text';

        if ($type === 'math') {
            return $this->generateMathCode();
        }

        return $this->generateTextCode();
    }

    /**
     * Generates the code for 'text' mode.
     */
    protected function generateTextCode(): array
    {
        $chars = $this->getCharset();
        $length = $this->config['length'];
        $code = '';

        $charsLength = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $charsLength - 1)];
        }

        return [$code, $code];
    }

    /**
     * Logic for selecting characters based on 'text_mode'.
     */
    protected function getCharset(): string
    {
        $mode = $this->config['text_mode'];

        $numbers = '23456789';
        $lower = 'abcdefghjkmnpqrstuvwxyz';
        $upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';

        return match ($mode) {
            'numbers' => $numbers,
            'letters' => str_replace(['o', 'i', 'l'], '', $lower . $upper),
            'upper' => str_replace(['O', 'I'], '', $upper),
            'lower' => str_replace(['o', 'i', 'l'], '', $lower),
            default => $numbers . $lower . $upper,
        };
    }

    /**
     * Generates code for 'math' mode.
     */
    protected function generateMathCode(): array
    {
        $max = $this->config['max_operand'];
        $op1 = random_int(1, $max);
        $op2 = random_int(1, $max);
        $operator = ['+', '-', '*'][random_int(0, 2)];

        if ($operator === '-') {
            // Prevent negative results
            if ($op1 < $op2) {
                [$op1, $op2] = [$op2, $op1];
            }
        } elseif ($operator === '*') {
            // Keep operands small for multiplication
            $op1 = random_int(1, $max > 5 ? 5 : $max);
            $op2 = random_int(1, $max > 5 ? 5 : $max);
        }

        $expression = "$op1 $operator $op2";

        // Calculate result safely without using eval()
        $code = match ($operator) {
            '+' => $op1 + $op2,
            '-' => $op1 - $op2,
            '*' => $op1 * $op2,
            default => 0,
        };

        return [(string)$code, $expression . " = ?"];
    }

    /**
     * Generates the image using GD library.
     */
    protected function createImage(string $text): string
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('GD extension is required for Aron Captcha.');
        }

        $width = $this->config['width'];
        $height = $this->config['height'];
        $fontSize = $this->config['font_size'];

        $fontPath = $this->config['font'];
        if (!file_exists($fontPath)) {
            // Fallback to default font if configured path does not exist
            $fontPath = __DIR__ . '/../resources/fonts/default.ttf';
        }

        $image = imagecreate($width, $height);

        // Define colors
        $bgColor = imagecolorallocate($image, 255, 255, 255); // White
        $textColor = imagecolorallocate($image, 0, 0, 0);     // Black

        // Add noise
        $this->addNoise($image, $width, $height);

        // -----------------------------------------------------------
        // Dynamic Font Scaling & Centering Logic
        // -----------------------------------------------------------

        $angle = random_int(-3, 3);
        $padding = 10; // Safety padding in pixels

        // Decrease font size until the text fits within the image width
        do {
            $bbox = imagettfbbox($fontSize, $angle, $fontPath, $text);

            // Calculate actual text dimensions
            // Index 0,1: Lower left | Index 4,5: Upper right
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);

            if ($textWidth > ($width - $padding * 2) || $textHeight > ($height - $padding)) {
                $fontSize--;
            } else {
                break;
            }
        } while ($fontSize > 8); // Minimum readable font size

        // Calculate X and Y coordinates to center the text
        $x = ($width - $textWidth) / 2;

        // Vertical centering: (Height / 2) - (Vertical Center of Text Bounding Box)
        $y = ($height / 2) - (($bbox[7] + $bbox[1]) / 2);

        // Draw the text
        imagettftext($image, $fontSize, $angle, (int)$x, (int)$y, $textColor, $fontPath, $text);

        // -----------------------------------------------------------

        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($contents);
    }

    /**
     * Adds noise (lines and dots) to the image.
     */
    protected function addNoise(GDImage $image, int $width, int $height): void
    {
        // Add random lines
        for ($i = 0; $i < $this->config['lines']; $i++) {
            $lineColor = imagecolorallocate($image, random_int(100, 200), random_int(100, 200), random_int(100, 200));
            imageline($image,
                random_int(0, $width), random_int(0, $height),
                random_int(0, $width), random_int(0, $height),
                $lineColor
            );
        }

        // Add random dots
        for ($i = 0; $i < $this->config['dots']; $i++) {
            $dotColor = imagecolorallocate($image, random_int(100, 200), random_int(100, 200), random_int(100, 200));
            imagesetpixel($image, random_int(0, $width), random_int(0, $height), $dotColor);
        }
    }
}