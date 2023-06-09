<?php
defined('BASEPATH') or exit('No direct script access allowed');

$Domain = $_SERVER['SERVER_NAME'];
$ref = $_SERVER['HTTP_REFERER'];
$refData = parse_url($ref);

if($refData['host'] !== $Domain) {
  die("No direct script access allowed");
}

class Series extends Controller
{
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");
        $Config['title']            = TITLE;
        $Config['description']      = DESC;
        $Config['nav']              = 'series';
        $Config['page']             = 'serie';


        $Categories = $this->db->from('categories')->orderby('name','ASC')->all();
        
        
        // Filter
        if($_POST['search']) {
            $Filter['search'] = $_POST['search'];
            header("location: ".APP.'/admin/'.$Config['nav'].'?filter='.json_encode($Filter));
        }
        if($_POST['_ACTION'] == 'filter') {
            foreach ($_POST as $key => $value) {
                if($value) {
                    $Filter[$key] = $value;
                }
            }
            if(count($Filter) > 1) {
                header("location: ".APP.'/admin/'.$Config['nav'].'?filter='.json_encode($Filter));
            } else {
                header("location: ".APP.'/admin/'.$Config['nav']);
            }
        }

        $Filter             = json_decode($_GET['filter'], true);  
        

        $FilterPermissions = array(
            'status',
            'featured',
            'category',
            'search'
        );
        if(count($Filter) >= 1) {
            $SearchPage     = '?filter='.htmlspecialchars($_GET['filter']).'&';
        }else{
            $SearchPage     = '?';
        } 
 
        $Where = 'WHERE posts.type = "serie" AND '; 
        if($Filter['_ACTION'] == 'filter') {  
            foreach ($Filter as $key => $value) {
                if(in_array($key, $FilterPermissions)) {
                    if($key == 'category') {
                        $Where .= 'posts_category.category_id = "'.$value.'" AND ';
                        $Join  .= 'LEFT JOIN posts_category ON posts_category.content_id = posts.id';
                    }else{
                        $Where .= ' posts.'.$key.' = "'.$value.'" AND ';
                    }
                }
            }
            $Where = rtrim($Where,' AND ');  
        } elseif($Filter['search']) { 
            $Where .= 'posts.title LIKE "%'.$Filter['search'].'%" AND'; 
            $Where = rtrim($Where,' AND ');  
        }
        $Where = rtrim($Where,' AND ');  
        if($Filter['sortable']) { 
            $OrderBy = 'ORDER BY posts.id '.$Filter['sortable'];
        }else{
            $OrderBy = 'ORDER BY posts.id DESC';
        }      
        // Query 
        $TotalRecord        = $this->db->from(null,'
            SELECT 
            COUNT(posts.id) as total 
            FROM `posts` 
            '.$Join.'
            '.$Where.'')
            ->total(); 
        $LimitPage          = $this->db->pagination($TotalRecord, PAGE_LIMIT, PAGE_PARAM); 
   
        $Listings = $this->db->from(null,'
            SELECT 
            posts.id, 
            posts.title, 
            posts.image, 
            posts.status,
            posts.created,
            categories.name
            FROM `posts` 
            LEFT JOIN posts_category ON posts_category.content_id = posts.id  
            LEFT JOIN categories ON categories.id = posts_category.category_id  
            '.$Where.'
            GROUP BY posts.id
            '.$OrderBy.'
            LIMIT '.$LimitPage['start'].','.$LimitPage['limit'])
            ->all();
        $Pagination         = $this->db->showPagination(APP.'/admin/'.$Config['nav'].$SearchPage.'page=[page]');
        
    
        if(isset($AuthUser['account_type']) == 'admin') {
        // Delete
        $Submit         = json_decode($_GET['submit'], true);  
        if($Submit['id'] AND $Submit['_ACTION'] == 'delete') {
            $this->db->delete('posts')->where('id',$Submit['id'],'=')->done(); 
            $Notify['type']     = 'success';
            $Notify['text']     = __('Deletion is successful'); 
            $this->notify($Notify);
            header("location: ".APP.'/admin/series');
        }
        } else {             	$Notify['type']     = 'danger';
            	$Notify['text']     = __('You are not logged in!'); 
            	$this->notify($Notify);
            	header("location: ".APP.'/login'); }
        $this->setVariable('Listings', $Listings); 
        $this->setVariable('Pagination', $Pagination); 
        $this->setVariable('Filter', $Filter); 
        $this->setVariable('Categories', $Categories); 
        $this->setVariable('TotalRecord', $TotalRecord); 
        $this->setVariable("Config", $Config);
        $this->view('series', 'admin');
    }

}
