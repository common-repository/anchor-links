<?php
/*
Plugin Name: Anchor Links Plugin
Plugin URI: http://oue.st/07-2010/anchor-links-plugin-liens-ancres.html
Description: Génère des ancres &lt;a name&gt; à partir des intertitres de vos articles et permet de les afficher où vous voulez sur votre blog. Génère un sommaire sur chacun de vos articles.
Version: 1.0
Author: Oue.st
Author URI: http://twitter.com/Oue_st
*/


//Installation du plugin, création de la table
function InstallAnchorLinks(){
	$create = mysql_query("CREATE TABLE `wp_anchors`
	( `id` int(50) NOT NULL auto_increment,
	  `anchor` varchar(255) NOT NULL,
	  `title` varchar(255) NOT NULL,
	  `post_ID` int(20) NOT NULL,
	  `post_url` varchar(255) NOT NULL,
	  PRIMARY KEY  (`id`))
	");
}
register_activation_hook( __FILE__, 'InstallAnchorLinks' );

//Fonction qui génère des ancres propres à partir des titres
function formater_url($url){
	$url = preg_replace("`\[.*\]`U","",$url);
	$url = preg_replace('`&(amp;)?#?[a-z0-9]+;`i','-',$url);
	$url = htmlentities($url, ENT_NOQUOTES, 'utf-8');
	$url = preg_replace( "`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig);`i","\\1", $url );
	$url = preg_replace( array("`[^a-z0-9]`i","`[-]+`") , "-", $url);
	$url = ( $url == "" ) ? $type : strtolower(trim($url, '-'));
	return $url;
}


//Génère les balises <a name> dans vos articles à partir des titres de paragraphe <h2> ainsi que le sommaire en début d'article.
function GenerateSommaire($content){

	$lien_top = 1; // Afficher le lien "Retour au sommaire" (1 = oui, 0 = non)

	preg_match_all("@<h2([^>]*)>(.*?)</h2>@mi",$content, $matches);
	$decoupe = preg_split('@<h2[^>]*+>@',$content);
	$sommaire .= '<ul id="sommaire">';
	foreach ($decoupe as $morceau) {
		$morceau = explode('</h2>',$morceau); 
		if (count($morceau)>1) {
			$sommaire .= '<li><a href="#'.formater_url($morceau[0]).'">'.$morceau[0].'</a></li>';
		}
	}
	$sommaire .= '</ul>';
		$i = 0;
	if($lien_top == 1){
		$retour_sommaire ="<a href=\"#sommaire\" title=\"Retour au sommaire\" rel=\"nofollow\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/AnchorLinks/sommaire.gif\" alt=\"Retour au sommaire\" /></a> ";
	}else{
		$retour_sommaire ="";
	}
	foreach($matches[0] as $match) {
		$content = str_replace($match, "<a name=\"".formater_url($matches[2][$i])."\"></a><h2".$matches[1][$i].">".$retour_sommaire."{$matches[2][$i]}</h2>\n\n", $content);
		$i++;
	}

	return $sommaire.$content;
}
add_filter('the_content','GenerateSommaire');

//Génère toutes les ancres articles par article et les enregistre en base de données.
function GenerateAnchors($content){
	global $wpdb;
	global $post;
	$content = $post->post_content;
	
	$post_ID = $post->ID;
	$wpdb->query("DELETE FROM wp_anchors WHERE post_ID = '$post_ID'");
	
	preg_match_all("@<h2([^>]*)>(.*?)</h2>@mi",$content, $matches);
	$i = 0;
	foreach($matches[0] as $match) {
		$title_anchor = addslashes($matches[2][$i]);
		$anchor = formater_url($matches[2][$i]);
		$url_post = get_permalink($post_ID);
		mysql_query("INSERT INTO wp_anchors VALUES('','$anchor','$title_anchor','$post_ID','$url_post')");
		$i++;
	}
}
add_filter('save_post','GenerateAnchors');

//Fonction qui permet de récupérer les ancres pour chacun de vos articles et de les afficher où vous voulez.
function ShowAnchorLinks($id,$format,$prefixe){
// Formats : list, inline
	if($format == "list"){
		$before_all = "<ul>";
		$before = "<li>";
		$sep = "</li>";
		$after_all = "</ul>";
	}elseif($format="inline"){
		$before_all = "";
		$before = " ";
		$sep = " ";
		$after_all = "";
	}else{
		$before_all = "";
		$before = "";
		$sep = "<br />";
		$after_all = "";
	}
	$anchors_req = mysql_query("SELECT * FROM wp_anchors WHERE post_ID = '$id'") or die(mysql_error());
	$return_datas .= $before_all;
	while($anchor = mysql_fetch_array($anchors_req)){
		$return_datas .= $before.'<a href="'.$anchor['post_url'].'#'.$anchor['anchor'].'">'.$prefixe.$anchor['title'].'</a>'.$sep;
	}
	$return_datas .= $after_all;
	echo $return_datas;
}
?>