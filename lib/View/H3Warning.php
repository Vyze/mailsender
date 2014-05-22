<?php
/**
 * Author: Vyze
 * Date: 18.05.14:23:28
 */
class View_H3Warning extends \H3{

    public $text;

    function init(){
        parent::init();
        $default =$this->api->_('Mail process is active! If you break it, there is no warranty than you could continue it correctly.');
        $this->set($this->text?$this->text:$default)->addClass('red');
    }
}