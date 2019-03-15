<?php
/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 24.11.15
 * Time: 18:52
 */

namespace Ksnk\scaner;

/**
 * Паучёк
 * Class spider
 */
class mailer extends scaner
{
    /**
     * @var resource|bool
     */
    var $imap=false;

    /**
     * @param $host
     * @param $user
     * @param $password
     * @sample {imap.gmail.com:993/imap/ssl/novalidate-cert/norsh}Inbox
     * @return bool
     */
    function imap_open($host, $user,$password){
        $this->imap = imap_open($host,$user,$password);
        if(!$this->imap)
            return $this->error('Cannot connect to Gmail: ' . imap_last_error());
    }

    function imap_search($search){
        if(empty($search)) {
            $search = 'SINCE "' . date("j F Y", strtotime("-7 days")) . '"';
         //   return $this->error('can\' search with empty string');
        }
        $emails = imap_search($this->imap, $search);

//If the $emails variable is not a boolean FALSE value or
//an empty array.
        if(!empty($emails)){
            //Loop through the emails.
            foreach($emails as $email){
                //Fetch an overview of the email.
                $overview = imap_fetch_overview($this->imap, $email);
                $overview = $overview[0];
                //Print out the subject of the email.
                echo '<b>' . htmlentities($overview->subject) . '</b><br>';
                //Print out the sender's email address / from email address.
                echo 'From: ' . $overview->from . '<br><br>';
                //Get the body of the email.
                $message = imap_fetchbody($this->imap, $email, 1, FT_PEEK);
            }
        }
    }

}
