<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Nook{
    
    public $CI;
    
    public $file_path = '';
    
    public $current_tier;
    public $current_tables;

    public $base_terms;
    public $meat_terms;
    public $search_terms;
    
    public $table_terms = array();
    
    public $tier_list;
    
    public $term_cache = array();
    public $obj_cache = array();
    
    public $query_start = 0;
    public $query_length = 10000;
    public $query_sort = 'oid';
    
    public $active_user;

    public function __construct(){

        $this->CI =& get_instance();
        $this->CI->load->database();
        
        $this -> file_path = APPPATH . '/cache/nook';

        $this->tier_list = $this->CI->db->list_tables();

        $this->search_terms = array(
            't'=>'type',
            's'=>'subtype',
            'a'=>'alias',
            'k'=>'keywords'
        );
        
        $this->obj_terms = array(
            'i'=>'id_',
            'A'=>'active_',
            'u'=>'updated_',
            'o'=>'order_',
            'O'=>'owner_',
            'c'=>'created_',
            'P'=>'perm_',
            'r'=>'rank_'
        );
        
        $this->base_terms = array(
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
        
        foreach($this->obj_terms as $key=>$value){
            $this->obj_terms[$value]=$key;
        }

    }
    
    public function add_tier($tier){
        $this->config_tier($tier, true);
    }

    private function config_tier($tier, $add=false){

        if(class_exists('Nog')){Elog::O();}

        $this->current_tier = $tier;

        $current_tables = array(

            'data'=>'nook_'.$tier.'_data',
            'match'=>'nook_'.$tier.'_match',
            'term'=>'nook_'.$tier.'_terms',
            'meta'=>'nook_'.$tier.'_obj'

        );

        $this->current_tables = $current_tables;

        $exists = in_array($current_tables['data'], $this->tier_list);

        if($add && !$exists){
            
            $this->data_cache[$tier] = array();
            $this->terms_cache[$tier] = array();

            $this->db_tables[] = $current_tables['match'];
            $this->db_tables[] = $current_tables['terms'];
            $this->db_tables[] = $current_tables['obj'];

            self::$CI->db->query('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');

            //self::$CI->db->query('DROP TABLE IF EXISTS `'.$current_tables['match'].'`');
            self::$CI->db->query('CREATE TABLE `'.$current_tables['match'].'` ( `oid` int(10) unsigned NOT NULL, `att` int(10) unsigned NOT NULL, `value` int(10) unsigned NOT NULL, PRIMARY KEY (`oid`,`name`,`value`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

            //self::$CI->db->query('DROP TABLE IF EXISTS `'.$current_tables['terms'].'`');
            self::$CI->db->query('CREATE TABLE `'.$current_tables['terms'].'` ( `tid` int(10) unsigned NOT NULL, `value` varchar(255) NOT NULL, PRIMARY KEY (`tid`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

            //self::$CI->db->query('DROP TABLE IF EXISTS `'.$this->current_tables['obj'].'0`');
            self::$CI->db->query('CREATE TABLE `'.$this->current_tables['obj'].'0` ( `oid` int(10) unsigned NOT NULL, `active_` tinyint(1) unsigned NOT NULL, `rank_` tinyint(1) unsigned NOT NULL, `updated_` bigint(15) unsigned NOT NULL, `created_` bigint(15) unsigned NOT NULL, `owner_` int(10) unsigned NOT NULL, `order_` int(10) unsigned NOT NULL, `perm_` int(10) unsigned NOT NULL, PRIMARY KEY (`oid`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

            
        }elseif(!$exists){

            if(class_exists('Nog')){Elog::M('tables do not exsist');}  
            if(class_exists('Nog')){Elog::C();}  
            exit;

        }elseif(!isset($this->data_cache[$tier])){
            
            $this->data_cache[$tier] = array();
            $this->terms_cache[$tier] = array();
            
            foreach($this->search_terms as $key=>$value){
                $this->terms_cache[$tier][$value]=$key;
            }

            foreach($this->base_terms as $key=>$value){
                $this->terms_cache[$tier][$value]=$key;
            }
            
            foreach($this->obj_terms as $key=>$value){
                $this->terms_cache[$tier][$value]=$key;
            }
            
            $term_result = self::$CI->db->get($current_tables['terms']);
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
                    $table = $this->current_tables['obj'];
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

        
    private function add_new_term($att){
        
        $att = self::$CI->db->escape($att);
        $new_att = $this->get_next_index('tid');       
        self::$CI->db->insert($tables['terms'], array('tid'=>$new_att, 'value'=>$att)); 
        $this->term_cache[$tier][$new_att] = $att;         
        $this->term_cache[$tier][$att] = $new_att; 
        return $new_att;
        
    }
    

    
    public function save($tier, $obj){
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
            
            self::$CI->db->where('oid',  $id);
            self::$CI->db->update($tables['obj'], array('active_' => $obj['active_'], 'rank_'=>'0', 'updated_'=>$obj['updated_'], 'created_' => $obj['created_'], 'owner_'=>$obj['owner_'], 'order_' => $obj['order_'], 'perm_' => $obj['perm_'])); 
 
            unset($obj['active_']);
            unset($obj['updated_']);
            unset($obj['created_']);
            unset($obj['owner_']);
            unset($obj['order_']);
            unset($obj['perm_']);
            unset($obj['id_']);
            
        }else{
            
            self::$CI->db->trans_start();

            $id = $this->get_next_index('oid');
            
            $new = true;
           
            if(!isset($obj['active_'])){$obj['active_'] = 1;}
            $obj['created_'] = time();
            $obj['updated_'] = time();
            if(!isset($obj['perm_'])){$obj['perm_'] = '';}
            if(!isset($obj['order_'])){$obj['order_'] = 0;}
            if(!isset($obj['owner_'])){$obj['owner_'] = $this->user;}
            
            self::$CI->db->insert($tables['obj'], array('oid'=>$id, 'active_' => $obj['active_'], 'rank_'=>'0',  'updated_'=>$obj['updated_'], 'created_' => $obj['created_'], 'owner_'=>$obj['owner_'], 'order_' => $obj['order_'], 'perm_' => $obj['perm_'])); 

            unset($obj['active_']);
            unset($obj['updated_']);
            unset($obj['created_']);
            unset($obj['owner_']);
            unset($obj['order_']);
            unset($obj['perm_']);
            unset($obj['id_']);
        }   
        


            
        $this->obj_cache[$tier][$id] = $obj;
        
        self::$CI->db->delete($tables['match'], array('oid' => $id));
        
        $converted_obj = array();
        
        $new_att;
        $searchable;
        $new_value;
        
        foreach($obj as $att => $value){ 
            
            $searchable = false;
            if(isset($this->search_terms[$att])){
                $searchable = true;
            }elseif(isset($this->term_cache[$tier][$att])){
                $new_att = $this->term_cache[$tier][$att];
            }else{
                $new_att = $this->add_new_term($att);
            }
            
            if(is_array($value)){
                
                
                
                if($searchable){

                    foreach($value as $key=>$entry){
                        if(isset($this->term_cache[$tier][$value])){
                            $new_value = $this->term_cache[$tier][$value];
                        }else{
                            $new_value = $this->add_new_term($value);
                        }

                        self::$CI->db->insert($tables['match'], array('oit'=>$id, 'att'=>$new_att, 'value'=>$new_value)); 

                    }
                }else{
                    
                    $new_array = array();
                    foreach($value as $key=>$entry){
                        $entry = str_replace('"', '¸½', $entry) ."×";
                        $new_array[$key] = self::$CI->db->escape($entry);
                    }
                    $converted_obj[$new_att] = $new_array;
                }
                
                
                
            }else{
                

                if($searchable){
                    if(isset($this->term_cache[$tier][$value])){
                        $new_value = $this->term_cache[$tier][$value];
                    }else{
                        $new_value = $this->add_new_term($value);
                    }
                    self::$CI->db->insert($tables['match'], array('oit'=>$id, 'att'=>$new_att, 'value'=>$new_value)); 
                }else{
                    $new_value = str_replace('"', '¸½', $value) ."×";
                    $new_value = self::$CI->db->escape($new_value);
                    $converted_obj[$new_att] = $new_value;
                }
             
                
                
            }
            
            
        }

        $json_str = json_encode($converted_obj);
        
        $data_size = strlen($json_str);
        
        if($data_size <= 200){
            $rank = 0;
            $data_type = 'varchar(255)';
        }elseif($data_size <= 1000){
            $rank = 1;
            $data_type = 'varchar(1100)';
        }elseif($data_size <= 3000){
            $rank = 2;
            $data_type = 'varchar(3200)';
        }elseif($data_size <= 65030){
            $rank = 3;
            $data_type = 'TEXT(65535)';
        }elseif($data_size <= 16770210){
            $rank = 4;
            $data_type = 'MEDIUMTEXT(16777215)';
        }else{
            $rank = 5;
            $data_type = 'LONGTEXT(4294967295)';
        }
        
        $data_table_name = $this->current_tables['obj'].$rank;
        
        if(!in_array($table_name, $this->db_tables)){
            
            //self::$CI->db->query('DROP TABLE IF EXISTS `'.$table_name.'`');
            self::$CI->db->query('CREATE TABLE `'.$data_table_name.'` ( `oid` int(10) unsigned NOT NULL, `value` varchar(255) NOT NULL, PRIMARY KEY (`did`)) ENGINE=InnoDB DEFAULT CHARSET=latin1');
            $this->db_tables[] = $data_table_name;
        }

        $data_table = check_data_table($data_size);
        
        for($i = 0;$i <= 5; $i +=1 ){
            if(in_array($tables['data'].$i, $this->db_tables)){
                self::$CI->db->delete($tables['data'].$i, array('oid' => $id));
            }
        }

        self::$CI->db->insert($data_table_name, array('oid'=>$id, 'value'=>$json_str)); 
        
        self::$CI->db->where('oid',  $id);
        self::$CI->db->update($tables['obj'], array('rank_' => $rank));
        
        if(class_exists('Nog')){Elog::O();}

    }   
    
    
    public function set_bounds($start, $end){
        
        $this->query_start = $start;
        $this->query_length = $end;
        
    }
    
    /*
     * Just tier: returns all objects in table
     * just $att as int: returns object with that id
     * just $att as str: returns search results from data table.
     * just $att as array: returns all listed ids
     * $att as str and value as str: returns all matching objects
     * 
     * 
     */
    
    public function get($tier, $att='', $value='', $as_array=true){
        
        $this -> config_tier($tier);
        
        if($att=''){
        
            $query = self::$CI->db->select('oid');
            $query = self::$CI->db->get($this->current_tables['data']);
            $result = $query->result_array();
            $this->load_object();        
            
        }elseif($value==''){
            if(is_numeric($att)){
                
                $this->load_object($att);
                if($as_array){
                    $ret[] = $this->obj_cache[$tier][$att];
                    return $ret;
                }else{
                    return $this->obj_cache[$tier][$att];
                }
                
            }elseif(is_string($att)){
                $list = array();
                $this->db->like('value', $att); 
                $query = self::$CI->db->get($this->current_tables['data'], $this->query_start, $this->query_length);
                $result = $query->result_array();
                foreach($result as $value){
                    $list[] = $value['oid'];
                }
                $this->load_object($list);
                if($as_array){
                    $ret = array();
                    foreach($list as $value){
                        $ret[$value] = $this->obj_cache[$tier][$value];
                    }
                    return $ret;
                }else{
                    return $this->obj_cache[$tier][$list[0]];
                }
                
                
            }elseif(is_array($att)){
                $this->load_object($att);
                if($as_array){
                    $ret = array();
                    foreach($att as $value){
                        $ret[$value] = $this->obj_cache[$tier][$value];
                    }
                    return $ret;
                }else{
                    return $this->obj_cache[$tier][$att[0]];
                }
            }
        }
        
        $this->query_start = 0;
        $this->query_length = 10000;
        
    }
    
    private function load_object($target = false){
        
        $tier = $this->current_tier;
        
        if(class_exists('Nog')){Elog::O();} 
        
        if($target){
        
            if(!is_array($target)){
                $target = array('0' => $target);
            }

            $look_up = array();

            foreach($target as $value){
                if(!is_numeric($value)){
                    if(class_exists('Nog')){Elog::C();} 
                    return;
                }
                if(!isset($this->obj_cache[$tier][$value]) && !in_array($look_up[$value])){
                    $look_up[] = $value;
                }
            }

            if(count($look_up) < 1){
                if(class_exists('Nog')){Elog::C();} 
                return;
            }


            $this->db->where_in('oid', $look_up);

        }
        
        self::$CI->db->from($this->current_tables['data']);
        self::$CI->db->order_by("did", "asc");
        
        
        $query = self::$CI->db->get();
        $result = $query->result_array();
        
        if(!$result || !is_array($result) || count($result) < 1){
            if(class_exists('Nog')){Elog::C();} 
            return;
        }
        
        $build_list = array();
        
        foreach($result as $entry){
            $oid = $entry['oid'];
            if(isset($build_list[$oid])){
                $build_list[$oid] .= $entry['value'];
            }else{
                $build_list[$oid] = $entry['value'];
            }
        }
        
    
        
        foreach($build_list as $oid=>$raw){
            
            $raw_object = json_decode($raw, true);
            $new_object = array();
            
            foreach($raw_object as $att=>$value){
            
                $searchable = false;
                
                if(isset($this->search_terms[$att])){
                    $new_att = $this->search_terms[$att];
                    $searchable = true;
                }elseif(isset($this->term_cache[$tier][$att])){
                    $new_att = $this->term_cache[$tier][$att];
                }else{
                    if(class_exists('Nog')){Elog::C();} 
                    return;
                }
                
                
                if(is_array($value)){
                    
                    $new_array = array();

                    if($searchable){
                        foreach($value as $key=>$entry){
                            $new_array[$key] = $this->term_cache[$tier][$entry];
                        } 
                    }else{
                        foreach($value as $key=>$entry){
                            $new_array[$key] = substr(str_replace('¸½', '"',$entry),0,-1);
                        }
                    }

                    $new_object[$new_att] = $new_array;

                }else{
               
                    if($searchable){

                        $new_object[$new_att] = $this->term_cache[$tier][$value];

                    }else{

                        $new_object[$new_att] = substr(str_replace('¸½', '"',$value),0,-1);

                    }
                    
                }
            
            }
            
            $this->obj_cache[$tier][$oid] = $new_object;

        }
        
    }

}