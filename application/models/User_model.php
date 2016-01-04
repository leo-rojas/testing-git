<?php
Class User_model extends CI_Model{		
    
    
    function saveUser($update){
        if(isset($update['id'])){
            $this->db->update('user', $update, array('id' => $update['id']));
            return $this->db->affected_rows();
            
        } else {
            $this->db->insert('user', $update);
            return $this->db->insert_id();
        }
    }
    
    
    function deleteUserInfoTest($id){
        $this->db->delete('admin', array('id' => $id));
        
        return $this->db->affected_rows();
    }
    
    
    
}