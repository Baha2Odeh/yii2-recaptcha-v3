<?php
/**
 * Created by PhpStorm.
 * User: bahaaodeh
 * Date: 12/22/18
 * Time: 7:51 PM
 */

namespace Baha2Odeh\RecaptchaV3;


use Yii;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\Html;
use yii\widgets\InputWidget;

class RecaptchaV3Widget extends InputWidget
{
    /**
     * Recaptcha component
     * @var string|array|RecaptchaV3
     */
    public $component = 'recaptchaV3';

    /**
     * @var string
     */
    public $buttonText = 'Submit';

    /**
     * @var string
     */
    public $actionName = 'homepage';

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
    }



    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->_component->registerScript($this->getView());
        $this->field->template = "{input}\n{error}";
        $formId = $this->field->form->id;
        $inputId = Html::getInputId($this->model, $this->attribute);

        return
            Html::tag('script', <<<JS
              grecaptcha.ready(function() {
                  grecaptcha.execute('{$this->_component->site_key}', {action: '{$this->actionName}'}).then(function(token) {
                      alert(token);
                      $('#{$inputId}').val(token);
                      $('#{$formId}').submit();
                  });
             });
JS
            )
            . Html::activeHiddenInput($this->model, $this->attribute)
            . Html::button($this->buttonText, $this->options);
    }
}