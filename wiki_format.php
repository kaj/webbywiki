<?php

function tidy_html($html) {

	$config = array(
		'indent' => 2, # auto
		'output-xhtml' => true,
		'doctype' => 'strict',
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
	$str = preg_replace('#<a href=.*title=Stacken([^&]+)&.*action=edit.*>(.*)</a>#U',
			    '<a href="$1">$2</a>', $str);

	# Clean up links
	$str = str_replace('"/wiki/Stacken','"',$str);

	# Remove misc stuff
	$str = str_replace('<a name="Stacken" id="Stacken"></a>','',$str);
	

	return $str;
}

function lang_menu($cnt, $lang) {
	$menu = "";
	foreach($cnt->ul->li as $li) {
		$row = $li->asXML();
		if (preg_match("#\[en\]#", $row) && !preg_match("#redlink#", $row)) {
			$row_en = $row;
		} else if (preg_match("#\[sv\]#", $row) && !preg_match("#redlink#", $row)) {
			$row_sv = $row;
		} else if (preg_match("#\[sv\]#", $row)) {
			$row_redlink = $row;
		}

		if (preg_match("#%break%#", $row)) {
			if ($lang == "sv") {
				if ($row_sv) {
					$menu .= str_replace("[sv]","",$row_sv);
				} else if ($row_en) {
					$menu .= $row_en;
				} else {
					$menu .= str_replace("[sv]","",$row_redlink);
				}
			} else {
				if ($row_en) {
					$menu .= str_replace("[en]","",$row_en);
				} else if ($row_sv) {
					$menu .= $row_sv;
				} else {
					$menu .= $row_redlink;
				}
			}

			unset($row_sv);
			unset($row_en);
		}

	}
	return $menu;
}

function menu_prepare($cnt) {
	$cnt = str_replace('&nbsp;','&#160;',$cnt);
	$xml = new SimpleXMLElement($cnt);
	$cnt = $xml->body->div->div->div->div->div[3];
	unset($cnt->h3);
	unset($cnt->div);
	return $cnt;
}

function flash_next_page($str) {
	$_SESSION['flash'] = $str;
}

function get_flash_message() {
	if (!isset($_SESSION['flash'])) return;
	$flash = $_SESSION['flash'];
	unset($_SESSION['flash']);
	return '<p class="flash">'.$flash.'</p>';
}

?>
