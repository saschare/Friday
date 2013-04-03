<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Moraso_Module_Navigation_BreadCrumb_Class extends Moraso_Module_Abstract {

    protected $type = 'navigation';
    protected $_allowEdit = false;

    protected function _main() {

        $view = $this->_getView();

        $view->bc = Aitsu_Persistence_View_Category :: breadCrumb();

        return $view->render('index.phtml');
    }

    protected function _cachingPeriod() {

        return 'eternal';
    }

}