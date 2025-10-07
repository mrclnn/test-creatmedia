<?php

namespace Services\Mailing;

use lib\DB;

class MailingSessionUsers
{
    private int $userId;
    private int $sessionId;
    const TABLE_NAME = 'mailing_session_users';

    public static function addProcessedUsers(array $usersIds, int $sessionId)
    {
        $insert = array_map(function(int $userId) use ($sessionId){
            return ['user_id' => $userId, 'session_id' => $sessionId];
        }, $usersIds);
        if(empty($insert)) return;
        $db = new DB();
        $db->massInsert(self::TABLE_NAME, ['user_id', 'session_id'], $insert);
    }
}