<?php
/**
 * Video Embed plugin for Craft CMS 5.x
 *
 * Generate an embed URL from a YouTube or Vimeo URL
 *
 * @link      https://www.viget.com/
 * @copyright Copyright (c) 2024 Trevor Davis
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
     * Static reference to this plugin instance.
     *
     * @var VideoEmbed
     * @todo v4 — remove this property. Consumers should resolve the service via
     *       Craft::$app->getPlugins()->getPlugin('video-embed') or the
     *       `craft.videoEmbed` Twig variable. Static plugin references are an
     *       anti-pattern in Craft 5+; this exists only for backwards
     *       compatibility.
     */
    public static VideoEmbed $plugin;

    // Public Properties
    // =========================================================================

    /**
     * The plugin's schema version, used by Craft to track install/migration
     * state. Declared on the plugin class per Craft 5 conventions rather than
     * in composer.json's `extra` block.
     */
    public string $schemaVersion = '3.0.0';

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

        if (Craft::$app->getConfig()->getGeneral()->devMode) {
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
}
