<?php

namespace xavoc\ispmanager;

class Model_Plan extends \xepan\commerce\Model_Item{
	// public $table = "isp_plan";
	public $status = ['Published','UnPublished'];
	public $actions = [
				'Published'=>['view','edit','delete','condition'],
				'UnPublished'=>['view','edit','delete','Published']
				];
	
	public $acl_type="ispmanager_plan";

	function init(){
		parent::init();

		// destroy extra fields
		$item_fields = $this->add('xepan\commerce\Model_Item')->getActualFields();
		$required_field = ['name','sku','description','sale_price','original_price','status','document_id','id','created_by','updated_by','created_at','updated_at','type','qty_unit_id','qty_unit','renewable_unit','renewable_value','is_renewable'];
		$destroy_field = array_diff($item_fields, $required_field);
		foreach ($destroy_field as $key => $field) {
			if($this->hasElement($field))
				$this->getElement($field)->destroy();
		}
		$this->getElement('status')->defaultValue('Published');
		
		// if($this->hasElement('minimum_order_qty'))
		// 	$this->getElement('minimum_order_qty')->set(1);

		$plan_j = $this->join('isp_plan.item_id');
		$plan_j->hasOne('xepan\commerce\Model_Taxation','tax_id');

		$plan_j->addField('maintain_data_limit')->type('boolean')->defaultValue(true);

		$plan_j->addField('is_topup')->type('boolean')->defaultValue(false);
		$plan_j->addField('is_auto_renew')->type('boolean')->defaultValue(0);
		$plan_j->addField('available_in_user_control_panel')->type('boolean');
		$plan_j->addField('plan_validity_value')->type('number')->defaultValue(1);

		$this->hasMany('xavoc\ispmanager\Condition','plan_id',null,'conditions');

		$this->addHook('beforeSave',$this,[],4);

		// $this->add('dynamic_model/Controller_AutoCreator');
	}

	function beforeSave(){
		// $this['original_price'] = $this['sale_price'];
		$this['minimum_order_qty'] = 1;

		// plan name must not be same
		$old_model = $this->add("xavoc\ispmanager\Model_Plan");
		$old_model->addCondition('name',$this['name']);
		if($this->loaded())
			$old_model->addCondition('id','<>',$this->id);
		$old_model->tryLoadAny();
		if($old_model->loaded())
			throw $this->Exception("(".$this['name'].') plan name is already exist ','ValidityCheck')->setField('name');
	}

	function page_condition($page){
		$condition_model = $this->add('xavoc\ispmanager\Model_Condition');
		$condition_model->addcondition('plan_id',$this->id);

		$crud = $page->add('xepan\hr\CRUD');

		if($crud->isEditing()){
			$form = $crud->form;
			//$form->setLayout('form/condition');
			$form->add('xepan\base\Controller_FLC')
				->addContentSpot()
			->layout([
						'remark~Row Name'=>'About Plan~c1~3',
						'data_limit'=>'c2~3~Data limit in Human Readable Formate 20gb, 1tb, 100mb',
						'time_limit'=>'c3~3~Time limit in minutes',
						'is_data_carry_forward~Data Carry Forward'=>'c4~3',
						'download_limit'=>'DL/UL Limit~c1~3~Limit per second',
						'upload_limit'=>'c11~3~Limit per second',
						'fup_download_limit'=>'c12~3~Limit per second',
						'fup_upload_limit'=>'c13~3~Limit per second',
						'accounting_download_ratio'=>'c2~6~Ratio in %',
						'accounting_upload_ratio'=>'c21~6~Ratio in %',
						'start_time'=>'Time~c1~6',
						'end_time'=>'c2~6',
						'sun'=>'Week~c1~1',
						'mon'=>'c2~1',
						'tue'=>'c3~1',
						'wed'=>'c4~1',
						'thu'=>'c5~1',
						'fri'=>'c6~1',
						'sat'=>'c7~1',
						'd01'=>'Days~c1~1',
						'd02'=>'c2~1',
						'd03'=>'c3~1',
						'd04'=>'c4~1',
						'd05'=>'c5~1',
						'd06'=>'c6~1',
						'd07'=>'c7~1',
						'd08'=>'c8~1',
						'd09'=>'c9~1',
						'd10'=>'c10~1',
						'd11'=>'c11~1',
						'd12'=>'c12~1',
						'd13'=>'c13~1',
						'd14'=>'c14~1',
						'd15'=>'c15~1',
						'd16'=>'c16~1',
						'd17'=>'c17~1',
						'd18'=>'c18~1',
						'd19'=>'c19~1',
						'd20'=>'c20~1',
						'd21'=>'c21~1',
						'd22'=>'c22~1',
						'd23'=>'c23~1',
						'd24'=>'c24~1',
						'd25'=>'c25~1',
						'd26'=>'c26~1',
						'd27'=>'c27~1',
						'd28'=>'c28~1',
						'd29'=>'c29~1',
						'd30'=>'c30~1',
						'd31'=>'c31~1',
						'data_reset_value'=>'Reset Data~c1~6',
						'data_reset_mode'=>'c2~6',
						'burst_dl_limit'=>'Burst~c1~3~limit per second',
						'burst_ul_limit'=>'c11~3~limit per second',
						'burst_threshold_dl_limit'=>'c12~3~limit per second',
						'burst_threshold_ul_limit'=>'c13~3~limit per second',
						'burst_dl_time'=>'c2~3~time in second',
						'burst_ul_time'=>'c21~3~time in second',
						'priority'=>'c22~3',
						'treat_fup_as_dl_for_last_limit_row'=>'MISC~c1~6',
						'is_pro_data_affected'=>'c2~6',
					]);
			
		}

		$crud->setModel($condition_model);

		$crud->grid->removeColumn('plan');
	}

	function import($data){
		// get list of plan
		$plan_list = [];
		foreach ($this->add('xavoc\ispmanager\Model_Plan')->getRows() as $key => $plan) {
			$plan_list[trim($plan['name'])] = $plan['id'];
		}

		// get list of unit
		$unit_list = [];
		foreach ($this->add('xepan\commerce\Model_Unit')->getRows() as $key => $unit) {
			$unit_list[trim($unit['name'])] = $unit['id'];
		}

		// echo "<pre>";
		// print_r($unit_list);
		// echo "</pre>";
		// die();
		// get list of tax
		$tax_list = [];
		foreach ($this->add('xepan\commerce\Model_Taxation')->getRows() as $key => $tax) {
			$tax_list[trim($tax['name'])] = $tax['id'];
		}

		$reset_mode = ['hours'=>'hours','hour'=>'hours','days'=>'days','day'=>'days','week'=>'weeks','weeks'=>'weeks','months'=>'months','month'=>'months','years'=>'years','year'=>'years'];
		
		// echo "<pre>";
		// print_r($data);
		// echo "</pre>";
		// die();

		try{
			$this->api->db->beginTransaction();

			foreach ($data as $key => $record) {
				// update plan
				$plan_field = ['NAME','CODE','STATUS','ORIGINAL_PRICE','SALE_PRICE','TAX','PLAN_VALIDITY_VALUE','PLAN_VALIDITY_UNIT','DESCRIPTION','RENEWABLE_VALUE','RENEWABLE_UNIT','IS_AUTO_RENEW','AVAILABLE_IN_USER_CONTROL_PANEL'];
				$plan_name = trim($record['NAME']);

				if(!isset($plan_list[$plan_name])){
					$plan_model = $this->add('xavoc\ispmanager\Model_Plan');
					foreach ($plan_field as $key=>$field) {
						$field_name = strtolower(trim($field));
						if($field_name == "code") $field_name = "sku";
						
						$value = $record[$field];
						if(in_array($field_name, ['plan_validity_unit','renewable_unit','tax'])){
							switch ($field_name) {
								case 'plan_validity_unit':
									$field_name = "qty_unit_id";
									$value = strtolower($value);
									$value = isset($unit_list[trim($value)])?$unit_list[trim($value)]:0;
									break;

								case 'renewable_unit':
									$value = strtoupper($value);
									break;

								case 'tax':
									$field_name = 'tax_id';
									$value = isset($tax_list[trim($value)])?$tax_list[trim($value)]:0;
									break;
							}
						}


						if($field_name == "original_price" OR $field_name == "sale_price"){
							$value = str_replace(" ", "",$value);
						}

						$plan_model[$field_name] = $value;

					}
					$plan_model->save();
					$plan_list[$plan_name] = $plan_model->id;
				}

				$plan_id = $plan_list[$plan_name];


				// unset plan field
				foreach ($plan_field as $key => $field) {
					unset($record[$field]);
				}
				
				//  add condition
				$condition_data = $record;
				$condition = $this->add('xavoc\ispmanager\Model_Condition');
				$condition->addCondition('plan_id',$plan_id);

				foreach ($condition_data as $field => $value) {
					$field = strtolower(trim($field));
					if(in_array($field, ['data_limit','download_limit','upload_limit','fup_download_limit','fup_upload_limit','burst_dl_limit','burst_ul_limit','burst_threshold_dl_limit','burst_threshold_ul_limit']))
						$value = $this->app->human2byte($value);
					
					if(in_array($field, ['download_limit','upload_limit','fup_download_limit','fup_upload_limit'])){
						$value = str_replace(" ","",$value);
						$value = str_replace("ps","",$value);
					}

					if($field == "data_reset_mode" && isset($reset_mode[$value])){
						$value = $reset_mode[$value];
					}

					$condition[$field] = $value;
				}
				$condition->save();
			}

			$this->api->db->commit();
		}catch(\Exception $e){
			$this->api->db->rollback();
			throw new \Exception($e->getMessage());
		}

	}

	function placeOrder($plan_id){
		
		$customer = $this->add('xavoc\ispmanager\Model_User');
		$customer->loadLoggedIn();

		$result = ['status'=>'error','message'=>'some thing went wrong'];

		$plan_model = $this->add('xavoc\ispmanager\Model_Plan');
		$plan_model->tryLoad($plan_id);
		if(!$plan_model->loaded())
			throw new \Exception("Plan not found", 1);
		
		// return to login page
		if(!$customer->loaded()){
			return ['status'=>'error','message'=>'unknow customer'];
		}
		
		try{

			// //Load Default TNC
			// $tnc = $this->add('xepan\commerce\Model_TNC')->addCondition('is_default_for_sale_order',true)->setLimit(1)->tryLoadAny();
			// $tnc_id = $tnc->loaded()?$tnc['id']:0;
			// $tnc_text = $tnc['content']?$tnc['content']:"not defined";

			// $country_id = $customer['billing_country_id']?:$customer['country_id']?:0;
			// $state_id = $customer['billing_state_id']?:$customer['state_id']?:0;
			// $city = $customer['billing_city']?:$customer['city']?:"not defined";
			// $address = $customer['billing_address']?:$customer['address']?:"not defined";
			// $pincode = $customer['billing_pincode']?:$customer['pin_code']?:"not defined";

			// $master_detail = [
			// 				'contact_id' => $customer->id,
			// 				'currency_id' => $customer['currency_id']?$customer['currency_id']:$this->app->epan->default_currency->get('id'),
			// 				'nominal_id' => 0,
			// 				'billing_country_id'=> $country_id,
			// 				'billing_state_id'=> $state_id,
			// 				'billing_name'=> $customer['name'],
			// 				'billing_address'=> $address,
			// 				'billing_city'=> $city,
			// 				'billing_pincode'=> $pincode,
			// 				'shipping_country_id'=> $country_id,
			// 				'shipping_state_id'=> $state_id,
			// 				'shipping_name'=> $customer['name'],
			// 				'shipping_address'=> $address,
			// 				'shipping_city'=> $city,
			// 				'shipping_pincode'=> $pincode,
			// 				'is_shipping_inclusive_tax'=> 0,
			// 				'is_express_shipping'=> 0,
			// 				'narration'=> null,
			// 				'round_amount'=> 0,
			// 				'discount_amount'=> 0,
			// 				'exchange_rate' => $this->app->epan->default_currency['value'],
			// 				'tnc_id'=>$tnc_id,
			// 				'tnc_text'=> $tnc_text,
			// 				'status' => "OnlineUnpaid"
			// 			];

			// $detail_data = [];
			// $taxation = $plan_model->applicableTaxation();
			// if($taxation instanceof \xepan\commerce\Model_Taxation){
			// 	$taxation_id = $taxation->id;
			// 	$tax_percentage = $taxation['percentage'];
			// }else{
			// 	$taxation_id = 0;
			// 	$tax_percentage = 0;
			// }


			// $sale_price = $plan_model['sale_price'];
			
			// // update plan pro data basis
			// // if($updating_plan){
			// // }

			// $qty_unit_id = $plan_model['qty_unit_id'];
			// $item = [
			// 	'item_id'=>$plan_model->id,
			// 	'price'=>$sale_price,
			// 	'quantity' => 1,
			// 	'taxation_id' => $taxation_id,
			// 	'tax_percentage' => $tax_percentage,
			// 	'narration'=>null,
			// 	'extra_info'=>"{}",
			// 	'shipping_charge'=>0,
			// 	'shipping_duration'=>0,
			// 	'express_shipping_charge'=>0,
			// 	'express_shipping_duration'=>null,
			// 	'qty_unit_id'=>$qty_unit_id,
			// 	'discount'=>0
			// ];

			// $detail_data[] = $item;

			$qsp = $customer->createQSP(null,[],'SalesOrder',$plan_id);
			$result = ['status'=>'success','message'=>'redirect to payment gateway please wait ...','order_id'=>$qsp['master_detail']['id']];

		}catch(\Exception $e){
			$result = ['status'=>'error','message'=>$e->getMessage()];
		}

		return $result;
	}

}