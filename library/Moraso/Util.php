<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Moraso_Util extends Aitsu_Util {

    public static function getIdlang() {

        $idlang = Aitsu_Registry :: get()->env->idlang;

        if (empty($idlang)) {
            $idlang = Aitsu_Registry :: get()->session->currentLanguage;
        }

        return $idlang;
    }

    public static function getEnv() {

        if (!empty($_SERVER['PHP_FCGI_CHILDREN']) || !empty($_SERVER['FCGI_ROLE'])) {
            $env = (getenv("REDIRECT_AITSU_ENV") == '' ? 'live' : getenv("REDIRECT_AITSU_ENV"));
        } else {
            $env = (getenv("AITSU_ENV") == '' ? 'live' : getenv("AITSU_ENV"));
        }

        return $env;
    }

    public static function getIdClient() {

        $idclient = Aitsu_Registry :: get()->env->idclient;

        if (empty($idclient)) {
            $idclient = Aitsu_Registry :: get()->session->currentClient;
        }

        return $idclient;
    }

    public static function parseSimpleIni($text, $base = null) {

        $base = new Zend_Config_Ini(!empty($base) ? $base : '1 = 1', null, array(
            'allowModifications' => true
        ));
        $text = new Zend_Config_Ini(!empty($text) ? $text : '1 = 1');

        return $base->merge($text);
    }
}