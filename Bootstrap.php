<?php

namespace soareseneves\youtubeapi;

/**
 * notifications module bootstrap class.
 */
class Bootstrap implements \yii\base\BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        // add module I18N category
        if (!isset($app->i18n->translations['modules/youtubeapi/*'])) {
            $app->i18n->translations['modules/youtubeapi*'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'pt-BR',
                'basePath' => '@soareseneves/youtubeapi/messages',
            ];
        }
    }
}
