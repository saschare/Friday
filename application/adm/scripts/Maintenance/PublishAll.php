<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 */

class Adm_Script_PublishAll extends Aitsu_Adm_Script_Abstract {

	public static function getName() {

		return Aitsu_Translate :: translate('Publish all pages.');
	}

	protected function _hasNext() {

		$article = Aitsu_Db :: fetchRow('' .
		'select ' .
		'	artlang.idartlang, ' .
		'	artlang.idart, ' .
		'	artlang.idlang ' .
		'from _art_lang artlang ' .
		'left join _pub pub on artlang.idartlang = pub.idartlang and pub.status = 1 ' .
		'where ' .
		'	(' .
		'		artlang.lastmodified > pub.pubtime ' .
		'		or pub.idartlang is null ' .
		'	) ' .
		'	and artlang.idlang = :idlang ' .
		'limit 0, 1', array (
			':idlang' => Aitsu_Registry :: get()->session->currentLanguage
		));

		if ($article)
			return true;

		return false;
	}

	protected function _next() {

		return 'do next';
	}

	protected function _executeStep() {

		$article = (object) Aitsu_Db :: fetchRow('' .
		'select ' .
		'	artlang.idartlang, ' .
		'	artlang.idart, ' .
		'	artlang.idlang ' .
		'from _art_lang artlang ' .
		'left join _pub pub on artlang.idartlang = pub.idartlang and pub.status = 1 ' .
		'where ' .
		'	(' .
		'		artlang.lastmodified > pub.pubtime ' .
		'		or pub.idartlang is null ' .
		'	) ' .
		'	and artlang.idlang = :idlang ' .
		'limit 0, 1', array (
			':idlang' => Aitsu_Registry :: get()->session->currentLanguage
		));
		
		Aitsu_Persistence_Article :: factory($article->idart, $article->idlang)->publish();

		$response = sprintf(Aitsu_Translate :: translate('Page with ID %s published.'), $article->idart);
		return Aitsu_Adm_Script_Response :: factory($response);
	}

}