<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2012, w3concepts AG
 */
class Aitsu_Revision_Article implements Aitsu_Event_Listener_Interface {

	public static function notify(Aitsu_Event_Abstract $event) {
		
		$idartlang = $event->idartlang;

		if (empty ($idartlang)) {
			$idart = $event->idart;
			if (empty ($idart)) {
				trigger_error('idart not set');
				trigger_error(var_export($event, true));
				return;
			}
			$idartlang = Aitsu_Db :: fetchOne('' .
			'select idartlang from _art_lang where idart = :idart and idlang = :idlang', array (
				':idart' => $idart,
				':idlang' => Aitsu_Registry :: get()->session->currentLanguage
			));
		}

		/*
		 * Add new revision entry in _revision.
		 */
		$revisionId = Aitsu_Db :: query('' .
		'insert into _revision (userid) values (:userid)', array (
			':userid' => Aitsu_Adm_User :: getInstance()->getId()
		))->getLastInsertId();

		/*
		 * Make revision of the artlang table.
		 */
		Aitsu_Db :: query('' .
		'insert into _revision_art_lang ' .
		'(revisionid, revisionhash, idartlang, title, urlname, pagetitle, teasertitle, summary, online, pubfrom, pubuntil, published, redirect, redirect_url, configsetid, config) ' .
		'select ' .
		'	:revisionid as revisionid, ' .
		'	unhex(md5(concat_ws(\'-\', title, urlname, pagetitle, teasertitle, summary, online, pubfrom, pubuntil, published, redirect, redirect_url, configsetid, config))) as revisionhash, ' .
		'	idartlang, ' .
		'	title, ' .
		'	urlname, ' .
		'	pagetitle, ' .
		'	teasertitle, ' .
		'	summary, ' .
		'	online, ' .
		'	pubfrom, ' .
		'	pubuntil, ' .
		'	published, ' .
		'	redirect, ' .
		'	redirect_url, ' .
		'	configsetid, ' .
		'	config ' .
		'from _art_lang where idartlang = :idartlang', array (
			':revisionid' => $revisionId,
			':idartlang' => $idartlang
		));

		/*
		 * Remove unnecessary revision entries.
		 */
		Aitsu_Db :: query('' .
		'delete a from _revision_art_lang as a ' .
		'left join ( ' .
		'	select idartlang, max(revisionid) as revisionid ' .
		'	from _revision_art_lang ' .
		'	where revisionid != :revisionid ' .
		'	group by idartlang ' .
		'	) as b on a.idartlang = b.idartlang ' .
		'left join _revision_art_lang as c on b.idartlang = c.idartlang and b.revisionid = c.revisionid ' .
		'where ' .
		'	a.revisionid = :revisionid ' .
		'	and c.revisionhash = a.revisionhash', array (
			':revisionid' => $revisionId
		));

		/*
		 * Make revision of the content table.
		 */
		Aitsu_Db :: query('' .
		'insert into _revision_content ' .
		'(revisionid, revisionhash, idartlang, `index`, `value`) ' .
		'select ' .
		'	:revisionid as revisionid, ' .
		'	unhex(md5(concat_ws(\'-\', `index`, `value`))) as revisionhash, ' .
		'	idartlang, ' .
		'	`index`, ' .
		'	`value` ' .
		'from _article_content ' .
		'where idartlang = :idartlang', array (
			':revisionid' => $revisionId,
			':idartlang' => $idartlang
		));

		/*
		 * Remove unnecessary revision entries.
		 */
		Aitsu_Db :: query('' .
		'delete a from _revision_content as a ' .
		'left join ( ' .
		'	select idartlang, `index`, max(revisionid) as revisionid ' .
		'	from _revision_content ' .
		'	where revisionid != :revisionid ' .
		'	group by idartlang, `index` ' .
		'	) as b on a.idartlang = b.idartlang and a.index = b.index ' .
		'left join _revision_content as c on b.idartlang = c.idartlang and b.index = c.index and b.revisionid = c.revisionid ' .
		'where ' .
		'	a.revisionid = :revisionid ' .
		'	and c.revisionhash = a.revisionhash', array (
			':revisionid' => $revisionId
		));

		/*
		 * Make revision of properties table.
		 */
		Aitsu_Db :: query('' .
		'insert into _revision_property ' .
		'(revisionid, revisionhash, propertyid, idartlang, textvalue, floatvalue, datevalue) ' .
		'select ' .
		'	:revisionid as revisionid, ' .
		'	unhex(md5(concat_ws(\'-\', textvalue, floatvalue, datevalue))) as revisionhash, ' .
		'	propertyid, ' .
		'	idartlang, ' .
		'	textvalue, ' .
		'	floatvalue, ' .
		'	datevalue ' .
		'from _aitsu_article_property ' .
		'where idartlang = :idartlang', array (
			':revisionid' => $revisionId,
			':idartlang' => $idartlang
		));

		/*
		 * Remove unnecessary revision entries.
		 */
		Aitsu_Db :: query('' .
		'delete a from _revision_property as a ' .
		'left join ( ' .
		'	select propertyid, idartlang, max(revisionid) as revisionid ' .
		'	from _revision_property ' .
		'	where revisionid != :revisionid ' .
		'	group by propertyid, idartlang ' .
		'	) as b on a.propertyid = b.propertyid and a.idartlang = b.idartlang ' .
		'left join _revision_property as c on c.idartlang = b.idartlang and c.propertyid = b.propertyid and b.revisionid = c.revisionid ' .
		'where ' .
		'	a.revisionid = :revisionid ' .
		'	and c.revisionhash = a.revisionhash', array (
			':revisionid' => $revisionId
		));

		/*
		 * Remove unused revisions.
		 */
		Aitsu_Db :: query('' .
		'delete from _revision ' .
		'where ' .
		'	revisionid not in (select distinct revisionid from _revision_art_lang) ' .
		'	and revisionid not in (select distinct revisionid from _revision_content) ' .
		'	and revisionid not in (select distinct revisionid from _revision_property)');
	}
}