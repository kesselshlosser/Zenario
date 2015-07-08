<?php
/*
 * Copyright (c) 2015, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

class zenario_extranet_registration extends zenario_extranet {

	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = false, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		
		if (post('extranet_register')){
			if ($this->setting("enable_registration_code") && !$this->checkCodeValid(post("registration_code"))) {
				if ($this->setting("require_valid_code")) {
					$this->errors[] = array('Error' => $this->phrase('Registration code not valid'));
				}
			}
		}
		
		$this->mode = 'modeRegistration';
		$this->registerGetRequest('extranet_resend');
		
		// Set the title
		if ($this->setting('registration_title')) {
			$this->subSections['Registration_Title_Section'] = true;
			$this->objects['Registration_Title'] = $this->phrase($this->setting('registration_title'));
		}
		
		if (!canSetCookie() && setting('cookie_consent_for_extranet') == 'required') {
			requireCookieConsent();
			$this->message = $this->phrase('_PLEASE_ACCEPT_COOKIES');
			$this->mode = 'modeCookiesNotEnabled';
			return true;
		
		} else {
			if (setting('cookie_consent_for_extranet') == 'granted') {
				hideCookieConsent();
			}
			$this->manageCookies();
			
			
			if ($this->useScreenName) {
				$this->subSections['Choose_Screen_Name'] = true;
			}
			
			if ($this->setting('requires_terms_and_conditions') && $this->setting('terms_and_conditions_page')) {
				$this->subSections['Ts_And_Cs_Section'] = true;
				$cID = $cType = false;
				$this->getCIDAndCTypeFromSetting($cID, $cType, 'terms_and_conditions_page');
				langEquivalentItem($cID, $cType);
				$TCLink = array( 'TCLink' =>$this->linkToItem($cID, $cType, true));
				$this->objects['Ts_And_Cs_Link'] =  $this->phrase('_T_C_LINK', $TCLink);
			}
			
			if (!empty($_SESSION['extranetUserID'])) {
				$this->mode = 'modeLoggedIn';
			} elseif (post('extranet_resend') && ($this->setting('initial_email_address_status')=='not_verified')) {
				$this->validateFormFields('Resend_Form');
				$user = $this->getDetailsFromEmail(post('email'));
				if ((!$this->errors) && (!empty($user['id'])) ) {
					$this->sendVerificationEmail(arrayKey($user,'id'));
					$this->mode = 'modeResent';
				} else {
					$this->mode = 'modeResend';
				}
			} elseif (get('extranet_resend') && ($this->setting('initial_email_address_status')=='not_verified')) {
				$this->mode = 'modeResend';
			} elseif (post('extranet_register')){
				$this->scrollToTopOfSlot();
				if (post('screen_name')) {
					$_POST['screen_name'] = trim($_POST['screen_name']);
				}
				if ($userId = $this->addUserRecord()){
					unset($_SESSION['captcha_passed__'. $this->instanceId]);
					if (setting('cookie_consent_for_extranet') == 'granted') {
						setCookieConsent();
					}
					$this->applyEmailVerificationPolicy($userId);
					if ($this->isEmailAddressVerified($userId)){
						$this->applyAccountActivationPolicy($userId);
						if ($this->isActive($userId)){
							$this->logUserIn($userId);
							$this->mode = 'modeLoggedIn';
							$this->redirectToPage();
						} else {
							$this->mode = 'modeRegisteredVerifiedNotActivated';
						}
					} else {
						$this->mode = 'modeRegisteredNotVerified';
					}
				} else {
					$this->mode = 'modeRegistration';
				}
			} elseif (get('confirm_email') && ($this->setting('initial_email_address_status')=='not_verified')) { 
				if ($userId = $this->getUserIdFromHashCode(get('hash'))){
					if (!$this->isEmailAddressVerified($userId)){
						$this->setEmailVerified($userId);
						$this->applyAccountActivationPolicy($userId);
						if ($this->isActive($userId)){
							if (setting('cookie_consent_for_extranet') == 'granted') {
								setCookieConsent();
							}
							$this->logUserIn($userId);
							$this->mode = 'modeLoggedIn';
							$this->redirectToPage();
						} else {
							$this->mode = 'modeRegisteredVerifiedNotActivated';
						}
					} else {
						$this->mode = 'modeVerificationAlreadyDone';
					}
				} else {
					$this->mode = 'modeVerificationFailed';
				}
			}
			
			if ($this->mode == 'modeRegistration') {
				if ($this->setting('use_captcha') && empty($_SESSION['captcha_passed__'. $this->instanceId])) {
					$this->objects['Captcha'] = $this->captcha();
					$this->subSections['Captcha'] = true;
				}
			}
			return true;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__users/panels/zenario_extranet_registration__codes':
				if ($refinerName == 'code_id') {
					$code = $this->getCode($refinerId);
					$panel['title'] = "Groups for the Code \"" . $code['code'] . "\"";
				}
				break;
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch($path) {
			case 'plugin_settings':
				$fields['set_timer_on_new_users']['hidden'] = !inc('zenario_user_timers');
				break;
			case "zenario_extranet_registration__codes":
				if (!empty($box['key']['id'])) {
					$code = $this->getCode($box['key']['id']);
					
					$values['details/code'] = arrayKey($code,'code');
					$values['details/description'] = arrayKey($code,'description');
					$box['tabs']['details']['edit_mode']['on'] = false;
					$box['tabs']['details']['edit_mode']['always_on'] = false;
					$box['title'] = adminPhrase( 'Viewing/Editing the Code "[[code]]"',array('code' => arrayKey($code,'code')));
				}
				break;
		}
			
		return parent::fillAdminBox($path, $settingGroup, $box, $fields, $values);
	}
	
	public function getCode ($id) {
		return getRow(ZENARIO_EXTRANET_REGISTRATION_PREFIX . "codes", array("id","code","description"), array("id" => $id));
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch($path) {
			case 'zenario_extranet_registration__codes':
				if (engToBooleanArray($box['tabs']['details'], 'edit_mode', 'on')) {
					if (empty($values['details/code'])) {
						$box['tabs']['details']['errors'][] =
							adminPhrase('Please enter a Code.');
					
					} elseif (!self::checkCodeUnique($values['details/code'],arrayKey($box['key'],'id'))) {
						$box['tabs']['details']['errors'][] =
							adminPhrase('This Code is already in use.');
					} elseif (preg_match('/\s/', $values['details/code'])) {
						$box['tabs']['details']['errors'][] = adminPhrase("You must not have a space in your Code.");
					} elseif (preg_match('/[^a-zA-Z 0-9_-]/', $values['details/code'])) {
						$box['tabs']['details']['errors'][] = adminPhrase("Your Code can only contain the letters a-z, numbers, underscores or hyphens.");
					}
				}
				break;
		}
	}
	
	public static function checkCodeUnique($code, $currentId = false) {
		$existingId =
			getRow(
				ZENARIO_EXTRANET_REGISTRATION_PREFIX . 'codes',
				'id',
				array('code' => $code));
				
		return !$existingId || ($currentId && $currentId == $existingId);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "zenario_extranet_registration__codes":
				if (engToBooleanArray($box['tabs']['details'], 'edit_mode', 'on')) {
					$box['key']['id'] = $this->saveCode(
						arrayKey($values, 'details/code'),
						arrayKey($values, 'details/description'),
						arrayKey($box['key'],'id'));
				}
				break;
		}
	}
	
	public function saveCode ($code,$description,$id) {
		return
			setRow(
				ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'codes',
				array(
					'code' => $code,
					'description' => $description),
				array('id' => (int) $id));
	}
	

	protected function validateFormFields($section) {
		$fields = parent::validateFormFields($section);
		if ($this->setting('user_passwords') && ($section=='Registration_Form')){
			$errors = $this->validatePassword(post('extranet_new_password'),post('extranet_new_password_confirm'),false,get_class($this));
			if (count($errors)) {
				$this->errors = array_merge ($this->errors, $errors);
				return false;
			}
		}
		if ($this->setting('use_captcha') && empty($_SESSION['captcha_passed__'. $this->instanceId])) {
			if ($this->checkCaptcha()) {
				$_SESSION['captcha_passed__'. $this->instanceId] = true;
			} else {
				$this->errors[] = array('Error' => $this->phrase('_CAPTCHA_INVALID'));
			}
		}
		return $fields;
	}

	protected function addUserRecord(){
		$fields = $this->validateFormFields('Registration_Form');
		if ($this->errors){
			return false;
		}
		
		if ($this->useScreenName) {
			if (post('screen_name')){
				$fields['screen_name'] = post('screen_name');
				$fields['screen_name_confirmed'] = 1;
			} else {
				$this->errors[] = array('Error' => $this->phrase('_ERROR_SCREEN_NAME'));
				return false;
			}
		}
		
		$fields['email_verified'] = 0;
		if ($this->setting('initial_account_status')=='pending'){
			$fields['status'] = 'pending';
			if ($this->setting('user_passwords')){
				$fields['password'] = post('extranet_new_password');
			} else {
				$fields['password'] = createPassword();
			}
		} else {
			$fields['status'] = 'contact';
		}
		$fields['created_date'] = date("Y-m-d H:i:s");
		if ($this->setting('user_passwords')){
			$fields['password'] = post('extranet_new_password');
		} else {
			$fields['password'] = createPassword();
		}
		$fields['hash'] =  md5(randomString());
		$fields['ip'] = visitorIP();
		
		if(request('extranet_terms_and_conditions') && $this->setting('requires_terms_and_conditions') && $this->setting('terms_and_conditions_page')){
			$fields['terms_and_conditions_accepted'] = 1;
		}
		
		if (isset($fields['%attributes%'])) {
			unset($fields['%attributes%']);
		}
		
		// Temp code to remove extra values from custom frameworks. Module should eventually be changed to user_forms.
		$fields2 = array();
		$sql = 'SHOW COLUMNS FROM '. DB_NAME_PREFIX. 'users';
		$result = sqlQuery($sql);
		while ($column = sqlFetchAssoc($result)) {
			if (isset($fields[$column['Field']])) {
				$fields2[$column['Field']] = $fields[$column['Field']];
			}
		}
		
		$userId = saveUser($fields2);
		
		// Set a group or checkbox value for a user
		if ($this->setting('set_characteristics_on_new_users') && $this->setting('select_characteristics_for_new_users')) {
			$field_dbcolumn = datasetFieldDBColumn($this->setting('select_characteristics_for_new_users'));
			$fields[$field_dbcolumn] = 1;
		}
		
		if (isError($userId)) {
			return false;
		} else {
			$fields['user_id'] = $userId;
			foreach(getFields(DB_NAME_PREFIX, 'users_custom_data') as $field => $details) {
				if (isset($fields[$field])) {
					setRow('users_custom_data', array($field => $fields[$field]), array('user_id' => $userId));
				}
			}
			
			if ($this->setting('select_characteristic_values_for_new_users')) {
				$field = getDatasetFieldDetails($this->setting('set_characteristics_on_new_users'));
				foreach (explode(',', $this->setting('select_characteristic_values_for_new_users')) as $value) {
					setRow('custom_dataset_values_link', array('linking_id'=> $userId, 'value_id'=> $value, 'dataset_id' => $field['dataset_id']),  array('linking_id'=> $userId, 'value_id'=> $value, 'dataset_id' => $field['dataset_id']));
				}
			}
			
			if (inc("zenario_user_timers") && $this->setting('timer_for_new_users')) {
				zenario_user_timers::addTimerToUser($userId, $this->setting('timer_for_new_users'));
			}
			
			if ($this->setting("enable_registration_code") && $this->checkCodeValid(post("registration_code"))) {
				$codeId = $this->getCodeIdFromCode(post("registration_code"));
				
				$groupIds = $this->getCodeGroups($codeId);
	
				foreach ($groupIds as $groupId) {
					addUserToGroup($userId,$groupId);
				}
			}
			
			$this->sendSignalFromForm('eventUserRegistered', $userId);
			
			return $userId;
		}
	}
	
	
	protected function applyEmailVerificationPolicy($userId){
		if ($this->setting('enable_notifications_on_user_signup') && ($this->setting('user_signup_notification_email_template')) && ($this->setting('user_signup_notification_email_address'))){
			$this->sendSignupNotification($userId);
		}
		if ($this->setting('initial_email_address_status')=='verified'){
			$this->setEmailVerified($userId);
		} elseif ($this->setting('initial_email_address_status')=='not_verified'){
			$this->sendVerificationEmail($userId);
		}
	}

	protected function setEmailVerified($userId){
		if ($userId){
			$sql ="
					UPDATE "
						. DB_NAME_PREFIX . "users 
					SET email_verified = 1";
			
			$sql .= " WHERE id = " . (int) $userId;
			sqlQuery($sql);

			if ($this->setting('add_user_to_group') && (int) $this->setting('select_group_for_new_users')) {
				addUserToGroup($userId, (int) $this->setting('select_group_for_new_users'));
			}
		}
	}
	
	protected function getUserIdFromHashCode($hash){
		if ($hash && ($userId = (int) getRow("users","id",array('hash'=>$hash)))){
			return $userId;
		} else {
			return 0;
		}
	}
	
	protected function sendVerificationEmail($userId) {
		$emailMergeFields = getUserDetails($userId);
		if (empty($emailMergeFields['hash'])){
			updateUserHash($userId);
			$emailMergeFields = getUserDetails($userId);
		}
		if (!empty($emailMergeFields['email']) && $this->setting('verification_email_template')) {
			$emailMergeFields['ip_address'] = visitorIP();
			$emailMergeFields['cms_url'] = absCMSDirURL();
			$emailMergeFields['email_confirmation_link'] = $this->linkToItem($this->cID, $this->cType, $fullPath = true, $request = '&confirm_email=1&hash='. $emailMergeFields['hash']);
						
			$emailMergeFields['user_groups'] = getUserGroupsNames($userId);
			
			if (inc('zenario_users')) {
				foreach (getUserDetails($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv);
				}
			}
			
			
			zenario_email_template_manager::sendEmailsUsingTemplate(arrayKey($emailMergeFields,'email'),$this->setting('verification_email_template'),$emailMergeFields,array());
		}
	}

	protected function sendSignupNotification($userId){
		if ($this->setting('user_signup_notification_email_address') && $this->setting('user_signup_notification_email_template')) {
			$emailMergeFields = getUserDetails($userId);
			if (empty($emailMergeFields['hash'])){
				updateUserHash(arrayKey($userId));
				$emailMergeFields = getUserDetails(arrayKey($_SESSION,'extranetUserID'));
			}
			$emailMergeFields['ip_address'] = visitorIP();
			$emailMergeFields['cms_url'] = absCMSDirURL();
			$emailMergeFields['email_confirmation_link'] = $this->linkToItem($this->cID, $this->cType, $fullPath = true, $request = '&confirm_email=1&hash='. $emailMergeFields['hash']);
			$emailMergeFields['organizer_link'] = httpOrhttps(). primaryDomain(). SUBDIRECTORY. 'zenario/admin/organizer.php#zenario__users/panels/users//'. $emailMergeFields['id'];
			
			$emailMergeFields['user_groups'] = getUserGroupsNames($userId);
			
			if (inc('zenario_users')) {
				foreach (getUserDetails($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv);
				}
			}
			
	
			zenario_email_template_manager::sendEmailsUsingTemplate($this->setting('user_signup_notification_email_address'),$this->setting('user_signup_notification_email_template'),$emailMergeFields,array());
		}
	}

	protected function applyAccountActivationPolicy($userId){
		if ($this->setting('user_activation_notification_email_enable') && ($this->setting('user_activation_notification_email_template')) && ($this->setting('user_activation_notification_email_address'))){
			$this->sendActivationNotification($userId);
		}
		
		switch ($this->setting('verified_account_status')) {
			case 'contact':
				$this->setAccountContact($userId);
				$this->sendWelcomeEmail($userId);
				break;
			case 'active':
				$this->setAccountActive($userId);
				$this->sendWelcomeEmail($userId);
				break;
			case 'check_trusted':
				$userDetails = getUserDetails($userId);
				$userEmail = $userDetails['email'];
				$domains = explode(',', $this->setting('trusted_email_domains'));
				$domains = array_map('trim', $domains);
				if (in_array(substr($userEmail, strpos($userEmail, '@')), $domains)) {
					$this->setAccountActive($userId);
					$this->sendWelcomeEmail($userId);
				}
				break;
		}
	}
	
	protected function setAccountContact($userId){
		if ($userId) {
			$sql = '
				UPDATE '
					 .DB_NAME_PREFIX . 'users
				SET 
					status = "contact"
				WHERE 
					id = '.(int)$userId;
			sqlQuery($sql);
		}
	}

	protected function setAccountActive($userId){
		if ($userId){
			$sql ="
					UPDATE " 
						. DB_NAME_PREFIX . "users
					SET 
						status='active'
					WHERE 
						id = " . (int) $userId;
			sqlQuery($sql);
		}				
	}
	
	protected function sendWelcomeEmail($userId){
		$emailMergeFields = getUserDetails($userId);
		if (!empty($emailMergeFields['email']) && $this->setting('welcome_email_template')) {
			
			$emailMergeFields['ip_address'] = visitorIP();
			$emailMergeFields['cms_url'] = absCMSDirURL();
			$emailMergeFields['user_groups'] = getUserGroupsNames($userId);
			
			if (inc('zenario_users')) {
				foreach (getUserDetails($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv);
				}
			}
			
			// If passwords are encrypted
			if (!setting('plaintext_extranet_user_passwords')) {
				// If user chose password, show ****
				if ($this->setting('user_passwords')) {
					$emailMergeFields['password'] = '********';
				// Otherwise generate a new password and show it
				} else {
					$password = createPassword();
					$emailMergeFields['password'] = $password;
					setUsersPassword($userId, $password);
				}
			}
			zenario_email_template_manager::sendEmailsUsingTemplate(arrayKey($emailMergeFields,'email'),$this->setting('welcome_email_template'),$emailMergeFields,array());
		}
	}

	protected function sendActivationNotification($userId){
		if ($this->setting('user_activation_notification_email_address') && $this->setting('user_activation_notification_email_template')) {
			$emailMergeFields = getUserDetails($userId);
			$emailMergeFields['ip_address'] = visitorIP();
			$emailMergeFields['cms_url'] = absCMSDirURL();
			$emailMergeFields['organizer_link'] = httpOrhttps(). primaryDomain(). SUBDIRECTORY. 'zenario/admin/organizer.php#zenario__users/panels/users//'. $emailMergeFields['id'];
			
			$emailMergeFields['user_groups'] = getUserGroupsNames($userId);
			
			if (inc('zenario_users')) {
				foreach (getUserDetails($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv);
				}
			}
			
			
			zenario_email_template_manager::sendEmailsUsingTemplate($this->setting('user_activation_notification_email_address'),$this->setting('user_activation_notification_email_template'),$emailMergeFields,array());
		}
	}
		
	protected function isEmailAddressVerified($userId){
		return (boolean) ((int) getRow('users','email_verified',array('id'=>$userId)));
	}

	protected function isActive($userId){
		return (getRow('users','status',array('id'=>$userId))=='active');
	}


	protected function modeRegistration() {
		if ($this->setting("enable_registration_code")) {
			$this->subSections['Registration_Code_Section'] = true;
		}
		
		$this->addLoginLinks();
		
		//Overwrite the Resend_Link from the addLoginLinks() function
		if ($this->setting('initial_email_address_status')=='not_verified'){
			$this->subSections['Resend_Link_Section'] = true;
			$this->objects['Resend_Link'] = $this->refreshPluginSlotAnchor('&extranet_resend=1');
		} else {
			$this->subSections['Resend_Link_Section'] = false;
			unset($this->objects['Resend_Link']);
		}

		
		$this->subSections['User_passwords'] = $this->setting('user_passwords');
		
		$this->frameworkHead('Outer', 'Registration_Form', $this->objects, $this->subSections);
			echo $this->openForm('',' class="form-horizontal"');
					$this->framework('Registration_Form', $this->objects, $this->subSections);
			echo $this->closeForm();
		$this->frameworkFoot('Outer', 'Registration_Form', $this->objects, $this->subSections);
	}

	protected function modeResend() {
		$this->addLoginLinks();
		
		$this->objects['Registration_Link'] = $this->refreshPluginSlotAnchor();
		
		$this->frameworkHead('Outer', 'Resend_Form', $this->objects, $this->subSections);
			echo $this->openForm('',' class="form-horizontal"'),
					$this->remember('extranet_resend');
					$this->framework('Resend_Form', $this->objects, $this->subSections);
			echo $this->closeForm();
		$this->frameworkFoot('Outer', 'Resend_Form', $this->objects, $this->subSections);
	}

	protected function modeVerificationFailed(){
		$this->subSections['Verification_Failed'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	protected function modeVerificationAlreadyDone(){
		$this->subSections['Verification_Already_Done'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	protected function modeRegisteredNotVerified(){
		$this->subSections['Registered_Not_Verified'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}

	protected function modeRegisteredVerifiedNotActivated(){
		$this->subSections['Registered_Verified_Not_Activated'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}

	protected function modeRegistered() {
		$this->subSections['Registered'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}

	protected function modeResent() {
		$this->subSections['Registered_Email_Resent'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				if(isset($values['initial_email_address_status'])) {
					$fields['select_group_for_new_users']['hidden'] = !$values['add_user_to_group'];
					
					$fields['verification_email_template']['hidden'] = $values['initial_email_address_status']=='verified';
	
					$fields['user_signup_notification_email_template']['hidden'] = !$values['enable_notifications_on_user_signup'];
					$fields['user_signup_notification_email_address']['hidden'] = !$values['enable_notifications_on_user_signup'];
					
					$fields['welcome_email_template']['hidden'] = !($values['verified_account_status'] == 'active' || $values['verified_account_status'] == 'contact');
					$fields['trusted_email_domains']['hidden'] = $values['verified_account_status'] != 'check_trusted';
		
					$fields['user_activation_notification_email_template']['hidden'] = !$values['user_activation_notification_email_enable'];
					$fields['user_activation_notification_email_address']['hidden'] = !$values['user_activation_notification_email_enable'];
	
					$fields['select_characteristics_for_new_users']['hidden'] = !$values['set_characteristics_on_new_users'];
					$fields['timer_for_new_users']['hidden'] = !$values['set_timer_on_new_users'];
				}
				if ($values['select_characteristics_for_new_users']) {
					$fieldType = getRow('custom_dataset_fields', 'type', $values['select_characteristics_for_new_users']);
					if(($fieldType == 'checkboxes' )|| ($fieldType == 'radios')) {
						$fields['select_characteristic_values_for_new_users']['hidden'] = false;
						$fields['select_characteristic_values_for_new_users']['values'] = getRowsArray('custom_dataset_field_values', 'label', array('field_id' => $values['select_characteristics_for_new_users']));
					} else {
						$fields['select_characteristic_values_for_new_users']['hidden'] = true;
					}
				} else {
					$fields['select_characteristic_values_for_new_users']['hidden'] = true;
				}
				$fields['welcome_page']['hidden'] = $values['show_welcome_page'] != '_ALWAYS' && $values['show_welcome_page'] != '_IF_NO_PREVIOUS_PAGE';
				$fields['registration_codes/require_valid_code']['hidden'] = !$values['registration_codes/enable_registration_code'];
				
				if (isset($box['tabs']['first_tab']['fields']['terms_and_conditions_page'])
				 && isset($box['tabs']['first_tab']['fields']['requires_terms_and_conditions'])) {
					$box['tabs']['first_tab']['fields']['terms_and_conditions_page']['hidden'] = 
						!$values['first_tab/requires_terms_and_conditions'];
				}
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case "zenario__users/panels/zenario_extranet_registration__code_groups":
				if (post('action') == 'add_group_to_code') {
					foreach (explode(',', $ids) as $id) {
						$sql = "REPLACE INTO " . DB_NAME_PREFIX . ZENARIO_EXTRANET_REGISTRATION_PREFIX. "code_groups
								SET code_id = " . (int) $refinerId . ",
									group_id = " . (int) $id;
						$result = sqlQuery($sql);
					}
				}
	
				if (post('action') == 'remove_group_from_code') {
					foreach (explode(',', $ids) as $id) {
						deleteRow(
							ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'code_groups',
							array('id' => (int)$id));
					}
				}
				break;
			case "zenario__users/panels/zenario_extranet_registration__codes":
				if (post('action') == 'delete_code') {
					foreach (explode(',', $ids) as $id) {
						deleteRow(
							ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'codes',
							array('id' => (int) $id));
						deleteRow(
							ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'code_groups',
							array('code_id' => (int) $id));
					}
				}
				
				break;
		}
	}
	
	public function checkCodeValid ($code) {
		return checkRowExists(ZENARIO_EXTRANET_REGISTRATION_PREFIX . "codes",array("code" => $code));
	}
	
	public function getCodeIdFromCode ($code) {
		return getRow(ZENARIO_EXTRANET_REGISTRATION_PREFIX . "codes","id",array("code" => $code));
	}
	
	public function getCodeGroups ($codeId) {
		$result = getRows(ZENARIO_EXTRANET_REGISTRATION_PREFIX . "code_groups","group_id",array("code_id" => $codeId));
		
		$groupIds = array();
		
		if (sqlNumRows($result)>0) {
			while ($row = sqlFetchAssoc($result)) { 
				$groupIds[] = $row['group_id'];
			}
		}
		return $groupIds;
	}
}
?>