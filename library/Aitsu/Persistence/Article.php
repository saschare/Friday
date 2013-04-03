<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 */

class Aitsu_Persistence_Article extends Aitsu_Persistence_Abstract {

	protected $_id = null;
	protected $_idartlang = null;
	protected $_data = null;
	protected $_idlang = null;
	protected $_catConf = null;
	protected $_tags = null;
	protected $_config = null;

	protected function __construct($id) {

		$this->_id = $id;
		$this->_idlang = Aitsu_Registry :: get()->session->currentLanguage;

		if (empty ($this->_idlang)) {
			$this->_idlang = Aitsu_Registry :: get()->env->idlang;
		}
	}

	public static function factory($id = null, $idlang = null) {

		static $instance = array ();

		if ($id == null || !isset ($instance[$id])) {
			$instance[$id] = new self($id);
		}

		if (!is_null($idlang)) {
			$instance[$id]->_idlang = $idlang;
		}

		return $instance[$id];
	}

	public function load($reload = false) {

		if (!$reload && ($this->_id == null || $this->_data !== null)) {
			return $this;
		}

		$this->_data = Aitsu_Db :: fetchRow('' .
		'select ' .
		'	artlang.*, ' .
		'	catart.*, ' .
		'	catlang.startidartlang, ' .
		'	artlang.created created, ' .
		'	if(pub.pubtime is null or pub.pubtime != artlang.lastmodified, 0, 1) as ispublished ' .
		'from _art_lang as artlang ' .
		'left join _art as art on artlang.idart = art.idart ' .
		'left join _cat_art as catart on catart.idart = artlang.idart ' .
		'left join _cat_lang as catlang on catart.idcat = catlang.idcat and artlang.idlang = catlang.idlang ' .
		'left join _pub as pub on artlang.idartlang = pub.idartlang and pub.status = 1 ' .
		'where ' .
		'	artlang.idart = :id ' .
		'	and artlang.idlang = :idlang', array (
			':id' => $this->_id,
			':idlang' => $this->_idlang
		));

		$this->_data['crosslinks'] = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	artlang.idartlang as idartlang, ' .
		'	artlang.idart as idart, ' .
		'	artlang.title as title, ' .
		'	artlang.pagetitle as pagetitle, ' .
		'	catlang.idcat as idcat, ' .
		'	catlang.name as category ' .
		'from _crosslink as crosslink ' .
		'left join _art_lang as artlang on artlang.idartlang in (crosslink.idartlanglow, crosslink.idartlanghigh) ' .
		'left join _cat_art as catart on artlang.idart = catart.idart ' .
		'left join _cat_lang as catlang on catart.idcat = catlang.idcat and catlang.idlang = artlang.idlang ' .
		'where ' .
		'	artlang.idartlang != :idartlang ' .
		'	and ( ' .
		'		crosslink.idartlanglow = :idartlang ' .
		'		or crosslink.idartlanghigh = :idartlang ' .
		'	)', array (
			':idartlang' => $this->_data['idartlang']
		));

		return $this;
	}

	public function isIndex() {

		return $this->idartlang == $this->startidartlang;
	}

	public function __get($key) {

		if ($this->_data === null) {
			$this->load();
		}

		if (!isset ($this->_data[$key])) {
			return null;
		}

		if (is_array($this->_data[$key])) {
			return $this->_data[$key];
		}

		return stripslashes($this->_data[$key]);
	}

	public function getData() {

		return $this->_data;
	}

	public function __set($key, $value) {

		if ($this->_data === null) {
			$this->_data = array ();
		}

		$this->_data[$key] = $value;
	}

	public function save($manualmoddate = false) {

		if (empty ($this->_data)) {
			return;
		}

		$this->_data['idlang'] = $this->_idlang;

		if (empty ($this->_data['urlname'])) {
			$this->_data['urlname'] = Moraso_Util_String::slugify($this->_data['title']);
		} else {
			$this->_data['urlname'] = Moraso_Util_String::slugify($this->_data['urlname']);
		}

		if (!isset ($this->_data['created'])) {
			$this->_data['created'] = date('Y-m-d H:i:s');
		}

		if (empty ($this->_data['configsetid'])) {
			$this->_data['configsetid'] = null;
		}

		// use current time as lastmodified, unless the flag
		// manualmoddate is set to true
		if (!$manualmoddate) {
			$this->_data['lastmodified'] = date('Y-m-d H:i:s');
		}

		if (empty ($this->_data['pubfrom'])) {
			$this->_data['pubfrom'] = ""; //new Zend_Db_Expr('NULL');
		}
		if (empty ($this->_data['pubuntil'])) {
			$this->_data['pubuntil'] = ""; //new Zend_Db_Expr('NULL');
		}

		try {
			Aitsu_Db :: startTransaction();

			$this->_data['idart'] = Aitsu_Db :: put('_art', 'idart', $this->_data);
			$this->_idartlang = Aitsu_Db :: put('_art_lang', 'idartlang', $this->_data);
			$this->_data['idartlang'] = $this->_idartlang;

			/*
			 * Setting date to NULL seems not to work in Zend. As a workaround we set the values
			 * to null where they are 0000-00-00 00:00:00
			 */
			Aitsu_Db :: query('update _art_lang set pubfrom = null where pubfrom = :null', array (
				':null' => '0000-00-00 00:00:00'
			));
			Aitsu_Db :: query('update _art_lang set pubuntil = null where pubuntil = :null', array (
				':null' => '0000-00-00 00:00:00'
			));

			Aitsu_Db :: query('' .
			'insert into _cat_art (idart, idcat) values (:idart, :idcat) ' .
			'on duplicate key update lastmodified = now() ', array (
				':idart' => $this->_data['idart'],
				':idcat' => $this->_data['idcat']
			));

			if (Aitsu_Db :: fetchOne('' .
				'select startidartlang from _cat_lang ' .
				'where ' .
				'	idcat = :idcat ' .
				'	and idlang = :idlang', array (
					':idcat' => $this->_data['idcat'],
					':idlang' => $this->_idlang
				)) == null) {
				/*
				 * There is no index article yet in the specified category. The current
				 * article therefore is automatically set as index article.
				 */
				Aitsu_Db :: query('' .
				'update _cat_lang set startidartlang = :idartlang ' .
				'where ' .
				'	idcat = :idcat ' .
				'	and idlang = :idlang', array (
					':idartlang' => $this->_idartlang,
					':idcat' => $this->_data['idcat'],
					':idlang' => $this->_idlang
				));
			}

			if ($this->_data['configsetid'] == null) {
				Aitsu_Db :: query('' .
				'update _art_lang set configsetid = null where idartlang = :idartlang', array (
					':idartlang' => $this->_idartlang
				));
			}

			Aitsu_Db :: commit();

			Aitsu_Event :: raise('persistence.article.save.end', (object) array (
				'idartlang' => $this->_idartlang,
				'action' => 'save'
			));

		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			throw $e;
		}

		return $this;
	}

	public function remove() {

		Aitsu_Db :: startTransaction();

		try {
			$this->_idartlang = Aitsu_Db :: query('' .
			'select idartlang from _art_lang ' .
			'where ' .
			'	idart = :idart ' .
			'	and idlang = :idlang ', array (
				':idart' => $this->_id,
				':idlang' => $this->_idlang
			));

			Aitsu_Db :: query('' .
			'delete from _art_lang ' .
			'where ' .
			'	idart = :id ' .
			'	and idlang = :idlang', array (
				':id' => $this->_id,
				':idlang' => $this->_idlang
			));

			if (Aitsu_Db :: fetchOne('' .
				'select count(*) from _art_lang where idart = :id', array (
					':id' => $this->_id
				)) == 0) {
				/*
				 * Delete the entry in _art, too, if there are no further
				 * entries in _art_lang for the given id.
				 */
				Aitsu_Db :: query('' .
				'delete from _art where idart = :id', array (
					':id' => $this->_id
				));
			}

			Aitsu_Event :: raise('persistence.article.remove.end', (object) array (
				'idartlang' => $this->_idartlang,
				'action' => 'delete'
			));

			Aitsu_Db :: commit();
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			throw new Exception(Zend_Registry :: get('Zend_Translate')->translate('An error occured while deleting the specified article.'));
		}
	}

	public function setAsIndex() {

		$details = Aitsu_Db :: fetchRow('' .
		'select ' .
		'	artlang.idartlang as idartlang, ' .
		'	catart.idcat as idcat ' .
		'from _art_lang as artlang ' .
		'left join _cat_art as catart on artlang.idart = catart.idart ' .
		'where ' .
		'	artlang.idart = :idart ' .
		'	and artlang.idlang = :idlang', array (
			':idart' => $this->_id,
			':idlang' => $this->_idlang
		));

		Aitsu_Db :: query('' .
		'update _cat_lang set startidartlang = :idartlang ' .
		'where ' .
		'	idcat = :idcat ' .
		'	and idlang = :idlang', array (
			':idcat' => $details['idcat'],
			':idartlang' => $details['idartlang'],
			':idlang' => $this->_idlang
		));

		return $this;
	}

	public function duplicate() {

		Aitsu_Db :: startTransaction();

		try {
			$details = Aitsu_Db :: fetchRow('' .
			'select ' .
			'	artlang.idartlang as idartlang, ' .
			'	catart.idcat as idcat ' .
			'from _art_lang as artlang ' .
			'left join _cat_art as catart on artlang.idart = catart.idart ' .
			'where ' .
			'	artlang.idart = :idart ' .
			'	and artlang.idlang = :idlang', array (
				':idart' => $this->_id,
				':idlang' => $this->_idlang
			));

			/*
			 * Make new entry in _art for the given client.
			 */
			$newIdart = Aitsu_Db :: query('' .
			'insert into _art (idclient) values (:idclient)', array (
				':idclient' => Aitsu_Registry :: get()->session->currentClient
			))->getLastInsertId();

			/*
			 * Replicate entry of _art_lang.
			 */
			$newIdartlang = Aitsu_Db :: query('' .
			'insert into _art_lang ' .
			'(idart, idlang, title, urlname, pagetitle, summary, created, lastmodified) ' .
			'select ' .
			'	:idart as idart, ' .
			'	idlang, ' .
			'	concat(:prefix, title) as title, ' .
			'	concat(urlname, \'-copy-\', :idart) as urlname, ' .
			'	pagetitle, ' .
			'	summary, ' .
			'	now() as created, ' .
			'	now() as lastmodified ' .
			'from _art_lang ' .
			'where idartlang = :idartlang', array (
				':idart' => $newIdart,
				':idartlang' => $details['idartlang'],
				':prefix' => Zend_Registry :: get('Zend_Translate')->translate('Copy of') . ' '
			))->getLastInsertId();

			/*
			 * Add the newly created article to the current category.
			 */
			Aitsu_Db :: query('' .
			'insert into _cat_art ' .
			'(idart, idcat) values (:idart, :idcat)', array (
				':idart' => $newIdart,
				':idcat' => $details['idcat']
			));

			/*
			 * Move the article's content to the new article.
			 */
			Aitsu_Db :: query('' .
			'insert into _article_content ' .
			'(idartlang, `index`, `value`, modified) ' .
			'select ' .
			'	:newIdartlang as idartlang, ' .
			'	`index`, ' .
			'	`value`, ' .
			'	now() as modified ' .
			'from _article_content ' .
			'where idartlang = :oldIdartlang', array (
				':newIdartlang' => $newIdartlang,
				':oldIdartlang' => $details['idartlang']
			));

			/*
			 * Move article's properties to the new article.
			 */
			Aitsu_Db :: query('' .
			'insert into _aitsu_article_property ' .
			'(propertyid, idartlang, textvalue, floatvalue, datevalue) ' .
			'select ' .
			'	propertyid, ' .
			'	:newIdartlang as idartlang, ' .
			'	textvalue, ' .
			'	floatvalue, ' .
			'	datevalue ' .
			'from _aitsu_article_property ' .
			'where idartlang = :oldIdartlang', array (
				':newIdartlang' => $newIdartlang,
				':oldIdartlang' => $details['idartlang']
			));

			Aitsu_Event :: raise('persistence.article.duplicate.end', (object) array (
				'idartlangold' => $details['idartlang'],
				'idartlangnew' => $newIdartlang,
				'action' => 'save'
			));

			Aitsu_Db :: commit();
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			throw $e;
		}
	}

	public function sync($syncLang) {

		Aitsu_Db :: startTransaction();

		try {
			$details = Aitsu_Db :: fetchRow('' .
			'select ' .
			'	artlang.idartlang as idartlang, ' .
			'	catart.idcat as idcat ' .
			'from _art_lang as artlang ' .
			'left join _cat_art as catart on artlang.idart = catart.idart ' .
			'where ' .
			'	artlang.idart = :idart ' .
			'	and artlang.idlang = :syncLang', array (
				':idart' => $this->_id,
				':syncLang' => $syncLang
			));

			/*
			 * Replicate entry of _art_lang.
			 */
			$newIdartlang = Aitsu_Db :: query('' .
			'insert into _art_lang ' .
			'(idart, idlang, title, urlname, pagetitle, summary, created, lastmodified) ' .
			'select ' .
			'	idart, ' .
			'	:idlang as idlang, ' .
			'	title, ' .
			'	urlname, ' .
			'	pagetitle, ' .
			'	summary, ' .
			'	now() as created, ' .
			'	now() as lastmodified ' .
			'from _art_lang ' .
			'where idartlang = :idartlang', array (
				':idlang' => $this->_idlang,
				':idartlang' => $details['idartlang']
			))->getLastInsertId();

			/*
			 * Move the article's content to the new article.
			 */
			Aitsu_Db :: query('' .
			'insert into _article_content ' .
			'(idartlang, `index`, `value`, modified) ' .
			'select ' .
			'	:newIdartlang as idartlang, ' .
			'	`index`, ' .
			'	`value`, ' .
			'	now() as modified ' .
			'from _article_content ' .
			'where idartlang = :oldIdartlang', array (
				':newIdartlang' => $newIdartlang,
				':oldIdartlang' => $details['idartlang']
			));

			/*
			 * Move article's properties to the new article.
			 */
			Aitsu_Db :: query('' .
			'insert into _aitsu_article_property ' .
			'(propertyid, idartlang, textvalue, floatvalue, datevalue) ' .
			'select ' .
			'	propertyid, ' .
			'	:newIdartlang as idartlang, ' .
			'	textvalue, ' .
			'	floatvalue, ' .
			'	datevalue ' .
			'from _aitsu_article_property ' .
			'where idartlang = :oldIdartlang', array (
				':newIdartlang' => $newIdartlang,
				':oldIdartlang' => $details['idartlang']
			));

			Aitsu_Db :: commit();

			Aitsu_Event :: raise('persistence.article.sync.end', (object) array (
				'idartlangold' => $details['idartlang'],
				'idartlangnew' => $newIdartlang,
				'idartlang' => $newIdartlang,
				'action' => 'save'
			));
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			throw $e;
		}
	}

	/**
	 * @deprecated 2.1.0.0 - 22.01.2011
	 */
	public function getCatConf($key) {

		if ($key == null) {
			return null;
		}

		if (isset ($this->_catConf[$key])) {
			return $this->_catConf[$key];
		}
		return false;
		/*$this->_catConf[$key] = Aitsu_Db :: fetchOne('' .
		'select conf.value ' .
		'from _cat_art as catart ' .
		'left join _cat as child on catart.idcat = child.idcat ' .
		'left join _cat as parent on child.lft between parent.lft and parent.rgt ' .
		'left join _cat_lang as catlang on parent.idcat = catlang.idcat ' .
		'left join _cat_lang_conf as conf on catlang.idcatlang = conf.idcatlang ' .
		'left join _conf_token as token on conf.conftokenid = token.conftokenid ' .
		'where ' .
		'	catart.idart = :idart ' .
		'	and catlang.idlang = :idlang ' .
		'	and token.token = :key ' .
		'order by parent.lft desc ' .
		'limit 0, 1', array (
			':idart' => $this->_id,
			':idlang' => $this->_idlang,
			':key' => $key
		));*/

		return $this->_catConf[$key];
	}

	public function getConfig() {

		if ($this->_config == null) {
			$this->_evalConfig();
		}

		return $this->_config;
	}

	protected function _evalConfig() {

		$config = Aitsu_Util :: parseSimpleIni(Aitsu_Db :: fetchOne('' .
		'select config from _configset ' .
		'where configsetid = 1'));

		$configData = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	artlang.config as artconfig, ' .
		'	artconf.config as artconfigset, ' .
		'	catlang.config as catconfig, ' .
		'	catconf.config as catconfigset ' .
		'from _art_lang artlang ' .
		'left join _cat_art catart on artlang.idart = catart.idart ' .
		'left join _cat cat on catart.idcat = cat.idcat ' .
		'left join _cat parent on cat.lft between parent.lft and parent.rgt ' .
		'left join _cat_lang catlang on parent.idcat = catlang.idcat and catlang.idlang = artlang.idlang ' .
		'left join _configset catconf on catconf.configsetid = catlang.configsetid ' .
		'left join _configset artconf on artconf.configsetid = artlang.configsetid ' .
		'where ' .
		'	artlang.idart = :idart ' .
		'	and artlang.idlang = :idlang ' .
		'order by ' .
		'	parent.lft desc', array (
			':idart' => $this->_id,
			':idlang' => $this->_idlang
		));

		if (!$configData) {
			$this->_config = $config;
		}

		if (empty ($configData[0]['artconfigset'])) {
			foreach ($configData as $conf) {
				if (!empty ($conf['catconfigset'])) {
					$config = Aitsu_Util :: parseSimpleIni($conf['catconfigset'], $config);
					break 1;
				}
			}
		} else {
			$config = Aitsu_Util :: parseSimpleIni($configData[0]['artconfigset'], $config);
		}

		$configData = array_reverse($configData);

		foreach ($configData as $conf) {
			if (!empty ($conf['catconfig'])) {
				$config = Aitsu_Util :: parseSimpleIni($conf['catconfig'], $config);
			}
		}

		if (!empty ($configData[0]['artconfig'])) {
			$config = Aitsu_Util :: parseSimpleIni($configData[0]['artconfig'], $config);
		}

		$this->_config = $config;
	}

	public function addTag($token, $value) {

		$value = empty ($value) ? null : $value;

		Aitsu_Db :: startTransaction();

		try {
			$tagid = Aitsu_Db :: fetchOne('' .
			'select tagid from _tag where tag = :tag', array (
				':tag' => $token
			));

			if (!$tagid) {
				$tagid = Aitsu_Db :: query('' .
				'insert into _tag (tag) values (:tag)', array (
					':tag' => $token
				))->getLastInsertId();
			}

			Aitsu_Db :: query('' .
			'insert into _tag_art (idart, tagid, val) values (:idart, :tagid, :value) ' .
			'on duplicate key update val = :value', array (
				':idart' => $this->_id,
				':tagid' => $tagid,
				':value' => $value
			));

			Aitsu_Db :: commit();
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			throw $e;
		}
	}

	public function getTags() {

		if ($this->_tags !== null) {
			return $this->_tags;
		}

		$this->_tags = Aitsu_Db :: fetchAll('' .
		'select token.tagid, token.tag as tag, tag.val as `value` ' .
		'from _tag_art as tag ' .
		'left join _tag as token on tag.tagid = token.tagid ' .
		'where tag.idart = :idart ' .
		'order by token.tag asc', array (
			':idart' => $this->_id
		));

		return $this->_tags;
	}

	public function removeTag($tagid) {

		Aitsu_Db :: query('' .
		'delete from _tag_art ' .
		'where ' .
		'	idart = :idart ' .
		'	and tagid = :tagid', array (
			':idart' => $this->_id,
			':tagid' => $tagid
		));
	}

	public static function getByTerm($term, $limit = 100) {

		return Aitsu_Db :: fetchAll('' .
		'select ' .
		'	artlang.idart, ' .
		'	artlang.idartlang, ' .
		'	artlang.title, ' .
		'	artlang.pagetitle, ' .
		'	catlang.name as category ' .
		'from _art_lang as artlang ' .
		'left join _art_meta as artmeta on artlang.idartlang = artmeta.idartlang ' .
		'left join _cat_art as catart on artlang.idart = catart.idart ' .
		'left join _cat_lang as catlang on catart.idcat = catlang.idcat and artlang.idlang = catlang.idlang ' .
		'where ' .
		'	artlang.idlang = :idlang ' .
		'	and ( ' .
		'		artlang.title like :term ' .
		'		or artlang.pagetitle like :term ' .
		'		or artlang.summary like :term ' .
		'		or artmeta.description like :term ' .
		'		or artmeta.keywords like :term ' .
		'		or catlang.name like :term ' .
		'	) ' .
		'limit 0, 20', array (
			':term' => '%' . $term . '%',
			':idlang' => Aitsu_Registry :: get()->session->currentLanguage
		));
	}

	public static function addCrosslink($idartlangA, $idartlangB) {

		if ($idartlangA == $idartlangB || $idartlangA == null || $idartlangB == null) {
			return;
		}

		$low = min($idartlangA, $idartlangB);
		$high = max($idartlangA, $idartlangB);

		Aitsu_Db :: query('' .
		'insert into _crosslink (idartlanglow, idartlanghigh) ' .
		'values (:low, :high) ' .
		'on duplicate key update created = now()', array (
			':low' => $low,
			':high' => $high
		));
	}

	public static function removeCrosslink($idartlangA, $idartlangB) {

		if ($idartlangA == $idartlangB || $idartlangA == null || $idartlangB == null) {
			return;
		}

		$low = min($idartlangA, $idartlangB);
		$high = max($idartlangA, $idartlangB);

		Aitsu_Db :: query('' .
		'delete from _crosslink ' .
		'where ' .
		'	idartlanglow = :low ' .
		'	and idartlanghigh = :high', array (
			':low' => $low,
			':high' => $high
		));
	}

	public function moveTo($idcat) {

		Aitsu_Db :: startTransaction();

		try {
			/*
			 * First we have to set null all startidartlangs of the table
			 * _cat_lang pointing to the idart of interest, except for the
			 * current category.
			 */
			Aitsu_Db :: query('' .
			'update _cat_lang set startidartlang = null ' .
			'where ' .
			'	startidartlang in ( ' .
			'		select idartlang from _art_lang where idart = :idart ' .
			'	) ' .
			'	and idcat != :idcat', array (
				':idcat' => $idcat,
				':idart' => $this->_id
			));

			/*
			 * Then we may reset the entry in the _cat_art table.
			 */
			Aitsu_Db :: query('' .
			'update _cat_art set ' .
			'	idcat = :idcat, ' .
			'	lastmodified = now() ' .
			'where idart = :idart', array (
				':idcat' => $idcat,
				':idart' => $this->_id
			));

			Aitsu_Db :: commit();
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			throw $e;
		}

		return $this;
	}

	public function rebuild($pubid) {

		Aitsu_Db :: startTransaction();

		$this->load();

		$this->revise($this->idartlang);

		try {

			$publishMap = new Zend_Config_Ini(APPLICATION_PATH . '/configs/publishmap.ini');

			foreach ($publishMap as $type => $tables) {
				foreach ($tables->toArray() as $table) {

					$marker = $table['marker'];

					$source = Aitsu_Db :: fetchAll('' .
					'select * from ' . $table['target'] . ' ' .
					'where pubid = :pubid', array (
						':pubid' => $pubid
					));

					if ($table['delete']) {
						Aitsu_Db :: query('' .
						'delete from ' . $table['source'] . ' ' .
						'where ' . $marker . ' = :marker', array (
							':marker' => $this-> $marker
						));

						$marker = null;
					}

					if ($source) {
						foreach ($source as $src) {
							Aitsu_Db :: put($table['source'], $marker, $src);
						}
					}
				}
			}

			Aitsu_Db :: query('' .
			'update _art_lang as artlang, _pub as pub ' .
			'set artlang.lastmodified = pub.pubtime ' .
			'where pub.pubid = :pubid ' .
			'and artlang.idartlang = :idartlang', array (
				':pubid' => $pubid,
				':idartlang' => $this->idartlang
			));

			Aitsu_Db :: commit();
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			throw $e;
		}

		return $this;
	}

	public function revise($idartlang) {

		$isPublished = Aitsu_Db :: fetchOne('' .
		'select count(*) ' .
		'from _art_lang as artlang, _pub as pub ' .
		'where artlang.idartlang = pub.idartlang ' .
		'and artlang.lastmodified = pub.pubtime ' .
		'and artlang.idartlang = :idartlang', array (
			':idartlang' => $idartlang
		));

		if ($isPublished > 0) {
			return false;
		}

		$this->publish(false);

		return true;
	}

	public function publish($publish = true, $cron = false) {

		Aitsu_Db :: startTransaction();
		
		$userId = $cron ? null : Aitsu_Adm_User :: getInstance()->getId();

		try {

			$this->load();

			$transactionTime = date('Y-m-d H:i:s');

			if ($publish) {
				Aitsu_Db :: query('' .
				'update _pub set status = 0 where idartlang = :idartlang and status = 1', array (
					':idartlang' => $this->idartlang
				));
			}

			$pubid = Aitsu_Db :: query('' .
			'insert into _pub ' .
			'(idartlang, userid, pubtime, status) ' .
			'values ' .
			'(:idartlang, :userid, :pubtime, :status)', array (
				':idartlang' => $this->idartlang,
				':userid' => $userId,
				':pubtime' => $transactionTime,
				'status' => $publish ? 1 : -1
			))->getLastInsertId();

			$publishMap = new Zend_Config_Ini(APPLICATION_PATH . '/configs/publishmap.ini');

			foreach ($publishMap as $type => $tables) {
				foreach ($tables->toArray() as $table) {
					$marker = $table['marker'];

					if ($publish) {
						Aitsu_Db :: query('' .
						'update ' . $table['target'] . ' set ' .
						'status = 0 ' .
						'where ' . $marker . ' = :marker ' .
						'and status = 1', array (
							':marker' => $this-> $marker
						));
					}

					$source = Aitsu_Db :: fetchAll('' .
					'select * from ' . $table['source'] . ' ' .
					'where ' . $marker . ' = :marker', array (
						':marker' => $this-> $marker
					));

					if ($source) {
						foreach ($source as $src) {
							Aitsu_Db :: put($table['target'], null, array_merge($src, array (
								'pubid' => $pubid,
								'status' => $publish ? 1 : -1
							)));
						}
					}
				}
			}

			Aitsu_Db :: query('' .
			'update _art_lang set ' .
			'	lastmodified = :transactiontime ' .
			'where idartlang = :idartlang', array (
				':idartlang' => $this->idartlang,
				':transactiontime' => $transactionTime
			));

			/*
			 * Clean article cache.
			 */
			Aitsu_Cache :: getInstance()->clean(array (
				'art_' . $this->idart,
				'volatile'
			));

			/*
			 * Clean db cache.
			 */
			Aitsu_Cache :: getInstance()->clean(array (
				'db'
			));

			Aitsu_Db :: commit();

			Aitsu_Event :: raise('persistence.article.publish.end', (object) array (
				'idartlang' => $this->idartlang,
				'action' => 'publish'
			));
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			throw $e;
		}

		return $this;
	}

	public static function touch($idartlang) {

		Aitsu_Db :: query('' .
		'update _art_lang set lastmodified = now() ' .
		'where ' .
		'	idartlang = :idartlang', array (
			':idartlang' => $idartlang
		));
	}
}