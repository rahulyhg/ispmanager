<?php

namespace xavoc\ispmanager;


/**
* 
*/
class Tool_HotspotRegistration extends \xepan\cms\View_Tool{
	public $options = [];

	function init(){
		parent::init();
		$otp_number = $this->app->stickyGET('secret_opt_pass_code');
		$mobile_no = $this->app->stickyGET('mobile_no');

		if(!$otp_number){
			$registration_form = $this->add('Form',null,null,['form/empty']);
			$registration_form->setLayout(['form/hotspot-registration']);
			$registration_form->layout->add('View',null,'form_title')->set('Hotspot Register');
			$registration_form->addField('Number','mobile_no','Mobile No')->validate('required');
			// $form->addField('Number','otp','OTP');

			$registration_form->addSubmit("Registration")->addClass('btn btn-success btn-lg text-center btn-block');
			$user = $this->add('xavoc\ispmanager\Model_User');

			if($registration_form->isSubmitted()){
				$user['first_name'] = "Guest";
				$user['last_name'] = "User";
				$user['status']="InActive";
				$user['is_verified']=0;
				$user['radius_username'] = $registration_form['mobile_no'];
				$user['radius_password'] = rand(999,999999);
				$user->save();
				
				$sms_model = $this->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'otp_msg_content'=>'Text',
								],
							'config_key'=>'ISPMANAGER_OTP_CONTENT',
							'application'=>'ispmanager'
					]);
				$sms_model->tryLoadAny();
				
				// send SMS
				// if($this->app->getConfig('send_sms',false)){
					$message = $sms_model['otp_msg_content'];
					$temp = $this->add('GiTemplate');
					$temp->loadTemplateFromString($message);
					$msg=$this->add('View',null,null,$temp);
					$msg->template->trySetHTML('otp_number',$user['radius_password']);
					// throw new \Exception($msg->getHtml(), 1);
					
					if(!$sms_model['otp_msg_content']) throw new \Exception("Please update OTP SMS Content");
					// $this->add('xepan\communication\Controller_Sms')->sendMessage($registration_form['mobile_no'],$msg->getHtml());


				// }
				$registration_form->js(null,
										$registration_form->js()
												->univ()
													->successMessage('Send OTP')
									)->reload(
										[
											'mobile_no'=>$user['radius_username'],
											'secret_opt_pass_code'=>$user['radius_password']
										]
									)->execute();
			}
		}else{

			$verify_form = $this->add('Form',null,null,['form/empty']);
			$verify_form->setLayout(['form/hotspot-registration']);
			$verify_form->layout->add('View',null,'form_title')->set('Hotspot Verify');
			$verify_form->addField('Number','mobile_no','Mobile No')->validate('required')->set($mobile_no);
			$verify_form->addField('Number','otp','OTP')->validate('required');//->set($otp_number);

			$verify_form->addSubmit("Verify OTP")->addClass('btn btn-success btn-lg text-center btn-block');
			
			if($verify_form->isSubmitted()){

				$user=$this->add('xavoc\ispmanager\Model_User');	
				$user->addCondition('radius_username',$verify_form['mobile_no']);
				$user->tryLoadAny();
				if(!$user->loaded())
					$verify_form->displayError('mobile_no','This M-Number is not registered');

				if($verify_form['otp']!=$user['radius_password'])
					$verify_form->displayError('otp','OTP did not match');

				$defalut_plan_model = $this->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'default_hotspot_plan'=>'DropDown',
								],
							'config_key'=>'ISPMANAGER_DEFAULT_HOTSPOT_PLAN',
							'application'=>'ispmanager'
					]);
				$defalut_plan_model->tryLoadAny();
				
				$user['status']="Active";
				$user['is_verified']=1;
				$user['plan_id']=$defalut_plan_model['default_hotspot_plan'];
				$user->save();
				$this->app->stickyForget('secret_opt_pass_code');
				$this->app->stickyForget('mobile_no');
				return $verify_form->js(null,$verify_form->js()->univ()->successMessage('Mobile Number is Registered'))->redirect($this->app->url())->execute();
			}




		}
		
	}
}