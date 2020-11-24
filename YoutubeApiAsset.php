<?php

namespace soareseneves\YoutubeApi;

use yii\web\AssetBundle;

/**
 * Class YoutubeApiAsset
 *
 * @package soareseneves\YoutubeApi
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
