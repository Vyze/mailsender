<?php
class Lister_Articles extends CompleteLister {
    public $category = false;
    public $time = true;
    function init() {
        parent::init();

        $this->addClass('lister_article');
    }
    function formatRow() {

        $this->current_row['url_to_article'] =
            $this->api->url('cat/one',array(
                'base_page'=>$category_hash,
                'url_hash'=>$this->current_row['id'].'-'.$this->current_row['url_hash']
            ));
        //$this->api->url($category_hash.'/'.$this->current_row['id'].'-'.$this->current_row['url_hash']);


        $this->current_row_html['description'] = $this->current_row['description'].(($this->current_row['is_migrated'])?'...':'');

        if($this->current_row['photo_author']!='')
            $this->current_row_html['author_photo']='Фото '.$this->current_row['photo_author'];
        else $this->current_row_html['author_photo_del']='';


        // time
        if ($this->time) {
            $datetime="";
            if ($this->current_row['date'] == date( 'Y-m-d', strtotime('today') )) {
            }
            // yesterday
            elseif ($this->current_row['date'] == date( 'Y-m-d', strtotime('-1 day') )) {
                $datetime=$this->api->_('yesterday').', ';
            }
            // name of the month
            else {
                $datetime=
                    date( 'd ', strtotime($this->current_row['date'])).
                    $this->api->_(date( 'F', strtotime($this->current_row['date']))).
                    date( ' Y', strtotime($this->current_row['date'])).', '
                ;
            }
            $this->current_row_html['time']=$datetime.date('H:i',strtotime($this->current_row['time']));
        } else {
            $this->current_row_html['del_time'] = '';
        }

    }

    function defaultTemplate() {
        return array('view/lister_article_mail');
    }
}
