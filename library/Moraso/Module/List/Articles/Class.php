<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Moraso_Module_List_Articles_Class extends Moraso_Module_Abstract {

    protected function _getDefaults() {

        $defaults = array(
            'categories' => Aitsu_Registry::get()->env->idcat,
            'useOfStartArticle' => 2,
            'sortCategoryFirst' => false,
            'orderBy' => 'artsort',
            'ascending' => true,
            'template' => 'index',
            'offset' => 0,
            'limit' => 10,
            'page' => 1,
            'templateRenderingWhenNoArticles' => true,
            'configurable' => array(
                'categories' => false,
                'useOfStartArticle' => false,
                'sortCategoryFirst' => false,
                'orderBy' => false,
                'ascending' => false,
                'template' => false,
                'offset' => false,
                'limit' => false,
                'page' => false,
                'templateRenderingWhenNoArticles' => false
            )
        );

        $moduleConfig = Moraso_Config::get('module.list.articles');

        if (isset($moduleConfig->categories->default)) {
            $defaults['categories'] = $moduleConfig->categories->default;
        }

        if (isset($moduleConfig->useOfStartArticle->default)) {
            $defaults['useOfStartArticle'] = (int) $moduleConfig->useOfStartArticle->default;
        }

        if (isset($moduleConfig->sortCategoryFirst->default)) {
            $defaults['sortCategoryFirst'] = filter_var($moduleConfig->sortCategoryFirst->default, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->orderBy->default)) {
            $defaults['orderBy'] = $moduleConfig->orderBy->default;
        }

        if (isset($moduleConfig->ascending->default)) {
            $defaults['ascending'] = filter_var($moduleConfig->ascending->default, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->template->default)) {
            $defaults['template'] = $moduleConfig->template->default;
        }

        if (isset($moduleConfig->offset->default)) {
            $defaults['offset'] = (int) $moduleConfig->offset->default;
        }

        if (isset($moduleConfig->limit->default)) {
            $defaults['limit'] = (int) $moduleConfig->limit->default;
        }

        if (isset($moduleConfig->page->default)) {
            $defaults['page'] = (int) $moduleConfig->page->default;
        }

        if (isset($moduleConfig->templateRenderingWhenNoArticles->default)) {
            $defaults['templateRenderingWhenNoArticles'] = filter_var($moduleConfig->templateRenderingWhenNoArticles->default, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->categories->configurable)) {
            $defaults['configurable']['categories'] = filter_var($moduleConfig->categories->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->useOfStartArticle->configurable)) {
            $defaults['configurable']['useOfStartArticle'] = filter_var($moduleConfig->useOfStartArticle->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->sortCategoryFirst->configurable)) {
            $defaults['configurable']['sortCategoryFirst'] = filter_var($moduleConfig->sortCategoryFirst->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->orderBy->configurable)) {
            $defaults['configurable']['orderBy'] = filter_var($moduleConfig->orderBy->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->ascending->configurable)) {
            $defaults['configurable']['ascending'] = filter_var($moduleConfig->ascending->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->template->configurable)) {
            $defaults['configurable']['template'] = filter_var($moduleConfig->template->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->offset->configurable)) {
            $defaults['configurable']['offset'] = filter_var($moduleConfig->offset->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->limit->configurable)) {
            $defaults['configurable']['limit'] = filter_var($moduleConfig->limit->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->page->configurable)) {
            $defaults['configurable']['page'] = filter_var($moduleConfig->page->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($moduleConfig->templateRenderingWhenNoArticles->configurable)) {
            $defaults['configurable']['templateRenderingWhenNoArticles'] = filter_var($moduleConfig->templateRenderingWhenNoArticles->configurable, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($this->_params->default)) {
            foreach ($this->_params->default as $param => $value) {
                $defaults[$param] = $value;
            }
        }

        if (isset($this->_params->categories)) {
            if ($this->_params->categories == 'config') {
                $defaults['configurable']['categories'] = true;
            } else {
                $defaults['categories'] = $this->_params->categories;
            }
        }

        if (isset($this->_params->useOfStartArticle)) {
            if ($this->_params->useOfStartArticle == 'config') {
                $defaults['configurable']['useOfStartArticle'] = true;
            } else {
                $defaults['useOfStartArticle'] = (int) $this->_params->useOfStartArticle;
            }
        }

        if (isset($this->_params->sortCategoryFirst)) {
            if ($this->_params->sortCategoryFirst == 'config') {
                $defaults['configurable']['sortCategoryFirst'] = true;
            } else {
                $defaults['sortCategoryFirst'] = filter_var($this->_params->sortCategoryFirst, FILTER_VALIDATE_BOOLEAN);
            }
        }

        if (isset($this->_params->orderBy)) {
            if ($this->_params->orderBy == 'config') {
                $defaults['configurable']['orderBy'] = true;
            } else {
                $defaults['orderBy'] = $this->_params->orderBy;
            }
        }

        if (isset($this->_params->ascending)) {
            if ($this->_params->ascending == 'config') {
                $defaults['configurable']['ascending'] = true;
            } else {
                $defaults['ascending'] = filter_var($this->_params->ascending, FILTER_VALIDATE_BOOLEAN);
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

        if (isset($this->_params->offset)) {
            if ($this->_params->offset == 'config') {
                $defaults['configurable']['offset'] = true;
            } else {
                $defaults['offset'] = (int) $this->_params->offset;
            }
        }

        if (isset($this->_params->limit)) {
            if ($this->_params->limit == 'config') {
                $defaults['configurable']['limit'] = true;
            } else {
                $defaults['limit'] = (int) $this->_params->limit;
            }
        }

        if (isset($this->_params->page)) {
            if ($this->_params->page == 'config') {
                $defaults['configurable']['page'] = true;
            } else {
                $defaults['page'] = (int) $this->_params->page;
            }
        }

        if (isset($this->_params->templateRenderingWhenNoArticles)) {
            if ($this->_params->templateRenderingWhenNoArticles == 'config') {
                $defaults['configurable']['templateRenderingWhenNoArticles'] = true;
            } else {
                $defaults['templateRenderingWhenNoArticles'] = filter_var($this->_params->templateRenderingWhenNoArticles, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $defaults;
    }

    protected function _main() {

        $translation = array();
        $translation['configuration'] = Aitsu_Translate::_('Configuration');

        $defaults = $this->_getDefaults();

        /* categories */
        if ($defaults['configurable']['categories']) {
            $categories = Aitsu_Content_Config_Text::set($this->_index, 'categories', Aitsu_Translate::_('Categories'), $translation['configuration']);
        }

        $categories = isset($categories) ? $categories : $defaults['categories'];

        /* useOfStartArticle */
        if ($defaults['configurable']['useOfStartArticle']) {
            $useOfStartArticleSelect = array(
                'show all articles' => 1,
                'do not show start articles' => 2,
                'show only start articles' => 3
            );

            $useOfStartArticle = Aitsu_Content_Config_Select::set($this->_index, 'useOfStartArticle', Aitsu_Translate::_('useOfStartArticle'), $useOfStartArticleSelect, $translation['configuration']);
        }

        $useOfStartArticle = isset($useOfStartArticle) ? (int) $useOfStartArticle : $defaults['useOfStartArticle'];

        /* sortCategoryFirst */
        if ($defaults['configurable']['sortCategoryFirst']) {
            $sortCategoryFirstSelect = array(
                'true' => true,
                'false' => false
            );

            $sortCategoryFirst = Aitsu_Content_Config_Select::set($this->_index, 'sortCategoryFirst', Aitsu_Translate::_('sortCategoryFirst'), $sortCategoryFirstSelect, $translation['configuration']);
        }

        $sortCategoryFirst = isset($sortCategoryFirst) ? filter_var($sortCategoryFirst, FILTER_VALIDATE_BOOLEAN) : $defaults['sortCategoryFirst'];

        /* orderBy */
        if ($defaults['configurable']['orderBy']) {
            $orderBySelect = array(
                'artsort' => 'artsort',
                'created' => 'created',
                'modified' => 'modified',
                'metadate' => 'metadate'
            );

            $orderBy = Aitsu_Content_Config_Select::set($this->_index, 'orderBy', Aitsu_Translate::_('orderBy'), $orderBySelect, $translation['configuration']);
        }

        $orderBy = isset($orderBy) ? $orderBy : $defaults['orderBy'];

        /* ascending */
        if ($defaults['configurable']['ascending']) {
            $ascendingSelect = array(
                'true' => true,
                'false' => false
            );

            $ascending = Aitsu_Content_Config_Select::set($this->_index, 'ascending', Aitsu_Translate::_('ascending'), $ascendingSelect, $translation['configuration']);
        }

        $ascending = isset($ascending) ? filter_var($ascending, FILTER_VALIDATE_BOOLEAN) : $defaults['ascending'];

        /* template */
        if ($defaults['configurable']['template']) {
            $template = Aitsu_Content_Config_Select::set($this->_index, 'template', Aitsu_Translate::_('Template'), $this->_getTemplates(), $translation['configuration']);
        }

        $template = isset($template) ? $template : $defaults['template'];

        /* Offset */
        if ($defaults['configurable']['offset']) {
            $offset = Aitsu_Content_Config_Text::set($this->_index, 'offset', Aitsu_Translate::_('Offset'), $translation['configuration']);
        }

        $offset = isset($offset) ? (int) $offset : $defaults['offset'];

        /* Limit */
        if ($defaults['configurable']['limit']) {
            $limit = Aitsu_Content_Config_Text::set($this->_index, 'limit', Aitsu_Translate::_('Limit'), $translation['configuration']);
        }

        $limit = isset($limit) ? (int) $limit : $defaults['limit'];

        /* Page */
        if ($defaults['configurable']['page']) {
            $page = Aitsu_Content_Config_Text::set($this->_index, 'page', Aitsu_Translate::_('Page'), $translation['configuration']);
        }

        $page = isset($_GET['page']) ? (int) $_GET['page'] : isset($page) ? (int) $page : $defaults['page'];

        /* templateRenderingWhenNoArticles */
        if ($defaults['configurable']['templateRenderingWhenNoArticles']) {
            $templateRenderingWhenNoArticlesSelect = array(
                'true' => true,
                'false' => false
            );

            $templateRenderingWhenNoArticles = Aitsu_Content_Config_Select::set($this->_index, 'templateRenderingWhenNoArticles', Aitsu_Translate::_('templateRenderingWhenNoArticles'), $templateRenderingWhenNoArticlesSelect, $translation['configuration']);
        }

        $templateRenderingWhenNoArticles = isset($templateRenderingWhenNoArticles) ? filter_var($templateRenderingWhenNoArticles, FILTER_VALIDATE_BOOLEAN) : $defaults['templateRenderingWhenNoArticles'];

        /* change Offset if not on first Page */
        if ($page > 1) {
            $offset = ($page - 1) * $limit;
        }

        $aggregation = Moraso_Aggregation_Article::factory();
        $aggregation->useOfStartArticle($useOfStartArticle);
        $aggregation->whereInCategories(array($categories));

        if ($sortCategoryFirst) {
            $aggregation->orderBy('catlang.idcat');
        }

        $aggregation->orderBy($orderBy, $ascending);

        if (isset($this->_params->populateWith)) {
            foreach ($this->_params->populateWith as $alias => $populateWith) {

                $type = $populateWith->index;

                if ($populateWith->type == 'property' || $populateWith->type == 'files') {
                    $type = $populateWith->type . ':' . $type;
                }

                $aggregation->populateWith($type, $alias, $populateWith->datatype);
            }
        }

        $aggregationAll = clone $aggregation;

        $articles = $aggregation->fetch($offset, $limit);

        if ((count($articles) === 0 && !$templateRenderingWhenNoArticles) || !in_array($template, $this->_getTemplates())) {
            return '';
        }

        $articlesAll = $aggregationAll->fetch(0, 99999);

        $view = $this->_getView();

        $view->articles = $articles;
        $view->pages = ceil(count($articlesAll) / $limit);
        $view->currentPage = $page;
        $view->idart = Aitsu_Registry::get()->env->idart;

        return $view->render($template . '.phtml');
    }

    protected function _cachingPeriod() {

        return Aitsu_Util_Date::secondsUntilEndOf('day');
    }

}