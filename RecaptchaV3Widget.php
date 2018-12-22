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
use yii\web\View;
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
        $callbackRandomString = time();

        $options = array_merge([
            //  'onClick' => "recaptchaCallback_{$callbackRandomString}()"
        ], $this->options);


        $this->view->registerJs(<<<JS

         grecaptcha.ready(function() {
                   grecaptcha.execute('{$this->_component->site_key}', {action: '{$this->actionName}'}).then(function(token) {
                       $('#{$inputId}').val(token);
                   });
         });
 $('#{$formId}').on('beforeSubmit',function(){
           if(!$('#{$inputId}').val()){
               grecaptcha.ready(function() {
                   grecaptcha.execute('{$this->_component->site_key}', {action: '{$this->actionName}'}).then(function(token) {
                       $('#{$inputId}').val(token);
                       $('#{$formId}').submit();
                   });
               });
               return false;
            }else{
               return true;
            }
 });
JS
            ,View::POS_READY);
        return

            Html::activeHiddenInput($this->model, $this->attribute,['value'=>'']);
    }
}