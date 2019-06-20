Yii2 reCAPTCHA v3
=================
Yii2 reCAPTCHA v3

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist baha2odeh/yii2-recaptcha-v3 "*"
```

or add

```
"baha2odeh/yii2-recaptcha-v3": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

add this to your components main.php

```php
'components' => [
        ...
        'recaptchaV3' => [
            'class' => 'Baha2Odeh\RecaptchaV3\RecaptchaV3',
            'site_key' => '###',
            'secret_key' => '###',
            'verify_ssl' => true, // default is true
        ],

```

and in your model

acceptance_score the minimum score for this request (0.0 - 1.0) or null

```php
public $code;
 
 public function rules(){
 	return [
 		...
 		 [['code'], RecaptchaV3Validator::className(), 'acceptance_score' => null]
 	];
    }
```

```php
   <?= $form->field($model,'code')->widget(\Baha2Odeh\RecaptchaV3\RecaptchaV3Widget::className()); ?>
```


