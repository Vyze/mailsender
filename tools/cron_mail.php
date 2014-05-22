<?php
/**/
$_SERVER['HTTP_HOST'] = 'crioclub';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$_SERVER['REQUEST_METHOD'] = 'GET';
chdir('/home/crioclub/public_html/');
/*

$message = "cron works! Dir: ".getcwd()."\r\n";
file_put_contents ('shared/upload/test.txt',$message,FILE_APPEND);
exit;
*/
include 'atk4/loader.php';
$api=new ApiCLI('sendmail');

// Check required ATK version
$api->requires('atk','4.2.5');

/*
// Connect database
//TODO: try/catch fails if file executed by cron.
try {
    $api->dbConnect();
}catch(eException $e){
    die("No database connection! :".$e->getMessage()."\n");
}
/**/
$api->dbConnect();

$api->pathfinder->addLocation('./',array(
    'addons'=>array('atk4-addons','addons','shared/addons'),
    'php'=>array('shared'),
    'template'=>'public/mailsender/',
    'mail'=>'public/mailsender/',
));

//Mail sender
$api->add('vyze\mailsender\Controller_Mailsender');
$api->mailsender->execute();