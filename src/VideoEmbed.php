<?php
/**
 * Video Embed plugin for Craft CMS 4.x
 *
 * Generate an embed URL from a YouTube or Vimeo URL
 *
 * @link      https://www.viget.com/
 * @copyright Copyright (c) 2022 Trevor Davis
 */

namespace viget\videoembed;

use Craft;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

use viget\videoembed\services\VideoEmbed as VideoEmbedService;

/**
 * Class VideoEmbed
 *
 * @author    Trevor Davis
 * @package   VideoEmbed
 * @since     1.2.0
 *
 */
class VideoEmbed extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var VideoEmbed
     */
    public static VideoEmbed $plugin;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('videoEmbed', VideoEmbedService::class);
            }
        );

        Craft::info(
            Craft::t(
                'video-embed',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }
}
