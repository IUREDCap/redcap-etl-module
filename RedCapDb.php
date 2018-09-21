<?php 

namespace IU\RedCapEtlModule;

class RedCapDb
{
    public function getUserInfo()
    {
        $users = array();
        $sql = "select ui_id, username, user_firstname, user_lastname, user_email "
            ." from redcap_user_information "
            ;
        $result = db_query($sql);
        while ($row = db_fetch_assoc($result)) {
            array_push($users, $row);
        }

        return $users;
    }

    public function getUserSearchInfo($term)
    {
        $users = array();
        $sql = "select ui_id as id, concat(username, ' (', user_firstname, ' ', user_lastname, ') - ', user_email) as value, username "
            ." from redcap_user_information "
            ." where username like '%".$term."%' "
            ."     or user_firstname like '%".$term."%'"
            ."     or user_lastname like '%".$term."%'"
            ."     or user_email like '%".$term."%'"
            ;
        $result = db_query($sql);
        while ($row = db_fetch_assoc($result)) {
            array_push($users, $row);
        }

        return $users;
    }

}
