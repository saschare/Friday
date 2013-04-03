<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Moraso_Shortcode extends Aitsu_Shortcode {

    public static function getInstance() {
        static $instance = null;

        if (!isset($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    public function evalModule($method, $params, $client, $index, $current = true) {

        $index = preg_replace('/[^a-zA-Z_0-9]/', '', $index);

        $profileDetails = new stdClass();
        Aitsu_Profiler :: profile($method . ':' . $index);

        $returnValue = '';
        Aitsu_Content_Edit :: isBlock(true);

        $files = array();

        $heredity = Moraso_Skin_Heredity::build();

        foreach ($heredity as $skin) {
            $files['Skin_Module'][] = APPLICATION_PATH . "/skins/" . $skin . "/module/" . str_replace('.', '/', $method) . "/Class.php";
        }

        $files['Moraso_Module'] = realpath(APPLICATION_PATH . '/../library/') . '/Moraso/Module/' . str_replace('.', '/', $method) . '/Class.php';
        $files['Module'] = APPLICATION_PATH . '/modules/' . str_replace('.', '/', $method) . '/Class.php';

        $exists = false;

        foreach ($files as $prefix => $file) {
            if (is_array($file)) {
                foreach ($file as $path) {
                    if (file_exists($path)) {
                        $exists = true;
                        $profileDetails->source = $prefix . '_' . str_replace('.', '_', $method) . '_Class';
                        include_once $path;
                        $returnValue = call_user_func(array(
                            $profileDetails->source,
                            'init'
                                ), array(
                            'index' => $index,
                            'params' => $params,
                            'className' => $profileDetails->source
                        ));
                        break;
                    }
                }
                if ($exists) {
                    break;
                }
            } else {
                if (file_exists($file)) {
                    $exists = true;
                    $profileDetails->source = $prefix . '_' . str_replace('.', '_', $method) . '_Class';
                    include_once $file;
                    $returnValue = call_user_func(array(
                        $profileDetails->source,
                        'init'
                            ), array(
                        'index' => $index,
                        'params' => $params,
                        'className' => $profileDetails->source
                    ));
                    break;
                }
            }
        }

        if (!$exists) {
            Aitsu_Profiler :: profile($method . ':' . $index, (object) array(
                        'source' => 'not found'
            ));
            if (Aitsu_Registry :: isEdit()) {
                return '<strong>' . sprintf(Aitsu_Registry :: translator()->translate('// The ShortCode \'%s\' does not exist. //'), $method) . '</strong>';
            } else {
                return '';
            }
        }

        if (is_object($returnValue)) {
            $index = $returnValue->index;
            $returnValue = $returnValue->out;
        }

        Aitsu_Profiler :: profile($method . ':' . $index, $profileDetails);

        if (Aitsu_Registry :: isBoxModel() && !Aitsu_Content_Edit :: noEdit($method)) {
            $returnValue = '<shortcode method="' . $method . '" index="' . $index . '">' . $returnValue . '</shortcode>';
        } elseif (Aitsu_Application_Status :: isStructured()) {
            $startmarker = '<!--fragment:start ' . $method . '-' . $index . '-->';
            $endmarker = '<!--fragment:end ' . $method . '-' . $index . '-->';
            $returnValue = $startmarker . $returnValue . $endmarker;
        } elseif (Aitsu_Registry :: isEdit() && !Aitsu_Content_Edit :: noEdit($method)) {
            $isBlock = Aitsu_Content_Edit :: isBlock();
            if ($isBlock === true) {
                $returnValue = '<div id="' . $method . '-' . $index . '-' . Aitsu_Registry :: get()->env->idartlang . '" class="aitsu_editable"><div class="aitsu_hover">' . $returnValue . '</div></div>';
            } else {
                $returnValue = '<span id="' . $method . '-' . $index . '-' . Aitsu_Registry :: get()->env->idartlang . '" class="aitsu_editable" style="display:inline;"><span class="aitsu_hover">' . $returnValue . '</span></span>';
            }
        }

        return $returnValue;
    }

}