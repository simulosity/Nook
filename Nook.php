<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Nook{
    
    public $CI;
    
    public $file_path = '';
    
    public $current_tier;
    public $current_tables;

    public $base_terms;
    public $obj_terms;
    public $search_terms;
    
    public $table_terms = array();
    
    public $tier_list;
    
    public $term_cache = array();
    public $obj_cache = array();
    
    public $query_start = 0;
    public $query_length = 10000;
    public $query_sort = 'id_';
    public $query_sort_dir = 'asc';
    public $query_filter = array();
    public $query_filter_upper = array();
    public $query_search_terms = array();
    
    public $active_user=1;

    public function __construct(){

        $this->CI =& get_instance();
        $this->CI->load->database();
        
        $this -> file_path = APPPATH . '/cache/nook';

        $this->tier_list = $this->CI->db->list_tables();


        
        $this->obj_terms = array(
            '0'=>'id_',
            '1'=>'active_',
            '2'=>'updated_',
            '3'=>'created_',
            '4'=>'owner_',
            '5'=>'order_',
            '6'=>'perm_',
            '7'=>'rank_'
        );
        
        $this->search_terms = array(
            '8'=>'type',
            '9'=>'subtype',
            '10'=>'alias',
            '11'=>'keywords'
        );
        
        $this->base_terms = array(
            '12'=>'img',
            '13'=>'url',
            '14'=>'html',
            '15'=>'file',
            '16'=>'folder',
            '17'=>'desc',
            '18'=>'body',
            '19'=>'name',
            '20'=>'text',
            '21'=>'username',
            '22'=>'password',
            '23'=>'email',
            '24'=>'object'
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
            self::$CI->db->query('CREATE TABLE `'.$current_tables['match'].'` ( `id_` int(10) unsigned NOT NULL, `att` int(10) unsigned NOT NULL, `value` int(10) unsigned NOT NULL, PRIMARY KEY (`id_`,`name`,`value`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

            //self::$CI->db->query('DROP TABLE IF EXISTS `'.$current_tables['terms'].'`');
            self::$CI->db->query('CREATE TABLE `'.$current_tables['terms'].'` ( `tid` int(10) unsigned NOT NULL, `value` varchar(255) NOT NULL, PRIMARY KEY (`tid`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

            //self::$CI->db->query('DROP TABLE IF EXISTS `'.$this->current_tables['obj'].'0`');
            self::$CI->db->query('CREATE TABLE `'.$this->current_tables['obj'].'` ( `id_` int(10) unsigned NOT NULL, `1` tinyint(1) unsigned NOT NULL, `2` tinyint(1) unsigned NOT NULL, `3` bigint(15) unsigned NOT NULL, `4` bigint(15) unsigned NOT NULL, `5` int(10) unsigned NOT NULL, `6` int(10) unsigned NOT NULL, `7` int(10) unsigned NOT NULL, PRIMARY KEY (`id_`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

            
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
                case 'id_':
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
                if($id_term = tid){
                    return 100;
                }
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
           
            
            
            self::$CI->db->where('id_',  $id);
            self::$CI->db->update($tables['obj'], array('1'=>$obj['active_'], '2'=>$obj['updated_'], '3' => $obj['created_'], '4'=>$obj['owner_'], '5' => $obj['order_'], '6' => $obj['perm_'], '7'=>0)); 
 
            unset($obj['active_']);
            unset($obj['updated_']);
            unset($obj['created_']);
            unset($obj['owner_']);
            unset($obj['order_']);
            unset($obj['perm_']);
            unset($obj['id_']);
            
        }else{
            
            self::$CI->db->trans_start();

            $id = $this->get_next_index('id_');
            
            $new = true;
           
            if(!isset($obj['active_'])){$obj['active_'] = 1;}
            $obj['created_'] = time();
            $obj['updated_'] = time();
            if(!isset($obj['perm_'])){$obj['perm_'] = '';}
            if(!isset($obj['order_'])){$obj['order_'] = 0;}
            if(!isset($obj['owner_'])){$obj['owner_'] = $this->user;}
            
            self::$CI->db->insert($tables['obj'], array('id_'=>$id, '1'=>$obj['active_'], '2'=>$obj['updated_'], '3' => $obj['created_'], '4'=>$obj['owner_'], '5' => $obj['order_'], '6' => $obj['perm_'], '7'=>0)); 
 
            unset($obj['active_']);
            unset($obj['updated_']);
            unset($obj['created_']);
            unset($obj['owner_']);
            unset($obj['order_']);
            unset($obj['perm_']);
            unset($obj['id_']);
        }   

        $this->obj_cache[$tier][$id] = $obj;
        
        self::$CI->db->delete($tables['match'], array('id_' => $id));
        
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
                        $array_added = array();
                        if(!in_array($new_value, $array_added)){
                            self::$CI->db->insert($tables['match'], array('oit'=>$id, 'att'=>$new_att, 'value'=>$new_value)); 
                            $array_added[] = $new_value;
                        }
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
            self::$CI->db->query('CREATE TABLE `'.$data_table_name.'` ( `id_` int(10) unsigned NOT NULL, `value` varchar(255) NOT NULL, PRIMARY KEY (`did`)) ENGINE=InnoDB DEFAULT CHARSET=latin1');
            $this->db_tables[] = $data_table_name;
        }

        $data_table = check_data_table($data_size);
        
        for($i = 0;$i <= 5; $i +=1 ){
            if(in_array($tables['data'].$i, $this->db_tables)){
                self::$CI->db->delete($tables['data'].$i, array('id_' => $id));
            }
        }

        self::$CI->db->insert($data_table_name, array('id_'=>$id, 'value'=>$json_str)); 
        
        self::$CI->db->where('id_',  $id);
        self::$CI->db->update($tables['obj'], array('7' => $rank));
        
        if(class_exists('Nog')){Elog::O();}

    }   

    
    public function query_length($length){
       $this->query_length = $length; 
    }
    
    public function query_start($start){
        $this->query_start = $start;
    }
    
    public function query_sort($sort, $dir = 'asc'){
        $this->query_sort = $sort;
        switch($dir){
            case 'a':
            case '+':
                $dir = 'asc';
            break;
            case 'd':
            case '-':
                $dir = 'desc';
            break;
        }
        $this->query_sort_dir = $dir;
    }
    
    public function query_filter($att, $value, $upper = false){

        if($att == 'id_'){
            
            $this->query_filter['_id'] = $value;
            
        }elseif(in_array($this->obj_terms)){
            
            $id = $this->obj_terms[$att];
            
            if($upper === false){
                $this->query_filter[$id] = $value;
            }else{
                $this->query_filter[$id.' >='] = $value;
                $this->query_filter[$id.' <='] = $upper;
            }
            
        }elseif(in_array($this->search_terms)){
            
            $id = $this->search_terms[$att];
            
            $this->query_search_terms[$id] = $value;
            
        } 
        
        $this->query_filter[$id] = $value;
    }
    
    /*
     * Just tier: returns all objects in table
     * just $att as int: returns object with that id
     * just $att as str: returns search results from data table.
     * just $att as array: returns all listed ids
     * $att as str and value as str: returns all matching objects
     */
    
    public function get($tier, $value='', $as_array=true){
        
        $this -> config_tier($tier);
        
        foreach($this->query_search_terms as $att=>$value1){
            
            $this->db->select('id_');
            if(is_array($value1)){
                $newTerms = array();
                foreach($value1 as $value2){
                    $newTerms[] = $this->term_cache[$tier][$value2];
                }
                $this->db->where_in($att, $newTerms);
            }else{
                $this->db->where($att, $this->term_cache[$tier][$value1]);
            }
            
            $query = self::$CI->db->get($this->current_tables['match']);
            $matches = $query->result_array();
            $new_matches = array();
            
            foreach($matches as $value3){
                $new_matches[] = $value3['id_'];
            }
            
            $this->db->where_in('id_', $new_matches);
            
        }
        
        if($value=''){
            
            self::$CI->db->order_by($this->query_sort, $this->query_sort_dir);
            foreach($this->query_filter as $att=>$value){
                if(is_array($value)){
                    $this->db->where_in($att, $value);
                }else{
                    $this->db->where($att, $value);
                }
            }
            $query = self::$CI->db->get($this->current_tables['obj'],
                    $this->query_start,
                    $this->query_length);

            $obj_query_result = $query->result_array();    
            
        }elseif(is_numeric($value)){
                
            foreach($this->query_filter as $att=>$value){
                if(is_array($value)){
                    $this->db->where_in($att, $value);
                }else{
                    $this->db->where('$att', $value);
                }
            }
            $this->db->where('id_', $value);
            $query = self::$CI->db->get($this->current_tables['obj'],
                    $this->query_start,
                    $this->query_length);

            $obj_query_result = $query->result_array();
            
        }elseif(is_string($att)){
            
            $result = preg_replace("/[^a-zA-Z0-9\s\.\$#%&_-':/]+/", "", is_string($att));
            $result = preg_replace("/\s+/", " ", is_string($att));
            $result_array = explode(' ', $result);
            
            $search_results = array();
            
            for($i = 5;$i >= 0; $i -=1 ){
                if(in_array($tables['data'].$i, $this->db_tables)){
                    
                    $this->db->select('id_');
                    
                    foreach($result_array as $entry){
                        $this->db->like('value', $entry);
                    }
                    
                    $query = self::$CI->db->get($this->current_tables['data']);
                    $inner_result = $query->result_array();
                    foreach($inner_result as $value){
                        $search_results[] = $value['id_'];
                    }
                }
            }
            

            self::$CI->db->order_by($this->query_sort, $this->query_sort_dir);
            foreach($this->query_filter as $att=>$value){
                if(is_array($value)){
                    $this->db->where_in($att, $value);
                }else{
                    $this->db->where('$att', $value);
                }
            }
            $this->db->where_in('id_', $search_results);
            $query = self::$CI->db->get($this->current_tables['obj'],
                    $this->query_start,
                    $this->query_length);

            $obj_query_result = $query->result_array();

            
        }elseif(is_array($att)){
            
         

            self::$CI->db->order_by($this->query_sort, $this->query_sort_dir);
            foreach($this->query_filter as $att=>$value){
                if(is_array($value)){
                    $this->db->where_in($att, $value);
                }else{
                    $this->db->where('$att', $value);
                }
            }
            $this->db->where_in('id_', $att);
            $query = self::$CI->db->get($this->current_tables['obj'],
                    $this->query_start,
                    $this->query_length);

            $obj_query_result = $query->result_array();

        }
        
        if(count($obj_query_result > 0)){
            
            foreach($obj_query_result as $entry){
                $tar_id = $entry['id_'];
                $requested_object[$tar_id] = $entry;
                $this->$obj_cache[$tar_id] = $entry;
            }

            $this->load_object($requested_object);

        }  
                
        $this->query_start = 0;
        $this->query_length = 10000;
        $this->query_sort = 'id_';
        $this->query_sort_dir = 'asc';
        $this->query_filter = array();
        $this->query_filter_upper = array();
        $this->query_search_terms = array();
        
        
        
        
        
        
    }
    
    private function load_object($target){
        
        $tier = $this->current_tier;
        
        if(class_exists('Nog')){Elog::O();} 
        
        if($target){

            $look_up = array();
            $match_look_up = array();

            foreach($target as $key=>$value){
                if(!isset($this->obj_cache[$tier][$value]) && !in_array($look_up[$value])){
                    $look_up[$value['r']][] = $key;
                    $match_look_up[] = $key;
                }
            }

            if(count($look_up) < 1){
                if(class_exists('Nog')){Elog::C();} 
                return;
            }

        }
        
        $match_list = array();
        
        $this->db->where_in('id_', $match_look_up);
        $query = self::$CI->db->get($this->current_tables['match']);
        $matches = $query->result_array();

        foreach($matches as $entry){
            $match_id = $entry['id_'];
            $match_att = $this->search_terms[$entry['att']];
            $match_value = $this->terms_cache[$tier][$entry['value']];
            if(isset($match_list[$match_id][$match_att])){
                if(is_array($match_list[$match_id][$match_att])){
                    $match_list[$match_id][$match_att][] = $match_value;
                }else{
                    $old = $match_list[$match_id][$match_att];
                    $match_list[$match_id][$match_att] = array();
                    $match_list[$match_id][$match_att][] = $old;
                    $match_list[$match_id][$match_att][] = $match_value;
                }
                
            }else{
                
                $match_list[$match_id][$match_att] = $match_value;
                
            }


        }
        
        $bulk_results = array();
        
        foreach($look_up as $key=>$value){
            $this->CI->db->where_in('id_', $value);
            $this->CI->db->from($this->current_tables['data'].$key);
            $query = $this->CI->db->get();
            $bulk_results = array_merge($bulk_results, $query->result_array());
        }

        if(!$bulk_results || count($bulk_results) < 1){
            if(class_exists('Nog')){Elog::C();} 
            return;
        }

        foreach($bulk_results as $raw){
            
            $raw_object = json_decode($raw['value'], true);
            $id_ = $raw['id_'];
            $obj_data = $target['id_'];
            unset($obj_data['id_']);
            $raw_object = array_merge($raw_object, $obj_data);

            foreach($raw_object as $att=>$value){

                if(isset($this->term_cache[$tier][$att])){
                    $new_att = $this->term_cache[$tier][$att];
                }else{
                    if(class_exists('Nog')){Elog::C();} 
                    return;
                }

                if(is_array($value)){
                    
                    $new_array = array();

                    foreach($value as $key=>$entry){
                        $new_array[$key] = substr(str_replace('¸½', '"',$entry),0,-1);
                    }
              
                    $new_object[$new_att] = $new_array;

                }else{


                    $new_object[$new_att] = substr(str_replace('¸½', '"',$value),0,-1);

                }
            
            }
            
            
            if(isset($match_list[$id_])){
                $new_object = array_merge($new_object, $match_list[$id_]);
            }

                

            
            
            $this->obj_cache[$tier][$id_] = $new_object;

        }
        
    }

}