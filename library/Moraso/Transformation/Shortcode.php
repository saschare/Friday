<?php

/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2012, w3concepts AG
 * 
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Moraso_Transformation_Shortcode implements Aitsu_Event_Listener_Interface {

    protected function __construct() {
        
    }

    public static function getInstance() {

        static $instance = null;

        if (!isset($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    public static function notify(Aitsu_Event_Abstract $event) {

        if (!isset($event->bootstrap->pageContent)) {
            return;
        }

        $event->bootstrap->pageContent = self :: getInstance()->getContent($event->bootstrap->pageContent);
    }

    protected function _rewrite(& $content) {

        $matches = array();

        if (preg_match_all('/(<p(>|\s+[^>]*>)\s\t_\\[(.*?)\\:(.*?)(:?\\:(\\d*))?\\]<\/p>)/', $content, $matches) > 0) {
            unset($matches[0]);
            unset($matches[2]);
            $content = $this->_rewriteShortcodes($content, array_values($matches));
        }
        
        unset($matches);
        
        if (preg_match_all('/_\\[(.*?)\\:(.*?)(:?\\:(\\d*))?\\]/', $content, $matches) > 0) {
            $content = $this->_rewriteShortcodes($content, $matches);
        }
        
        unset($matches);

        if (preg_match_all('@<script\\s+type=\"application/x-(aitsu|moraso)\"\\s+src=\"([^:\"]+):?([^\"]*)\"[^/>]*(?:(?:/>)|(?:>(.*?)</script>))@s', $content, $matches) > 0) {
            unset($matches[1]);
            $content = $this->_rewriteScriptCodes($content, array_values($matches));
        }

        unset($matches);
    }

    public function getContent($content) {

        $this->_rewrite($content);

        return str_replace('_|[', '_[', $content);
    }

    protected function _switchTo($idartlang, $back = false) {

        static $old = array();
        static $regClone;

        if ($back) {
            Aitsu_Registry :: get()->env = $regClone->env;
            Aitsu_Registry :: get()->env->idartlang = $old['idartlang'];
            Aitsu_Registry :: get()->env->idart = $old['idart'];
            Aitsu_Registry :: get()->env->idlang = $old['idlang'];
            Aitsu_Registry :: get()->env->client = $old['client'];
            return;
        }

        $context = Aitsu_Core_Module_Context :: get($idartlang);
        $regClone = clone Aitsu_Registry :: get();
        $old['idartlang'] = Aitsu_Registry :: get()->env->idartlang;
        $old['idart'] = Aitsu_Registry :: get()->env->idart;
        $old['idlang'] = Aitsu_Registry :: get()->env->idlang;
        $old['idcat'] = Aitsu_Registry :: get()->env->idcat;
        $old['client'] = Aitsu_Registry :: get()->env->client;
        Aitsu_Registry :: get()->env->idart = $context['idart'];
        Aitsu_Registry :: get()->env->idartlang = $context['idartlang'];
        Aitsu_Registry :: get()->env->idlang = $context['idlang'];
        Aitsu_Registry :: get()->env->idcat = $context['idcat'];
        Aitsu_Registry :: get()->env->client = $context['client'];
    }

    protected function _rewriteShortcodes($content, $matches) {

        $client = Aitsu_Config::get('sys.client');

        $sc = Moraso_Shortcode :: getInstance();

        for ($i = 0; $i < count($matches[0]); $i++) {
            $method = $matches[1][$i];

            if (!empty($matches[3][$i])) {
                $this->_switchTo(substr($matches[3][$i], 1));
            }

            try {
                $replacement = $sc->evalModule($method, null, $client, $matches[2][$i], empty($matches[3][$i]));
            } catch (Aitsu_Security_Exception $e) {
                throw $e;
            } catch (Exception $e) {
                $replacement = $e->getMessage();
            }

            if (!empty($matches[3][$i])) {
                $this->_switchTo(0, true);
            }

            $content = str_replace($matches[0][$i], $replacement, $content);
        }

        $this->_rewrite($content);

        return $content;
    }

    protected function _rewriteScriptCodes($content, $matches) {

        $client = Aitsu_Config::get('sys.client');

        $sc = Moraso_Shortcode :: getInstance();

        for ($i = 0; $i < count($matches[0]); $i++) {
            $method = $matches[1][$i];
            $index = isset($matches[2][$i]) ? $matches[2][$i] : '';
            $params = isset($matches[3][$i]) ? $matches[3][$i] : null;

            $switched = false;
            if (preg_match('/execcontext\\s*=\\s*(\\d*)/', $params, $match)) {
                $this->_switchTo($match[1]);
                $switched = true;
            }

            try {
                $replacement = $sc->evalModule($method, $params, $client, $index, true);
            } catch (Aitsu_Security_Exception $e) {
                throw $e;
            } catch (Exception $e) {
                $replacement = $e->getMessage();
            }

            if ($switched) {
                $this->_switchTo(0, true);
            }

            $content = str_replace($matches[0][$i], $replacement, $content);
        }

        $this->_rewrite($content);

        return $content;
    }

}