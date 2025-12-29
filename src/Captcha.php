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
    /**
     * Generates the image using GD library.
     */
    /**
     * Generates the image using GD library (TrueColor for better quality).
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
            $fontPath = __DIR__ . '/../resources/fonts/default.ttf';
        }

        // 1. Use TrueColor for better quality
        $image = imagecreatetruecolor($width, $height);

        // 2. Set white background
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        $textColor = imagecolorallocate($image, 0, 0, 0);

        $this->addNoise($image, $width, $height);

        // -----------------------------------------------------------
        //  Final Fix for Centering (Bounding Box Average Method)
        // -----------------------------------------------------------

        $angle = random_int(-3, 3);
        $padding = 10;

        // A. Resize font if text is too big
        do {
            $bbox = imagettfbbox($fontSize, $angle, $fontPath, $text);

            // 0: lower-left X, 4: upper-right X
            $textWidth = abs($bbox[4] - $bbox[0]);
            // 1: lower-left Y, 5: upper-right Y
            $textHeight = abs($bbox[5] - $bbox[1]);

            if ($textWidth > ($width - $padding * 2) || $textHeight > ($height - $padding)) {
                $fontSize--;
            } else {
                break;
            }
        } while ($fontSize > 10);

        // B. Calculate X (Horizontal Center)
        // Formula: (ImageWidth - TextWidth) / 2
        $x = ($width - $textWidth) / 2;

        // C. Calculate Y (Vertical Center)
        // Formula: (ImageHeight / 2) - (TextCenterOffset)
        // TextCenterOffset = (TopY + BottomY) / 2
        // Note: bbox[7] is Top-Left Y, bbox[1] is Bottom-Left Y
        $textCenterY = ($bbox[7] + $bbox[1]) / 2;
        $y = ($height / 2) - $textCenterY;

        // *** MANUAL CORRECTION ***
        // اگر هنوز متن پایین است، عدد زیر را منفی کنید (مثلاً -5 یا -10)
        // اگر متن بالا است، عدد را مثبت کنید
        $yCorrection = 0;
        $y += $yCorrection;

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