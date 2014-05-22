<?php
class Form_Subscriber_PreviewMessage extends \Form {
    function init(){
        parent::init();

        $fill_articles = $this->addField('checkbox','fill_article','Заполнить статьи в предпросмотре');
        $preview_v =$this->addField('Readonly','preview','')->addClass('span5');
//        $preview_v->set('<p>some day I just wanna up and call it quits.</p> <p>I fill like I surrounded by a wall of bricks.</p>');
//        $preview_v->js(true)->closest('.atk-form-row')->hide();

//        //TODO: check if is possible to use 'yes/no' confirmation
//        $preview_b->js('click')->univ()->confirm('Вывести список статей в предпросмотре? Будут подобраны все статьи для текущего пользователя за период со стр. "Отправка".')
//            ->ajaxec($this->api->url(null,array('fill_article'=>1))
//            );
//
//        if(!is_null($_GET['fill_article'])){
//            $_GET['fill_article'] = null;
//            exit('article used! DEBUG');
//        }



        $test_button = $this->addButton('test button','test_but');
        $test_button->js('click',
            $this->getElement('preview')->js()->val('А роза упала на лапу Азора')
        );

        //sumbit
        $this->addSubmit('Message preview');
        $this->onSubmit(array($this,'checkSubmittedForm'));

        //order
        $this->toPlaces() ;
    }

//    private $m;
//    function setModel($model, $actual_fields = UNDEFINED) {
//
//
//    }

    function toPlaces() {
        $this->addClass('stacked atk-row');

        $order = $this->add('Order');
        $order
            ->move($this->addSeparator('span24'),'after','fill_article')
            ->now();

    }

    function checkSubmittedForm() {
        $js = array();

        if (count($js)) {
            $this->js(null,$js)->execute();
        }

//        if($this->get('fill_article')){
//            exit('ololo '. $this->get('fill_article'));
//        }else{
//            exit('empty');
//        }
        $message = $this->api->mailsender->getMessagePreview($this->get('fill_article'));
        $this->get('preview')->js()->val($message)->execute();


/*ATK EXAMPLE
        $form   = $page->add('Form');
    $form->addField('Text','text');
    $form->addSubmit('Revert Text');
    $form->addButton('Set testing text')->js('click',
        $form->getElement('text')->js()->val('А роза упала на лапу Азора')
    );
    $form->onSubmit(function($form){
    $string = iconv('utf-8', 'utf-16le', $form->get('text'));
    $string = strrev($string);
    $string = iconv('utf-16be', 'utf-8', $string);
    $form->getElement('text')->js()->val($string)->execute();
    });
------------------------------------------*/

    }
}
