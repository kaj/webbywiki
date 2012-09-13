<?php

function tidy_html($html) {

	$config = array(
		'indent' => 2, # auto
		'output-xhtml' => true,
		'doctype' => 'transitional',
		'wrap' => 120
	);

	$tidy = new tidy;
	$tidy->parseString($html, $config, 'utf8');
	$tidy->cleanRepair();

	return $tidy;

}

function wiki_format($str) {
	# Remove comments
	$str = preg_replace('/<!--(.+)-->/Us','',$str);

	# Remove dead (edit-links)
	$str = preg_replace('#<a href=.*action=edit.*>(.*)</a>#U','$1',$str);

	# Clean up links
	$str = str_replace('/wiki/','',$str);
	$str = str_replace('href="Stacken/','href="/',$str);

	# Remove misc stuff
	$str = str_replace('<a name="Stacken" id="Stacken"></a>','',$str);
	

	return $str;
}

?>
