<?php

namespace Baha2Odeh\RecaptchaV3;

use Yii;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\validators\Validator;

/**
 * RecaptchaV3Validator validates a reCAPTCHA v3 token stored in a model attribute.
 *
 * Usage in model rules:
 * ```php
 * public $captchaToken;
 *
 * public function rules()
 * {
 *     return [
 *         [['captchaToken'], RecaptchaV3Validator::class, 'acceptance_score' => 0.5],
 *     ];
 * }
 * ```
 *
 * Note: `$acceptance_score` uses snake_case to preserve backwards compatibility
 * with existing model rules from before PSR conventions were adopted.
 *
 * @author Bahaa Odeh <bw4@hotmail.it>
 * @since 1.0
 */
class RecaptchaV3Validator extends Validator
{
    /**
     * @var bool reCAPTCHA tokens must always be present; empty values are invalid.
     */
    public $skipOnEmpty = false;

    /**
     * @var string|array|RecaptchaV3 The component ID or configuration array for the [[RecaptchaV3]] component.
     */
    public $component = 'recaptchaV3';

    /**
     * @var float|null Minimum acceptable score (0.0–1.0). When `null`, any passing score is accepted.
     */
    public $acceptance_score;

    /**
     * @var string|null Expected reCAPTCHA action name. When set, the action returned by Google
     *   must match this value or validation will fail.
     * @since 1.4
     */
    public $action;

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

        if ($this->message === null) {
            $this->message = Yii::t('recaptchav3', 'The verification code is incorrect.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value)
    {
        $score = $this->recaptcha->validateValue($value, $this->action);

        if ($score === false) {
            return [$this->message, []];
        }

        if ($this->acceptance_score !== null && $score < $this->acceptance_score) {
            return [$this->message, []];
        }

        return null;
    }
}
