<?php

class User_model extends CI_Model
{
    public function saveUser($update)
    {
        if (isset($update['id'])) {
            $this->db->update('user', $update, array('id' => $update['id']));
            return $this->db->affected_rows();
        } else {
            $this->db->insert('user', $update);
            return $this->db->insert_id();
        }
    }


    public function deleteUserInfoTest($id)
    {
        $this->db->delete('admin', array('id' => $id));

        return $this->db->affected_rows();
    }
}
