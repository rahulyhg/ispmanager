<?php

namespace xavoc\ispmanager;

class Model_Plan extends \xepan\commerce\Model_Item{
	// public $table = "isp_plan";
	public $status = ['active','deactive'];
	public $actions = [
				'active'=>['view','edit','delete','condition'],
				'deactive'=>['view','edit','delete','active']
				];
	
	public $acl_type="ispmanager_plan";

	function init(){
		parent::init();

		// destroy extra fields
		$item_fields = $this->add('xepan\commerce\Model_Item')->getActualFields();
		$required_field = ['name','sku','description','sale_price','status','document_id','id','created_by','updated_by','created_at','updated_at','type'];
		$destroy_field = array_diff($item_fields, $required_field);
		foreach ($destroy_field as $key => $field) {
			if($this->hasElement($field))
				$this->getElement($field)->destroy();
		}
		$this->getElement('status')->enum(['active','deactive'])->defaultValue('active');
		
		// if($this->hasElement('minimum_order_qty'))
		// 	$this->getElement('minimum_order_qty')->set(1);

		$plan_j = $this->join('isp_plan.item_id');

		$plan_j->addField('maintain_data_limit')->type('boolean')->defaultValue(true);
		$plan_j->addField('period')->type('number');
		$plan_j->addField('period_unit')->enum(['hours','days','months','years']);

		$plan_j->addField('is_topup')->type('boolean')->defaultValue(false);
		$plan_j->addField('is_auto_renew')->type('boolean')->defaultValue(0);
		$plan_j->addField('available_in_user_control_panel')->type('boolean');

		$this->hasMany('xavoc\ispmanager\Condition','plan_id',null,'conditions');

		$this->addHook('beforeSave',$this,[],4);

		// $this->add('dynamic_model/Controller_AutoCreator');
	}

	function beforeSave(){
		$this['original_price'] = $this['sale_price'];
		$this['minimum_order_qty'] = 1;
	}

	function page_condition($page){
		$condition_model = $this->add('xavoc\ispmanager\Model_Condition');
		$condition_model->addcondition('plan_id',$this->id);

		$crud = $page->add('xepan\hr\CRUD');
		$crud->setModel($condition_model);
		// if($crud->isEditing()){
		// 	$form = $crud->form;
		// 	$recurring_field = $form->getElement('is_recurring');
		// 	$recurring_field->js(true)->univ()
		// 			->bindConditionalShow([
		// 				'1'=>['data_reset_value','data_reset_mode'],
		// 			],'div.atk-form-row');
		// }
	}
}