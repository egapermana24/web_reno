<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SitemapCategories extends Controller {
    public function process() {
        $AuthUser   = $this->getVariable("AuthUser");
        $Route      = $this->getVariable("Route");
        if($Route->params->page == 1) {
            $PageStart = 0;
        } elseif($Route->params->page > 1) {
            $PageStart = ceil(($Route->params->page-1) * SITEMAP_PAGE);
        }
        $Listings = $this->db->from(null,'
            SELECT 
            categories.id,  
            categories.self
            FROM `categories`
            LIMIT '.$PageStart.','.SITEMAP_PAGE)
            ->all(); 
        if(count($Listings) == 0) {
            header('location:'.APP.'/404');
        }
        header("Content-Type: application/xml; charset=utf-8");
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($Listings as $Listing) {
            echo '<url>';
            echo '<loc>'.APP.'/category/'.$Listing['self'].'</loc>';
            echo '<lastmod>'.date("Y-m-d")."T".date("H:i:s").'</lastmod>';
            echo '<changefreq>hourly</changefreq>';
            echo '<priority>1.0</priority>';
            echo '</url>';
        }
        echo '</urlset>'; 
       

    }

}