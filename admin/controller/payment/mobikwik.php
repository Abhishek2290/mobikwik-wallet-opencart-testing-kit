<?php
class ControllerPaymentMobikwik extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/mobikwik');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('mobikwik', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect(HTTPS_SERVER . 'index.php?route=extension/payment&token=' . $this->session->data['token']);
		}

		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_all_zones'] = $this->language->get('text_all_zones');
		$this->data['text_yes'] = $this->language->get('text_yes');
		$this->data['text_no'] = $this->language->get('text_no');

		$this->data['entry_MID'] = $this->language->get('entry_MID');
		$this->data['entry_MName'] = $this->language->get('entry_MName');
        $this->data['entry_success_status'] = $this->language->get('entry_success_status');
        $this->data['entry_pending_status'] = $this->language->get('entry_pending_status');
        $this->data['entry_failed_status'] = $this->language->get('entry_failed_status');
		$this->data['entry_RedirectURL'] = $this->language->get('entry_RedirectURL');
		$this->data['entry_Wkey'] = $this->language->get('entry_Wkey');
		$this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');

 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

 		if (isset($this->error['MID'])) {
			$this->data['error_MID'] = $this->error['MID'];
		} else {
			$this->data['error_MID'] = '';
		}
		
		if (isset($this->error['MName'])) {
			$this->data['error_MName'] = $this->error['MName'];
		} else {
			$this->data['error_MName'] = '';
		}
        
        if (isset($this->error['RedirectURL'])) {
			$this->data['error_RedirectURL'] = $this->error['RedirectURL'];
		} else {
			$this->data['error_RedirectURL'] = '';
		}

		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => HTTPS_SERVER . 'index.php?route=common/home&token=' . $this->session->data['token'],      		
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_payment'),
			'href'      => HTTPS_SERVER . 'index.php?route=extension/payment&token=' . $this->session->data['token'],
      		'separator' => ' :: '
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => HTTPS_SERVER . 'index.php?route=payment/mobikwik&token=' . $this->session->data['token'],
      		'separator' => ' :: '
   		);

		$this->data['action'] = HTTPS_SERVER . 'index.php?route=payment/mobikwik&token=' . $this->session->data['token'];

		$this->data['cancel'] = HTTPS_SERVER . 'index.php?route=extension/payment&token=' . $this->session->data['token'];

		if (isset($this->request->post['mobikwik_MID'])) {
			$this->data['mobikwik_MID'] = $this->request->post['mobikwik_MID'];
		} else {
			$this->data['mobikwik_MID'] = $this->config->get('mobikwik_MID');
		}
		
		if (isset($this->request->post['mobikwik_MName'])) {
			$this->data['mobikwik_MName'] = $this->request->post['mobikwik_MName'];
		} else {
			$this->data['mobikwik_MName'] = $this->config->get('mobikwik_MName');
		}

		/*if (isset($this->request->post['mobikwik_RedirectURL'])) {
			$this->data['mobikwik_RedirectURL'] = $this->request->post['mobikwik_RedirectURL'];
		} else {
			$this->data['mobikwik_RedirectURL'] = $this->config->get('mobikwik_RedirectURL');
		}*/
        
        $this->data['mobikwik_RedirectURL'] = HTTP_CATALOG . 'index.php?route=payment/mobikwik/callback';
        
        if (isset($this->request->post['mobikwik_Wkey'])) {
			$this->data['mobikwik_Wkey'] = $this->request->post['mobikwik_Wkey'];
		} else {
			$this->data['mobikwik_Wkey'] = $this->config->get('mobikwik_Wkey');
		}
        
        if (isset($this->request->post['mobikwik_success_status_id'])) {
			$this->data['mobikwik_success_status_id'] = $this->request->post['mobikwik_success_status_id'];
		} else {
			$this->data['mobikwik_success_status_id'] = $this->config->get('mobikwik_success_status_id');
		}
        
        if (isset($this->request->post['mobikwik_pending_status_id'])) {
			$this->data['mobikwik_pending_status_id'] = $this->request->post['mobikwik_pending_status_id'];
		} else {
			$this->data['mobikwik_pending_status_id'] = $this->config->get('mobikwik_pending_status_id');
		}
        
        if (isset($this->request->post['mobikwik_failed_status_id'])) {
			$this->data['mobikwik_failed_status_id'] = $this->request->post['mobikwik_failed_status_id'];
		} else {
			$this->data['mobikwik_failed_status_id'] = $this->config->get('mobikwik_failed_status_id');
		}
        
		$this->load->model('localisation/order_status');

		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['mobikwik_geo_zone_id'])) {
			$this->data['mobikwik_geo_zone_id'] = $this->request->post['mobikwik_geo_zone_id'];
		} else {
			$this->data['mobikwik_geo_zone_id'] = $this->config->get('mobikwik_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['mobikwik_status'])) {
			$this->data['mobikwik_status'] = $this->request->post['mobikwik_status'];
		} else {
			$this->data['mobikwik_status'] = $this->config->get('mobikwik_status');
		}
		
		if (isset($this->request->post['mobikwik_sort_order'])) {
			$this->data['mobikwik_sort_order'] = $this->request->post['mobikwik_sort_order'];
		} else {
			$this->data['mobikwik_sort_order'] = $this->config->get('mobikwik_sort_order');
		}

		$this->template = 'payment/mobikwik.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
        
        $this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
        //$this->response->setOutput($this->render());
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'payment/mobikwik')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['mobikwik_MID']) {
			$this->error['MID'] = $this->language->get('error_MID');
		}
		
		if (!$this->request->post['mobikwik_MName']) {
			$this->error['MName'] = $this->language->get('error_MName');
		}
        /*
        if (!$this->request->post['mobikwik_RedirectURL']) {
			$this->error['RedirectURL'] = $this->language->get('error_RedirectURL');
		}
		*/

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
}
?>