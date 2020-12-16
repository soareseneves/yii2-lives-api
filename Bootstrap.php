<?php

namespace soareseneves\livesapi;

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
        if (!isset($app->i18n->translations['modules/livesapi/*'])) {
            $app->i18n->translations['modules/livesapi*'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'pt-BR',
                'basePath' => '@soareseneves/livesapi/messages',
            ];
        }
    }
}
