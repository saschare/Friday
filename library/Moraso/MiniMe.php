<?php

/**
 * CSS and JS minifier. This class lets you add CSS and JS resources
 * from within other classes and modules. It adds a single reference to the
 * head of the HTML document and delivers the specified resources bundled
 * to a single file (one for each type, i.e. one CSS and one JS).
 * 
 * The files are expected to be in the directory /application/skins/myTemplate/css, 
 * respectivelly /application/skins/myTemplate/js or beneath. Except otherwise configured
 * in the config.ini.
 * 
 * IMPORTANT: If you change the resources (i.e. add new, delete existing or if you
 * change their content), you have to clear the cache by (domain.tld/?clearcache=minifier).
 * 
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2011, w3concepts AG
 * 
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Moraso_MiniMe implements Aitsu_Event_Listener_Interface {

    protected $cache = null;
    protected $pathSkin = null;
    protected $pathCss = null;
    protected $pathJs = null;
    protected $resources;
    protected $addedResources = array();

    public static function notify(Aitsu_Event_Abstract $event) {

        if (!isset($_GET['minify'])) {
            return;
        }

        self :: init();
    }

    protected function __construct() {

        $this->pathSkin = APPLICATION_PATH . '/skins/' . Aitsu_Registry :: get()->config->skin;
        $this->pathCss = $this->pathSkin;
        $this->pathJs = $this->pathSkin;

        if (isset(Aitsu_Registry :: get()->config->minifier->css->path)) {
            $this->pathCss = Aitsu_Registry :: get()->config->minifier->css->path;
        }

        if (isset(Aitsu_Registry :: get()->config->minifier->js->path)) {
            $this->pathJs = Aitsu_Registry :: get()->config->minifier->js->path;
        }

        $this->_mapResources();
    }

    /**
     * This method is called from the controller if a minified ressource is
     * requested. As defined in the Aitsu_Core_Init_Interface.
     */
    public static function init() {

        header("Pragma: public");
        header("Cache-Control: max-age=" . (60 * 60 * 24 * 7));
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60 * 60 * 24 * 7) . ' GMT');

        if (isset($_GET['type']) && $_GET['type'] == 'js') {
            header("Content-Type: text/javascript; charset=UTF-8");

            $cache = Aitsu_Cache :: getInstance('MiniMe_JS_' . (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : uniqid()));
            if ($cache->isValid()) {
                header("HTTP/1.1 304 Not Modified");
                header("Connection: Close");
                exit();
            }

            $cacheId = str_replace('-', '_', $_GET['minify']);
            $cache = Aitsu_Cache :: getInstance('MiniMe_JS_' . $cacheId);
            if ($cache->isValid()) {
                $output = $cache->load();
                $etag = hash('md4', $output);
                header("ETag: {$etag}");
                echo $output;
                exit();
            }

            $instance = self :: getInstance();

            $output = '';
            $resources = explode('-', $_GET['minify']);
            foreach ($resources as $res) {
                if (isset($instance->resources['js'][0][$res])) {
                    $output .= file_get_contents($instance->resources['js'][0][$res]);
                }
            }

            $instance->_minifyJs($output);

            $cache->setLifetime(60 * 60 * 24 * 365);
            $cache->save($output, array(
                'skin'
            ));

            $etag = hash('md4', $output);
            $cache = Aitsu_Cache :: getInstance('MiniMe_JS_' . $etag);
            $cache->setLifetime(60 * 60 * 24 * 365);
            $cache->save($output, array(
                'skin'
            ));
            header("ETag: {$etag}");

            $instance->_saveTransparentCache($output);

            echo $output;
            exit();
        }

        if (isset($_GET['type']) && $_GET['type'] == 'css') {
            header("Content-Type: text/css; charset=UTF-8");

            $cache = Aitsu_Cache :: getInstance('MiniMe_CSS_' . (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : uniqid()));
            if ($cache->isValid()) {
                header("HTTP/1.1 304 Not Modified");
                header("Connection: Close");
                exit();
            }

            $cacheId = str_replace('-', '_', $_GET['minify']);
            $cache = Aitsu_Cache :: getInstance('MiniMe_CSS_' . $cacheId);
            if ($cache->isValid()) {
                $output = $cache->load();
                $etag = hash('md4', $output);
                header("ETag: {$etag}");
                echo $output;
                exit();
            }

            $instance = self :: getInstance();

            $output = '';
            $resources = explode('-', $_GET['minify']);

            /*
             * We have to remove the first segment, as this segment's solely
             * intention is to make the css uri unique.
             */
            array_shift($resources);

            foreach ($resources as $res) {
                $output .= $instance->_makeAbsolute(file_get_contents($instance->resources['css'][0][$res]), $instance->resources['css'][0][$res]);
            }

            $output = preg_replace('/@charset\\s\"UTF-8\";/', "", $output);
            $output = "@charset \"UTF-8\";\n" . $output;

            $instance->_minifyCss($output);

            $cache->save($output, array(
                'skin'
            ));

            $etag = hash('md4', $output);
            $cache = Aitsu_Cache :: getInstance('MiniMe_CSS_' . $etag);
            $cache->setLifetime(60 * 60 * 24 * 365);
            $cache->save($output, array(
                'skin'
            ));

            $instance->_saveTransparentCache($output);

            header("ETag: {$etag}");

            echo $output;
            exit();
        }

        exit();
    }

    protected function _saveTransparentCache($output) {

        $pathInfo = pathinfo($_SERVER['REQUEST_URI']);
        $path = trim($pathInfo['dirname'], '/');

        $dir = APPLICATION_PATH . '/data/cachetransparent/data/' . $path;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($dir . '/' . $_GET['minify'] . '.minime.' . $_GET['type'], $output);
    }

    public static function getInstance() {

        static $instance;

        if (!isset($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Called from the controller in the transformation phase (after rendering). The
     * method adds zero, one or two references to CSS and/or JS ressources, representing
     * the specified and optionally minified ressources.
     */
    public function getContent($content) {

        if (isset($this->addedResources['css'])) {
            $content = str_replace('<!-- minify:CSS -->', implode('-', $this->addedResources['css']) . '.minime.css', $content);
        }

        if (isset($this->addedResources['js'])) {
            $content = str_replace('<!-- minify:JS -->', implode('-', $this->addedResources['js']) . '.minime.js', $content);
        }

        return $content;
    }

    public static function addCss($path) {

        $instance = self :: getInstance();

        if (!isset($instance->resources['css'][1][$path])) {
            throw new Aitsu_MiniMe_ResourceMissingException('The resource ' . $path . ' is not available.');
        }

        $instance->addedResources['css'][$path] = $instance->resources['css'][1][$path];
    }

    public static function addJs($path) {

        $instance = self :: getInstance();

        if (!isset($instance->resources['js'][1][$path])) {
            throw new Aitsu_MiniMe_ResourceMissingException('The resource ' . $path . ' is not available.');
        }

        $instance->addedResources['js'][$path] = $instance->resources['js'][1][$path];
    }

    public static function getUri($type, $resources) {

        $instance = self :: getInstance();

        $addedResources = array();
        foreach ($resources as $res) {
            if (!isset($instance->resources[$type][1][$res])) {
                throw new Aitsu_MiniMe_ResourceMissingException('The resource ' . $res . ' is not available.');
            }
            $addedResources[] = $instance->resources[$type][1][$res];
        }
        
        return uniqid() . '-' . implode('-', $addedResources) . '.minime.' . $type;
    }

    protected function _mapResources() {

        $css = array();

        $heredity = Moraso_Skin_Heredity::build();

        $heredity_reversed = array_reverse($heredity);

        $resourcesMap = array();
        
        foreach ($heredity_reversed as $skin) {
            $this->_scanDir(APPLICATION_PATH . '/skins/' . $skin, $css, '.css');

            foreach ($css as $key => $resource) {
                $parts = explode('/', substr($resource, strlen(APPLICATION_PATH . '/skins') + 1));

                $resourcesMap['css'][0][base_convert($key, 10, 36)] = $resource;
                $resourcesMap['css'][1][substr($resource, strlen(APPLICATION_PATH . '/skins/' . $parts[0]))] = base_convert($key, 10, 36);
            }

            $js = array();
            $this->_scanDir(APPLICATION_PATH . '/skins/' . $skin, $js, '.js');
            foreach ($js as $key => $resource) {
                $parts = explode('/', substr($resource, strlen(APPLICATION_PATH . '/skins') + 1));

                $resourcesMap['js'][0][base_convert($key, 10, 36)] = $resource;
                $resourcesMap['js'][1][substr($resource, strlen(APPLICATION_PATH . '/skins/' . $parts[0]))] = base_convert($key, 10, 36);
            }
        }

        $this->resources = $resourcesMap;
    }

    protected function _scanDir($path, & $files, $endsWith) {

        if (!is_dir($path) || !is_readable($path)) {
            return;
        }

        $content = scandir($path);

        foreach ($content as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($path . '/' . $file)) {
                    $this->_scanDir($path . '/' . $file, $files, $endsWith);
                } else {
                    if (substr($file, strlen($endsWith) * -1) == $endsWith) {
                        $files[] = $path . '/' . $file;
                    }
                }
            }
        }
    }

    protected function _makeAbsolute($css, $res) {

        $res = substr($res, strlen($this->pathSkin));
        $dir = dirname($res);
        $levels = explode('/', $dir);

        if (preg_match_all('/url\\((["\']?)?([^\\1\\)]*)\\1\\)/', $css, $matches) == 0) {
            return $css;
        }

        $env = '';
        if (isset(Aitsu_Registry :: get()->config->env) && Aitsu_Registry :: get()->config->env == 'admin') {
            $env = 'admin/';
        }

        for ($i = 0; $i < count($matches[0]); $i++) {
            $match = $matches[0][$i];
            $quote = $matches[1][$i];
            $url = $matches[2][$i];
            if (substr($url, 0, 4) != 'http' && substr($url, 0, 1) != '/') {
                if (substr($url, 0, 1) != '.' && substr($url, 0, 4) != 'http') {
                    // Link showing downwards.
                    $css = str_replace($matches[0][$i], "url({$quote}" . Aitsu_Registry :: get()->config->sys->mainDir . "skin/{$dir}/{$url}{$quote})", $css);
                } else {
                    // Link showing upwards.
                    $dirs = substr_count($url, '../');
                    $new = array();
                    for ($j = 0; $j < count($levels) - $dirs; $j++) {
                        $new[] = $levels[$j];
                    }
                    $new = implode('/', $new);
                    $url = str_replace('../', '', $url);
                    $css = str_replace($matches[0][$i], "url({$quote}" . Aitsu_Registry :: get()->config->sys->mainDir . "skin/{$env}{$new}/{$url}{$quote})", $css);
                    $css = str_replace('//', '/', $css);
                }
            }
        }

        return $css;
    }

    protected function _minifyCss(& $css) {

        // Remove comments
        $css = preg_replace('@/\\*.*?\\*/@s', "", $css);

        // Remove whitespace
        $css = preg_replace('/\\s*{/s', "{", $css);
        $css = preg_replace('/\\{\\s*/s', "{", $css);
        $css = preg_replace('/\\}\\s*/', "}", $css);
        $css = preg_replace('/;\\s*/', ";", $css);
        $css = preg_replace('/,\\s*/', ",", $css);
    }

    protected function _minifyJs(& $js) {

        include_once ('Aitsu/MiniMe/jsmin.php');

        $js = JSMin :: minify($js);
    }

}