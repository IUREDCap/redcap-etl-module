<?php 

namespace IU\RedCapEtlModule;

class RedCapDb
{
    public function getUserInfo($username)
    {
        $userInfo = array();
        $sql = "select ui_id, username, user_firstname, user_lastname, user_email "
            ." from redcap_user_information "
            ." where username = '".$username."' and user_suspended_time is null "
            ;
        $result = db_query($sql);
        if ($row = db_fetch_assoc($result)) {
            $userInfo = $row;
        }

        return $userInfo;
    }

    /**
     * Returns information on all REDCap users that have not been suspended who have the
     * specified term in their username, first name, last name or (first) e-mail.
     */
    public function getUserSearchInfo($term)
    {
        $users = array();
        $sql = "select ui_id as id, concat(username, ' (', user_firstname, ' ', user_lastname, ') - ', user_email) as value, username "
            ." from redcap_user_information "
            ." where user_suspended_time is null and "
            ."     (username like '%".$term."%' "
            ."     or user_firstname like '%".$term."%'"
            ."     or user_lastname like '%".$term."%'"
            ."     or user_email like '%".$term."%'"
            ."     ) "
            ;
        $result = db_query($sql);
        while ($row = db_fetch_assoc($result)) {
            array_push($users, $row);
        }

        return $users;
    }

}
