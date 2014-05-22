<?php
namespace vyze\mailsender;
class Controller_Mailsender extends \Controller {

/**
 * Addon to send mail to subscribers.
 * Mail sender mechanism is released for ApiCLI, but it's possible to use from ApiWEB.
 * Task must be started from page 'sendingM'
 *
 * Installation:
 * 1. Add these lines to yor API:
 * $this->add('vyze\mailsender\Controller_Mailsender');
 * 1.1. Add vyze\logger if you want to log process; set TRUE to $use_log
 * $this->add('vyze\logger\Controller_Logger'); ????
 *
 * 2. Extend all pages to your API from mailsender/page
 *  example: class page_subscriber extends vyze\mailsender\page_subscribersM {}
 *
 * 3. Use tools/cron_mail.php to start sending by cron.
 * As alternative way you can execute mailsender from extended page SendingM by adding this line to submit:
 *$this->api->mailsender->execute();
 * In this case, mails will be sent during the hoster's limits of mail sending
 *
 * Algo:
 * 1. Create log file
 * 2. Get main settings  and check if mail process is active
 * 3. Get queue of subscriber
 * 3.1 Get user's subscribe categories
 * 4. Get all aritcles from set period
 * 4.1 Get article's categories
 * 5. Form letter
 * 6. Send letters to users
 * 7. Clear subscriber queue and change user's 'last-mail-date' field to current date
 * 8. Switch setting's active state to inactive.
 *
 * @author Vyze
*/

    public $missed_user = null; //array of user ID from queue which is absent in table 'user'. Used to correct queue.

    //enable logger
    public $use_log = false;
    //start of log message
    public $log_str = 'Stop execution: ';

    //base path for article link
    public $article_path_out = 'http://crioclub.com/articles/';
    //sender's email
    public $sender_email=null;//no-reply@example.com

	function init() {
		parent::init();

        //set default
        $this->use_log = $this->api->getConfig('use_mail_log',false);

        // add add-on locations to pathfinder
        $this->loc = $this->api->locate('addons',__NAMESPACE__,'location');
        $addon_location = $this->api->locate('addons',__NAMESPACE__);
        $this->api->pathfinder->addLocation($addon_location,array(
            'php'=>'lib',
            'template'=>'templates',
            'mail'=>'templates/mail',
        ))->setParent($this->loc);

        $this->api->mailsender = $this;

        if($this->use_log){
            $this->logger = $this->add('vyze\logger\Controller_Logger');
        }
	}

/**------Data functions----------*/

    /**
     * @param bool $active - sets model condition to active if true
     * @return array of hashes from db or bool false if there is no data
     */
    public function getSettings($active = true){

        //get settings
        $m = $this->add('Model_Subscriber_Settings');
        if($active){
            $m->addCondition('is_active',1);
        }
        $m->tryLoadAny();
        if($m->loaded()){
            $data = $m->getRows();
            return $data[0];
        }else{
            return false;
        }
    }

    private function getQueue($limit = null){
        $q = $this->add('Model_Subscriber_Queue')->dsql();

        $q->limit($limit?$limit:100); //set default limit to 100
        $q->field('id');
        $q->field('user_id');
        return $q->get();
    }

    private function getSubscriber($user_ids){
        //TODO: maybe is a reason to make a join to queue table?
        $m = $this->add('Model_Subscriber');
        $m->tryLoadBy('id','in',$user_ids);
        if($m->loaded()){
            return  $m->getRows();
        }else{
            return false;
        }
    }

    private function getUserCategory($user_ids){
        $q = $this->add('Model_User_Category')->dsql();
        $q->field('user_id');
        $q->field('category_id');
        $q->where('user_id','in',$user_ids);
        return $q->get();
    }

    private function getArticle($date_from, $date_to){
        $q= $this->add('Model_Article_NotArchived')->dsql();
        $q->field('id');
        $q->field('title');
        $q->field('description');
        $q->field('url_hash');

        $q->where('date','>=',$date_from /*$q->expr("DATE('".$date_from."')")*/ );
        $q->where('date','<=',$date_to);

        $q->order('date');
        $article_data_temp = $q->get();
        $article_all_ids = array_column($article_data_temp,'id');
        return array_combine($article_all_ids,$article_data_temp); //setting ID to array's key
    }

    /**
     * Delete users from queue; change 'last mail date' for them
     * @param null $user_ids
     * @param null $cur_date
     * @return bool
     */
    private function queueCorrection($user_ids = null,$cur_date = null){

       if(!$user_ids){
           return false;
       }
       if(!$cur_date){
           $cur_date=date('Y-m-d');
       }

       //remove users from queue
       $subscriber_cleaner_q = $this->add('Model_Subscriber_Queue')->dsql();
       $subscriber_cleaner_q->where('user_id','in',$user_ids);
       $subscriber_cleaner_q->delete();

       //change user's last mail date
       $user_q = $this->add('Model_User')->dsql();
       $user_q->field('last_mail_date');
       $user_q->where('id','in',$user_ids);

       $user_q->set('last_mail_date',$cur_date);
       $user_q->update();
       $user_q = null;

       return true;
   }

    /**
     * Sets setting to inactive with current date
     */
    private function finishTask()
    {
        $q=$this->add('Model_Subscriber_Settings')->dsql();
        $q->where('is_active',1);
        $q->set('last_mail_date',date('Y-m-d'));
        $q->set('is_active',0);
        $q->update();
    }

    /**
     * Custom stop task
     */
    public function stopTask(){
    }


    public function isActiveTask(){
        $q = $this->add('Model_Subscriber_Settings')->dsql()->field('id')->where('is_active',1)->get();
        return count($q);
    }


/**------Mail functions----------*/

    function createMailText($settings, $articles=null, $ids=null, $use_articles=1){

        $article_l ='';

        if($use_articles){
            if(!$articles)return $article_l;

            if($ids){
                foreach ($ids as $el) {
                    $line = $articles[$el];
                    $article_l.= '<a href="'
                        .$this->article_path_out
                        .$line['url_hash']
                        .'" target="_blank"><b>'
                        .$line['title']
                        .'</b></a><br/>'
                        .$line['description'].'<br/>';
                }

            }else{
                foreach ($articles as $line) {
                    $article_l.= '<a href="'
                        .$this->article_path_out
                        .$line['url_hash']
                        .'" target="_blank"><b>'
                        .$line['title']
                        .'</b></a><br/>'
                        .$line['description'].'<br/>';
                }
            }
        }

        if($settings['mail_ending_proj'] && $settings['mail_ending_link']){

            $link= str_replace('http://','',$settings['mail_ending_link']); //check if link already has http://
            $project = '<a href="http://'
                .$link
                .'" target="_blank">'
                .$settings['mail_ending_proj']
                .'</a>';

        }else{
            $project = $settings['mail_ending_proj'];
        }

        $subject = $settings['message'];
        $search = array('ᛝtitleᛝ','ᛝending-1lᛝ','ᛝending-2lᛝ','ᛝprojectᛝ','ᛝarticleᛝ'); //template spots
        $replace = array($settings['title'],$settings['mail_ending_1l'],$settings['mail_ending_2l'],$project,$article_l);
        return str_ireplace($search,$replace,$subject);
    }

    /**
     * Send a mail for subscriber from $user_ids
     * @param array $user_ids
     * @param array $user_data
     * @param array $setings - array of settings data
     */
    function send($user_data, $user_ids, $title, $text){

        $sent_user_ids = array();

        foreach ($user_ids as $el) {

        $message= str_ireplace(
            array('ᛝusernameᛝ', 'ᛝemailᛝ'),
            array($user_data[$el]['name'],$user_data[$el]['email']),
            $text);

            $mail = $this->add('TMail');
            $mail->loadTemplate('mail');

            $mail->subject= $title;
            $mail->body= $message;

            $mail->setTag('subject',$title);
            $mail->setTag('body',strip_tags($message));
            $mail->setHtml($message);

            if($mail->send($user_data[$el]['email'],$this->sender_email)){ //mail sent successfully
                $sent_user_ids[] = $el;
            }
        }
       return $sent_user_ids;
    }


    /**
     * Execute sender
     */
    public function execute(){
        //get settings
        if(!$settings = $this->getSettings()) return; //no active settings - no task

        if($this->use_log){
            $log = $this->logger->getLog(); //start log
            $this->logger->addToLog($log,'======Start: '.date('Y-m-d:H:i:s').' =======');
        }

        //get subscriber queue
        if($this->use_log){
            $this->logger->addToLog($log,'getting subscribers......');
        }
        $subscriber_queue = $this->getQueue($settings['mail_limit']);

        if(!$subscriber_queue){
            $this->log_str.='subscriber queue is empty!';
            if($this->use_log)$this->logger->closeLog($log,$this->log_str);
            exit($this->log_str); //check queue data
        }

        $queue_ids = array_column($subscriber_queue,'id');
        $user_ids = array_column($subscriber_queue,'user_id');
        if(!$user_ids){
            $this->log_str.='There is no user in subscriber queue!';
            if($this->use_log)  $this->logger->closeLog($log,$this->log_str);
            exit($this->log_str);
        }

        //get users
        $user_data = $this->getSubscriber($user_ids);
        $user_data = array_combine(array_column($user_data,'id'),$user_data); //setting ID to array's key
        //data validation
        $user_ids = $this->userDataValidation($user_ids,array_column($user_data,'id'));

        //get user's categories
        $category = $this->getUserCategory($user_ids);

        $user_cat_ids = array_unique(array_column($category,'user_id')); //users with set categories
        $user_no_cat_ids = array_diff($user_ids,$user_cat_ids); // users without set categories

        if($this->use_log){
            $this->logger->addToLog($log,'getting articles......');
        }

        //get articles
        $article = $this->getArticle($settings['date_from'],$settings['date_to']);

        if($this->use_log){
            $this->logger->addToLog($log,'Preparing letter for subscriber without any category......');
        }

        $text = $this->createMailText($settings,$article);

        $sent_user_ids = array();
        $unsent_user_ids = array();

        //first chunk of mails - users without set categories
        $sent_user_ids = array_merge($sent_user_ids,
            $this->send($user_data,$user_no_cat_ids,$settings['title'],$text));
        $unsent_user_ids = array_merge($unsent_user_ids,
                array_diff($user_no_cat_ids,$sent_user_ids));

        if($this->use_log){
            $this->logger->addToLog($log,'Preparing letter for subscriber with categories......');
        }

        $art_cat_q= $this->add('Model_ArticleCategory')->dsql();
        $art_cat_q->where('category_id','in',array_column($category,'category_id'));
        $art_cat_data = $art_cat_q->get();


        //second chunk of mails - users with set categories
        $sent_user_ids = array_merge($sent_user_ids,
            $this->send($user_data,$user_cat_ids,$settings['title'],$text));
        $unsent_user_ids = array_merge($unsent_user_ids,
            array_diff($user_no_cat_ids,$sent_user_ids));

        if($unsent_user_ids){
            if($this->use_log){
                $err_msg= "ERROR: mail wasn't set for users: ";
                foreach ($unsent_user_ids as $el) {
                    $err_msg.= $user_data[$el]['name'] . ' (id='.$user_data[$el]['id'].')';
                }
                $this->logger->addToLog($log,$err_msg);
            }
        }

        if($sent_user_ids){
            $this->queueCorrection($sent_user_ids);
        }

        //send mails ----//


        if($this->getQueue(1)){
        }else{
        //finish if queue is empty
        $this->finishTask();
        }

        //close log
        if($this->use_log)  $this->logger->closeLog($log,'======Finish: '.date('Y-m-d:H:i:s').' =======');

    }

    /**
     * Function shows preview of message from settings.
     * @return string of message html
     */
    public function getMessagePreview($use_articles = 0)
    {

        //get settings
        if(!$settings = $this->getSettings(false)) return; //no active settings - no task

        //get users
        $user_data = $this->api->auth->model;

        if($use_articles){
            //get articles
            $article_data = $this->getArticle($settings['date_from'],$settings['date_to']);
            $subject = $this->createMailText($settings,$article_data,null,$use_articles);
        }else{
//            $article='<br> ᛝarticleᛝ <br>';
            $subject = $this->createMailText($settings,null,null,$use_articles);
        }

        $search = array('ᛝusernameᛝ', 'ᛝemailᛝ');
        $replace = array($user_data['name'],$user_data['email']);
        return str_ireplace($search,$replace,$subject);
    }


    /**------Validations----------*/

    /**
     * @return array - intersect of  param arrays
     * @param array $user_ids - user ids from queue
     * @param array $user_data_ids - user ids from t.'user'
     */
    private function userDataValidation($user_ids,$user_data_ids){
        $user_dif = array_diff($user_ids, $user_data_ids);
        if($user_dif){
            $this->$missed_user = $user_dif;
            return array_intersect($user_ids,$user_data_ids);
        }
        return $user_ids;
    }


/**---- API functions ----------*/

    /**
     * Function adds menu with control pages to owner page .
     * Usage: Copy function to your API and recall from owner page.
     * Example: call from page: $this->api->addMailerMenu();
     * TODO: make independent of API usage
     */
    function addMailerMenu(){
        $this->api->menu_mailer=$this->add('Menu',null,'Menu_Mail');
        if ($this->api->auth->isLoggedIn()) {
            $this->api->menu_mailer->addMenuItem('subscribersM','Users');
            $this->api->menu_mailer->addMenuItem('sendingM','Sending');
            $this->api->menu_mailer->addMenuItem('settingsM','Settings');
            $this->api->menu_mailer->addMenuItem('helpM','Help');
        }
        return $this->api;
    }

}
