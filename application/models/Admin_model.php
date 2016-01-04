<?php
Class Admin_model extends CI_Model{		
    
    
    function saveAdmin($update){
        if(isset($update['id'])){
            $this->db->update('admin', $update, array('id' => $update['id']));
            return $this->db->affected_rows();
            
        } else {
            $this->db->insert('user', $update);
            return $this->db->insert_id();
        }
    }
    
    
    function deleteAdmin($id){
        $this->db->delete('admin', array('id' => $id));
        
        return $this->db->affected_rows();
    }
    
    
    
    
}