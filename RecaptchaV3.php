<?php

namespace Baha2Odeh\RecaptchaV3;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\web\View;

/**
 * RecaptchaV3 is an application component for integrating Google reCAPTCHA v3.
 *
 * Configuration example (config/main.php):
 * ```php
 * 'components' => [
 *     'recaptchaV3' => [
 *         'class' => 'Baha2Odeh\RecaptchaV3\RecaptchaV3',
 *         'site_key' => 'YOUR_SITE_KEY',
 *         'secret_key' => 'YOUR_SECRET_KEY',
 *     ],
 * ],
 * ```
 *
 * Note: public property names use snake_case to preserve backwards compatibility
 * with existing configurations from before PSR conventions were adopted.
 *
 * @author Bahaa Odeh <bw4@hotmail.it>
 * @since 1.0
 */
class RecaptchaV3 extends Component
{
    // PSR-12 §4.2: constants must be declared before properties.

    const SCRIPT_URL            = 'https://www.google.com/recaptcha/api.js';
    const SCRIPT_ENTERPRISE_URL = 'https://www.google.com/recaptcha/enterprise.js';
    const VERIFY_URL            = 'https://www.google.com/recaptcha/api/siteverify';
    const VERIFY_ENTERPRISE_URL = 'https://recaptchaenterprise.googleapis.com/v1/projects/%s/assessments';

    /**
     * @var string The reCAPTCHA site key (public key shown to users).
     */
    public $site_key;

    /**
     * @var string The reCAPTCHA secret key (used for server-side verification).
     */
    public $secret_key;

    /**
     * @var bool Whether to verify the SSL certificate when calling Google's API. Defaults to `true`.
     * Set to `false` only in development environments.
     */
    public $verify_ssl = true;

    /**
     * @var bool Whether to use reCAPTCHA Enterprise. Defaults to `false`.
     * When `true`, uses the enterprise script and verification endpoint.
     * Requires [[project_id]] to be set.
     * @since 1.4
     */
    public $enterprise = false;

    /**
     * @var string|null Google Cloud project ID. Required when [[enterprise]] is `true`.
     * @since 1.4
     */
    public $project_id;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (empty($this->site_key)) {
            throw new InvalidConfigException('The "site_key" property must be set.');
        }
        if (empty($this->secret_key)) {
            throw new InvalidConfigException('The "secret_key" property must be set.');
        }
        if ($this->enterprise && empty($this->project_id)) {
            throw new InvalidConfigException('The "project_id" property must be set when "enterprise" is true.');
        }
    }

    /**
     * Registers the reCAPTCHA v3 JavaScript API in the page `<head>`.
     *
     * @param View $view
     */
    public function registerScript($view)
    {
        $baseUrl = $this->enterprise ? static::SCRIPT_ENTERPRISE_URL : static::SCRIPT_URL;
        $url = $baseUrl . '?render=' . urlencode($this->site_key);

        $view->registerJsFile($url, [
            'position' => View::POS_HEAD,
        ], 'recaptcha-v3-script');
    }

    /**
     * Validates a reCAPTCHA token against Google's API and returns the risk score.
     *
     * @param string $value The token received from the client-side widget.
     * @param string|null $action Expected reCAPTCHA action name for additional verification.
     *   If provided and the action returned by Google does not match, validation fails.
     * @return float|false The score (0.0–1.0) on success, or `false` on failure.
     */
    public function validateValue($value, $action = null)
    {
        if (empty($value)) {
            return false;
        }

        try {
            $response = $this->curl([
                'secret'   => $this->secret_key,
                'response' => $value,
                'remoteip' => Yii::$app->has('request') ? Yii::$app->request->userIP : null,
            ]);

            if (!empty($response) && $response['success'] === true) {
                if ($action !== null && isset($response['action']) && $response['action'] !== $action) {
                    Yii::warning(
                        sprintf('reCAPTCHA action mismatch: expected "%s", got "%s".', $action, $response['action']),
                        __METHOD__
                    );
                    return false;
                }
                return (float) $response['score'];
            }

            if (!empty($response['error-codes'])) {
                Yii::warning('reCAPTCHA verification failed: ' . implode(', ', $response['error-codes']), __METHOD__);
            }
        } catch (\Exception $e) {
            Yii::error('reCAPTCHA request error: ' . $e->getMessage(), __METHOD__);
        }

        return false;
    }

    /**
     * Sends verification data to Google's reCAPTCHA API via cURL.
     *
     * @param array $params POST parameters including `secret`, `response`, and optionally `remoteip`.
     * @return array Decoded JSON response. Enterprise responses are normalized to include
     *   `success` and `score` keys matching the standard API format.
     * @throws \RuntimeException if the cURL request fails.
     */
    protected function curl(array $params)
    {
        if ($this->enterprise) {
            return $this->curlEnterprise($params);
        }

        $params = array_filter($params, static function ($v) {
            return $v !== null;
        });

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => static::VERIFY_URL,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        if (!$this->verify_ssl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($data === false) {
            throw new \RuntimeException('reCAPTCHA cURL request failed: ' . $error);
        }

        return Json::decode($data, true);
    }

    /**
     * Sends a verification request to the reCAPTCHA Enterprise API and normalizes the response.
     *
     * @param array $params Parameters containing `response` (token) and optionally `remoteip`.
     * @return array Normalized response with `success`, `score`, `action`, and `hostname` keys.
     * @throws \RuntimeException if the cURL request fails.
     */
    private function curlEnterprise(array $params)
    {
        $url  = sprintf(static::VERIFY_ENTERPRISE_URL, $this->project_id) . '?key=' . urlencode($this->secret_key);
        $body = Json::encode([
            'event' => array_filter([
                'token'         => isset($params['response']) ? $params['response'] : null,
                'siteKey'       => $this->site_key,
                'userIpAddress' => isset($params['remoteip']) ? $params['remoteip'] : null,
            ]),
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        if (!$this->verify_ssl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($data === false) {
            throw new \RuntimeException('reCAPTCHA Enterprise cURL request failed: ' . $error);
        }

        $response = Json::decode($data, true);

        $valid = isset($response['tokenProperties']['valid']) && $response['tokenProperties']['valid'] === true;

        return [
            'success'  => $valid,
            'score'    => $valid && isset($response['riskAnalysis']['score'])
                ? (float) $response['riskAnalysis']['score']
                : 0.0,
            'action'   => isset($response['tokenProperties']['action'])
                ? $response['tokenProperties']['action']
                : null,
            'hostname' => isset($response['tokenProperties']['hostname'])
                ? $response['tokenProperties']['hostname']
                : null,
        ];
    }
}
