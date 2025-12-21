# 🛡️ Aron Captcha
### Secure & Customizable CAPTCHA for Laravel

**Aron Captcha** is a lightweight, secure, and fully customizable CAPTCHA package for Laravel, supporting both **text-based** and **math-based** challenges with Ajax refresh and clean Blade integration.

---

## 👤 Author & Maintainer

**Aron Captcha** is developed and maintained by **AronSoft**.

 Website: https://aron-soft.com  
 Author: AronSoft Team

---

##  Table of Contents (English)

1. Requirements
2. Installation
3. Configuration
4. Usage in Blade
5. Validation
6. Customization
7. Ajax Refresh Mechanism
8. Contribution
9. Security
10. License

---

##  Requirements

- PHP >= 8.1
- Laravel >= 10
- PHP GD extension enabled

---

##  Installation

```bash
composer require aronlabs/captcha
```

Laravel will automatically discover the service provider.

---

##  Configuration

```bash
php artisan vendor:publish --tag=aronlabs-captcha-config
```

This will publish the configuration file to:

```text
config/aron-captcha.php
```

---

##  Usage in Blade

```blade
<form method="POST">
    @csrf

    @include('aronlabs-captcha::captcha-input')

    <button type="submit">Submit</button>
</form>
```

---

##  Validation

```php
use AronLabs\Captcha\Rules\CaptchaRule;

$request->validate([
    'captcha' => ['required', new CaptchaRule],
]);
```

---

##  Customization

### Publish Views
```bash
php artisan vendor:publish --tag=aronlabs-captcha-views
```

### Publish Fonts
```bash
php artisan vendor:publish --tag=aronlabs-captcha-fonts
```

---

##  Ajax Refresh Mechanism

Make sure your main layout contains:

```blade
@yield('scripts')
```

The CAPTCHA view automatically injects required JavaScript.

---

##  Contribution

Contributions are welcome!  
Feel free to submit issues or pull requests via GitHub.

---

##  Security

If you discover a security vulnerability, please report it responsibly:

 Email: security@aron-soft.com  
 Website: https://aron-soft.com

---

##  License

This package is open-sourced software licensed under the **MIT License**.
