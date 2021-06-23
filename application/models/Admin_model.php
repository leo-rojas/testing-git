<?php

class Admin_model extends CI_Model
{
    public function saveAdmin($update)
    {
        if (isset($update['id'])) {
            $this->db->update('admin', $update, ['id' => $update['id']]);

            return $this->db->affected_rows();
        } else {
            $this->db->insert('user', $update);

            return $this->db->insert_id();
        }
    }

    public function deleteAdmin($id)
    {
        $this->db->delete('admin', ['id' => $id]);

        return $this->db->affected_rows();
    }
}
