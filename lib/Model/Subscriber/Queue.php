<?php
class Model_Subscriber_Queue extends \Model_Table {
    public $table = 'subscribe_queue';
    function init(){
        parent::init();
        $this->hasOne('Model_Subscriber')->mandatory('required');
    }

}//Model_Subscriber_Queue