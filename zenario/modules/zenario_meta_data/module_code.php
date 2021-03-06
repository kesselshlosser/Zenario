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


class zenario_meta_data extends ze\moduleBaseClass {
	
	var $mergeFields = [];
	var $showSections = [];
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = (bool) $this->setting('show_categories'), $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		return true;
	}
	
	//The showSlot method is called by the CMS, and displays the Plugin on the page
	function showSlot() {
		if ($this->setting('show_labels')) {
			$this->showSections['show_labels'] = true;
		}
		$this->getContentItemMetaData();
		$this->framework('Outer',$this->mergeFields,$this->showSections);
	}
	
	
	//Attempt to look up the release_date column from the database.
	function getContentItemMetaData() {
		if ($this->setting('show_date') && $this->setting('date_format')){
			$dates = ze\row::get('content_item_versions', ['release_date'], ['id'=>$this->cID, 'type'=>$this->cType, 'version'=>$this->cVersion]);
			if ($releaseDate = ze\date::format($dates['release_date'], $this->setting('date_format'))) {
				$this->mergeFields['Date'] = ['value' => $releaseDate, 'html_tag' => $this->setting('date_html_tag'), 'label' => 'Release date', 'class' => 'release_date'];
				$this->showSections['show_date'] = true;
			}
		}
		if ($this->setting('show_writer_name')){
			if ($writerName = ze\row::get('content_item_versions', 'writer_name', ['id'=>$this->cID, 'type'=>$this->cType, 'version'=>$this->cVersion])){
				$this->mergeFields['Writer_name'] = ['value' => htmlspecialchars($writerName), 'html_tag' => $this->setting('writer_name_html_tag'), 'label' => 'Writer name', 'class' => 'writer_name'];
				$this->showSections['show_writer_name'] = true;
			}
		}
		if ($this->setting('show_writer_image')){
			$sql = '
				SELECT f.id, f.width, f.height, f.alt_tag
				FROM '.DB_PREFIX.'content_item_versions v
				INNER JOIN '.DB_PREFIX.'admins a
					ON v.writer_id = a.id
				INNER JOIN '.DB_PREFIX.'files f
					ON a.image_id = f.id
				WHERE v.id = '.(int)$this->cID.'
					AND v.type = "'.ze\escape::sql($this->cType).'"
					AND v.version = '.(int)$this->cVersion;
			$result = ze\sql::select($sql);
			$file = ze\sql::fetchAssoc($result);
			if (!empty($file)) {
				$width = $height = $url = false;
				ze\file::imageLink($width, $height, $url, $file['id'], $this->setting('width'), $this->setting('height'), $this->setting('canvas'), $this->setting('offset'));
				if ($url) {
					$this->mergeFields['Writer_image'] = ['Writer_Src' => $url, 'Writer_Alt' => $file['alt_tag'], 'html_tag' => $this->setting('writer_image_label_html_tag'), 'label' => 'Writer image', 'class' => 'writer_image'];
					$this->showSections['show_writer_image'] = true;
				}
			}
		}
		if ($this->setting('show_sticky_image')) {
			$sql = '
				SELECT f.id, f.alt_tag
				FROM '.DB_PREFIX.'content_item_versions v
				INNER JOIN '.DB_PREFIX.'files f
					ON v.feature_image_id = f.id
				WHERE v.id = '.(int)$this->cID.'
					AND v.type = "'.ze\escape::sql($this->cType).'"
					AND v.version = '.(int)$this->cVersion;
			$result = ze\sql::select($sql);
			$file = ze\sql::fetchAssoc($result);
			if (!empty($file)) {
				$width = $height = $url = false;
				ze\file::imageLink($width, $height, $url, $file['id'], $this->setting('sticky_image_width'), $this->setting('sticky_image_height'), $this->setting('sticky_image_canvas'), $this->setting('sticky_image_offset'));
				if ($url) {
					$this->mergeFields['Sticky_image'] = ['Sticky_Image_Src' => $url, 'Sticky_Image_Alt' => $file['alt_tag'], 'html_tag' => $this->setting('sticky_image_label_html_tag'), 'label' => 'Featured image', 'class' => 'sticky_image'];
					$this->showSections['show_sticky_image'] = true;
				}
			}
		}
		
		if ($this->setting('show_title')){
			if ($this->mergeFields['Title'] = ['value' => htmlspecialchars(ze::$pageTitle), 'html_tag' => $this->setting('title_html_tag'), 'label' => 'Page title', 'class' => 'page_title']){
				$this->showSections['show_title'] = true;
			}
		}
		if ($this->setting('show_description')){
			if (!empty($description = htmlspecialchars(ze::$pageDesc))) {
				$this->mergeFields['Description'] = ['value' => $description, 'html_tag' => $this->setting('description_html_tag'), 'label' => 'Page description', 'class' => 'page_description'];
				$this->showSections['show_description'] = true;
			}
		}
		if ($this->setting('show_summary')){
			$row = ze\row::get('content_item_versions', ['content_summary'], ['id'=>$this->cID, 'version' => $this->cVersion, 'type' => $this->cType]);
			if (!empty($row['content_summary'])) {
				$this->mergeFields['Summary'] = ['value' => $row['content_summary'], 'html_tag' => $this->setting('summary_html_tag'), 'label' => 'Page summary', 'class' => 'page_summary'];
				$this->showSections['show_summary'] = true;
			}
		}	
		if ($this->setting('show_keywords')){
			if (!empty($keywords = htmlspecialchars(ze::$pageKeywords))) {
				$this->mergeFields['Keywords'] = ['value' => $keywords, 'html_tag' => $this->setting('keywords_html_tag'), 'label' => 'Keywords', 'class' => 'keywords'];
				$this->showSections['show_keywords'] = true;
			}
		}	
		if ($this->setting('show_language')){
			if ($this->mergeFields['Language'] = ['value' => htmlspecialchars(ze::$langId), 'html_tag' => $this->setting('language_html_tag'), 'label' => 'Language code', 'class' => 'language_code']){
				$this->showSections['show_language'] = true;
			}
		}	
		
		if ($this->setting('show_language_name')) {
			$this->mergeFields['Language_name'] = ['value' => ze\lang::localName(), 'html_tag' => $this->setting('language_name_html_tag'), 'label' => 'Language', 'class' => 'language'];
			$this->showSections['show_language_name'] = true;
		}
		
		if ($this->setting('show_categories') && is_array($itemCats = ze\category::contentItemCategories($this->cID, $this->cType, true))) {
			$this->showSections['show_categories'] = true;
			$this->showSections['categories'] = [];
			
			$c = -1;
			foreach($itemCats as $cat) {
				++$c;
				$section = ['Category' => htmlspecialchars($cat['public_name'])];
				
				$this->showSections['categories'][] = $section;
			}
			
			$comma = $this->phrase('_COMMA');
			foreach ($this->showSections['categories'] as $i => &$section) {
				if ($i != $c) {
					$section['Comma'] = $comma;
				}
			}
			$this->mergeFields['Categories'] = ['html_tag' => $this->setting('categories_html_tag'), 'label' => 'Categories', 'class' => 'categories'];
		}
		
		$this->mergeFields['content'] = [];
		
		foreach (explode(',', $this->setting('reorder_fields')) as $field) {

			$fieldName = str_replace('show_', '', $field);	
			
			if(isset($this->mergeFields[ucwords($fieldName)])) {				
				$this->mergeFields['content'][$field] = $this->mergeFields[ucwords($fieldName)];
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':

				$fields['first_tab/date_format']['hidden'] = !$values['first_tab/show_date'];
				
				$hidden = !$values['first_tab/show_writer_image'];
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden);
				
				$hidden = !$values['first_tab/show_sticky_image'];
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden, 'sticky_image_');
				
				//Control visibility of "Writer image label html tag" setting & "Feature image label html tag" setting
				if ($values['show_writer_image'] == 1 && $values['show_labels'] == 1) {
					$fields['first_tab/writer_image_label_html_tag']['hidden'] = false;
				} else {
					$fields['first_tab/writer_image_label_html_tag']['hidden'] = true;
				}
				
				if ($values['show_sticky_image'] == 1 && $values['show_labels'] == 1) {
					$fields['first_tab/sticky_image_label_html_tag']['hidden'] = false;
				} else {
					$fields['first_tab/sticky_image_label_html_tag']['hidden'] = true;
				}
				
				//All available fields in Details tab
				$availableFields = ['show_date',
									'show_title',
									'show_description',
									'show_summary',
									'show_categories',
									'show_keywords',
									'show_language_name',
									'show_language',
									'show_writer_name',
									'show_writer_image',
									'show_sticky_image'
									];
													
				$fieldsWithNiceNames = [];
				
				//Give these fields nice names
				foreach($availableFields as $field) {
					if ($values['first_tab/' . $field] == "1") {
						$niceName = ucwords(str_replace('_', ' ', str_replace('show_', '', $field)));
						
						if($niceName == 'Sticky Image') {
							$niceName = 'Feature Image';
						}
						
						if($niceName == 'Categories') {
							$niceName = 'Public Categories';
						}
						
						$fieldsWithNiceNames[$field] = $niceName;
					}
				}
				
				//Check if this is the first time the admin box has been run...
				if(isset($fields['order_tab/reorder_fields']['current_value'])) {
					//... if not (e.g. switching a tab), use current order instead of getting it from the database...
					$metaData = explode(',', $fields['order_tab/reorder_fields']['current_value']);
				} elseif(!empty($fields['order_tab/reorder_fields']['value'])) {
					//... if yes (opening the admin box), get the order from the database...
					$metaData = explode(',', $fields['order_tab/reorder_fields']['value']);
				} else {
					//... or if the plugin has never been used before, use the default order.
					$metaData = $availableFields;
				}
				
				$fieldsInOrder = [];
				
				//Only process fields selected on Details page
				foreach($metaData as $field) {
					if($values[$field] == 1) {
						$fieldsInOrder[$field] = $fieldsWithNiceNames[$field];
					}
				}
				
				//If a previously unselected field has been selected now, add it
				foreach($fieldsWithNiceNames as $field => $value) {
					if(!isset($metaData[$field]) && $values[$field] == 1) {
						$fieldsInOrder[$field] = $value;
					}
				}
				
				$fields['order_tab/reorder_fields']['values'] = [];
				$fields['order_tab/reorder_fields']['values'] = $fieldsInOrder;
				
				$fields['order_tab/reorder_fields']['current_value'] 
					= $fields['order_tab/reorder_fields']['value'] = implode(",", array_keys($fields['order_tab/reorder_fields']['values']));
				break;
		}
	}	
}