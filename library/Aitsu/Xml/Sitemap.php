<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2011, w3concepts AG
 */

class Aitsu_Xml_Sitemap {

	protected function __construct() {
	}

	public static function getInstance() {

		static $instance;

		if (!isset ($instance)) {
			$instance = new self();
		}

		return $instance;
	}

	protected function _getArticles() {

		return Aitsu_Db :: fetchAll('' .
		'');
		/*
select distinct
  concat(if(cat.parentid = 0, '', catlang.url), '/', if(catlang.startidartlang = artlang.idartlang, '', concat(artlang.urlname, '.html'))) loc,
  date_format(artlang.lastmodified, '%Y-%m-%dT%H:%i:%s') lastmod
from _art_lang artlang
left join _cat_art catart on artlang.idart = catart.idart
left join _cat cat on catart.idcat = cat.idcat
left join _cat_lang catlang on cat.idcat = catlang.idcat and artlang.idlang = catlang.idlang
where 
  catlang.visible = 1
  and catlang.public = 1
  and artlang.online = 1		
		*/
	}
}