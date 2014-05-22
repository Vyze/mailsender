<?php
class Form_Subscriber_Settings extends \Form {
    function init(){
        parent::init();
        $this->add('rvadym/x_tinymce/Controller_TinyMCE');
        $this->js()->trigger('reload');
        $this->api->x_sm->showMessages();

        $this->add('P')->set('Не рекомендуется вносить изменения в поле "' .$this->api->_('last mail date').'"');
        $this->add('P')->set('Содержимое из полей ввода будет подставлено в  текст сообщения вместо шаблонов полей (напр.ᛝprojectᛝ).
                    Если поле будет не заполнено или его шаблон не будет вставлен в текст сообщения, тогда его содержимое в письмо не попадет.
                    Поле "Ссылка" Связано с полем "Проект". Если поля  "Ссылка" и "Шаблон" заполнены, тогда в поле "Сообщение" шаблон ᛝprojectᛝ будет заменен ссылкой с текстом поля "проект".
                    ');
        $this->add('P')->set('Во всех полях можно использовать шаблоны, которые будут заменены соотвествующими значениями:');
        $this->add('P')->set('ᛝusernameᛝ - имя пользователя, ᛝemailᛝ - email пользователя, ᛝarticleᛝ - список статей рассылки');
        $this->add('P')->set('Обязательные к заполнению поля "Название" и "Сообщение". Остальные поля можно не использовать.
                    Ни один из шаблонов не обязательно указывать в тексте сообщения.
                    Если шаблон будет указан несколько раз, то и заполнится в письме он несколько раз. Например, если в сообщении указать 2 раза ᛝtitleᛝ, то 2 раза будет добавлена строка с содержимым поля "Название".');

    }

    private $m;
    function setModel($model, $actual_fields = UNDEFINED) {

        $this->m = $this->add($model);

        $this->m->tryLoadAny();

        if(!$this->m->loaded()){
            exit ($this->api->_('Database error: model is not loaded!'));
        }


        //fields
        $last_f = $this->addField('DatePicker','last_mail_date','last mail date');

        $title_f = $this->addField('line','mail_title','Title')->addComment('Шаблон: ᛝtitleᛝ');

        $mail_ending_1l_f = $this->addField('line','mail_ending_1l','Ending: 1-st line')->addComment('Шаблон: ᛝending-1lᛝ');

        $mail_ending_2l_f = $this->addField('line','mail_ending_2l','Ending: 2-nd line')->addComment('Шаблон: ᛝending-2lᛝ');
        $mail_ending_proj_f = $this->addField('line','mail_ending_proj','Ending: project')->addComment('Шаблон: ᛝprojectᛝ');
        $mail_ending_link_f = $this->addField('line','mail_ending_link','Ending: link')->addComment('Шаблон: ᛝlinkᛝ');


        $message_f = $this->addField('text','message','Message');
        $this->TinyMCE->addEditorTo($message_f,array('theme'=>'simple','height'=>'100','width'=>'650', 'language'=>'ru',));
        $intstr = $this->addField('Readonly','Instructions:')->set("нужно написать пример... Порядок элементов в письме: ; можно использовать шаблон ᛝusernameᛝ для подстановки именипользователя");

        //fill form from model
        $this->set($this->m->get());

//
//        //preview mewsage
//        $fill_articles = $this->addField('checkbox','fill_article','Заполнить статьи в предпросмотре');
//        $preview_v =$this->addField('Readonly','preview','Preview message:');
//        $preview_v->set('');
//        $preview_v->js(true)->closest('.atk-form-row')->hide();
//
//        $preview_b = $this->addButton('Preview message');
//
//
//
//
//        //actions
//
//
//        if($preview_b->isClicked()){
//
//            $a=$fill_articles->js(true)->val();
//            echo "fill art: \n";
//            echo $a;
//            exit;
//
//            if($this->get('fill_article')) exit ('ololo checked!');
//            $subject =  "Вчера #u# ходил в #p# и купил #s#.\n Ох уж этот #u#...";
//            $search = array('#u#','#p#','#s#');
//            $replace = array('Вася','магазин','презерватив');
//
//
//            echo str_replace($search,$replace,$subject);
//            exit('        *************button');
//        }
////        //TODO: check if is possible to use 'yes/no' confirmation
////        $preview_b->js('click')->univ()->confirm('Вывести список статей в предпросмотре? Будут подобраны все статьи для текущего пользователя за период со стр. "Отправка".')
////            ->ajaxec($this->api->url(null,array('fill_article'=>1))
////            );
////
////        if(!is_null($_GET['fill_article'])){
////            $_GET['fill_article'] = null;
////            exit('article used! DEBUG');
////        }



        //sumbit
        $this->addSubmit('Save');
        $this->onSubmit(array($this,'checkSubmittedForm'));

        //order
        $this->toPlaces() ;
    }

    function toPlaces() {
        $this->addClass('stacked atk-row');

        $order = $this->add('Order');
        $order
            ->move($this->addSeparator('span2'),'before','last_mail_date')
            ->move($this->addSeparator('span5'),'after','last_mail_date')
            ->move($this->addSeparator('span10'),'before','message')
            ->now();
//        $order->move($this->addSeparator('span2'),'before','last_mail_date');
//
//        $fields = array('mail_title','mail_ending_1l','mail_ending_2l','mail_ending_proj','mail_ending_link',);
//        foreach ($fields as $el) {
//            $order
//                ->move($this->addSeparator('span4'),'before',$el)
//                ->move($this->addSeparator('span3'),'after',$el);
//        }




        $order->move($this->addSeparator('span10'),'before','message')
            ->now();
    }

    function checkSubmittedForm() {
        $js = array();

        if (count($js)) {
            $this->js(null,$js)->execute();
        }

        $this->m->set($this->get());

        $last= DateTime::createFromFormat('Y-m-d', $this->get('last_mail_date'));

        $this->m->set('last_mail_date',$last->format('Y-m-d'));
        $this->api->x_sm->addMessage('message_key','success','Saved');
        $this->m->save();
        $this->api->redirect('settingsM');

    }
}
