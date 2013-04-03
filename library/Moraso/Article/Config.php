<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Moraso_Article_Config extends Aitsu_Article_Config {

    private $_data = null;

    protected function __construct($idart, $idlang) {

        $defaultConfig = Moraso_Db::fetchOneC('eternal', '' .
                                'select ' .
                                '   config ' .
                                'from ' .
                                '   _configset ' .
                                'where ' .
                                '   configsetid =:configsetid', array(
                            ':configsetid' => 1
        ));

        $articleConfig = Moraso_Db::fetchOneC('eternal', '' .
                        'select ' .
                        '   configset.config ' .
                        'from ' .
                        '   _configset as configset ' .
                        'left join ' .
                        '   _art_lang as artlang on configset.configsetid = artlang.configsetid ' .
                        'where ' .
                        '   artlang.idart =:idart ' .
                        '   and artlang.idlang =:idlang ' .
                        'limit ' .
                        '   0, 1', array(
                    ':idart' => $idart,
                    ':idlang' => $idlang
        ));

        if (!$articleConfig) {
            $articleConfig = Moraso_Db::fetchOneC('eternal', '' .
                            'select ' .
                            '   configset.config ' .
                            'from ' .
                            '   _art_lang as artlang ' .
                            'left join ' .
                            '   _cat_art as catart on artlang.idart = catart.idart ' .
                            'left join ' .
                            '   _cat as child on catart.idcat = child.idcat ' .
                            'left join ' .
                            '   _cat as parent on child.lft between parent.lft and parent.rgt ' .
                            'left join ' .
                            '   _cat_lang as catlang on parent.idcat = catlang.idcat and artlang.idlang = catlang.idlang ' .
                            'left join ' .
                            '   _configset as configset on catlang.configsetid = configset.configsetid ' .
                            'where ' .
                            '	artlang.idart =:idart ' .
                            '	and ' .
                            '   artlang.idlang =:idlang ' .
                            '	and ' .
                            '   configset.configsetid is not null ' .
                            'order by ' .
                            '   parent.lft desc ' .
                            'limit ' .
                            '   0, 1', array(
                        ':idart' => $idart,
                        ':idlang' => $idlang
            ));
        }

        $this->_data = Moraso_Util::parseSimpleIni($articleConfig ? $articleConfig : $defaultConfig, $articleConfig ? $defaultConfig : null);
    }

    public static function factory($idart = null) {

        static $instance = array();

        $idart = $idart == null ? Aitsu_Registry :: get()->env->idart : $idart;
        $idlang = Aitsu_Registry :: get()->env->idlang;

        if (!isset($instance[$idart . '_' . $idlang])) {
            $instance[$idart . '_' . $idlang] = new self($idart, $idlang);
        }

        return $instance[$idart . '_' . $idlang];
    }

    public function __get($key) {

        if (!isset($this->_data->$key)) {
            return null;
        }

        return $this->_data->$key;
    }

}