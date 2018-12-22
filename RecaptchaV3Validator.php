<?php
/**
 * Created by PhpStorm.
 * User: bahaaodeh
 * Date: 12/22/18
 * Time: 7:23 PM
 */

namespace Baha2Odeh\RecaptchaV3;


use Yii;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\validators\Validator;

class RecaptchaV3Validator extends Validator
{
    /**
     * @var bool
     */
    public $skipOnEmpty = false;


    /**
     * Recaptcha component
     * @var string|array|RecaptchaV3
     */
    public $component = 'recaptchaV3';


    /**
     * the minimum score for this request (0.0 - 1.0)
     * @var null|int
     */
    public $acceptance_score = null;

    /**
     * @var RecaptchaV3
     */
    private $_component = null;
    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $component = Instance::ensure($this->component, RecaptchaV3::class);
        if ($component == null) {
            throw new InvalidConfigException(Yii::t('recaptchav3', 'component is required.'));
        }
        $this->_component = $component;

        if ($this->message === null) {
            $this->message = Yii::t('recaptchav3', 'The verification code is incorrect.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value)
    {
        $result = $this->_component->validateValue($value);
        if($result === false){
            return [$this->message, []];
        }
        if($this->acceptance_score !== null && $result < $this->acceptance_score){
            return [$this->message, []];
        }
        return null;
    }

}