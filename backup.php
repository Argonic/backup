<?php

function backup($project){
	global $key;
	$path = realpath("{$project}/");
	
	$zip = new ZipArchive();
	$zip->open("{$project}-compress.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($path),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ($files as $name => $file){
		if (!$file->isDir()){
			$filePath = $file->getRealPath();
			$relativePath = substr($filePath, strlen($path) + 1);

			$zip->addFile($filePath, $relativePath);
		}
	}

	$zip->close();

	$string = file_get_contents("{$project}-compress.zip");

	$iv = mcrypt_create_iv(
		mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC),
		MCRYPT_DEV_URANDOM
	);

	$encrypted = 
		$iv .
		mcrypt_encrypt(
			MCRYPT_RIJNDAEL_128,
			hash('sha256', $key, true),
			$string,
			MCRYPT_MODE_CBC,
			$iv
		);
	
	$store = "backup/{$project}-backup";
	file_put_contents($store,$string);
	//unlink("{$project}-compress.zip");
}
function restore($project) {
	global $key;
	$store = "backup/{$project}-backup";
	$content = file_get_contents($store);
	$iv = substr($content, 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC));

	$decrypted = mcrypt_decrypt(
		MCRYPT_RIJNDAEL_128,
		hash('sha256', $key, true),
		substr($content, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC)),
		MCRYPT_MODE_CBC,
		$iv
	);
	
	file_put_contents("{$project}-compress.zip",$content);
	
	$zip = new ZipArchive();
	$res = $zip->open("{$project}-compress.zip");
	$zip->extractTo("restore/{$project}/");
	$zip->close();
	unlink("{$project}-compress.zip");
}

switch($_GET["action"]){
	case "backup":
		$project = $_GET["project"];
		backup($project);
		die("Backup Done ");
	break;
	case "restore":
		$project = $_GET["project"];
		restore($project);
		die("Restore Done ");
	break;
}
?>