<?php


/**
 * Google Maps Javascript API implementation.
 * 
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2011, w3concepts AG
 */

class Module_Custom_Interbrain_Perfect2_Class extends Aitsu_Module_Tree_Abstract {

	protected function _init() {

		$view = $this->_getView();

		$client = new SoapClient('http://212.25.31.113/p2online/P2online.asmx?wsdl', array (
			'classmap' => array (
				'OfferItem' => 'Wdrei_Interbrain_Perfect2_OfferItem',
				'OfferPosition' => 'Wdrei_Interbrain_Perfect2_OfferItem',
				'ClientData' => 'Wdrei_Interbrain_Perfect2_ClientData',
				'OrderPosition' => 'Wdrei_Interbrain_Perfect2_OrderPosition',
				'Order' => 'Wdrei_Interbrain_Perfect2_Order'
			),
			'trace' => true
		));

		$voucher = $client->GetVoucher(0, 'SD');

		//$view->data = $voucher;
		//return $view->render('index.phtml');

		$itemId = $voucher->GetVoucherResult->Offer->Items->OfferPosition[0]->ItemId;

		$order = Wdrei_Interbrain_Perfect2_Order :: instance(array (
			'Items' => array (
				Wdrei_Interbrain_Perfect2_OrderPosition :: instance(array (
					'Id' => 1,
					'Client' => Wdrei_Interbrain_Perfect2_ClientData :: instance(array (
						'EMailAddress' => 'a.kummer@wdrei.ch'
					)),
					'ItemId' => $voucher->GetVoucherResult->Offer->Items->OfferPosition[0]->ItemId,
					'ItemLanguage' => 'SD',
					'Quantity' => 1,
					//'ValidFrom' => $voucher->GetVoucherResult->Offer->Items->OfferPosition[0]->ValidFrom->From,
					//'ValidTo' => $voucher->GetVoucherResult->Offer->Items->OfferPosition[0]->ValidFrom->To
				))
			)
		));

		/*$view->data = $order;
		return $view->render('index.phtml');*/

		$view->data = $client->ValidateOrder($order);

		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$doc->loadXML($client->__getLastRequest());
		$view->request = $doc->saveXML();

		return $view->render('index.phtml');
	}

}