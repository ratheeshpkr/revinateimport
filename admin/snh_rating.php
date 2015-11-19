<?php
		
	function pagination($url,$pagLimit,$size=10,$sort='desc'){
		for($i=1;$i<=$pagLimit; $i++){

		$uri = $url."?page=".$i."&size=".$size."&sort=".$sort;
		Renivate::rev_install_data($uri);
		}
	
	}
	pagination('https://porter.revinate.com/hotels/10470/reviews',252);
?>