<?php

/**CREATING DEFAULT DB INSERT FOR TESTING=============================\\*/

/** instructions:
 * 1. set general variables
 * 2. every part of request has own variables. If it doesn't have - previous var. set is able to be used
 * 3. in the part 'articles' is implemented var. 'categories' with manual set of existing category ids
 * @Result: file with sql request for create/edit test data
*/

/*QUERY TEMPLATE
INSERT INTO `basename`.`tablename` (`field_1`, `field_2`, `field_3`)
VALUES  (val_1_int, 'val_2_string', val_3_date),
        (val_1_int, 'val_2_string', val_3_date);
 * */


/** general*/
$basename = 'cryo';
$init_id = 100; //initial ID for all tables
$text = "START TRANSACTION;";// sql text



/**table user*/
$count_of_lines = 500;
$emails = array('cryo.test@yandex.ru','cryo.test@yandex.com','cryo.test@yandex.ua','cryo.test@yandex.kz','cryo.test@yandex.by','cryo.test@ya.ru');
$cur_email = current($emails);
$password = 'tester';

$header = "INSERT INTO `".$basename."`.`user` (`id`, `name`, `email`, `password`, `about`, `avatar_id`, `type`, `is_active`, `is_subscriber`, `last_mail_date`) VALUES ";
$values= "";


$id = $init_id;
for($i=1;$i<=$count_of_lines;$i++){

    $id++;

    $values .= "\n ('".$id."','Test user №".$i."','".$cur_email."','".$password."','user for test API Mailer',NULL,'user',1,1,2013-01-01)";

    if($i<$count_of_lines){
        $values .=',';
    }else{
        $values .=';';
    }

    //check position in email array
    $cur_email = next($emails);
    if(!$cur_email){
        reset($emails);
        $cur_email = current($emails);
    }
}

$text .= "\n".$header.$values;

/** table user_category*/
$count_of_lines = 20; //each chunk of categories
$categories = array(
    array(1,2), array(3,4),array(5,6)
);
$header = "INSERT INTO `".$basename."`.`user_category` (`id`, `user_id`, `category_id`) VALUES ";
$values= "";
$user_id=200;
$id = $init_id;

$cat_count = count($categories);
for($cat_i=0;$cat_i<$cat_count;$cat_i++) {

    $chunk = $categories[$cat_i];//pair of categories
     $first = true;

    for($i=1;$i<=$count_of_lines;$i++){

        $user_id++;

        $id++;
        $values .= "\n ('".$id."','".$user_id."','".$chunk[0]."'),";
        $id++;
        $values .= "\n ('".$id."','".$user_id."','".$chunk[1]."')";

        if($i==$count_of_lines and $cat_i==($cat_count-1)){
            $values .= ';';
        }else{
            $values .= ',';
        }
    }
}

$text .= "\n".$header.$values;

/** table articles*/

/* all fields:
id, image_id,category,title, description, text, url_hash, date, time,
meta_keywords, meta_description, news_of_the_day, is_published, is_archived,
is_blocked, main_news, from_name, from_url, user_id, photo_author, is_migrated,
migrated_image, migrated_image_json, show_photo_in_article, views, search
 * */

$count_of_lines = 5; //each chunk of categories
$categories = array(1,2,3,4,5,6); //array with manual set category ID
$header = "INSERT INTO `".$basename."`.`article` (`id`, `category`,`title`,"
        ."`description`,`text`,`url_hash`,`date`,`time`,"
        ."`news_of_the_day`,`is_published`,`is_archived`,`is_blocked`,`main_news`,"
        ."`user_id`,`is_migrated`,`show_photo_in_article`) VALUES ";
$values= "";
$id = $init_id;
$cat_count = count($categories);
$must_be_added = $cat_count*$count_of_lines;
$added_lines = 0;

for($cat_i=1;$cat_i<=$cat_count;$cat_i++){

    for($i=1;$i<=$count_of_lines;$i++){
        $id++;
        $added_lines++;
        $values .= "\n ('".$id."','test','test #".$added_lines."','article for test API Mailer',"
                ."'nothing to read','test_article_".$added_lines."','2014-01-10','10:10:10',"
                ."0,1,0,0,0,3,0,0)";

        if($added_lines<$must_be_added){
            $values .=',';
        }else{
            $values .=';';
        }
    }
}

$text .= "\n".$header.$values;

/** table article_category*/
$header = "INSERT INTO `".$basename."`.`article_category` (`id`, `article_id`,`category_id`) VALUES ";
$values= "";
$id = $init_id;
$added_lines = 0;
for($cat_i=1;$cat_i<=$cat_count;$cat_i++){
    $cur_cat= $categories[$cat_i];

    for($i=1;$i<=$count_of_lines;$i++){
        $id++;
        $added_lines++;
        $values .= "\n ('".$id."','".$id."','".$cur_cat."')";

        if($added_lines<$must_be_added){
            $values .=',';
        }else{
            $values .=';';
        }
    }
}
$text .= "\n".$header.$values;

$text.="\n COMMIT;";//close transaction


//exit("SQL text: \n".$text);
/**CREATING DEFAULT DB INSERT FOR TESTING=============================//*/

/**----corrections----*/

//correct user_category
//            $m_us = $this->add('Model_User_Category');
//            $m_us->addCondition('id',">",100);
//
//            $data=$m_us->getRows();
////            $mc = count($data);
//
//            foreach($data as $row){
//                $m_us = $this->add('Model_User_Category');
//                $m_us->tryLoad($row['id']);
//                if($m_us->loaded()){
//                    $m_us->set('user_id',$row['id']);
//                    $m_us->save();
//                }
//            }
//
//            //correct article date
//            $m_us = $this->add('Model_Article');
//            $m_us->addCondition('id',">",100);
//
//            $data=$m_us->getRows();
////            $mc = count($data);
//
//            foreach($data as $row){
//                $q = $this->add('Model_Article')->dsql();
//                $q->field('date')
//                  ->field('time');
//              $q->where('id',$row['id']);
////                $date = $q->expr('DATE(2014-01-05');
//                $q ->set('date',$q->expr("DATE('2014-01-05')"));
//                $q ->set('time',$q->expr("TIME('0000')"));
//                $q->update();
//            }
//
//            exit('corrected!');
/**-------------------*/

/** final actions*/
$f = fopen('./test.txt','wt');
$write = fwrite($f,$text);
fclose($f);