<?php

namespace xavoc\ispmanager;

class Controller_Greet extends \AbstractController {

	function do($contact,$event,$related_document=null){


		$messages_model = $this->add('xepan\base\Model_ConfigJsonModel',
			[
				'fields'=>[
							'lead_assign_sms_content'=>'Text',
							'lead_assign_email_subject'=>'Line',
							'lead_assign_email_content'=>'xepan\base\RichText',

							'installation_lead_assign_sms_content'=>'Text',
							'installation_lead_assign_email_subject'=>'Line',
							'installation_lead_assign_email_content'=>'xepan\base\RichText',

							'new_account_sms_content'=>'Text',
							'new_account_email_subject'=>'Line',
							'new_account_email_content'=>'xepan\base\RichText',

							'invoice_paid_sms_content'=>'Text',
							'invoice_paid_email_subject'=>'Line',
							'invoice_paid_email_content'=>'xepan\base\RichText',

							'renewal_alert_sms_content'=>'Text',
							'renewal_alert_email_subject'=>'Line',
							'renewal_alert_email_content'=>'xepan\base\RichText',

							'account_reactivation_sms_content'=>'Text',
							'account_reactivation_email_subject'=>'Line',
							'account_reactivation_email_content'=>'xepan\base\RichText',
							
							'account_reactivation_sms_content'=>'Text',
							'account_reactivation_email_subject'=>'Line',
							'account_reactivation_email_content'=>'xepan\base\RichText',

							'plan_changed_sms_content'=>'Text',
							'plan_changed_email_subject'=>'Line',
							'plan_changed_email_content'=>'xepan\base\RichText',

						],
					'config_key'=>'ISPMANAGER_EMAIL_SMS_CONTENT',
					'application'=>'ispmanager'
			]);
		$messages_model->tryLoadAny();


		if($related_document){
			if(is_array($related_document))
				$data = array_merge($contact->data,$related_document);
			else
				$data = array_merge($contact->data,$related_document->data);
		}
		else{
			$data = $contact->data;
		}

		if($this->app->getConfig('send_email',false)){
			
			if($messages_model[$event.'_mail_subject'] AND $messages_model[$event.'_mail_content']){

				$temp = $this->add('GiTemplate');
				$temp->loadTemplateFromString($messages_model[$event.'_mail_subject']);

				$temp->set($data);
				$subject = $temp->render();
					// body
				$temp = $this->add('GiTemplate');
				$temp->loadTemplateFromString($messages_model[$event.'_mail_content']);
				$temp->set($data);
				$body = $temp->render();

				$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting')->setOrder('id','asc');
				$email_setting->addCondition('is_active',true);
				$email_setting->tryLoadAny();

				if(!$email_setting->loaded()) throw new \Exception("update your email setting ", 1);


				$communication = $this->add('xepan\communication\Model_Communication_Abstract_Email');
				$communication->addCondition('communication_type','Email');

				$communication->getElement('status')->defaultValue('Draft');
				$communication['direction']='Out';
				$communication->setfrom($email_setting['from_email'],$email_setting['from_name']);
				
				$communication->addTo($contact['email']);
				$communication->setSubject($subject);
				$communication->setBody($body);
				$communication->send($email_setting);
			}
		}

		if($this->app->getConfig('send_sms',false)){

			$message = $messages_model[$event.'_sms_content'];
			$temp = $this->add('GiTemplate');
			$temp->loadTemplateFromString($message);
			$temp->set($data);
			$message = $temp->render();
				
			if($messages_model[$event.'_sms_content']){
				$this->add('xepan\communication\Controller_Sms')->sendMessage($contact['mobile_number'],$message);
			}
		}
	}
}