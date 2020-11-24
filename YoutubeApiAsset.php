<?php

namespace soareseneves\youtubeapi;

use yii\web\AssetBundle;

/**
 * Class YoutubeApiAsset
 *
 * @package soareseneves\youtubeapi
 */
class YoutubeApiAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__.'/assets';

    /**
     * @inheritdoc
     */
    public $js = [
        'js/youtubeapi.js',
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/youtubeapi.css',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\web\YiiAsset',
    ];

}
