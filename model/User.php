<?php

class M_User extends Model {
    
    function __construct(){
        $this->table = TB_PREFIX.'user';
        parent::__construct();
    }

    public function SelectAll(){
        $field = ['id', 'username', 'password'];
        return $this->Field($field)->Select();
    }

    public function getUserByUsername($username){
        $sql = 'SELECT * FROM '.$this->table.' WHERE username = "'.$username.'"';
        return $this->QueryOne($sql);
    }

    //复杂的SQL(join 等)使用原生的SQL来写，方便维护
    public function getOnlineUsers($roomID){
        $sql = 'SELECT u.id, u.username, c.roomName FROM '.$this->table.' AS u LEFT JOIN '.TB_PREFIX.'chatroom AS c ON u.roomID = c.id WHERE u.roomID = "'.$roomID.'" ORDER BY u.id DESC LIMIT 20';
        return $this->Query($sql);
    }
}