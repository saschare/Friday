<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class luceneAnalyserDashboardController extends Aitsu_Adm_Plugin_Controller {

    const ID = '515c5162-69dc-488f-b3aa-2f77c0a8b230';

    public function init() {

        $this->_helper->layout->disableLayout();
        header("Content-type: text/javascript");
    }

    public static function register() {

        $client_data = Aitsu_Persistence_Clients::factory(Aitsu_Registry::get()->session->currentClient);

        $client_config = new Zend_Config_Ini('application/configs/clients/' . $client_data->config . '.ini', Moraso_Util::getEnv());

        return (object) array(
                    'name' => 'luceneAnalyser',
                    'tabname' => Aitsu_Translate :: _('luceneAnalyser'),
                    'enabled' => isset($client_config->search->lucene->index) ? true : false,
                    'id' => self :: ID
        );
    }

    public function indexAction() {
        
    }

    public function storeAction() {

        $articles = Moraso_Db::fetchAll('' .
                        'select ' .
                        '   lucene.uid, ' .
                        '   lucene.lastindexed, ' .
                        '   lucene.idart, ' .
                        '   lucene.idlang, ' .
                        '   artlang.pagetitle as pagetitle, ' .
                        '   artlang.teasertitle as teasertitle ' .
                        'from ' .
                        '   _lucene_index as lucene ' .
                        'left join ' .
                        '   _art_lang as artlang on artlang.idart = lucene.idart and artlang.idlang = lucene.idlang ' .
                        'where ' .
                        '   lucene.idlang =:idlang ' .
                        'order by ' .
                        '   lucene.lastindexed DESC', array(
                    ':idlang' => Aitsu_Registry::get()->session->currentLanguage
        ));

        $client_data = Aitsu_Persistence_Clients::factory(Aitsu_Registry::get()->session->currentClient);

        $client_config = new Zend_Config_Ini('application/configs/clients/' . $client_data->config . '.ini', Moraso_Util::getEnv());

        $index = new Zend_Search_Lucene(APPLICATION_PATH . '/data/lucene/' . $client_config->search->lucene->index . '/');

        foreach ($articles as $key => $article) {
            $article = (object) $article;

            $hits = $index->find('uid:' . $article->uid . ' AND lang:' . $article->idlang . ' AND idart:' . $article->idart);

            if (isset($hits[0]) && !empty($hits[0]->id)) {
                if ($hits[0]->score == 1) {
                    $articles[$key]['id'] = $hits[0]->id;
                    $articles[$key]['uid'] = $hits[0]->uid;
                }
            }

            unset($hits);
        }

        $this->_helper->json((object) array(
                    'articles' => $articles
        ));
    }

    public function optimizeAction() {

        $this->_helper->layout->disableLayout();
        header("Content-type: text/javascript");

        $client_data = Aitsu_Persistence_Clients::factory(Aitsu_Registry::get()->session->currentClient);

        $client_config = new Zend_Config_Ini('application/configs/clients/' . $client_data->config . '.ini', Moraso_Util::getEnv());

        $index = new Zend_Search_Lucene(APPLICATION_PATH . '/data/lucene/' . $client_config->search->lucene->index . '/');

        $index->optimize();

        $this->_helper->json((object) array());
    }

    public function deleteAction() {

        $this->_helper->layout->disableLayout();
        header("Content-type: text/javascript");

        $client_data = Aitsu_Persistence_Clients::factory(Aitsu_Registry::get()->session->currentClient);

        $client_config = new Zend_Config_Ini('application/configs/clients/' . $client_data->config . '.ini', Moraso_Util::getEnv());

        $index = new Zend_Search_Lucene(APPLICATION_PATH . '/data/lucene/' . $client_config->search->lucene->index . '/');

        $index->delete($this->getRequest()->getParam('id'));

        Moraso_Db::query('' .
                'delete from ' .
                '   _lucene_index ' .
                'where ' .
                '   uid =:uid', array(
            ':uid' => $this->getRequest()->getParam('uid')
        ));

        $this->_helper->json((object) array());
    }

    public function deletebrokenAction() {

        $this->_helper->layout->disableLayout();
        header("Content-type: text/javascript");

        $articles = Moraso_Db::fetchAll('' .
                        'select ' .
                        '   uid, ' .
                        '   idart, ' .
                        '   idlang ' .
                        'from ' .
                        '   _lucene_index ' .
                        'where ' .
                        '   idlang =:idlang', array(
                    ':idlang' => Aitsu_Registry::get()->session->currentLanguage
        ));

        $client_data = Aitsu_Persistence_Clients::factory(Aitsu_Registry::get()->session->currentClient);

        $client_config = new Zend_Config_Ini('application/configs/clients/' . $client_data->config . '.ini', Moraso_Util::getEnv());

        foreach ($articles as $article) {
            $article = (object) $article;

            // index muss ich jedes mal neu aufrufen, sonst löscht der alle bis auf einen, komische Sache
            $index = new Zend_Search_Lucene(APPLICATION_PATH . '/data/lucene/' . $client_config->search->lucene->index . '/');
            
            $hits = $index->find('uid:' . $article->uid . ' AND lang:' . $article->idlang . ' AND idart:' . $article->idart);

            if (isset($hits[0]) && !empty($hits[0]->id)) {
                if ($hits[0]->score == 1) {
                    $luceneDocument = $hits[0]->getDocument();
                    $pagetitle = $luceneDocument->pagetitle;

                    if (empty($pagetitle)) {
                        $index->delete($hits[0]->id);

                        Moraso_Db::query('' .
                                'delete from ' .
                                '   _lucene_index ' .
                                'where ' .
                                '   uid =:uid', array(
                            ':uid' => $article->uid
                        ));
                    }
                }
            } elseif ((isset($hits[0]) && empty($hits[0]->id)) || !isset($hits[0])) {
                Moraso_Db::query('' .
                        'delete from ' .
                        '   _lucene_index ' .
                        'where ' .
                        '   uid =:uid', array(
                    ':uid' => $article->uid
                ));
            }

            unset($hits);
        }

        $this->_helper->json((object) array());
    }

}