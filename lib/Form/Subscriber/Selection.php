<?php
class Form_Subscriber_Selection extends \Form{

    public $reload_element = '';
    private $last;//last mail date

    function init(){
        parent::init();
    }

    private $m;
    function setModel($model, $actual_fields = UNDEFINED) {
        $this->m = $model;

        $this->m->tryLoadAny();

        if(!$this->m->loaded()){
            exit("Отсутствуют записи в базе данных в таблице настроек рассылки!");
        }

        $last_f = $this->addField('Readonly','last_mail_date','last mail date');
        $select_date_f = $this->addField('DatePicker','select_date','select date');
        $all_users_f = $this->addField('checkbox','all_users', 'all users');

        //fill form fields
        $this->last = $this->m->get('last_mail_date');
        $date = DateTime::createFromFormat('Y-m-d', $this->last);
        $last_f->set($date->format('d/m/Y'));
        $select_date_f->set($date->format('d/m/Y'));


        //sumbit
        $this->onSubmit(array($this,'checkSubmittedForm'));


        $submit_select_b = $this->addButton('Select');

        $submit_select_b->js('click')->univ()->confirm($this->api->_('Queue will be renewed. Continue?'))
            ->ajaxec($this->api->url(null,array('make_submit'=>1))
            );

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
            ->now();
    }

    function checkSubmittedForm() {
        $js = array();


        if (count($js)) {
            $this->js(null,$js)->execute();
        }

        //clear queue table if it is necessary
        $list_m = $this->add('Model_Subscriber_Queue');
        $list_m->tryLoadAny();

        if ($list_m->loaded()){
            $q = $this->api->db->dsql()->table('subscribe_queue');
            $q->delete();
            $list_m->unload();
        }


        //get subscriber list

        $last= DateTime::createFromFormat('Y-m-d', $this->last);
        $last->setTime(0,0.0);
        $from= DateTime::createFromFormat('Y-m-d', $this->get('select_date'));
        $from->setTime(0,0,0);


        $user_m = $this->add('Model_Subscriber_Active')->dsql();
        $user_m->field('id');
        if (!$this->get('all_users')){

            $date = $last>$from ? $last : $from;
            $user_m->where('last_mail_date','<=',
                $user_m->expr('STR_TO_DATE('.$date->format('Y-m-d').',"%Y-%m-%d")')
            );
        }

        $ar = $user_m->get();

        $users = array();
        $i = 0;
        foreach($ar as $el){
            $i++;
            $users[] = array('id'=>$i, 'user_id'=> $el['id']-0);
        }

        $list_m->dsql()->insertAll($users);

        //final actions
        $js[] = $this->js()->_selector('#'.$this->reload_element)->trigger('reload');
        $this->js(null,$js)->execute();
    }
}//Form_Selection