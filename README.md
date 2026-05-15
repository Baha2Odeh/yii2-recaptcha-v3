Yii2 reCAPTCHA v3
=================

Google reCAPTCHA v3 integration for Yii2 — provides an application component, a model validator,
and a form widget. Supports both standard reCAPTCHA v3 and reCAPTCHA Enterprise.

Installation
------------

Install via [Composer](https://getcomposer.org/):

```
composer require baha2odeh/yii2-recaptcha-v3
```

Or add to the `require` section of your `composer.json`:

```json
"baha2odeh/yii2-recaptcha-v3": "*"
```

Configuration
-------------

Register the component in your application configuration (`config/main.php`):

```php
'components' => [
    'recaptchaV3' => [
        'class' => 'Baha2Odeh\RecaptchaV3\RecaptchaV3',
        'site_key'   => 'YOUR_SITE_KEY',
        'secret_key' => 'YOUR_SECRET_KEY',
        // 'verify_ssl' => true,  // default; set false only in development
    ],
],
```

Usage
-----

### Model

Add the validator to your model's `rules()`. The optional `acceptance_score` sets the minimum
score (0.0–1.0) required to pass; `null` accepts any score returned by Google.

```php
use Baha2Odeh\RecaptchaV3\RecaptchaV3Validator;

class ContactForm extends \yii\base\Model
{
    public $captchaToken;

    public function rules()
    {
        return [
            [['captchaToken'], RecaptchaV3Validator::class, 'acceptance_score' => 0.5],
        ];
    }
}
```

### View / Form

```php
use Baha2Odeh\RecaptchaV3\RecaptchaV3Widget;

<?= $form->field($model, 'captchaToken')->widget(RecaptchaV3Widget::class, [
    'actionName' => 'contact',  // matches the action used in the validator
]) ?>
```

The widget registers the Google reCAPTCHA script, executes the challenge, and stores the token
in a hidden field. On form submit the token is refreshed if it has been cleared.

Advanced Options
----------------

### Action Verification (recommended)

Pass an `action` to the validator to verify the action name returned by Google matches the
expected value. This prevents token reuse across different forms.

```php
[['captchaToken'], RecaptchaV3Validator::class, 'acceptance_score' => 0.5, 'action' => 'contact'],
```

Use the same action name in the widget:

```php
<?= $form->field($model, 'captchaToken')->widget(RecaptchaV3Widget::class, [
    'actionName' => 'contact',
]) ?>
```

### reCAPTCHA Enterprise

To use [reCAPTCHA Enterprise](https://cloud.google.com/recaptcha-enterprise/docs), set
`enterprise` to `true` and provide your Google Cloud `project_id`:

```php
'recaptchaV3' => [
    'class'      => 'Baha2Odeh\RecaptchaV3\RecaptchaV3',
    'site_key'   => 'YOUR_SITE_KEY',
    'secret_key' => 'YOUR_API_KEY',
    'enterprise' => true,
    'project_id' => 'YOUR_GCP_PROJECT_ID',
],
```

The widget and validator work identically with Enterprise; no other code changes are required.

Properties Reference
--------------------

### RecaptchaV3 component

| Property     | Type     | Default | Description |
|--------------|----------|---------|-------------|
| `site_key`   | string   | —       | reCAPTCHA site key (**required**) |
| `secret_key` | string   | —       | reCAPTCHA secret key (**required**) |
| `verify_ssl` | bool     | `true`  | Verify SSL certificate on API calls |
| `enterprise` | bool     | `false` | Use reCAPTCHA Enterprise |
| `project_id` | string   | `null`  | GCP project ID (required when `enterprise` is `true`) |

### RecaptchaV3Validator

| Property           | Type         | Default        | Description |
|--------------------|--------------|----------------|-------------|
| `component`        | string/array | `'recaptchaV3'`| Component ID or config |
| `acceptance_score` | float\|null  | `null`         | Minimum passing score |
| `action`           | string\|null | `null`         | Expected action name |

### RecaptchaV3Widget

| Property     | Type   | Default      | Description |
|--------------|--------|--------------|-------------|
| `component`  | string | `'recaptchaV3'` | Component ID |
| `actionName` | string | `'homepage'` | reCAPTCHA action name |
