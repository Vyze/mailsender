<?php
namespace vyze\mailsender;
class page_sendingM extends \Page {
    function init() {
        parent::init();
    }

    function page_index() {
        $this->add('H3')->set('Перед использованием ознакомтесь с инструкцией на странице "Справка"!');

        $m = $this->add('Model_Subscriber_Settings');

        if($this->api->mailsender->isActiveTask()){
            $this->add('View_H3Warning');

            $form = $this->add('Form');
            $stop_b = $form->addButton('stop')->set($this->api->_('Stop process'));
            $stop_b->js('click')->univ()->confirm($this->api->_('Mail sending will be stopped. Continue?'))
                ->ajaxec($this->api->url(null,array('make_submit'=>1))
                );
            if(!is_null($_GET['make_submit'])){
                $_GET['make_submit'] = null;
                $form->js()->submit()->execute();
            }

            if($form->isSubmitted()){
                $m->loadAny(1);
                $m->set('is_active',0);
                $m->save();
                $this->api->redirect('sendingM');
            }
        }else{
            $form = $this->add('Form_Subscriber_Sending',null);
            $form->setModel($m);
        }

    }
}


