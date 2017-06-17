<?php

namespace xavoc\ispmanager;


/**
* 
*/
class Tool_StaffPanel extends \xepan\cms\View_Tool{
	function init(){
		parent::init();

		$user_model = $this->add('xavoc\ispmanager\Model_User');

		$form = $this->add('Form',null,null,['form/empty']);
		$form->setLayout(['form/staff/userregistration']);
		$form->setModel($user_model,['plan_id','plan','radius_username','radius_password','simultaneous_use','grace_period_in_days','custom_radius_attributes','mac_address','first_name','last_name','country_id','state_id','city','address','pin_code']);
		$form->addSubmit("Submit",null,'form_buttons');
		
		if($form->isSubmitted()){
			$form->update();
			$form->js(null,$form->js()->reload())->univ()->successMessage('user created');
		}

	}
}