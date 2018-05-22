<meta content="UTF-8">
<?php

class GetContent {
	
	private $url;
	private $html;
	private $links = array();
	
	
	function GetContent(){
		
		$this->SetUrl();
		$this->SetMainAddress($this->url);
		$this->FindLinks();
		
	}
	function SetUrl(){
		
		$this->url = "https://pt.wikihow.com";
		
	}
	function SetMainAddress($url) {
		
		$options = array(
			'http'=>array(
				'methods'=>"GET",
				'headers'=>"User-Agent: howBot/0.1\n"
			)
		);
		
		$context = stream_context_create($options);
		
		$this->html = file_get_contents($url, false, $context);
	}
	function FindLinks() { //crawler
	
		$dom = new DOMDocument();
		@$dom->loadHTML($this->html);
		
		$xpath = new DOMXPath($dom);
		$hrefs = $xpath->evaluate("/html/body//a");
		
		$i = 0;
		$l = 0;
		
		while ($i < $hrefs->length) {
			
			$href  = $hrefs->item($i);
			
			$href = $href->getAttribute('href');	
			
			if (substr($href,0,1)!="#" && substr($href,0,1)!="" && preg_match("/Categoria/",$href)) {
				
				
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
	function FollowLinks ($link){
		
		
		
		
		
	}	
	function FilterTitle($url){

		$cap_content = strstr($url,'<h1 class="firstHeading no_toc">');
		$cap_content = str_replace('<h1 class="firstHeading no_toc">',"",$cap_content);
		$cap_content = strstr($cap_content,'">');
		$cap_content = str_replace('">',"",$cap_content);
		$cap_content = strstr($cap_content,'</a></h1>',true);

		echo $cap_content; 
		
	}
	//function FilterIntro();
	
	//function FilterSteps();
	
	function Test(){
		
		for($i=0;$i<count($this->links);$i++){
			echo $this->links[$i]."\n";
		}
	}
	
}

$a = new GetContent;
$a->Test(); 
?>
