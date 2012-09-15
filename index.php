<?php

error_reporting(E_ALL & (~E_NOTICE));

require "wiki_format.php";

# Sätt språk
$lang = empty($_REQUEST['lang']) ? 'sv' : $_REQUEST['lang'];

##
# Hämta och formatera sidan
##

$page = isset($_GET['page']) ? '/'.$_GET['page'] : '';
$pageclass = 'stacken' . strtolower(preg_replace('/\W+/', '_', $page));

if ($lang == "sv") {
	$url = "http://wiki.stacken.kth.se/wiki/Stacken{$page}?printable=yes";
} else {
	$url = "http://wiki.stacken.kth.se/wiki/Stacken{$page}.{$lang}?printable=yes";
}

$content = @file_get_contents($url);

$content = str_replace('&nbsp;','&#160;',$content);

# Hack
# Lägg till __SHOW_TOC:Kalender__ på en sida för att visa
# TOC från Stacken/Menu/Kalender i stället för den på sidan.
$show_toc_menu = false;
$content = preg_replace_callback("#__SHOW_TOC:([a-z]+)__#i", function($m) {
	global $show_toc_menu;
	$show_toc_menu = $m[1];
	return '';
	}, $content);

# Specialsidor med genererat innehåll
$content = preg_replace("#__SPECIAL:([a-z/.]+)__#ie", "file_get_contents(dirname(__FILE__).'/$1')", $content);

if ($content) {
	$xml = new SimpleXMLElement($content);

	$content = $xml->body->div->div->div->div;
	$last_mod = $xml->body->div->div[3]->ul->li;

	# ta bort lite saker
	unset($content->h3[0]); #sideSub
	unset($content->div);

	$title = "" . $content->h1->span;

	if ($content->table) {
		$toc = $content->table->tr->td->ul->asXML();
		unset($content->table[0]);
	}

	unset($content->h1);
	unset($content->script);

	$content = $content->asXML();
} else {
	header("HTTP/1.0 404 Not Found");
	$content = "
		<div id=\"bodyContent\">
			<p>The requested URL {$page} was not found on this server.</p>
		</div>
		";
	$title = "404: Not Found";
}

##
# Hämta och formatera menyn
##

$menu_url = "http://wiki.stacken.kth.se/wiki/Stacken/Menu?printable=yes";
$cnt = file_get_contents($menu_url);
$cnt = menu_prepare($cnt);
$menu = lang_menu($cnt);

###
# Hämta och formatera sidmeny (om den finns)
##

preg_match("#/([a-z0-9]+)#i", $page, $m);
$menu_url = "http://wiki.stacken.kth.se/wiki/Stacken/{$m[1]}/Menu?printable=yes";
$cnt = @file_get_contents($menu_url);
if (!empty($cnt) && !empty($page)) {
	$cnt = menu_prepare($cnt);
	$menu_sub = "<h2>".ucfirst($m[1])."</h2>";
	$menu_sub .= lang_menu($cnt);
}

###
# Hämta och formatera special-TOC
# (den skriver över vanlig TOC)
##

if ($show_toc_menu) {
	$url = "http://wiki.stacken.kth.se/wiki/Stacken/Menu/".$show_toc_menu."?printable=yes";
	$str = file_get_contents($url);
	$str = str_replace('&nbsp;','&#160;',$str);
	$xml = new SimpleXMLElement($str);
	$cnt = $xml->body->div->div->div->div;
	$toc = $cnt->ul->asXML();
}

ob_start();
?>
<html>
	<head>
		<title><?=$title?> - Datorföreningen Stacken</title>
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link type="text/css" rel="stylesheet" href="/style/style.css">
	</head>
	<body class="<?=$pageclass?>">
		<div class="topmatter">
			<a name="top"></a>
			<? if ($lang == "sv"): ?>
				<span class="langlink">[<a href="<?=$page?>.en">In english</a>]</span>
			<? else: ?>
				<span class="langlink">[<a href="<?=$page?>.sv">På svenska</a>]</span>
			<? endif; ?>

			<span class="helplink">[<a href="/help/web" title="Om Stackens websidor, navigation och utseende.">Hjälp</a>]</span>
			<strong><a href="/" title="The Computer Club @ KTH">Stacken</a></strong>
		 </div>
		<div id="wrap">
		        <div id="preContent">
			     <h1><?=$title?></h1>
			</div>
			<!-- Sidan importerad från: <?=$url?> -->
			<?=wiki_format($content)?>
			<!-- Slut på import -->
			<div class="menu">
				<? if($toc): ?>
					<!-- Importera TOC -->
					<h2>Innehåll</h2>
					<?=wiki_format($toc)?>
					<!-- Slut på import -->
				<? endif; ?>
				<? if($menu_sub): ?>
					<!-- Importera undermeny -->
					<?=wiki_format($menu_sub)?>
					<!-- Slut på import -->
				<? endif; ?>
				<!-- Menyn importerad från: <?=$menu_url?> -->
				<h2><?=($lang=="sv"?'Meny':'Menu')?></h2>
				<?=wiki_format($menu)?>
				<!-- Slut på import -->
			</div>
			<div id="footer">
				<p class="signed">
					<a href="/Webmasters">Webmasters</a> @ 
					<a href="/">Stacken</a>
				</p>
			     <p class="dated"><?=$last_mod?></p>
			</div>
		</div>
	</body>
</html>
<?php

$html = ob_get_clean();
echo tidy_html($html);

?>
