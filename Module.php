<?php

namespace soareseneves\livesapi;

use Yii;
use yii\base\InvalidParamException;
use Carbon\Carbon;
use yii\web\ServerErrorHttpException;
use soareseneves\livesapi\providers\YouTubeProvider;
use soareseneves\livesapi\providers\FacebookProvider;

class Module extends \yii\base\Module
{

    public $providers;
    public $youtube;
    public $facebook;

    public function init(){
        parent::init();

        if (isset($this->providers['youtube'])){
            $this->youtube = new YouTube($this->providers['youtube']);
        }

        if (isset($this->providers['facebook'])){
            $this->facebook = new Facebook($this->providers['facebook']);
        }
    }

     

}
