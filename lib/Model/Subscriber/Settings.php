<?php
class Model_Subscriber_Settings extends \Model_Table{
    public $table = 'subscribe_settings';
    function init(){
        parent::init();

        $this->addField('last_mail_date');
//        $this->addField('default_period')
//            ->enum(array('day','week','month','3 month'))
//            ->defaultValue('month');
        $this->addField('message');
        $this->addField('mail_limit')->type('int');
        $this->addField('is_active')->type('boolean');
        $this->addField('date_from')->type('date');
        $this->addField('date_to')->type('date');
        $this->addField('mail_title');
        $this->addField('mail_ending_1l');
        $this->addField('mail_ending_2l');
        $this->addField('mail_ending_link');
        $this->addField('mail_ending_proj');

    }
}//Model_Subscriber_Settings