<?php
namespace AronLabs\Captcha;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class CaptchaController extends Controller
{
    protected $captcha;

    public function __construct()
    {
        $this->captcha = App::make('aron-captcha');
    }

    public function refresh(): JsonResponse
    {
        $image = $this->captcha->generateBase64Image();

        return new JsonResponse(['image' => $image]);
    }
}
