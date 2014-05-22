<?php
class Form_Subscriber_Sending extends \Form{

    //TODO: correct date operation !!!!

    public $reload_element = '';
    private $last;//last mail date

    function init(){
        parent::init();
        $this->api->x_sm->showMessages();
        $this->js()->trigger('reload');
    }

    private $m;
    function setModel($model, $actual_fields = UNDEFINED) {
        $this->m = $model;

        $this->m->tryLoadAny();

        if(!$this->m->loaded()){
            exit("Отсутствуют записи в базе данных в таблице настроек рассылки!");
        }

        $last_f = $this->addField('Readonly','last_mail_date','last mail date');
        $from_f = $this->addField('DatePicker','from_date','from date');
        $to_f = $this->addField('DatePicker','to_date','to date');


        //fill form fields
        $this->last = $this->m->get('last_mail_date');
        $date = DateTime::createFromFormat('Y-m-d', $this->last);
        $last_f->set($date->format('Y-m-d'));
        $from_f->set($date->format('Y-m-d'));
        $to_f->set(date('d/m/Y'));


        //sumbit
        $this->onSubmit(array($this,'checkSubmittedForm'));


        $submit_exec_b = $this->addButton('Execute');
        $submit_exec_b->js('click')->univ()->confirm($this->api->_('Starting to send mails. Continue?'))
            ->ajaxec($this->api->url(null,array('make_submit'=>1)));

        if(!is_null($_GET['make_submit'])){
            $_GET['make_submit'] = null;
            $this->js()->submit()->execute();
        }

        //order
        $this->toPlaces() ;

    }//setModel()

    function toPlaces() {
        $this->addClass('stacked atk-row article-form');

        $this->add('Order')
            ->move($this->addSeparator('span3'),'before','last_mail_date')
            ->move($this->addSeparator('span2'),'after','last_mail_date')
            ->move($this->addSeparator('span2'),'after','from_date')
            ->move($this->addSeparator('span3'),'after','to_date')
            ->now();
    }

    function checkSubmittedForm() {
        $js = array();

        //check query table
        $list_m = $this->add('Model_Subscriber_Queue');
        $list_m->tryLoadAny();

        if (!$list_m->loaded()){
            $js[]=$this->js()->univ()->errorMessage($this->api->_('Subscriber query is empty!'));
        }

        if (count($js)) {
            $this->js(null,$js)->execute();
        }

            //check dates
            $last= DateTime::createFromFormat('Y-m-d', $this->last);
            $last->setTime(0,0.0);
            $from= DateTime::createFromFormat('Y-m-d', $this->get('from_date'));
            $from->setTime(0,0,0);
            $to= DateTime::createFromFormat('Y-m-d', $this->get('to_date'));
            $to->setTime(0,0,0);

            if($to<$from){
                $to = $from;
            }

        $q = $this->m->dsql();
        $q->set('is_active',1);
        $q->set('date_from',date('Y-m-d',$from->getTimestamp()));
        $q->set('date_to',date('Y-m-d',$to->getTimestamp()));
        $q->update();

        //final actions
        $this->api->redirect('sendingM');
    }

}//Form_Subscriber_Sending