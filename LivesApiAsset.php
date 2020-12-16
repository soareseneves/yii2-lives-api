<?php

namespace soareseneves\livesapi;

use yii\web\AssetBundle;

/**
 * Class YoutubeApiAsset
 *
 * @package soareseneves\youtubeapi
 */
class LivesApiAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__.'/assets';

    /**
     * @inheritdoc
     */
    public $js = [
        'js/lives-api.js',
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/lives-api.css',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\web\YiiAsset',
    ];

}
