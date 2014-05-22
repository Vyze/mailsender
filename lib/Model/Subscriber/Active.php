<?php
class Model_Subscriber_Active extends Model_Subscriber {
    function init(){
        parent::init();
        $this->addCondition('is_active',1);
    }

}