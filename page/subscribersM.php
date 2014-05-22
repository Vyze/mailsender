<?php
namespace vyze\mailsender;
class page_subscribersM extends \Page {
    function init() {
        parent::init();
    }

    function page_index() {

        $this->add('H3',null,'h3')->set('Перед использованием ознакомтесь с инструкцией на странице "Справка"!');

        if($this->api->mailsender->isActiveTask()){
            $this->add('View_H3Warning');
        }else{
            //grid
            $cr = $this->add('CRUD'
                ,array('allow_edit'=>false,
                    'form_class'=>'Form_Subscriber_QueueAdd')
            /*, 'grid'*/);
            $cr->setModel('Model_Subscriber_Queue'
                ,array('id','user_id')
                ,array('id','user')
            );
            $cr->js('reload')->reload();

            if($cr->grid){
                $cr->grid->addPaginator(25);

                //clear grid
                $clear_b=$this->add('Button')->setLabel('Clear data');
                $clear_b->js('click')->univ()->confirm($this->api->_('All data will be lost. Continue?'))
                    ->ajaxec($this->api->url(null,array('cl_grid'=>1))
                    );

                if(!is_null($_GET['cl_grid'])){
                    $_GET['cl_grid'] = null;
                    //clear query table if it is necessary
                    $list_m = $this->add('Model_Subscriber_Queue');
                    $list_m->tryLoadAny();

                    if ($list_m->loaded()){
                        $q = $this->api->db->dsql()->table('subscribe_queue');
                        $q->delete();
                        $list_m->unload();
                    }
                    $this->api->redirect('subscribersM');
                }

            }

            //selection form
            $form = $this->add('Form_Subscriber_Selection',array('reload_element'=>$cr->name),'filter');
            $m = $this->add('Model_Subscriber_Settings');
            $form->setModel($m);
        }

    }

    function defaultTemplate(){
        return array('page/subscribers');
    }
}