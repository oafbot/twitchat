<?php
    error_reporting(E_ERROR | E_WARNING | E_PARSE); // Turn on error reporting for debugging.
/**
 *  Release Notes: 
 *
 *  Php hack to connect the Program-O chatbot to a twitter account.
 *  This is a quick and dirty modification of the original script found here:
 *
 *  http://www.rebelwords.org/2012/08/twitterbot-php-and-program-o/
 *
 *  Credit should be given to Robert of www.rebelwords.com for figuring out 
 *  the bulk of the steps for this procedure. 
 *
 *  This script just attempts to "fix" some of the issues I ran into when I tried
 *  to apply Robert's script on my chatbot.
 *  
 *  Let me know if you make any useful modifications to this script. 
 *  Robert of www.rebelwords.com may also apprecaite the source code to any future modifications.
 *
 *  @filesource     twitter_chatbot.php
 *  @date           2013-01-25 21:53:22 -0500 (Fri, 25 Jan 2013)
 *
 *  @author (of minor modifications) Leonard M. Witzel <witzel@post.harvard.edu> 
 */

    $db_host = '';  /* Your db host name here, probably 'localhost' */
    $db_user = '';  /* Your database user name here */
    $db_pw   = '';  /* Your password goes here */
    $db      = '';  /* Your database name goes here */

    /* Twitter keys & secrets here */
    $consumer_key        = '';
    $consumer_secret     = '';
    $access_token        = '';
    $access_token_secret = '';
    
    $me = "";      /* Your Twitter Username without the @ symbol */
    $website = ""; /* Your website root url. */
    
    /* Default response if chatbot fails to find appropriate answer. */
    $fail = array(
        "Sorry, I wasn't paying attention...",
        "I'm sorry. I'm a bit sleepy. What was that again?",
        "Can you rephrase that?",
        "So how about that local sports team?",
        "When I grow up I want to be a giant robot.",
        "Will this be on the test?",
        "I like turtles.",
        "fascinating."
    );

    /**
     * curl_operation function.
     */
    function curl_operation($text, $user){
        $text = urlencode($text);
        $url  = curl_init('http://'.$website.'/gui/plain/index.php?say='.$text.'&submit=say&convo_id='.$user.'&bot_id=1&format=html');
        curl_setopt($url, CURLOPT_HEADER, 0);
        curl_exec($url);
        curl_close($url);
    }
    
    /**
     * make_reply function.
     */
    function make_reply($text){
        $sql   = "SELECT * FROM conversation_log WHERE input LIKE '%".$text."%' ORDER BY id DESC LIMIT 1";
        $rs    = mysql_query($sql) or die (mysql_error());
        $row   = mysql_fetch_array($rs);
        $reply = $row['response'];
        return $reply;
    }


        
    /*  MySQL Setup */
    mysql_connect($db_host,$db_user,$db_pw) or die('Could not connect to database');
    mysql_select_db($db) or die('Could not select database');
    $file_name="./dm_grassbrig.log";
    
    /*  Oauth setup */
    require_once('twitteroauth/twitteroauth/twitteroauth.php');
    $oauth = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
    $oauth->useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9'; /* Avoids API penalties */
    $credentials = $oauth->get('account/verify_credentials');
    
    echo 'Connected as @' . $credentials->screen_name . '\n';
    $remaining = $oauth->get('account/rate_limit_status');
    echo "Current API hits remaining: {$remaining->remaining_hits}.\n";
    $since_id = file_get_contents($file_name);


    /* HANDLE DIRECT MESSAGES */    
    $direct_msg = $oauth->get('direct_messages', array('cursor' => -1));
    
    foreach ($direct_msg as $msg){
        sleep(rand(5,20));  /* Makes the bot a bit more human like */
        $user = $msg->sender_screen_name;
        $text = $msg->text;
        $msg_id = $msg->id;
        curl_operation($text, $user);
        $grassbrig_reply   = $oauth->post('direct_messages/new',array('screen_name'=>$user,'text'=>make_reply($text)));
        $grassbrig_destory = $oauth->post('direct_messages/destroy/'.$msg_id);
    }
         
    /*  HANDLE MENTIONS */
    $tweets2me = $oauth->get('http://search.twitter.com/search.json', array('q'=>"@$me", 'since_id' => $since_id))->results;
    
    if(isset($tweets2me[0]))
        file_put_contents($file_name, $tweets2me[0]->id_str);
    
    foreach($tweets2me as $tweets){
        sleep(rand(5,20));
        $from = '@'.$tweets->from_user;
        
        if($tweets->from_user != $me){
            $tweet = $tweets->text;
            // $text  = str_replace("@$me", "", "$tweet"); // Remove just the bot's username 
            $text  = preg_replace('/@(\w+)\s\b/i', "", "$tweet"); // Or remove all mentions.
            $id    = $tweets->id;
            
            curl_operation($text, $from);
            $bot_reply = make_reply($text);
            
            if(ctype_space($bot_reply) || empty($bot_reply) || $bot_reply == ".")
                $bot_reply = $fail[array_rand($fail, 1)]; // curl_operation($text, $id);
                
            $reply = "$from $bot_reply";
            
            if($bot_reply != NULL && isset($bot_reply))
                $oauth->post('statuses/update', array('status'=>$reply, 'in_reply_to_status_id' => $id));
                // $oauth->post('statuses/update', array('status'=>$reply));
        }
    }
    
    /*This is the auto-follow routine*/
    $followers = $oauth->get('followers/ids', array('cursor' => -1));
    $followerIds = array();
    
    foreach($followers->ids as $i => $id){
        $followerIds[] = $id;
        if($i == 99) break; // Deal with only the latest 100 followers.
    }
    
    $friends = $oauth->get('friends/ids', array('cursor' => -1));
    $friendIds = array();
    
    foreach($friends->ids as $i => $id){
        $friendIds[] = $id;
        if($i == 1999) break; // Deal with only the latest 2000 friends.
    }
    
    foreach($followerIds as $id){
        if(empty($friendIds) or !in_array($id, $friendIds))
            $ret = $oauth->post('friendships/create', array('user_id' => $id));
    }
?>