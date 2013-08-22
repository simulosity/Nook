<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Nook{
    
    public $CI;
    
    public $current_tier;
    public $current_tables;

    public $base_terms;
    
    public $table_terms = array();
    
    public $tier_list;
    
    public $active_user;
    
    
    public function __construct(){

        $this->CI =& get_instance();
        $this->CI->load->database();

        $this->tier_list = $this->CI->db->list_tables();

        $this->base_terms = array(
          'i'=>'id_',
          't'=>'type',
          's'=>'subtype',
          'a'=>'alias',
          'k'=>'keywords',
          'A'=>'active_',
          'u'=>'updated_',
          'o'=>'order_',
          'O'=>'owner_',
          'c'=>'created_',
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

            $this->db_tables[] = $current_tables['data'];
            $this->db_tables[] = $current_tables['match'];
            $this->db_tables[] = $current_tables['terms'];

            self::$CI->db->query('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');

            self::$CI->db->query('DROP TABLE IF EXISTS `'.$current_tables['data'].'`'); 
            self::$CI->db->query('CREATE TABLE `'.$current_tables['data'].'` ( `did` int(10) unsigned NOT NULL, `rid` int(10) unsigned NOT NULL, `value` varchar(255) NOT NULL, PRIMARY KEY (`did`)) ENGINE=InnoDB DEFAULT CHARSET=latin1');

            self::$CI->db->query('DROP TABLE IF EXISTS `'.$current_tables['match'].'`');
            self::$CI->db->query('CREATE TABLE `'.$current_tables['match'].'` ( `rid` int(10) unsigned NOT NULL, `name` int(10) unsigned NOT NULL, `value` int(10) unsigned NOT NULL, PRIMARY KEY (`rid`,`name`,`value`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

            self::$CI->db->query('DROP TABLE IF EXISTS `'.$current_tables['terms'].'`');
            self::$CI->db->query('CREATE TABLE `'.$current_tables['terms'].'` ( `tid` int(10) unsigned NOT NULL, `value` varchar(255) NOT NULL, PRIMARY KEY (`tid`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

        }elseif(!$exists){

            if(class_exists('Nog')){Elog::M('tables do not exsist');}  
            if(class_exists('Nog')){Elog::C();}  
            exit;

        }


        if(class_exists('Nog')){Elog::C();}  
        return $current_tables;
    }

    public function set_user($user){
        if(class_exists('Nog')){Elog::O();}

        $this->active_user = $user;

        if(class_exists('Nog')){Elog::C();}   
    }

    public function save($obj, $tier){
        if(class_exists('Nog')){Elog::O();}

            if(!is_array($obj) || count($obj) <= 2){
                if(class_exists('Nog')){Elog::M('save data is not valid');}
                if(class_exists('Nog')){Elog::C();}
                return null;
            }

        if(class_exists('Nog')){Elog::O();}

    }   
      
}