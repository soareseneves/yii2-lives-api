<?php

namespace soareseneves\livesapi;

use Yii;
use yii\base\InvalidParamException;
use Carbon\Carbon;
use yii\web\ServerErrorHttpException;
use soareseneves\livesapi\providers\YouTube;

class Module extends \yii\base\Module
{

    public $providers;
    public $youtube;

    public function init(){
        parent::init();

        if (isset($this->providers['youtube'])){
            $this->youtube = new YouTube($this->providers['youtube']);
        }
    }

     

}
