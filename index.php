<?php
if (isset($_FILES['toqrcode'])) {
	ToQrCode($_FILES['toqrcode']);
}elseif (isset($_FILES['fromqrcode'])) { 
	$array = FromQrCode($_FILES['fromqrcode']);
	
/*	echo '
	Base64 File : <textarea>'.$array['content'].'</textarea><br>
	<form enctype="multipart/form-data" action="dl.php" method="POST">
    	<input name="f" type="hidden" value="'.$array['name'].'"/>
    	<input name="s" type="hidden" value="'.$array['size'].'"/>
    	<input name="c" type="hidden" value="'.$array['content'].'"/>
    	<br><br>
    	<input type="submit" value="Download !" />
	</form>
	';*/
		echo  $array['content'];
}else{
	?>
<center>
	<form enctype="multipart/form-data" action="" method="POST">
    	<label for="toqrcode">File To QrcodeVideo: </label><input name="toqrcode" id="toqrcode" type="file" /><br><br>
    	<input type="submit" value="Go on!" />
	</form>
	<br><br><h3>OR</h3><br><br>
	<form enctype="multipart/form-data" action="" method="POST">
    	<label for="fromqrcode">QrcodeVideo to File: </label><input name="fromqrcode" id="fromqrcode" type="file" /><br><br>
    	<input type="submit" value="Go on!" />
	</form>
    	
</center>
	<?php
}


function ToQrCode($file){
	$text = base64_encode(file_get_contents($file['tmp_name']));
	$file_extn = substr($file['name'], strrpos($file['name'], '.',0)+1);
	$max = 1024;
	$current = strlen($text);

	$nb = floor($current / $max) + 1;

	$id = uniqid();
	$tmpfold = dirname(__FILE__).'/tmp/'.$id;
	mkdir($tmpfold);

	$videopath = dirname(__FILE__).'/videos/'.$id.'.'.$file_extn.'.mpg';

	for ($i=0; $i < $nb; $i++) { 
		$poss = $i * $max;
		exec('qrencode -o '.$tmpfold.'/'.$i.'.png "'.substr($text, $poss,$max).'"');
		//file_put_contents($fold.'/img/'.$i.'.png', file_get_contents('http://chart.googleapis.com/chart?cht=qr&chs=300x300&choe=UTF-8&chld=H&chl='.urlencode(substr($text, $poss,$max))));
	}
	exec('ffmpeg -f image2 -i '.$tmpfold.'/%d.png '.$videopath);

	rmdir_recursive($tmpfold);
	header("Content-Type: video/mpeg; name=\"" . basename($videopath) . "\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".filesize($videopath));
	header("Content-Disposition: attachment; filename=\"" . basename($videopath) . "\"");
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	readfile($videopath);
	exit();
	//return '<a href="videos/'.$id.'.'.$file_extn.'.mpg" >Download Vids '.$id.' Here</a><br><a href="">Home</a>';
}

function FromQrCode($file){
	$uploaddir = dirname(__FILE__).'/videos/';
	$uploadfile = $uploaddir.basename($file['name']);
	move_uploaded_file($file['tmp_name'], $uploadfile);

	$file_extn = substr($file['name'], strrpos($file['name'], '.')+1);
	$fold = 'tmp/'.uniqid();
    mkdir($fold);
	exec('ffmpeg -i '.$uploadfile.' '.$fold.'/%d.png');
	$files = glob($fold.'/*.png', GLOB_BRACE);
	natcasesort($files);
	$out = '';
	foreach($files as $file) {
 	 $out .= exec('zbarimg -D -q --raw '.realpath($file));
	}
	rmdir_recursive($fold);
	unlink($uploadfile);

 	return array('name' => 'Download.'.$file_extn, 'size' => strlen(base64_decode($out)),'content' => $out);
}



function rmdir_recursive($dir)
{
	$dir_content = scandir($dir);
	if($dir_content !== FALSE){
		foreach ($dir_content as $entry)
		{
			if(!in_array($entry, array('.','..'))){
				$entry = $dir . '/' . $entry;
				if(!is_dir($entry)){
					unlink($entry);
				}
				else{
					rmdir_recursive($entry);
				}
			}
		}
	}
	rmdir($dir);
}
?>