<?php
class Model_Subscriber extends \Model_User {
    function init(){
        parent::init();
        $this->addCondition('is_subscriber',1);
    }

}