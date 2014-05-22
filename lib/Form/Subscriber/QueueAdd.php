<?php
class Form_Subscriber_QueueAdd extends \Form{
    function init(){
        parent::init();
        $this->addHook('validate',array($this,'validateCustomData'));
    }

    function validateCustomData(){

        $js = array();

        $m = $this->add('Model_Subscriber_Queue');
        $m->tryLoadBy('user_id',$this->get('user_id'));

        if($m->loaded()){
            $js[] = $this->js()->atk4_form('fieldError','user_id',$this->api->_('user is already set'));
        }

        if (count($js)) {
            $this->js(null,$js)->execute();
        }

    }

}