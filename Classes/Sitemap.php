<?php

namespace Base; 

class Sitemap
{
    private $dom; 

    public function __construct() {
        $this->dom = new \DOMDocument();
    }

    private function appendUrl($location, $last_modified){
      $urlset = $this->dom->getElementsByTagName('urlset')->item(0);
      $url = $this->dom->createElement('url');
      $loc = $this->dom->createElement('loc', $location);
      $lastmod = $this->dom->createElement('lastmod', $last_modified);
      $url->appendChild($loc);
      $url->appendChild($lastmod);
      $urlset->appendChild($url);
    }

    // Add URL node
    public function addNode($location='', $last_modified='') 
    {
      try{
        if(!empty($location) && !empty($last_modified)){
          $this->dom->load('sitemap.xml');
          $this->appendUrl($location, $last_modified);
          $this->dom->save('sitemap.xml');
        }
        return TRUE;
      }
      catch (\Exception $e) {
        return $e->getMessage();
      }
    }

    // Update current URL nodes
    public function updateNodes($exclutions=array(), $urls=array()) 
    {
      try{
        $this->dom->load('sitemap.xml');
        $this->appendUrl(APP_URL, date('Y-m-d'));

        $routes = include("routes/web.php");
        foreach ($routes as $key => $value) {
          if(count(array_intersect(array_map('strtolower', explode('/', $key)), $exclutions)) == 0){
            $this->appendUrl(route($key), date('Y-m-d'));
          }
        }

        if(count($urls) > 0){
          foreach ($urls as $url) {
            $this->appendUrl($url[0], $url[1]);
          }
        }

        $this->dom->save('sitemap.xml');
        return $urls;
      }
      catch (\Exception $e) {
        return $e->getMessage();
      }
    }

    // Delete current URL nodes
    public function deleteNodes() 
    {
      try{
        $this->dom->load('sitemap.xml');
        $matchingElements = $this->dom->getElementsByTagName('url');
        $totalMatches     = $matchingElements->length;
        $elementsToDelete = array();
        for ($i = 0; $i < $totalMatches; $i++){
            $elementsToDelete[] = $matchingElements->item($i);
        }
        foreach ( $elementsToDelete as $elementToDelete ) {
            $elementToDelete->parentNode->removeChild($elementToDelete);
        }
        $this->dom->save('sitemap.xml');
        return TRUE;
      }
      catch (\Exception $e) {
        return $e->getMessage();
      }
    }

    public function refreshSitemap($exclutions=array(), $urls=array()) 
    {
      $this->deleteNodes();
      $urls = $this->updateNodes($exclutions, $urls);
      return $urls;
    }

}

?>