<?php

error_reporting(E_ALL & (~E_NOTICE));

require "wiki_format.php";

##
# Hämta och formatera sidan
##

$page = isset($_GET['page']) ? '/'.$_GET['page'] : '';
$pageclass = 'page_' . preg_replace('/\W+/', '_', $page);
$url = "http://wiki.stacken.kth.se/wiki/Stacken{$page}?printable=yes";
$content = @file_get_contents($url);

$content = str_replace('&nbsp;','&#160;',$content);

# Hack
# Lägg till __SHOW_TOC:Kalender__ på en sida för att visa
# TOC från Stacken/Menu/Kalender i stället för den på sidan.
if (preg_match("#__SHOW_TOC:([a-z]+)__#i",$content)) {
	preg_match("#__SHOW_TOC:([a-z]+)__#i", $content, $m);
	define("SHOW_TOC_MENU", $m[1]);
	$content = preg_replace("#__SHOW_TOC:([a-z]+)__#i",'',$content);
} else {
	define("SHOW_TOC_MENU", false);
}

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
$cnt = str_replace('&nbsp;','&#160;',$cnt);
$xml = new SimpleXMLElement($cnt);

$cnt = $xml->body->div->div->div->div;

# ta bort lite saker
unset($cnt->h3);
unset($cnt->div);

$menu = $cnt->asXML();
$menu = str_replace('<div id="bodyContent">','',$menu);
$menu = str_replace('</div>','',$menu);

###
# Hämta och formatera special-TOC
# (den skriver över vanlig TOC)
##

if (SHOW_TOC_MENU) {
	$url = "http://wiki.stacken.kth.se/wiki/Stacken/Menu/".SHOW_TOC_MENU."?printable=yes";
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
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link type="text/css" rel="stylesheet" href="http://cookie.stacken.kth.se/style/style.css">
	</head>
	<body class="<?=$pageclass?>">
		<div class="topmatter">
			<a name="top"></a>
			<span class="langlink">[<a href="<?=$page?>/English">In english</a>]</span>
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
				<!-- Menyn importerad från: <?=$menu_url?> -->
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
