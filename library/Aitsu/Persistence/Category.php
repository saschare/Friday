<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 */

class Aitsu_Persistence_Category extends Aitsu_Persistence_Abstract {

	protected $_id = null;
	protected $_data = null;

	protected function __construct($id) {

		$this->_id = $id;
	}

	public static function factory($id = null) {

		static $instance = array ();

		if ($id == null || !isset ($instance[$id])) {
			$id = is_null($id) ? uniqid() : $id;
			$instance[$id] = new self($id);
		}

		return $instance[$id];
	}

	public function load() {

		if ($this->_id == null || $this->_data !== null) {
			return $this;
		}

		$idlang = Aitsu_Registry :: get()->session->currentLanguage;
                
                if (empty ($idlang)) {
			$idlang = Aitsu_Registry :: get()->env->idlang;
		}

		$this->_data = Aitsu_Db :: fetchRow('' .
		'select ' .
		'	*, ' .
		'	unix_timestamp(created) as createdts, ' .
		'	unix_timestamp(lastmodified) as modifiedts ' .
		'from _cat_lang ' .
		'where ' .
		'	idcat = :id ' .
		'	and idlang = :idlang', array (
			':id' => $this->_id,
			':idlang' => $idlang
		));

		$this->_populateConfigs();

		return $this;
	}

	public function getData() {

		if ($this->_data === null) {
			$this->load();
		}

		return $this->_data;
	}

	public function __get($key) {

		if ($this->_data === null) {
			$this->load();
		}

		if (!isset ($this->_data[$key])) {
			return null;
		}

		if (is_object($this->_data[$key]) || is_array($this->_data[$key])) {
			return $this->_data[$key];
		}

		return stripslashes($this->_data[$key]);
	}

	public function __set($key, $value) {

		if ($this->_data === null) {
			$this->_data = array ();
		}

		$this->_data[$key] = $value;
	}

	public function save( $manualmoddate=false ) {

		$this->_data['urlname'] = Moraso_Util_String::slugify($this->_data['urlname']);
		if (empty ($this->_data['urlname'])) {
			$this->_data['urlname'] = Moraso_Util_String::slugify($this->_data['name']);
		}

		foreach ($this->_data as $key => $value) {
			if (!is_array($value) && !is_object($value)) {
				$this->_data[$key] = stripslashes($value);
			}
		}

		if (empty ($this->_data['configsetid'])) {
			$this->_data['configsetid'] = null;
		}

		if (empty ($this->_data['startidartlang'])) {
			$this->_data['startidartlang'] = null;
		}

		if (empty ($this->_data['url'])) {
			$this->_data['url'] = null;
		}

		// use current time as lastmodified, unless the flag
		// manualmoddate is set to true
		if( !$manualmoddate ) {
			$this->_data['lastmodified'] = Date('Y-m-d H:i:s');
		}

		$this->_setConfigs();

		Aitsu_Db :: put('_cat_lang', 'idcatlang', $this->_data);

		if (empty ($this->_data['configsetid'])) {
			Aitsu_Db :: query('' .
			'update _cat_lang set configsetid = null where idcatlang = :idcatlang', array (
				':idcatlang' => $this->_data['idcatlang']
			));
		}

		/*
		 * Remove cache tagged as navigation.
		 */
		Aitsu_Cache :: getInstance()->clean(array (
			'type_navigation'
		));
		
		return $this;
	}

	public function remove($idlang) {

		$idcat = $this->_id;

		Aitsu_Db :: startTransaction();

		try {
			Aitsu_Db :: query('' .
			'lock tables ' .
			'	_cat as a write, ' .
			'	_cat as b write, ' .
			'	_cat as c write, ' .
			'	_lang write, ' .
			'	_cat write, ' .
			'	_cat as node write, ' .
			'	_cat as parent write, ' .
			'	_cat_lang write, ' .
			'	_art_lang write, ' .
			'	_cat_art write, ' .
			'	_art write, ' .
			'	_art_lang as artlang write, ' .
			'	_cat_art as catart write, ' .
			'	_cat_lang as catlang write, ' .
			'	_art as art write');

			Aitsu_Db :: query("" .
			'select @client := idclient from _lang where idlang = :idlang', array (
				':idlang' => $idlang
			));

			/*
			 * The removement has to be done sequentially, because there might
			 * be left unused con_cat entries that have to be deleted too. For each
			 * deleted con_cat entry we then have to close the gap it is leaving.
			 * Therefore the removement has to be done from the last and lowest category
			 * within the category tree upwards to the frist entry.
			 */
			$categories = Aitsu_Db :: fetchCol('' .
			'select ' .
			'	node.idcat ' .
			'from _cat as node, _cat as parent ' .
			'where ' .
			'	node.lft between parent.lft and parent.rgt ' .
			'	and node.idclient = @client ' .
			'	and parent.idcat = :idcat ' .
			'order by ' .
			'	node.lft desc ', array (
				':idcat' => $idcat
			));

			if (!$categories) {
				throw new Aitsu_Core_Exception('No categories to delete.');
			}

			foreach ($categories as $category) {
				$this->_remove($category, $idlang);
			}

			$this->_checkConsistency();

			Aitsu_Db :: commit();

			Aitsu_Db :: query('unlock tables');

			/*
			* Remove cache tagged as navigation.
			*/
			Aitsu_Cache :: getInstance()->clean(array (
				'type_navigation'
			));
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			Aitsu_Db :: query('unlock tables');
			throw new Aitsu_Core_Exception($e->getMessage() . ' ' . $e->getTraceAsString());
		}
	}

	public function insert($idlang) {

		$idcat = $this->_id;

		Aitsu_Db :: startTransaction();

		try {
			Aitsu_Db :: query('' .
			'lock tables ' .
			'	_cat write, ' .
			'	_cat_lang write, ' .
			'	_cat as node write, ' .
			'	_cat as parent write, ' .
			'	_cat as a write, ' .
			'	_cat as b write, ' .
			'	_cat as c write, ' .
			'	_lang write');

			Aitsu_Db :: query("" .
			'select @client := idclient from _lang where idlang = :idlang', array (
				':idlang' => $idlang
			));

			/*
			 * Then we have to determine the preid and the lft value of the 
			 * new category. The postid will be 0, because we are inserting 
			 * the new category at the end of the parent's child element 
			 * (if there are any).
			 */
			Aitsu_Db :: query('' .
			'select @preid := idcat, @lft := rgt + 1 from _cat ' .
			'where ' .
			'	parentid = :idcat ' .
			'	and idclient = @client ' .
			'order by rgt desc ' .
			'limit 0, 1 ', array (
				':idcat' => $idcat
			));
			Aitsu_Db :: query('' .
			'select ' .
			'	@preid := if(@preid is null, 0, @preid), ' .
			'	@lft := if (@lft is null, lft + 1, @lft) ' .
			'from _cat ' .
			'where ' .
			'	idcat = :idcat ' .
			'	and idclient = @client ', array (
				':idcat' => $idcat
			));

			if (Aitsu_Db :: fetchOne('select count(*) from _cat where idclient = @client ') == 0) {
				/*
				 * This is the very first entry. We therefore set preid to 0
				 * and lft to 1.
				 */
				Aitsu_Db :: query('' .
				'select @preid := 0, @lft := 1 ');
			}

			/*
			 * We open the necessary lft-rgt gap for the new category.
			 */
			Aitsu_Db :: query('' .
			'update _cat set lft = lft + 2 where lft >= @lft and idclient = @client ');
			Aitsu_Db :: query('' .
			'update _cat set rgt = rgt + 2 where rgt >= @lft and idclient = @client ');

			/*
			 * ...and insert the new category.
			 */
			Aitsu_Db :: query('' .
			'insert into _cat ' .
			'(idclient, parentid, preid, postid, lft, rgt, created, lastmodified) ' .
			'values ' .
			'(@client, :idcat, @preid, 0, @lft, @lft + 1, now(), now()) ', array (
				':idcat' => $idcat
			));
			Aitsu_Db :: query('select @idcat := last_insert_id() ');

			/*
			 * We then set the newly created idcat as the postid of the last
			 * element.
			 */
			Aitsu_Db :: query('' .
			'update _cat set postid = @idcat where idcat = @preid ');

			/*
			 * And last not least we insert the new language specific category.
			 */
			Aitsu_Db :: query('' .
			'insert into _cat_lang ' .
			'(idcat, idlang, name, visible, public, created, lastmodified, urlname) ' .
			'values ' .
			'(@idcat, :idlang, concat(:newname, @idcat), 0, 1, now(), now(), concat(:newurlname, @idcat)) ', array (
				':idlang' => $idlang,
				':newname' => 'New category ',
				':newurlname' => 'new-category-'
			));

			$this->_checkConsistency();

			Aitsu_Db :: commit();

			Aitsu_Db :: query('unlock tables');

			return Aitsu_Db :: fetchOne('select @idcat');

		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			Aitsu_Db :: query('unlock tables');
			throw new Aitsu_Core_Exception($e->getMessage() . ' ' . $e->getTraceAsString());
		}
	}

	protected function _checkConsistency() {

		/*
		 * Consistency checking is done for security reasons. The method
		 * fires an exception if there is evidence of inconsistency.
		 */

		/*
		 * If there are no rows left, we ommit the consistency check.
		 */
		if (Aitsu_Db :: fetchOne('' .
			'select count(*) from _cat where idclient = @client ') == 0) {
			return;
		}

		/*
		 * First we check if there are duplicate lft or rgt key within
		 * the given client.
		 */
		if (Aitsu_Db :: fetchOne('' .
			'select count(*) from (' .
			'	select count(*) from _cat ' .
			'	where idclient = @client ' .
			'	group by lft ' .
			'	having count(*) > 1 ' .
			'	) as a ') > 0) {
			throw new Aitsu_Core_Category_DataNotConsistentException('Duplicate lft key found');
		}
		if (Aitsu_Db :: fetchOne('' .
			'select count(*) from (' .
			'	select count(*) from _cat ' .
			'	where idclient = @client ' .
			'	group by rgt ' .
			'	having count(*) > 1 ' .
			'	) as a ') > 0) {
			throw new Aitsu_Core_Category_DataNotConsistentException('Duplicate rgt key found');
		}
		if (Aitsu_Db :: fetchOne('' .
			'select count(*) ' .
			'from _cat as a, _cat as b ' .
			'where ' .
			'	a.lft = b.rgt ' .
			'	and a.idclient = b.idclient ' .
			'	and a.idclient = @client ') > 0) {
			throw new Aitsu_Core_Category_DataNotConsistentException('Duplicate lft/rgt key found');
		}

		/*
		 * And then we check if the min(lft) == 1 and max(rgt) == 2 times 
		 * the number of rows.
		 */
		$counts = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	count(*) as numberofrows, ' .
		'	min(lft) as minleft, ' .
		'	max(rgt) as maxright ' .
		'from _cat where idclient = @client ');
		if ($counts[0]['minleft'] != 1 || $counts[0]['maxright'] / 2 != $counts[0]['numberofrows']) {
			throw new Aitsu_Core_Category_DataNotConsistentException(print_r($counts, true));
			throw new Aitsu_Core_Category_DataNotConsistentException('Rowcount does not match keys.');
		}

		/*
		 * And last not least we check that the data is well formed. Which means
		 * that there is no no lft in another's lft and rgt while the rgt is outside.
		 */
		if (Aitsu_Db :: fetchOne('' .
			'select ' .
			'	count(*) ' .
			'from _cat as a, _cat as b ' .
			'where ' .
			'	a.lft between b.lft and b.rgt ' .
			'	and a.rgt not between b.lft and b.rgt ' .
			'	and a.idclient = @client ' .
			'	and b.idclient = @client ') > 0) {
			throw new Aitsu_Core_Category_DataNotConsistentException('Data is not well formed.');
		}

		/*
		 * Remove cache tagged as navigation.
		 */
		Aitsu_Cache :: getInstance()->clean(array (
			'type_navigation'
		));
	}

	protected function _remove($idcat, $idlang) {

		/*
		 * First we determine the lft and rgt values of the category 
		 * in question to be able to close the gap afterwards.
		 */
		Aitsu_Db :: query('' .
		'select ' .
		'	@lft := lft, @rgt := rgt ' .
		'from _cat ' .
		'where idcat = :idcat ', array (
			':idcat' => $idcat
		));

		/*
		 * Remove the specified language specific categories.
		 */
		Aitsu_Db :: query('' .
		'delete from _cat_lang ' .
		'where idcat = :idcat and idlang = :idlang', array (
			':idcat' => $idcat,
			':idlang' => $idlang
		));

		if (Aitsu_Db :: fetchOne('' .
			'select count(*) from _cat_lang where idcat = :idcat', array (
				':idcat' => $idcat
			)) == 0) {
			/*
			 * As there are unused category entries, we have to
			 * delete them. But first we have to close the gap
			 * as long as we have the necessary information available.
			 */
			$this->_closePrePostGap($idcat);

			/*
			 * Then we can do deletion.
			 */
			Aitsu_Db :: query('' .
			'delete from _cat where idcat = :idcat ', array (
				':idcat' => $idcat
			));

			/*
			 * And close the left lft and rgt gap.
			 */
			Aitsu_Db :: query('' .
			'update _cat set rgt = rgt - 2 where rgt > @rgt and idclient = @client ');
			Aitsu_Db :: query('' .
			'update _cat set lft = lft - 2 where lft > @lft and idclient = @client ');
		}

		/*
		 * In any case we have to delete orphaned entries in con_art_lang and con_art.
		 */
		Aitsu_Db :: query('' .
		'delete artlang.* from _art_lang as artlang ' .
		'left join _cat_art as catart on artlang.idart = catart.idart ' .
		'left join _cat_lang as catlang on catart.idcat = catlang.idcat and artlang.idlang = catlang.idlang ' .
		'where catlang.idcatlang is null ');
		Aitsu_Db :: query('' .
		'delete art.* from _art as art ' .
		'left join _art_lang as artlang on art.idart = artlang.idart ' .
		'where artlang.idart is null ');
	}

	protected function _closePrePostGap($idcat) {

		Aitsu_Db :: query('' .
		'update _cat as a, _cat as b set ' .
		'	a.preid = b.preid ' .
		'where ' .
		'	a.idcat = b.postid ' .
		'	and b.idcat = :idcat ', array (
			':idcat' => $idcat
		));
		Aitsu_Db :: query('' .
		'update _cat as a, _cat as b set ' .
		'	a.postid = b.postid ' .
		'where ' .
		'	a.idcat = b.preid ' .
		'	and b.idcat = :idcat ', array (
			':idcat' => $idcat
		));
	}

	public function setOnline($online, $propagate, $idlang) {

		$idcat = $this->_id;

		Aitsu_Db :: startTransaction();

		try {
			if ($propagate) {
				Aitsu_Db :: query('' .
				'update _cat as parent, _cat as node, _cat_lang as catlang set ' .
				'	catlang.visible = :online ' .
				'where ' .
				'	node.lft >= parent.lft ' .
				'	and node.rgt <= parent.rgt ' .
				'	and catlang.idcat = node.idcat ' .
				'	and parent.idcat = :idcat ' .
				'	and catlang.idlang = :idlang ', array (
					':online' => $online,
					':idcat' => $idcat,
					':idlang' => $idlang
				));
			} else {
				Aitsu_Db :: query('' .
				'update _cat_lang set ' .
				'	visible = :online ' .
				'where ' .
				'	idcat = :idcat ' .
				'	and idlang = :idlang ', array (
					':online' => $online,
					':idcat' => $idcat,
					':idlang' => $idlang
				));
			}

			Aitsu_Db :: commit();

			Aitsu_Cache :: getInstance()->clean(array (
				'type_navigation'
			));
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			throw new Aitsu_Core_Exception($e->getMessage() . ' ' . $e->getTraceAsString());
		}
	}

	public function setPublic($public, $propagate, $idlang) {

		$idcat = $this->_id;

		Aitsu_Db :: startTransaction();

		try {
			if ($propagate) {
				Aitsu_Db :: query('' .
				'update _cat as parent, _cat as node, _cat_lang as catlang set ' .
				'	catlang.public = :public ' .
				'where ' .
				'	node.lft >= parent.lft ' .
				'	and node.rgt <= parent.rgt ' .
				'	and catlang.idcat = node.idcat ' .
				'	and parent.idcat = :idcat ' .
				'	and catlang.idlang = :idlang ', array (
					':public' => $public,
					':idcat' => $idcat,
					':idlang' => $idlang
				));
			} else {
				Aitsu_Db :: query('' .
				'update _cat_lang set ' .
				'	public = :public ' .
				'where ' .
				'	idcat = :idcat ' .
				'	and idlang = :idlang ', array (
					':public' => $public,
					':idcat' => $idcat,
					':idlang' => $idlang
				));
			}

			Aitsu_Db :: commit();

			Aitsu_Cache :: getInstance()->clean(array (
				'type_navigation'
			));
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			throw new Aitsu_Core_Exception($e->getMessage() . ' ' . $e->getTraceAsString());
		}
	}

	protected function _populateConfigs() {

		$idlang = Aitsu_Registry :: get()->session->currentLanguage;

		$defaultConfig = Aitsu_Db :: fetchOne('' .
		'select config from _configset where configsetid = 1');

		$defaultConfig = Aitsu_Util :: parseSimpleIni($defaultConfig);

		$config = Aitsu_Db :: fetchOne('' .
		'select configset.config ' .
		'from _configset as configset ' .
		'left join _cat_lang as catlang on configset.configsetid = catlang.configsetid ' .
		'where ' .
		'	catlang.idcat = :idcat ' .
		'	and catlang.idlang = :idlang ' .
		'limit 0, 1', array (
			':idcat' => $this->_id,
			':idlang' => $idlang
		));

		if (!$config) {
			$config = Aitsu_Db :: fetchOne('' .
			'select configset.config ' .
			'from _cat_lang as source ' .
			'left join _cat as child on source.idcat = child.idcat ' .
			'left join _cat as parent on child.lft between parent.lft and parent.rgt ' .
			'left join _cat_lang as catlang on parent.idcat = catlang.idcat and source.idlang = catlang.idlang ' .
			'left join _configset as configset on catlang.configsetid = configset.configsetid ' .
			'where ' .
			'	source.idcat = :idcat ' .
			'	and source.idlang = :idlang ' .
			'	and configset.configsetid is not null ' .
			'order by parent.lft desc ' .
			'limit 0, 1', array (
				':idcat' => $this->_id,
				':idlang' => $idlang
			));
		}

		if ($config) {
			$this->configs = Aitsu_Util :: parseSimpleIni($config, $defaultConfig);
		} else {
			$this->configs = $defaultConfig;
		}

		$config = Aitsu_Db :: fetchOne('' .
		'select config from _cat_lang ' .
		'where ' .
		'	idcat = :idcat ' .
		'	and idlang = :idlang', array (
			':idcat' => $this->_id,
			':idlang' => $idlang
		));

		if (!empty ($config)) {
			$this->configs = Aitsu_Util :: parseSimpleIni($config, $this->configs);
		}
	}

	protected function _configsAsString() {

		if (!isset ($this->_data['configs'])) {
			return;
		}

		$out = array ();

		ksort($this->_data['configs']);

		foreach ($this->_data['configs'] as $token => $config) {
			if ($config['type'] == 'normal') {
				$out[] = $token . ' = ' . $config['value'];
			}
		}

		$this->_data['configString'] = implode("\n", $out);
	}

	public function setValues(array $values) {

		foreach ($values as $key => $value) {
			$this->_data[$key] = $value;
		}

		return $this;
	}

	protected function _setConfigs() {

		$idlang = Aitsu_Registry :: get()->session->currentLanguage;

		$configs = Aitsu_Util :: parseConfig($this->configString);
		return false;
		Aitsu_Db :: query('' .
		'delete b from _cat_lang as a, _cat_lang_conf as b ' .
		'where ' .
		'	a.idcatlang = b.idcatlang ' .
		'	and a.idcat = :idcat ' .
		'	and a.idlang = :idlang ', array (
			':idcat' => $this->_id,
			':idlang' => $idlang
		));

		foreach ($configs as $token => $value) {
			$conftokenid = Aitsu_Db :: fetchOne('' .
			'select conftokenid from _conf_token ' .
			'where token = :token ', array (
				':token' => $token
			));
			if (!$conftokenid) {
				Aitsu_Db :: query('' .
				'insert into _conf_token ' .
				'(token) values (:token) ', array (
					':token' => $token
				));
				$conftokenid = $this->db->fetchOne('select last_insert_id() ');
			}
			Aitsu_Db :: query('' .
			'insert into _cat_lang_conf ' .
			'(idcatlang, conftokenid, value) ' .
			'select ' .
			'	idcatlang, ' .
			'	:conftokenid, ' .
			'	:value ' .
			'from _cat_lang ' .
			'where ' .
			'	idcat = :idcat ' .
			'	and idlang = :idlang ', array (
				':conftokenid' => $conftokenid,
				':value' => stripslashes($value),
				':idcat' => $this->_id,
				':idlang' => $idlang
			));
		}

		$this->_populateConfigs();
	}

	public function moveAfterCat($target) {

		$idlang = Aitsu_Registry :: get()->session->currentLanguage;

		Aitsu_Db :: startTransaction();

		try {
			Aitsu_Db :: query('' .
			'lock tables ' .
			'	_cat as a write, ' .
			'	_cat as b write, ' .
			'	_cat as c write, ' .
			'	_lang write, ' .
			'	_cat write, ' .
			'	_cat as node write, ' .
			'	_cat as parent write ');

			Aitsu_Db :: query("" .
			'select @client := idclient from _lang where idlang = :idlang', array (
				':idlang' => $idlang
			));

			/*
			 * Set parent id.
			 */
			Aitsu_Db :: query('' .
			'update _cat as a, _cat as b set ' .
			'	a.parentid = b.parentid ' .
			'where ' .
			'	a.idcat = :idcat ' .
			'	and b.idcat = :target ', array (
				':idcat' => $this->_id,
				':target' => $target
			));

			/*
			 * Close the gap.
			 */
			$this->_closePrePostGap($this->_id);

			/*
			 * Link the category in.
			 */
			Aitsu_Db :: query('' .
			'update _cat as a, _cat as b, _cat as c set ' .
			'	a.postid = b.idcat, ' .
			'	b.preid = a.idcat, ' .
			'	b.postid = if(c.idcat is null, 0, c.idcat), ' .
			'	c.preid = b.idcat ' .
			'where ' .
			'	a.idcat = :target ' .
			'	and b.idcat = :idcat ' .
			'	and c.idcat = a.postid ', array (
				':target' => $target,
				':idcat' => $this->_id
			));

			/*
			 * Open lft rgt gap.
			 */
			$this->_determineGapOfSource();

			Aitsu_Db :: query('' .
			'select @trgt := rgt ' .
			'from _cat where idcat = :target ', array (
				':target' => $target
			));
			Aitsu_Db :: query('' .
			'update _cat set lft = lft + @gap where lft > @trgt and idclient = @client ');
			Aitsu_Db :: query('' .
			'update _cat set rgt = rgt + @gap where rgt > @trgt and idclient = @client');

			$this->_determineLftRgtOfSource();

			/*
			 * Move category to the open gap.
			 */
			Aitsu_Db :: query('' .
			'update _cat set ' .
			'	lft = lft + @trgt + 1 - @lft, ' .
			'	rgt = rgt + @trgt + 1 - @lft ' .
			'where ' .
			'	lft >= @lft ' .
			'	and rgt <= @rgt ' .
			'	and idclient = @client ');

			$this->_closeLftRgtGap();

			$this->_checkConsistency();

			Aitsu_Db :: commit();

			Aitsu_Db :: query('unlock tables');
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			Aitsu_Db :: query('unlock tables');
			throw new Aitsu_Core_Exception($e->getMessage() . ' ' . $e->getTraceAsString());
		}
	}

	protected function _determineGapOfSource() {

		Aitsu_Db :: query('' .
		'select @gap := rgt - lft + 1 ' .
		'from _cat where idcat = :idcat ', array (
			':idcat' => $this->_id
		));
	}

	protected function _determineLftRgtOfSource() {

		Aitsu_Db :: query('' .
		'select @lft := lft, @rgt := rgt ' .
		'from _cat where idcat = :idcat ', array (
			':idcat' => $this->_id
		));
	}

	protected function _closeLftRgtGap() {

		Aitsu_Db :: query('' .
		'update _cat set lft = lft - @rgt + @lft - 1 where lft > @lft and idclient = @client ');
		Aitsu_Db :: query('' .
		'update _cat set rgt = rgt - @rgt + @lft - 1 where rgt > @rgt and idclient = @client ');
	}

	public function moveBeforeCat($target) {

		$idlang = Aitsu_Registry :: get()->session->currentLanguage;

		Aitsu_Db :: startTransaction();

		try {
			Aitsu_Db :: query('' .
			'lock tables ' .
			'	_cat as a write, ' .
			'	_cat as b write, ' .
			'	_cat as c write, ' .
			'	_lang write, ' .
			'	_cat write, ' .
			'	_cat as node write, ' .
			'	_cat as parent write ');

			Aitsu_Db :: query("" .
			'select @client := idclient from _lang where idlang = :idlang', array (
				':idlang' => $idlang
			));

			/*
			 * Set parent id.
			 */
			Aitsu_Db :: query('' .
			'update _cat as a, _cat as b set ' .
			'	a.parentid = b.parentid ' .
			'where ' .
			'	a.idcat = :idcat ' .
			'	and b.idcat = :target ', array (
				':idcat' => $this->_id,
				':target' => $target
			));

			/*
			 * Close the gap.
			 */
			$this->_closePrePostGap($this->_id);

			/*
			 * Link the category in.
			 */
			Aitsu_Db :: query('' .
			'update _cat as a, _cat as b, _cat as c set ' .
			'	a.postid = b.idcat, ' .
			'	b.preid = if(a.idcat is null, 0, a.idcat), ' .
			'	b.postid = c.idcat, ' .
			'	c.preid = b.idcat ' .
			'where ' .
			'	a.idcat = c.preid ' .
			'	and b.idcat = :idcat ' .
			'	and c.idcat = :target ', array (
				':idcat' => $this->_id,
				':target' => $target
			));

			/*
			 * Open lft rgt gap.
			 */
			$this->_determineGapOfSource();

			Aitsu_Db :: query('' .
			'select @tlft := lft ' .
			'from _cat where idcat = :target ', array (
				':target' => $target
			));
			Aitsu_Db :: query('' .
			'update _cat set lft = lft + @gap where lft >= @tlft and idclient = @client');
			Aitsu_Db :: query('' .
			'update _cat set rgt = rgt + @gap where rgt > @tlft and idclient = @client');

			$this->_determineLftRgtOfSource();

			/*
			 * Move category to the open gap.
			 */
			Aitsu_Db :: query('' .
			'update _cat set ' .
			'	lft = lft + @tlft - @lft, ' .
			'	rgt = rgt + @tlft - @lft ' .
			'where ' .
			'	lft >= @lft ' .
			'	and rgt <= @rgt ' .
			'	and idclient = @client ');

			$this->_closeLftRgtGap();

			$this->_checkConsistency();

			Aitsu_Db :: commit();

			Aitsu_Db :: query('unlock tables');
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			Aitsu_Db :: query('unlock tables');
			throw new Aitsu_Core_Exception($e->getMessage() . ' ' . $e->getTraceAsString());
		}
	}

	public function moveInsideCat($target) {

		/*
		 * If the catgory is not empty, we have to determine the id
		 * of the last category within the target and do a moveAfterCat.
		 */
		if ($newTarget = Aitsu_Db :: fetchOne('' .
			'select idcat from _cat ' .
			'where ' .
			'	parentid = :idcat ' .
			'order by ' .
			'	rgt desc ' .
			'limit 0, 1', array (
				':idcat' => $target
			))) {
			$this->moveAfterCat($newTarget);
			return;
		}

		$idlang = Aitsu_Registry :: get()->session->currentLanguage;

		Aitsu_Db :: startTransaction();

		try {
			Aitsu_Db :: query('' .
			'lock tables ' .
			'	_cat as a write, ' .
			'	_cat as b write, ' .
			'	_cat as c write, ' .
			'	_lang write, ' .
			'	_cat write, ' .
			'	_cat as node write, ' .
			'	_cat as parent write ');

			Aitsu_Db :: query("" .
			'select @client := idclient from _lang where idlang = :idlang', array (
				':idlang' => $idlang
			));

			/*
			 * Change the parentid of the source category to the value of
			 * the target category.
			 */
			Aitsu_Db :: query('' .
			'update _cat set parentid = :target where idcat = :idcat', array (
				':target' => $target,
				':idcat' => $this->_id
			));

			/*
			 * Close the preid postid gap where the the source category
			 * comes from. 
			 */
			$this->_closePrePostGap($this->_id);

			/*
			 * As the category will be the last within the target category
			 * we can set its postid value to 0. The preid value will also
			 * be 0, except there is already one or more child categories
			 * in the target category.
			 */
			Aitsu_Db :: query('' .
			'update _cat set postid = 0, preid = 0 where idcat = :idcat ', array (
				':idcat' => $this->_id
			));

			$childCatId = Aitsu_Db :: fetchOne('' .
			'select idcat from _cat where parentid = :target and postid = 0 and idcat != :idcat ', array (
				':target' => $target,
				':idcat' => $this->_id
			));
			if ($childCatId) {
				/*
				 * The target category is not empty. We therefore have to put
				 * the category at the end of the specific category.
				 */
				Aitsu_Db :: query('' .
				'update _cat as a, _cat as b set ' .
				'	a.postid = b.idcat, ' .
				'	b.preid = a.idcat ' .
				'where ' .
				'	a.idcat = :target ' .
				'	and b.idcat = :idcat ', array (
					':target' => $target,
					':idcat' => $this->_id
				));
			}

			/*
			 * At the very beginning of the nested sets update we have to 
			 * open the area where the category will reside after moving.
			 */
			$this->_determineGapOfSource();

			Aitsu_Db :: query('' .
			'select @tlft := lft, @trgt := rgt ' .
			'from _cat where idcat = :target ', array (
				':target' => $target
			));
			Aitsu_Db :: query('' .
			'update _cat set lft = lft + @gap where lft > @tlft and idclient = @client ');
			Aitsu_Db :: query('' .
			'update _cat set rgt = rgt + @gap where rgt >= @trgt and idclient = @client ');

			/*
			 * Then we determine the lft and rgt value of the category in
			 * question to be able to reference them later on.
			 */
			$this->_determineLftRgtOfSource();

			/*
			 * It's time now to move the category and all its child elements.
			 */
			Aitsu_Db :: query('' .
			'update _cat set ' .
			'	lft = lft + @trgt - @lft, ' .
			'	rgt = rgt + @trgt - @lft ' .
			'where ' .
			'	lft >= @lft ' .
			'	and rgt <= @rgt ' .
			'	and idclient = @client ');

			/*
			 * Now we close the lft/rgt gap where the source category
			 * comes from.
			 */
			$this->_closeLftRgtGap();

			$this->_checkConsistency();

			Aitsu_Db :: commit();

			Aitsu_Db :: query('unlock tables');
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			Aitsu_Db :: query('unlock tables');
			throw new Aitsu_Core_Exception($e->getMessage() . ' ' . $e->getTraceAsString());
		}
	}

	public function synchronize($syncLang) {

		$idlang = Aitsu_Registry :: get()->session->currentLanguage;

		Aitsu_Db :: startTransaction();

		try {
			Aitsu_Db :: query('' .
			'lock tables ' .
			'	_cat_lang write, ' .
			'	_cat_lang as catlangsrc write ');

			Aitsu_Db :: query('' .
			'select @idlang := :idlang ', array (
				':idlang' => $idlang
			));
			Aitsu_Db :: query('' .
			'insert into _cat_lang ' .
			'(idcat, idlang, name, visible, public, urlname) ' .
			'select ' .
			'	idcat, ' .
			'	@idlang, ' .
			'	name, ' .
			'	0 as visible, ' .
			'	1 as public, ' .
			'	urlname ' .
			'from _cat_lang as catlangsrc ' .
			'where ' .
			'	idcat = :idcat ' .
			'	and idlang = :syncLang ', array (
				':idcat' => $this->_id,
				':syncLang' => $syncLang
			));

			Aitsu_Db :: commit();

			Aitsu_Db :: query('unlock tables');
		} catch (Exception $e) {
			Aitsu_Db :: rollback();
			Aitsu_Db :: query('unlock tables');
			throw new Aitsu_Core_Exception($e->getMessage() . ' ' . $e->getTraceAsString());
		}
	}

	public static function path($idcat) {

		return Aitsu_Db :: fetchCol('' .
		'select parent.idcat ' .
		'from _cat as child ' .
		'left join _cat as parent on child.lft between parent.lft and parent.rgt ' .
		'where child.idcat = :idcat ' .
		'order by parent.lft asc ', array (
			':idcat' => $idcat
		));
	}

	public static function isChildOf($child, $parent) {

		if (Aitsu_Db :: fetchOne('' .
			'select count(parent.idcat) from _cat as parent ' .
			'left join _cat as child on child.lft between parent.lft and parent.rgt ' .
			'where ' .
			'	parent.idcat = :parent ' .
			'	and child.idcat = :child ', array (
				':child' => $child,
				':parent' => $parent
			)) > 0) {
			return true;
		}

		return false;
	}
}