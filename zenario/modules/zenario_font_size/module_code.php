<?php
/*
 * Copyright (c) 2018, Tribal Limited
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




class zenario_font_size extends ze\moduleBaseClass {
	
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$this->callScript('zenario_font_size', 'init', $this->setting('minimum_font_size'),$this->setting('maximum_font_size'));
		return true;
	}
	
	function showSlot() {
		$this->framework('Font_Size');		
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'plugin_settings':
				if (!empty($values['first_tab/minimum_font_size'])){
					if (!is_numeric(($values['first_tab/minimum_font_size'] ?? false))  || !(($values['first_tab/minimum_font_size'] ?? false)>0) ){
						$box['tabs']['first_tab']['errors'][] = "Error. The minimum font size must be a number greater than 0.";
					}
				}
				if (!empty($values['first_tab/maximum_font_size'])){
					if (!is_numeric(($values['first_tab/maximum_font_size'] ?? false))  || !(($values['first_tab/maximum_font_size'] ?? false)>0) ){
						$box['tabs']['first_tab']['errors'][] = "Error. The maximum font size must be a number greater than 0.";
					}
				}
				break;
		}
	}



}
