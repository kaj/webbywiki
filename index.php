<?php

error_reporting(E_ALL & (~E_NOTICE));

session_start();

require "wiki_format.php";

# Sätt språk
$lang = empty($_REQUEST['lang']) ? 'sv' : $_REQUEST['lang'];

##
# Hämta och formatera sidan
##

$page = isset($_GET['page']) && $_GET['page'] != "Start" ? '/'.$_GET['page'] : '';

$pageclass = 'stacken' . strtolower(preg_replace('/\W+/', '_', $page));

function get_url($lang) {
	global $page;
	# NOTE This only removes the slash internally, without redirect.
	# The /foo/ will be identical to /foo (bad google carma).
	$id = rtrim($page, '/');
	if ($lang != "sv") {
		$id = "{$id}.{$lang}";
	}
	return "http://wiki.stacken.kth.se/wiki/Stacken{$id}?printable=yes";
}

$url = get_url($lang);

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

        # Denna tar bort innehållsförteckningen
        unset($content->div[3]->table);

	if ($content->table) {
		$toc = $content->table->tr->td->ul->asXML();
		unset($content->table[0]);
	}

	$content = $content->asXML();
} else {

	# Page not found
	# Kolla om vi har en sida på det andra språket som vi kan skicka vidare till

	if ($lang == "en") {
		if (file_get_contents(get_url("sv"))) {
			flash_next_page("No English page available, you have been redirected to the swedish page.");
			header("Location: ".(empty($page)?'/Start':$page));
			exit;
		}
	} else if ($lang == "sv") {
		if (file_get_contents(get_url("en"))) {
			flash_next_page("Det fanns ingen svensk sida, du har blivit vidareskickad till den engelska sidan.");
			header("Location: ".(empty($page)?'/Start.en':$page.'.en'));
			exit;
		}
	}

	# Om sidan inte finns på webbywiki, prova om den finns på gamla sidan och
	# försök att hämta den i stället.
	if ($old_page = file_get_contents("http://oldwww.stacken.kth.se/$page")) {
		$old_page = str_replace("/css/stacken2012.css",
			"http://oldwww.stacken.kth.se/css/stacken2012.css",$old_page);
		$old_page = str_replace("/css/print.css",
			"http://oldwww.stacken.kth.se/css/print.css",$old_page);
		echo utf8_encode($old_page);
		exit;
	}

	header("HTTP/1.0 404 Not Found");
	$content = "
		<div id=\"bodyContent\">
			<p>The requested URL {$page} was not found on this server.</p>
		</div>
		";
}

##
# Hämta och formatera menyn
##

$menu_url = "http://wiki.stacken.kth.se/wiki/Stacken/Menu?printable=yes";
$cnt = file_get_contents($menu_url);
$cnt = menu_prepare($cnt);
$menu = lang_menu($cnt, $lang);

###
# Hämta och formatera sidmeny (om den finns)
##

preg_match("#/([a-z0-9]+)#i", $page, $m);
$menu_url = "http://wiki.stacken.kth.se/wiki/Stacken/{$m[1]}/Menu?printable=yes";
$cnt = @file_get_contents($menu_url);
if (!empty($cnt) && !empty($page)) {
	$cnt = menu_prepare($cnt);
	$menu_sub = "<h2>".ucfirst($m[1])."</h2>";
	$menu_sub .= lang_menu($cnt, $lang);
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
<html lang="<?=$lang?>">
	<head>
		<title>Datorföreningen Stacken</title>
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link type="text/css" rel="stylesheet" href="/style/style.css">
	</head>
	<body class="<?=$pageclass?>">
		<div id="top">
			<? if ($lang == "sv"): ?>
				<span class="langlink">[<a href="<?=(empty($page))?'/Start':$page?>.en">In english</a>]</span>
			<? else: ?>
				<span class="langlink">[<a href="<?=(empty($page))?'/Start':$page?>.sv">På svenska</a>]</span>
			<? endif; ?>

			<span class="helplink">[<a href="/help/web" title="Om Stackens websidor, navigation och utseende.">Hjälp</a>]</span>
			<strong><a href="/" title="The Computer Club @ KTH">Stacken</a></strong>
		 </div>
		<div id="wrap">
                        <div id="preContent">
				 <?=get_flash_message()?>
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
					<a href="/webmaster">Webmasters</a> @
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
