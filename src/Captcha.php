<?php

namespace Aron\Captcha;

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
    The main method for generating the code and image, and returning the Base64 representation.
     */
    public function generateBase64Image(): string
    {

        [$code, $display] = $this->generateCode();


        $this->session->put('aron_captcha_code', $code);


        return $this->createImage($display);
    }

    /**
     * Generates the CAPTCHA code (text or mathematical).
     * @return array [string $code, string $display]
     */
    protected function generateCode(): array
    {
        $type = $this->config['type'];

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

        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
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
     * تولید کد برای حالت 'math'
     */
    protected function generateMathCode(): array
    {
        $max = $this->config['max_operand'];
        $op1 = random_int(1, $max);
        $op2 = random_int(1, $max);
        $operator = ['+', '-', '*'][random_int(0, 2)];

        if ($operator === '-') {
            // جلوگیری از نتیجه منفی
            if ($op1 < $op2) {
                [$op1, $op2] = [$op2, $op1];
            }
        } elseif ($operator === '*') {
            // کوچک کردن اپراندها در ضرب برای جلوگیری از اعداد بزرگ
            $op1 = random_int(1, $max > 5 ? 5 : $max);
            $op2 = random_int(1, $max > 5 ? 5 : $max);
        }

        $expression = "$op1 $operator $op2";
        // استفاده از eval برای محاسبه (احتیاط لازم است، اما اینجا امن است)
        $code = eval("return $expression;");

        return [(string)$code, $expression . " = ?"];
    }


    /**
     * تولید تصویر با استفاده از GD
     */
    protected function createImage(string $text): string
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('GD extension is required for Aron Captcha.');
        }

        $width = $this->config['width'];
        $height = $this->config['height'];
        $fontSize = $this->config['font_size'];

        // مسیر فونت: قوی سازی مسیر با realpath
        $fontPath = $this->config['font'];
        if (!file_exists($fontPath)) {
            // اگر کاربر کانفیگ را publish کرده اما فونت را جابجا نکرده، از مسیر اصلی پکیج استفاده کند
            $fontPath = realpath(__DIR__ . '/../resources/fonts/default.ttf');
        }

        $image = imagecreate($width, $height);

        // تعیین رنگ‌ها
        $bgColor = imagecolorallocate($image, 255, 255, 255); // سفید
        $textColor = imagecolorallocate($image, 0, 0, 0); // مشکی

        // افزودن نویز
        $this->addNoise($image, $width, $height);

        // افزودن متن
        // محاسبه موقعیت برای مرکزیت
        $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
        $x = $width / 2 - ($bbox[2] - $bbox[0]) / 2;
        $y = $height / 2 - ($bbox[7] - $bbox[1]) / 2;

        // Draw text with a slight rotation randomness (optional)
        $angle = random_int(-3, 3);
        imagettftext($image, $fontSize, $angle, (int)$x, (int)$y + ($fontSize / 2), $textColor, $fontPath, $text);

        // خروجی تصویر به صورت Base64
        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($contents);
    }

    /**
     * افزودن خطوط و نقاط نویز به تصویر
     */
    protected function addNoise(GDImage $image, int $width, int $height): void
    {
        // خطوط درهم برهم
        for ($i = 0; $i < $this->config['lines']; $i++) {
            $lineColor = imagecolorallocate($image, random_int(100, 200), random_int(100, 200), random_int(100, 200));
            imageline($image,
                random_int(0, $width), random_int(0, $height),
                random_int(0, $width), random_int(0, $height),
                $lineColor
            );
        }

        // نقاط نویز
        for ($i = 0; $i < $this->config['dots']; $i++) {
            $dotColor = imagecolorallocate($image, random_int(100, 200), random_int(100, 200), random_int(100, 200));
            imagesetpixel($image, random_int(0, $width), random_int(0, $height), $dotColor);
        }
    }
}
