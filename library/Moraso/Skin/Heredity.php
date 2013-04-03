<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Moraso_Skin_Heredity {

    public static function build() {

        $cachedSkinHeredity = Aitsu_Core_Cache::getInstance('skinHeredity_Client' . Aitsu_Config::get('sys.client'));

        if ($cachedSkinHeredity->isValid()) {
            return unserialize($cachedSkinHeredity->load());
        }

        $heredity = self::_build();

        $cachedSkinHeredity->setLifetime(60 * 60 * 24 * 365 * 10);
        $cachedSkinHeredity->save(serialize($heredity), array('skin'));

        return $heredity;
    }

    private static function _build(& $heredity = null, $skin = null) {

        if (empty($heredity)) {
            $heredity = array();
        }

        if (empty($skin)) {
            $skin = Aitsu_Registry :: get()->config->skin;
        }

        $heredity[] = $skin;

        $xml_file = APPLICATION_PATH . '/skins/' . $skin . '/skin.xml';

        if (is_readable($xml_file)) {
            $xml = simplexml_load_file($xml_file);

            $parentSkin = (string) $xml->parent->skin[0];

            if (!empty($parentSkin)) {
                self::buildHeredity($heredity, $parentSkin);
            }
        }

        return $heredity;
    }

}