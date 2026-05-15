<?php

namespace Baha2Odeh\RecaptchaV3;

use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use yii\widgets\InputWidget;

/**
 * RecaptchaV3Widget renders a hidden reCAPTCHA v3 token input field inside a form.
 *
 * The widget registers the Google reCAPTCHA script, executes the challenge on page load,
 * and stores the resulting token in a hidden input for server-side validation.
 *
 * Usage:
 * ```php
 * <?= $form->field($model, 'captchaToken')->widget(\Baha2Odeh\RecaptchaV3\RecaptchaV3Widget::class) ?>
 * ```
 *
 * @author Bahaa Odeh <bw4@hotmail.it>
 * @since 1.0
 */
class RecaptchaV3Widget extends InputWidget
{
    /**
     * @var string|array|RecaptchaV3 The component ID or configuration array for the [[RecaptchaV3]] component.
     */
    public $component = 'recaptchaV3';

    /**
     * @var string The reCAPTCHA action name associated with this form submission.
     * Use descriptive names such as `login`, `register`, or `contact`.
     */
    public $actionName = 'homepage';

    /**
     * @var string Submit button label.
     * @deprecated This property has no effect and will be removed in a future version.
     */
    public $buttonText = 'Submit';

    /**
     * @var RecaptchaV3 Resolved component instance.
     */
    private $recaptcha;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->recaptcha = Instance::ensure($this->component, RecaptchaV3::class);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->recaptcha->registerScript($this->getView());

        $this->field->template = "{input}\n{error}";

        $formId   = $this->field->form->id;
        $inputId  = Html::getInputId($this->model, $this->attribute);
        $siteKey  = Json::htmlEncode($this->recaptcha->site_key);
        $action   = Json::htmlEncode($this->actionName);
        $inputSel = Json::htmlEncode('#' . $inputId);
        $formSel  = Json::htmlEncode('#' . $formId);

        $this->view->registerJs(<<<JS
(function() {
    var siteKey  = {$siteKey};
    var action   = {$action};
    var inputSel = {$inputSel};
    var formSel  = {$formSel};

    grecaptcha.ready(function() {
        grecaptcha.execute(siteKey, {action: action}).then(function(token) {
            jQuery(inputSel).val(token);
        });
    });

    jQuery(formSel).on('beforeSubmit', function() {
        if (!jQuery(inputSel).val()) {
            grecaptcha.ready(function() {
                grecaptcha.execute(siteKey, {action: action}).then(function(token) {
                    jQuery(inputSel).val(token);
                    jQuery(formSel).submit();
                });
            });
            return false;
        }
        return true;
    });
}());
JS
            , View::POS_READY);

        return Html::activeHiddenInput($this->model, $this->attribute, ['value' => '']);
    }
}
