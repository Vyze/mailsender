<?php
namespace vyze\mailsender;
class page_settingsM extends \Page{
//TODO: make preview for message
     function init(){
        parent::init();

         if($this->api->mailsender->isActiveTask()){
            $this->add('View_H3Warning');
         }else{
             $m = $this->add('Model_Subscriber_Settings');
             $form = $this->add('Form_Subscriber_Settings');
             $form->setModel($m);
         }
    }
}