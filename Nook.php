<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Nook{
    
    public $CI;
    
    public $current_tier;
    public $current_tables;

    public $base_terms;
    public $search_terms;
    
    public $table_terms = array();
    
    public $tier_list;
    
    public $term_cache = array();
    public $obj_cache = array();
    
    public $active_user;

    public function __construct(){

        $this->CI =& get_instance();
        $this->CI->load->database();

        $this->tier_list = $this->CI->db->list_tables();

        $this->search_terms = array(
            't'=>'type',
            's'=>'subtype',
            'a'=>'alias',
            'k'=>'keywords'
        );
        
        $this->base_terms = array(
            'i'=>'id_',
            'A'=>'active_',
            'u'=>'updated_',
            'o'=>'order_',
            'O'=>'owner_',
            'c'=>'created_',
            'P'=>'perm_',
            'I'=>'img',
            'U'=>'url',
            'h'=>'html',
            'f'=>'file',
            'F'=>'folder',
            'd'=>'desc',
            'b'=>'body',
            'n'=>'name',
            'T'=>'text',
            'N'=>'username',
            'p'=>'password',
            'e'=>'email',
            'J'=>'object'
        );
        
        foreach($this->search_terms as $key=>$value){
            $this->search_terms[$value]=$key;
        }
        
        foreach($this->base_terms as $key=>$value){
            $this->base_terms[$value]=$key;
        }

    }
    
    public function add_tier($tier){
        $this->config_tier($tier, true);
    }

    private function config_tier($tier, $add=false){

        if(class_exists('Nog')){Elog::O();}

        $this->current_tier = $tier;

        $current_tables = array(

            'data'=>'nook_'.$table.'_data',
            'match'=>'nook_'.$table.'_match',
            'term'=>'nook_'.$table.'_terms'

        );

        $this->current_tables = $current_tables;

        $exists = in_array($current_tables['data'], $this->tier_list);

        if($add && !$exists){
            
            $this->data_cache[$table] = array();
            $this->match_cache[$table] = array();
            $this->terms_cache[$table] = array();

            $this->db_tables[] = $current_tables['data'];
            $this->db_tables[] = $current_tables['match'];
            $this->db_tables[] = $current_tables['terms'];

            self::$CI->db->query('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');

            self::$CI->db->query('DROP TABLE IF EXISTS `'.$current_tables['data'].'`'); 
            self::$CI->db->query('CREATE TABLE `'.$current_tables['data'].'` ( `did` int(10) unsigned NOT NULL, `oid` int(10) unsigned NOT NULL, `value` varchar(255) NOT NULL, PRIMARY KEY (`did`)) ENGINE=InnoDB DEFAULT CHARSET=latin1');

            self::$CI->db->query('DROP TABLE IF EXISTS `'.$current_tables['match'].'`');
            self::$CI->db->query('CREATE TABLE `'.$current_tables['match'].'` ( `oid` int(10) unsigned NOT NULL, `att` int(10) unsigned NOT NULL, `value` int(10) unsigned NOT NULL, PRIMARY KEY (`oid`,`name`,`value`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

            self::$CI->db->query('DROP TABLE IF EXISTS `'.$current_tables['terms'].'`');
            self::$CI->db->query('CREATE TABLE `'.$current_tables['terms'].'` ( `tid` int(10) unsigned NOT NULL, `value` varchar(255) NOT NULL, PRIMARY KEY (`tid`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

        }elseif(!$exists){

            if(class_exists('Nog')){Elog::M('tables do not exsist');}  
            if(class_exists('Nog')){Elog::C();}  
            exit;

        }elseif(!isset($this->data_cache[$table])){
            
            $this->data_cache[$table] = array();
            $this->match_cache[$table] = array();
            $this->terms_cache[$table] = array();
            
            $term_result = self::$CI->db->get(self::T_TERM);
            $term_array = $term_result->result_array();

            foreach($term_array as $entry){
                $this->term_cache[$tier][$entry['tid']] = $entry['value'];         
                $this->term_cache[$tier][$entry['value']] = $entry['tid']; 
            }

        }

        if(class_exists('Nog')){Elog::C();}  
        return $current_tables;
    }

    public function set_user($user){
        if(class_exists('Nog')){Elog::O();}

        $this->active_user = $user;

        if(class_exists('Nog')){Elog::C();}   
    }
    
    private function get_next_index($id_term){
        
            switch($id_term){
                case 'oid':
                    $table = $this->current_tables['data'];
                break;
                case 'did':
                    $table = $this->current_tables['data'];
                break;
                case 'tid':
                    $table = $this->current_tables['terms'];
                break;    
            
            }
        
            self::$CI->db->select($id_term);
            self::$CI->db->order_by($id_term, "desc");
            $query = self::$CI->db->get($table, 0, 1);
            
            if($query->num_rows() < 1){
                return 0;
            }else{
                $result = $query->row_array();
                return $result[$id_term] + 1;
            }
            
    }

    public function save($obj, $tier){
        if(class_exists('Nog')){Elog::O();}

        if(!is_array($obj) || count($obj) <= 2){
            if(class_exists('Nog')){Elog::M('save data is not valid');}
            if(class_exists('Nog')){Elog::C();}
            return null;
        }
        
        $tables = $this->config_tier($tier);

        if(isset($obj['id_'])){
            if(is_numeric($obj['id_'])){

                $id = $obj['id_'];
                $new = false;

                if(!isset($obj['active_'])){$obj['active_'] = 1;}
                if(!isset($obj['created_'])){$obj['created_'] = time();}
                if(!isset($obj['perm_'])){$obj['perm_'] = '';}
                $obj['updated_'] = time();
                if(!isset($obj['order_'])){$obj['order_'] = 0;}
                if(!isset($obj['owner_'])){$obj['owner_'] = $this->active_user;}
                
                self::$CI->db->trans_start();
            }else{

                if(class_exists('Nog')){Elog::O();}
                return false;
            }

        }else{
            
            self::$CI->db->trans_start();

            $id = $this->get_next_index('oid');
            
            $obj['id_'] = $id;
            $new = true;
            
            if(!isset($obj['active_'])){$obj['active_'] = 1;}
            $obj['created_'] = time();
            $obj['updated_'] = time();
            if(!isset($obj['perm_'])){$obj['perm_'] = '';}
            if(!isset($obj['order_'])){$obj['order_'] = 0;}
            if(!isset($obj['owner_'])){$obj['owner_'] = $this->user;}
        }    
            
        $obj_cache[$id] = $obj;
        
        self::$CI->db->delete($tables['match'], array('oid' => $id));
        
        $converted_array = array();
        
        $new_att;
        $save_term;
        $new_value;
        
        foreach($obj as $att => $value){ 
            
            $save_term = false;
            if(isset($this->search_terms[$att])){
                $new_att = $this->search_terms[$att];
                $save_term = true;
            }elseif(isset($this->base_terms[$att])){
                $new_att = $this->base_terms[$att];
            }elseif(isset($this->term_cache[$att])){
                $new_att = $this->term_cache[$att];
            }else{
                $new_att = $this->add_new_term($att);
            }
            
            if(is_array($value)){
                
                
            }else{
                

                if($save_term){
                    if(isset($this->term_cache[$value])){
                        $new_value = $this->term_cache[$value];
                    }else{
                        $new_value = $this->add_new_term($value);
                    }
                    self::$CI->db->insert($tables['match'], array('oit'=>$id, 'att'=>$new_att, 'value'=>$new_value)); 
                }else{
                    
                    
                    
                    
                }
             
                
                
            }
            
            
        }
        

        if(class_exists('Nog')){Elog::O();}

    }   
    
    private function add_new_term($term){
        
        $new_att = $this->get_next_index('tid');       
        self::$CI->db->insert($tables['terms'], array('tid'=>$new_att, 'value'=>$att)); 
        $this->term_cache[$tier][$new_att] = $att;         
        $this->term_cache[$tier][$att] = $new_att; 
        return $new_att;
        
    }
    
    private function encode_value($value){
        
        
        
        
    }
    
    private function decode_value($value){
        
        
        
    }
      
}