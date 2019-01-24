<?php
/**
 * Video Embed plugin for Craft CMS 3.x
 *
 * Generate an embed URL from a YouTube or Vimeo URL
 *
 * @link      https://www.viget.com
 * @copyright Copyright (c) 2018 Trevor Davis
 */

namespace viget\videoembed\models;

use viget\videoembed\VideoEmbed;

use Craft;
use craft\base\Model;

/**
 * VideoEmbed Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Trevor Davis
 * @package   VideoEmbed
 * @since     1.2.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $ytAutoplay = false;
    public $ytColor = 'red';
    public $ytControls = 'true';
    public $ytEnableJsApi = false;
    public $ytFullscreen = 'true';
    public $ytLoop = false;
    public $ytRelated = 'true';
    public $ytShowInfo = 'true';

    public $vAutoplay = false;
    public $vByline = 'true';
    public $vColor = '';
    public $vLoop = false;
    public $vPortrait = 'true';
    public $vTitle = 'true';


    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['vColor', 'validateHexadecimal'],
            [
                ['ytAutoplay', 'ytColor', 'ytControls', 'ytEnableJsApi', 'ytFullscreen',
                'ytLoop', 'ytRelated', 'ytShowInfo', 'vAutoplay', 'vByline', 'vColor',
                'vLoop', 'vPortrait', 'vTitle'], 'default', 'value' => false
            ]
        ];
    }

    public function validateHexadecimal($attribute, $params, $validator)
    {
        if (!preg_match('/^#[a-f0-9]{6}$/i', $this->$attribute) && !preg_match('/^#[a-f0-9]{3}$/i', $this->$attribute)) {
            $this->addError($attribute, 'It must be a hexadecimal color string (e.g. #ffffff or #fff).');
        }
    }
}