<?php
// Print two names on the picture, which accepted by query string parameters.
	$nb = $_GET['nb'];
	
	header("Content-type: image/png");
	$image = imagecreatefrompng("images/pinKm.png");
	$w = imagesx($image);
	$h = imagesy($image);
	
	$sprite = imagecreatetruecolor($w*$nb, $h);
	imagealphablending($sprite, false);
	imagesavealpha($sprite,true);
	// Make it transparent
	$white = imagecolorallocatealpha($sprite, 255, 255, 255, 127);
	$color = imagecolorallocate($sprite, 255, 255, 255);
	imagealphablending($sprite, true);
	imagefill($sprite, 0, 0, $white);
	for ($i = 0; $i < $nb; $i++) {
		imagecopy($sprite,$image,$i*$w,0,0,0,$w,$h);
		// Calculate horizontal alignment for the text
		$boundingBox = imagettfbbox(6, 0, 'fonts/OpenSans-Semibold.ttf', sprintf("%d",$i+1));
		$x = floor(($w-$boundingBox[2]+$boundingBox[0])/2);
		$y = floor((15-$boundingBox[7]+$boundingBox[1])/2);
		imagettftext($sprite, 6, 0, $i*$w+$x, $y, $color, 'fonts/OpenSans-Semibold.ttf', sprintf("%d",$i+1));
	}
	
	// Return output.
	
	imagepng($sprite);
	imagedestroy($image);
	imagedestroy($sprite);
?>