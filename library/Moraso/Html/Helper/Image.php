<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Moraso_Html_Helper_Image {

    public static function getHtml($idart, $filename, $width, $height, $boxed, $attributes = null) {

        $imgPath = self::getPath($idart, $filename, $width, $height, $boxed);

        if (!empty($attributes)) {
            $imgAttr = self::_createAttributes($attributes);
        }

        return '<img src="' . $imgPath . '" ' . $imgAttr . ' />';
    }

    public static function getPath($idart, $filename, $width, $height, $boxed) {

        return Aitsu_Config::get('sys.webpath') . 'image/' . $width . '/' . $height . '/' . $boxed . '/' . $idart . '/' . $filename;
    }

    private static function _createAttributes($attributes) {

        $return = '';

        foreach ($attributes as $attr => $value) {
            if (is_object($value)) {
                $return .= ' ' . $attr . '="';
                foreach ($value as $style => $styleValue) {
                    if (!empty($styleValue)) {
                        if ($style == 'self') {
                            $return .= $styleValue;
                        } else {
                            $return .= '' . $style . ': ' . $styleValue . ';';
                        }
                    }
                }
                $return .= '"';
            } else {
                if (!empty($value)) {
                    $return .= ' ' . $attr . '="' . $value . '"';
                }
            }
        }

        return $return;
    }

}