<?php
/**
 * Video Embed plugin for Craft CMS 3.x
 *
 * Generate an embed URL from a YouTube or Vimeo URL
 *
 * @link      https://www.viget.com/
 * @copyright Copyright (c) 2017 Trevor Davis
 */

namespace viget\videoembed;

use viget\videoembed\variables\VideoEmbedVariable;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

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
    public static $plugin;

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
                $variable->set('videoEmbed', VideoEmbedVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
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

    // Protected Methods
    // =========================================================================

}
