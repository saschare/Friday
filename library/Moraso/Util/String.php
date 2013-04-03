<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2012, webtischlerei <http://www.webtischlerei.de>
 * 
 * @category Moraso
 * @package Util
 * @subpackage String
 * 
 * @since 1.2.4-1
 */
class Moraso_Util_String {

    /**
     * The perfect PHP clean url generator
     * 
     * Here is an example:
     * <pre><code>
     * <?php
     * $slug = Moraso_Util_String::slugify("Hällö Foo");
     * 
     * echo $slug; // haelloe-foo
     * ?>
     * </code></pre>
     * 
     * @example http://www.webtischlerei.de/moraso-cms/doc/functions/util/string/slugify.html How to use this function
     * @since 1.2.4-1
     * @param string $string String to slugify
     * @return string $slug Slugified string
     */
    public static function slugify($string) {

        $in_charset = mb_detect_encoding($string);
        $out_charset = 'ASCII//TRANSLIT';

        $slug_trim = trim($string);

        $slug_tolower = strtolower($slug_trim);

        $specialCharactersSearch = array('ä', 'ö', 'ü', 'à', 'ç', 'è', 'é', 'ê', 'ß');
        $specialCharactersReplace = array('ae', 'ou', 'ue', 'a', 'c', 'e', 'e', 'e', 'ss');

        $slug_specialCharactersReplaced = str_replace($specialCharactersSearch, $specialCharactersReplace, $slug_tolower);

        $slug_iconv = iconv($in_charset, $out_charset, $slug_specialCharactersReplaced);

        $slug_whitespace = preg_replace("%[^-/+|\w ]%", '', $slug_iconv);

        return preg_replace("/[\/_|+ -]+/", '-', $slug_whitespace);
    }

    /**
     * Creates a random string
     * 
     * Here is an example:
     * <pre><code>
     * <?php
     * $random = Moraso_Util_String::random(8, 'avd75FGW');
     * 
     * echo $random; // v5GFav7W
     * ?>
     * </code></pre>
     * 
     * @example http://www.webtischlerei.de/moraso-cms/doc/functions/util/string/random.html How to use this function
     * @since 1.2.5-1
     * @param int $length Length of random string
     * @return string $character Characters to create random string
     */
    public static function random($length = 16, $character = null) {

        if (empty($character)) {
            $character = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        $character_count = strlen($character);

        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= $character[mt_rand(0, $character_count - 1)];
        }

        return $random;
    }

}