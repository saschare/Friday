<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Moraso_Module_Template_Class extends Moraso_Module_Abstract {

    protected static function _getDefaultTemplate($index, $params) {

        if (isset(Moraso_Article_Config::factory()->module->template->$index->defaultTemplate)) {
            $defaultTemplate = Moraso_Article_Config::factory()->module->template->$index->defaultTemplate;
        }

        if (!isset($defaultTemplate)) {
            return $params->defaultTemplate;
        }

        if (!isset($defaultTemplate->ifindex)) {
            return $defaultTemplate->default;
        }

        if (Aitsu_Persistence_Article::factory(Aitsu_Registry::get()->env->idart, Aitsu_Registry::get()->env->idlang)->isIndex()) {
            return $defaultTemplate->ifindex;
        }

        return $defaultTemplate->default;
    }

    protected function _init() {

        if (isset($_REQUEST['renderOnly'])) {
            return '<script type="application/x-moraso" src="' . $_REQUEST['renderOnly'] . '">' . (isset($_REQUEST['params']) ? $_REQUEST['params'] : '') . '</script>';
        }

        Aitsu_Content_Edit::noEdit('Template', true);

        $index = str_replace('_', ' ', $this->_index);
        $parameters = $this->_params;
        $params = Aitsu_Content_Config_Hidden::set($index, 'Template_params', $parameters);

        $idartlang = Aitsu_Registry::get()->env->idartlang;

        $startTag = '';
        $endTag = '';
        $output = '';

        $keys = array();

        if (isset($params->template)) {

            $data = (array) $params->template;

            foreach ($data as $key => $line) {
                $keyValuePairs[$line->name] = $key;
                $keys[] = $key;
            }
            $template = Aitsu_Content_Config_Radio::set($index, 'SubTemplate', '', $keyValuePairs, 'Template');

            if (Aitsu_Registry::isEdit()) {
                $edit = (isset($params->hoverEdit) && $params->hoverEdit) || !isset($params->hoverEdit) ? ' no-edit' : ' ';
                
                $startTag = '<div id="Template-' . $index . '-' . $idartlang . '" class="aitsu_editable on-demand' . $edit . '"><div class="aitsu_hover">';
                $startTag .= '<div class="show-on-demand" style="cursor:pointer; background-color:black; color:white; padding:10px; margin-bottom:5px; display:none;">Edit template area <strong>' . $index . '</strong></div>';
                $endTag = '</div></div>';
            }

            if (Aitsu_Registry::isBoxModel() && count($keys) > 1) {
                $startTag = '<shortcode method="Template" index="' . $index . '">';
                $startTag .= 'isEdit: ' . var_export(Aitsu_Registry::isEdit(), true);
                $endTag = '</shortcode>';
            }

            if (empty($template) && isset($params->defaultTemplate)) {
                $template = self::_getDefaultTemplate($index, $params);
            }
        } else {
            $template = self::_getDefaultTemplate($index, $params);

            if (!isset($params->defaultTemplate) && $index != 'Root') {
                $output .= '<!-- use of template shortcode without defaultTemplate ' . var_export($this->_context, true) . ' -->';
            }
        }

        $code = '';

        if ((Aitsu_Registry::isEdit() || Aitsu_Registry::get()->env->editAction == '1') && count($keys) > 1) {
            $parameters = str_replace("\n", '\n', str_replace("\r\n", "\n", $this->_context['params']));
            $code = '<code class="aitsu_params" style="display:none;">' . $parameters . '</code>';
        }

        try {
            if (!empty($template)) {
                $view = new Zend_View();

                if (isset($params->template->$template->param)) {
                    $view->param = $params->template->$template->param;
                }

                if (isset($params->template->$template->file)) {
                    $template = $params->template->$template->file;
                }

                $heredity = Moraso_Skin_Heredity::build();

                foreach ($heredity as $skin) {
                    $skinPath = APPLICATION_PATH . '/skins/' . $skin;

                    if (file_exists($skinPath . '/' . $template)) {
                        $view->setScriptPath($skinPath);
                        break;
                    }
                }

                $output = $view->render($template);
            }
        } catch (Exception $e) {
            $output = '<strong>' . $e->getMessage() . '</strong><pre>' . $e->getTraceAsString() . '</pre>';
        }

        return $startTag . $code . $output . $endTag;
    }

}