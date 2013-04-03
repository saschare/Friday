<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Moraso_Module_Image_Class extends Moraso_Module_Abstract {

    protected function _getDefaults() {

        $defaults = array(
            'width' => 200,
            'height' => 200,
            'render' => 0,
            'float' => '',
            'style' => '',
            'template' => 'index',
            'idart' => Aitsu_Registry :: get()->env->idart,
            'rel' => '',
            'attr' => '',
            'all' => false,
            'configurable' => array(
                'width' => false,
                'height' => false,
                'render' => false,
                'float' => false,
                'style' => false,
                'template' => false,
                'idart' => false,
                'rel' => false,
                'attr' => false,
                'all' => false
            )
        );

        $moduleConfig = Moraso_Config::get('module.image');

        if (isset($moduleConfig->width->default)) {
            $defaults['width'] = (int) $moduleConfig->width->default;
        }

        if (isset($moduleConfig->height->default)) {
            $defaults['height'] = (int) $moduleConfig->height->default;
        }

        if (isset($moduleConfig->render->default)) {
            $defaults['render'] = (int) $moduleConfig->render->default;
        }

        if (isset($moduleConfig->float->default)) {
            $defaults['float'] = $moduleConfig->float->default;
        }

        if (isset($moduleConfig->style->default)) {
            $defaults['style'] = $moduleConfig->style->default;
        }

        if (isset($moduleConfig->template->default)) {
            $defaults['template'] = $moduleConfig->template->default;
        }

        if (isset($moduleConfig->idart->default)) {
            $defaults['idart'] = (int) $moduleConfig->idart->default;
        }

        if (isset($moduleConfig->rel->default)) {
            $defaults['rel'] = $moduleConfig->rel->default;
        }

        if (isset($moduleConfig->attr->default)) {
            $defaults['attr'] = $moduleConfig->attr->default;
        }

        if (isset($moduleConfig->all->default)) {
            $defaults['all'] = filter_var($moduleConfig->all->default, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->width->configurable)) {
            $defaults['configurable']['width'] = filter_var($moduleConfig->width->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->height->configurable)) {
            $defaults['configurable']['height'] = filter_var($moduleConfig->height->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->render->configurable)) {
            $defaults['configurable']['render'] = filter_var($moduleConfig->render->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->float->configurable)) {
            $defaults['configurable']['float'] = filter_var($moduleConfig->float->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->style->configurable)) {
            $defaults['configurable']['style'] = filter_var($moduleConfig->style->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->template->configurable)) {
            $defaults['configurable']['template'] = filter_var($moduleConfig->template->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->idart->configurable)) {
            $defaults['configurable']['idart'] = filter_var($moduleConfig->idart->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->rel->configurable)) {
            $defaults['configurable']['rel'] = filter_var($moduleConfig->rel->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->attr->configurable)) {
            $defaults['configurable']['attr'] = filter_var($moduleConfig->attr->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->all->configurable)) {
            $defaults['configurable']['all'] = filter_var($moduleConfig->all->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($this->_params->default)) {
            foreach ($this->_params->default as $param => $value) {
                $defaults[$param] = $value;
            }
        }

        if (isset($this->_params->width)) {
            if ($this->_params->width == 'config') {
                $defaults['configurable']['width'] = true;
            } else {
                $defaults['width'] = (int) $this->_params->width;
            }
        }

        if (isset($this->_params->height)) {
            if ($this->_params->height == 'config') {
                $defaults['configurable']['height'] = true;
            } else {
                $defaults['height'] = (int) $this->_params->height;
            }
        }

        if (isset($this->_params->render)) {
            if ($this->_params->render == 'config') {
                $defaults['configurable']['render'] = true;
            } else {
                $defaults['render'] = (int) $this->_params->render;
            }
        }

        if (isset($this->_params->float)) {
            if ($this->_params->float == 'config') {
                $defaults['configurable']['float'] = true;
            } else {
                $defaults['float'] = $this->_params->float;
            }
        }

        if (isset($this->_params->style)) {
            if ($this->_params->style == 'config') {
                $defaults['configurable']['style'] = true;
            } else {
                $defaults['style'] = $this->_params->style;
                ;
            }
        }

        if (isset($this->_params->template)) {
            if ($this->_params->template == 'config') {
                $defaults['configurable']['template'] = true;
            } else {
                $defaults['template'] = $this->_params->template;
            }
        }

        if (isset($this->_params->idart)) {
            if ($this->_params->idart == 'config') {
                $defaults['configurable']['idart'] = true;
            } else {
                $defaults['idart'] = (int) $this->_params->idart;
            }
        }

        if (isset($this->_params->rel)) {
            if ($this->_params->rel == 'config') {
                $defaults['configurable']['rel'] = true;
            } else {
                $defaults['rel'] = $this->_params->rel;
            }
        }

        if (isset($this->_params->attr)) {
            if ($this->_params->attr == 'config') {
                $defaults['configurable']['attr'] = true;
            } else {
                $defaults['attr'] = $this->_params->attr;
            }
        }

        if (isset($this->_params->all)) {
            if ($this->_params->all == 'config') {
                $defaults['configurable']['all'] = true;
            } else {
                $defaults['all'] = filter_var($this->_params->all, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $defaults;
    }

    protected function _main() {

        $defaults = $this->_getDefaults();

        if ($defaults['configurable']['idart']) {
            $idart = Aitsu_Content_Config_Text::set($this->_index, 'idart', Aitsu_Translate::_('Article (idart)'), Aitsu_Translate::_('Configuration'));
        }

        $idart = isset($idart) ? (int) $idart : $defaults['idart'];

        if ($defaults['configurable']['width']) {
            $width = Aitsu_Content_Config_Text::set($this->_index, 'width', Aitsu_Translate::_('Width'), Aitsu_Translate::_('Configuration'));
        }

        $width = isset($width) ? (int) $width : $defaults['width'];

        if ($defaults['configurable']['height']) {
            $height = Aitsu_Content_Config_Text::set($this->_index, 'height', Aitsu_Translate::_('Height'), Aitsu_Translate::_('Configuration'));
        }

        $height = isset($height) ? (int) $height : $defaults['height'];

        if ($defaults['configurable']['render']) {
            $renderSelect = array(
                'Variante 1' => 0,
                'Variante 2' => 1,
                'Variante 3' => 2
            );

            $render = Aitsu_Content_Config_Select::set($this->_index, 'render', Aitsu_Translate::_('Render'), $renderSelect, Aitsu_Translate::_('Configuration'));
        }

        $render = isset($render) ? (int) $render : $defaults['render'];

        if ($defaults['configurable']['template']) {
            $template = Aitsu_Content_Config_Select::set($this->_index, 'template', Aitsu_Translate::_('Template'), $this->_getTemplates(), Aitsu_Translate::_('Configuration'));
        }

        $template = isset($template) ? $template : $defaults['template'];

        if ($defaults['configurable']['all']) {
            $showAllSelect = array(
                'show all Images' => true,
                'select single Images' => false
            );

            $all = Aitsu_Content_Config_Radio::set($this->_index, 'all', Aitsu_Translate::_('show all Images'), $showAllSelect, Aitsu_Translate::_('Configuration'));
        }

        $all = isset($all) ? filter_var($all, FILTER_VALIDATE_BOOLEAN) : $defaults['all'];

        if (!$all) {
            $images = Moraso_Content_Config_Media :: set($this->_index, 'Image.Media', 'Media', $idart);
            $selectedImages = Moraso_Persistence_View_Media::byFileName($idart, $images);
        } else {
            $selectedImages = Moraso_Persistence_View_Media::ofSpecifiedArticle($idart);
        }

        if ($defaults['configurable']['attr']) {
            $attr = Aitsu_Content_Config_Textarea::set($this->_index, 'attr', Aitsu_Translate::_('Attributes'), Aitsu_Translate::_('Configuration'));
        }

        $attr = Moraso_Util::parseSimpleIni(isset($attr) ? $attr : $defaults['attr']);

        if ($defaults['configurable']['rel']) {
            $attr->rel = Aitsu_Content_Config_Text::set($this->_index, 'rel', Aitsu_Translate::_('rel'), Aitsu_Translate::_('Configuration'));
        }

        $attr->rel = isset($attr->rel) ? $attr->rel : $defaults['rel'];

        $attr->style = new stdClass();

        if ($defaults['configurable']['style']) {
            $attr->style->self = Aitsu_Content_Config_Text::set($this->_index, 'style', Aitsu_Translate::_('Style'), Aitsu_Translate::_('Configuration'));
        }

        $attr->style->self = isset($attr->style->self) ? $attr->style->self : $defaults['style'];

        if ($defaults['configurable']['float']) {
            $floatSelect = array(
                Aitsu_Translate::_('not specified') => '',
                Aitsu_Translate::_('left') => 'left',
                Aitsu_Translate::_('right') => 'right',
                Aitsu_Translate::_('none') => 'none'
            );

            $attr->style->float = Aitsu_Content_Config_Select::set($this->_index, 'float', Aitsu_Translate::_('Float'), $floatSelect, Aitsu_Translate::_('Configuration'));
        }

        $attr->style->float = isset($attr->style->float) ? $attr->style->float : $defaults['float'];

        if (empty($template) || empty($selectedImages) || !in_array($template, $this->_getTemplates())) {
            return '';
        }

        $view = $this->_getView();

        $view->selectedImages = $selectedImages;

        $view->width = $width;
        $view->height = $height;
        $view->render = $render;
        $view->attributes = $attr;

        /**
         * Diese 3 (float, style, rel) befinden sich in der $view->attributes
         * Habe es nur aus Kompatibilitätsgründen drin gelassen!
         */
        $view->float = $view->attributes->style->float; // depracted
        $view->style = $view->attributes->style->self; // depracted
        $view->rel = $view->attributes->rel; // depracted

        return $view->render($template . '.phtml');
    }

    protected function _cachingPeriod() {

        return 'eternal';
    }

}