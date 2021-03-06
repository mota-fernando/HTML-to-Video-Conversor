<meta content="UTF-8">
<?php
/*
@author Fernando Mota
@license licensed under MIT
@copyright Copyright (c) 2018 Fernando Mota https://github.com/mota-fernando
@package Html2video
@version 1.0.0-beta
*/
class GetContent 
{	
	private $url;
	private $html;
	private $links = array();
	private $title;
	private $description;
	private $images = array();
	private $jsonobj = array();
	private $i;
	
	public function GetContent(){
		
		$this->SetUrl();
		$this->SetMainAddress($this->url);
		$this->FindLinks();
		
	}
	private function SetUrl(){
		
		$this->url = "https://pt.wikihow.com";
		
	}
	private function SetMainAddress($url) {
		
		$options = array(
			'http'=>array(
				'methods'=>"GET",
				'headers'=>"User-Agent: howBot/0.1\n"
			)
		);
		
		$context = stream_context_create($options);
		
		$this->html = file_get_contents($url, false, $context);
	}
	private function FindLinks() { //crawler
	
		$dom = new DOMDocument();
		@$dom->loadHTML($this->html);
		
		$xpath = new DOMXPath($dom);
		$hrefs = $xpath->evaluate("/html/body//a");
		
		$i = 0;
		$l = 0;
		
		while ($i < $hrefs->length) {
			
			$href  = $hrefs->item($i);
			
			$href = $href->getAttribute('href');	
			
			if (substr($href,0,1)!="#" && substr($href,0,1)!="" && !preg_match("/Categoria/",$href) && !preg_match("/Especial/",$href) && !preg_match("/wikiHow/",$href)&& !preg_match("/Special/",$href)) {
				
				if (substr($href,0,1)=="/" && substr($href,0,2)!="//"){
					
					$href = parse_url($this->url,PHP_URL_SCHEME)."://".parse_url($this->url, PHP_URL_HOST).$href;
					$this->links[$l] = $href;
					$l++;
										
				}else if (substr($href,0,2)== "//"){
					
					$href = parse_url($this->url,PHP_URL_SCHEME).":".$href;
					$this->links[$l] = $href;
					$l++;
					
				}					
				
			} $i++;
				
		}	
	
	}

	private function FilterContent($url){

		$this->SetMainAddress($url);
				
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		@$dom->loadHTML($this->html);
		
		$title = $dom->getElementsByTagName("title");
		$this->title = $title->item(0)->nodeValue;
		
		$bodycontents = strstr($this->html,'<div id="bodycontents">');
		$bodycontents = strstr($bodycontents,'id="article_info_section"',true);
		$cap_content = strstr($bodycontents,'<p>');
		$cap_content = str_replace('<p>',"",$cap_content);
		$cap_content = strstr($cap_content,'</p>',true);
		
		$this->description = $cap_content;
		
		$cap_content = strstr($this->html,'}</script><script type="application/ld+json">');
		$cap_content = strstr($cap_content,'">');
		$cap_content = str_replace('">',"",$cap_content);
		$cap_content = strstr($cap_content,'</script>',true);
		
		$this->jsonobj = json_decode($cap_content);
		
		$tags = $dom->getElementsByTagName("div");
		$this->images = null;
		$this->i = 0;
		for ($i=0;$i < $tags->length;$i++) {
			$tag = $tags->item($i);
			if ($tag->getAttribute("id") == "bodycontents")
				$this->shownode($tags->item($i));
		}		
	}
	
	private function shownode($x) {
		
		foreach ($x->childNodes as $p)
		
		  if ($this->hasChild($p)) {			  		  
			  
			  $this->shownode($p);
			  
		  } elseif ($p->nodeType ==  XML_ELEMENT_NODE){
			  
		   	if ($p->nodeName == "img" && $p->getAttribute("src")){
				
				$this->images[$this->i] = $p->getAttribute("src")."<br>";

				$this->i++;
			}
			
		  }
		  
	}
	private function hasChild($p) {
		
		if ($p->hasChildNodes()) {
		
		foreach ($p->childNodes as $c) {
		
			if ($c->nodeType == XML_ELEMENT_NODE)
		
				return true;
			}
		}
		return false;
	}
	
	private function Test(){
			
		for($i=1;$i<2/*<count($this->links)*/;$i++){
			echo $this->links[$i]."<br>";
				$this->FilterContent($this->links[$i]);
			}
		
	}
	
	private function InsertIntoDB(){
		
		$conn = mysqli_connect("127.0.0.1","root","","html2video");
		
		for($i=0;$i<count($this->links);$i++){
			$this->FilterContent($this->links[$i]);
			if (!mysqli_query($conn, 
				
				"INSERT INTO content (	
									main_link,
									main_title,
									main_description,
									main_tags
								   )
				SELECT '".$this->links[$i]."',
							'".$this->title."',
							'".$this->description."',
							''
				WHERE NOT EXISTS(SELECT main_link
				FROM content
				WHERE main_link = '".
				$this->links[$i]."');"))
				echo "Error description: " . mysqli_error($conn);
				;
				
				$last_insert_id = mysqli_insert_id($conn);
				
			for($j=0;$j<count($this->jsonobj->step);$j++){
				
				mysqli_query($conn, "
				INSERT INTO content_method(
						method,
						content_id
				)VALUES(
						'Método " . $this->jsonobj->step[$j]->position . ": ". $this->jsonobj->step[$j]->name
						."',
						'".$last_insert_id."');");
						
				
				for($l=0;$l<count($this->jsonobj->step[$j]->itemListElement);$l++){
				
					if (!mysqli_query($conn, "				
						INSERT INTO content_detail(	
							content_method_id,
							image,
							step
						)VALUES(
							LAST_INSERT_ID(),
							'".$this->images[$l]."',
							'".$this->jsonobj->step[$j]->itemListElement[$l]->itemListElement->text."');
						"))					
					echo("Error description: " . mysqli_error($conn));
				}
			}
		}		
		mysqli_close($conn);
	}	
}
?>
