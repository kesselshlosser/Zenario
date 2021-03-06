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

class zenario_slideshow_2 extends ze\moduleBaseClass {
	
	public $slideData = [];
	public $placeholderCSS = '';
	public $allowCaching = true;
	
	public function init() {
		
		if ($userId = ze\user::id()) {
			$userDetails = ze\user::details($userId);
		}
		$this->slideData['adminId'] = ze\admin::id();
		if ($this->slideData["slides"] = $this->getSlideDetails('main')) {
			$mobileImages = false;
			$missingMobileImage = false;
			$mobileImageDetails = [];
			$maxWidth = 0;
			$maxHeight = 0;
			$maxMobileWidth = 0;
			$maxMobileHeight = 0;
			$placeholderSlide = false;
			foreach ($this->slideData["slides"] as $index => &$slide) {
				if (!is_array($slide)) {
					continue;
				}
				
				// Hide hidden slides if no admin is logged in
				if (!ze\admin::id()) {
					if ($slide['hidden']) {
						unset($this->slideData["slides"][$index]);
						continue;
					}
					if ($slide['slide_visibility'] == 'call_static_method') {
						if (!(ze\module::inc($slide['plugin_class'])
							&& (method_exists($slide['plugin_class'], $slide['method_name']))
							&& (call_user_func([$slide['plugin_class'], $slide['method_name']],$slide['param_1'], $slide['param_2']))))
						{
							unset($this->slideData["slides"][$index]);
						}
						
					} elseif ($userId) {
						switch($slide['slide_visibility']) {
							case 'logged_in_with_field':
							case 'logged_in_without_field':
								$fieldValue = ze\dataset::fieldValue('users', $slide['field_id'], $userId);
								$fieldMatches = (bool)$fieldValue;
								if ($fieldValue != 1) {
									$fieldMatches = false;
								}
								if ($slide['slide_visibility'] != 'logged_in_with_field') {
									$fieldMatches = !$fieldMatches;
								}
								if (!$fieldMatches) {
									unset($this->slideData["slides"][$index]);
								}
								break;
							case 'logged_out':
								unset($this->slideData["slides"][$index]);
								break;
						}
					} else {
						switch($slide['slide_visibility']) {
							case 'logged_in_with_field':
							case 'logged_in_without_field':
							case 'logged_id':
								unset($this->slideData["slides"][$index]);
								break;
						}
					}
					if ($slide['slide_visibility'] != 'everyone') {
						$this->allowCaching = false;
					}
				}
				// Generate slide link if internal
				if (($slide['target_loc'] == 'internal') && $slide['dest_url']) {
					$cID = $cType = false;
					ze\content::getCIDAndCTypeFromTagId($cID, $cType, $slide['dest_url']);
					if ($slide['link_to_translation_chain']) {
						ze\content::langEquivalentItem($cID, $cType);
					}
					$slide['dest_url'] = ze\link::toItem($cID, $cType);
				}
				
				// Get slides max mobile width and height
				if ($this->setting('mobile_options') == 'seperate_fixed' || $this->setting('mobile_options') == 'desktop_fixed') {
					if ($slide['mobile_image_src']) {
						$mobileImages = true;
						$maxMobileWidth = ($slide['m_width'] > $maxMobileWidth) ? $slide['m_width'] : $maxMobileWidth;
						$maxMobileHeight = ($slide['m_height'] > $maxMobileHeight) ? $slide['m_height'] : $maxMobileHeight;
					} else {
						$missingMobileImage = true;
					}
				}
				
				// Get slides max desktop width and height
				if (($height = max([$slide['height'], $slide['r_height']])) > $maxHeight) {
					$maxHeight = $height;
				}
				if (($width = max([$slide['width'], $slide['r_width']])) > $maxWidth) {
					$maxWidth = $width;
				}
				
				// Add a placeholder image to appear while js loads, or in case of no js
				if (!$placeholderSlide) {
					$this->slideData['placeholder'] = [
						'image_src' => $slide['image_src'],
						'mobile_image_src' => $slide['mobile_image_src']];
					$this->placeholderCSS = "
						#".$this->containerId."_placeholder_image {
						 display:block;
						 height: ".$slide['height']."px;
						 width: ".$slide['width']."px;
						 background-image: url(".$slide['image_src'].");
						 background-repeat: no-repeat;
						}
						@media (max-width: ". (int) ze::$minWidth. "px) {
						 #".$this->containerId."_placeholder_image {
						  display:block;
						  height: ".$slide['m_height']."px;
						  width: ".$slide['m_width']."px;
						  background-image: url(".$slide['mobile_image_src'].");
						  background-repeat: no-repeat;
						 }
						}
					";
					$placeholderSlide = true;
				}
				
				// Translate phrases
				if ($slide['overwrite_alt_tag']) {
					$slide['overwrite_alt_tag'] = $this->phrase($slide['overwrite_alt_tag']);
				}
				if ($slide['tab_name']) {
					$slide['tab_name'] = $this->phrase($slide['tab_name']);
				}
				if ($slide['slide_title']) {
					$slide['slide_title'] = $this->phrase($slide['slide_title']);
				}
				if ($slide['slide_extra_html']) {
					$slide['slide_extra_html'] = $this->phrase($slide['slide_extra_html']);
				}
				if ($slide['slide_more_link_text']) {
					$slide['slide_more_link_text'] = $this->phrase($slide['slide_more_link_text']);
				}
				if ($slide['rollover_overwrite_alt_tag']) {
					$slide['rollover_overwrite_alt_tag'] = $this->phrase($slide['rollover_overwrite_alt_tag']);
				}
				if ($slide['mobile_overwrite_alt_tag']) {
					$slide['mobile_overwrite_alt_tag'] = $this->phrase($slide['mobile_overwrite_alt_tag']);
				}
				if ($slide['mobile_tab_name']) {
					$slide['mobile_tab_name'] = $this->phrase($slide['mobile_tab_name']);
				}
				if ($slide['mobile_slide_title']) {
					$slide['mobile_slide_title'] = $this->phrase($slide['mobile_slide_title']);
				}
				if ($slide['mobile_slide_extra_html']) {
					$slide['mobile_slide_extra_html'] = $this->phrase($slide['mobile_slide_extra_html']);
				}
			}
			// If a slide has a mobile image, but some do not, display a smaller version of the original image instead
			if ($mobileImages && $missingMobileImage && ($this->setting('mobile_options') == 'seperate_fixed')) {
				foreach ($this->slideData["slides"] as $index => &$slide) {
					if (!$slide['mobile_image_id']) {
						$width = $height = $url = false;
						ze\file::imageLink($width, $height, $url, $slide['image_id'], $maxMobileWidth, $maxMobileHeight);
						$slide['mobile_image_src'] = $url;
					}
				}
			}
			
			// Add a heading
			$this->slideData['heading'] = $this->phrase($this->setting('heading'));
			
			$settings = [
				'desktop_height' => $maxHeight,
				'desktop_width' => $maxWidth,
				'mobile_height' => $maxMobileHeight,
				'mobile_width' => $maxMobileWidth,
				'slide_transition' => $this->setting("fx"),
				'mobile_resize_width' => (int) ze::$minWidth,
				'hover_to_pause' => (int)$this->setting("hover_to_pause"),
				'enable_swipe' => (int)$this->setting("enable_swipe"),
				'auto_play' => (int)$this->setting("auto_play"),
				'slide_duration' => $this->setting("slide_duration"),
				'enable_arrow_buttons' => (int)$this->setting("arrow_buttons"),
				'navigation_style' => $this->setting("navigation_style"),
				'mobile_options' => $this->setting('mobile_options'),
				'desktop_resize_greater_than_image' => (int)$this->setting('desktop_resize_greater_than_image'),
				'has_mobile_images' => $mobileImages];
			
			$this->callScript(
				"zenario_slideshow_2", 
				"initiateSlideshow",
				$this->slideData["slides"],
				$this->pluginAJAXLink(),
				$this->slotName,
				$this->instanceId,
				$settings);
		}
		
		$this->allowCaching(
			$atAll = $this->allowCaching, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = true);
		
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->slideData);
	}
	
	public function addToPageHead() {
		echo "\n<style type=\"text/css\">\n", $this->placeholderCSS, "\n</style>\n";
	}
	
	public function pluginAJAX() {
		
		ze\priv::exitIfNot('_PRIV_MANAGE_REUSABLE_PLUGIN');
		
		switch($_REQUEST['mode'] ?? false) {
			case 'get_details':
				
				$recommededSize = false;
				$recommededSizeMessage = '';
				if ($this->setting('banner_width') && $this->setting('banner_height')) {
					$recommededSize = true;
					$recommededSizeMessage = ze\admin::phrase('Recommended dimensions: [[width]] x [[height]]', 
						[
							'width' => $this->setting('banner_width'), 
							'height' => $this->setting('banner_height')
						]
					);
				}
				
				$details = [
					'tabs' => ($this->setting('navigation_style') == 'thumbnail_navigator'),
					'mobile_option' => $this->setting('mobile_options'),
					'slides' => $this->getSlideDetails('admin'),
					'dataset_fields' => ze\datasetAdm::listCustomFields('users', false, 'boolean_and_groups_only', false),
					'recommededSize' => true,
					'recommededSizeMessage' => $recommededSizeMessage
				];
				header('Content-Type: text/javascript; charset=UTF-8');
				echo json_encode($details);
				break;
				
			case "file_upload":
				ze\fileAdm::exitIfUploadError();
				ze\fileAdm::putUploadFileIntoCacheDir($_FILES['Filedata']['name'], $_FILES['Filedata']['tmp_name'], ($_REQUEST['_html5_backwards_compatibility_hack'] ?? false));
				exit;
				
			case "add_slides_from_organizer": 
				$keys = explode(',', ($_GET["ids"] ?? false));
				$data = [];
				foreach($keys as $key) {
					$data[] = $this->getNewImageDetails($key);
				}
				header('Content-Type: text/javascript; charset=UTF-8');
				echo json_encode($data);
				break;
				
			case "change_image_from_organizer": 
				header('Content-Type: text/javascript; charset=UTF-8');
				echo json_encode($this->getNewImageDetails($_GET["new_image_id"] ?? false));
				break;
				
			case "save_slides":
				
				$slides = json_decode($_POST["slides"] ?? false, true);
				
				
				$ordinals = explode(',', ($_POST["ordinals"] ?? false));
				$errors = [];
				// Check for errors
				foreach ($slides as $key => $value) {
					// Validation for slides
					$index = array_search($key, $ordinals);
					switch ($value['slide_visibility']) {
						case 'call_static_method':
							if (!$value['plugin_class']) {
								$errors[$index][] = ze\admin::phrase('Please enter the Class Name of a Plugin.');
							} elseif (!ze\module::inc($value['plugin_class'])) {
								$errors[$index][] = ze\admin::phrase('Please enter the Class Name of a Plugin that you have running on this site.');
							} elseif ($value['method_name'] 
								&& !method_exists(
									$value['plugin_class'],
									$value['method_name'])
							) {
								$errors[$index][] = ze\admin::phrase('Please enter the name of an existing Static Method.');
							}
							if (!$value['method_name']) {
								$errors[$index][] = ze\admin::phrase('Please enter the name of a Static Method.');
							}
							break;
						case 'logged_in_with_field':
						case 'logged_in_without_field':
							if (!$value['field_id']) {
								$errors[$index][] = ze\admin::phrase('Please choose a dataset field.');
							}
							break;
					}
					switch($value['target_loc']) {
						case 'internal':
							if (empty($value['content_item_tag_id'])) {
								$errors[$index][] = ze\admin::phrase('Please select a content item.');
							}
							break;
						case 'external':
							if (empty($value['external_link'])) {
								$errors[$index][] = ze\admin::phrase('Please enter a URL.');
							}
							break;
					}
				}
				
				// Save a new slide if one doesn't exist otherwise update the existing one.
				if (empty($errors)) {
					
					// Delete currently saved slides if not in save data.
					$ids = ze\row::getAssocs(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', ['image_id'], ['instance_id' => $this->instanceId]);
					foreach ($ids as $key => $slideDetails) {
						if (array_search($key, $ordinals) === false) {
							ze\row::delete(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', ["id" => $key]);
							if (!ze\row::exists(ZENARIO_SLIDESHOW_2_PREFIX . 'slides', ['instance_id' => $this->instanceId, 'image_id' => $slideDetails['image_id']])) {
								ze\row::delete('inline_images', 
									[
										'image_id' => $slideDetails['image_id'],
										'foreign_key_to' => 'library_plugin', 
										'foreign_key_id' => $this->instanceId
									]
								);
							}
						}
					}
					
					foreach ($slides as $key => $value) {
						if (!in_array($value['slide_visibility'], ['logged_in_with_field', 'logged_in_without_field'])) {
							$value['field_id'] = 0;
						}
						
						$value['dest_url'] = '';
						switch ($value["target_loc"]) {
							case 'internal':
								$value['dest_url'] = $value['content_item_tag_id'];
								break;
							case 'external':
								$value['dest_url'] = $value['external_link'];
								break;
							case 'none':
								$value["slide_more_link_text"] = '';
								break;
						}
						
						if (ze\row::exists(ZENARIO_SLIDESHOW_2_PREFIX. "slides", ["id" => $key])) {
							ze\row::update(ZENARIO_SLIDESHOW_2_PREFIX . "slides",
								[
									"ordinal" => array_search($key, $ordinals),
									"mobile_slide_extra_html" => $value["mobile_slide_extra_html"],
									"mobile_slide_title" => $value["mobile_slide_title"],
									"mobile_tab_name" => $value["mobile_tab_name"],
									"mobile_overwrite_alt_tag" => $value["mobile_overwrite_alt_tag"],
									"rollover_overwrite_alt_tag" => $value["rollover_overwrite_alt_tag"],
									"overwrite_alt_tag" => $value["overwrite_alt_tag"],
									"tab_name" => $value["tab_name"],
									"slide_title" => $value["slide_title"],
									"slide_extra_html" => $value["slide_extra_html"],
									"slide_more_link_text" => $value["slide_more_link_text"],
									"open_in_new_window" => $value["open_in_new_window"],
									"target_loc" => $value["target_loc"],
									"dest_url" => $value["dest_url"],
									"slide_visibility" => $value["slide_visibility"],
									"plugin_class" => $value["plugin_class"],
									"method_name" => $value["method_name"],
									"param_1" => $value["param_1"],
									"param_2" => $value["param_2"],
									'field_id' => (int)$value['field_id'],
									"link_to_translation_chain" => $value["link_to_translation_chain"],
									"transition_code" => isset($value["transition_code"]) ? $value["transition_code"] : '',
									"use_transition_code" => $value["use_transition_code"],
									"hidden" => $value["hidden"]],
								["id" => $key]);
								
							$this->saveImageForSlide($key, $value, "image_id");
							$this->saveImageForSlide($key, $value, "rollover_image_id");
							$this->saveImageForSlide($key, $value, "mobile_image_id");
								
						} else {
							$newKey = ze\row::insert(ZENARIO_SLIDESHOW_2_PREFIX. "slides",
								[
									"image_id" => 0,
									"ordinal" => array_search($key, $ordinals),
									"instance_id" => $this->instanceId,
									"mobile_slide_extra_html" => $value["mobile_slide_extra_html"],
									"mobile_slide_title" => $value["mobile_slide_title"],
									"mobile_tab_name" => $value["mobile_tab_name"],
									"mobile_overwrite_alt_tag" => $value["mobile_overwrite_alt_tag"],
									"rollover_overwrite_alt_tag" => $value["rollover_overwrite_alt_tag"],
									"overwrite_alt_tag" => $value["overwrite_alt_tag"],
									"tab_name" => $value["tab_name"],
									"slide_title" => $value["slide_title"],
									"slide_extra_html" => $value["slide_extra_html"],
									"slide_more_link_text" => $value["slide_more_link_text"],
									"open_in_new_window" => $value["open_in_new_window"],
									"target_loc" => $value["target_loc"],
									"dest_url" => $value["dest_url"],
									"slide_visibility" => $value["slide_visibility"],
									"plugin_class" => $value["plugin_class"],
									"method_name" => $value["method_name"],
									"param_1" => $value["param_1"],
									"param_2" => $value["param_2"],
									'field_id' => (int)$value['field_id'],
									"link_to_translation_chain" => $value["link_to_translation_chain"],
									"transition_code" => $value["transition_code"],
									"use_transition_code" => $value["use_transition_code"],
									"hidden" => $value["hidden"]]);
							
							$this->saveImageForSlide($newKey, $value, "image_id");
							$this->saveImageForSlide($newKey, $value, "rollover_image_id");
							$this->saveImageForSlide($newKey, $value, "mobile_image_id");
						}
					}
				}
				header('Content-Type: text/javascript; charset=UTF-8');
				//echo '<pre>';var_dump($errors);exit;
				echo json_encode($errors);
				break;
				
		}
	}
	
	public function saveImageForSlide($key, $value, $image_id) {
		
		if (isset($value[$image_id]) && $value[$image_id] !== NULL) {
			
			switch($image_id) {
				case "rollover_image_id":
					$prefix = "r_";
					break;
				case "mobile_image_id":
					$prefix = "m_";
					break;
				default:
					$prefix = "";
					break;
			}
			
			if ($value[$image_id] == 0) {
				// new uploaded image
				$path = ze\file::getPathOfUploadInCacheDir($value[$prefix. "cache_id"]);
				if ($id = ze\file::addToDatabase("image", $path)) {
					ze\row::update(ZENARIO_SLIDESHOW_2_PREFIX. "slides", 
						[$image_id => $id],
						["id" => $key]);
				}
			} else {
				if ($id = ze\file::copyInDatabase("image", $value[$image_id], $value[$prefix. "filename"])) {
					// new image from organizer
					ze\row::update(ZENARIO_SLIDESHOW_2_PREFIX. "slides",
						[$image_id => $id],
						["id" => $key]);
				}
			}
			
			ze\row::set('inline_images', 
				['in_use' => 1], 
				[
					'image_id' => $id, 
					'foreign_key_to' => 'library_plugin', 
					'foreign_key_id' => $this->instanceId
				]
			);
			
		} else {
			ze\row::update(ZENARIO_SLIDESHOW_2_PREFIX. "slides", 
				[$image_id => NULL],
				["id" => $key]);
		}
	}
	
	public function getSlideDetails($mode) {
		$adminMode = ($mode == 'admin');
		$mainMode = ($mode == 'main');
		$mobileMode = ($mode == 'mobile');
		
		$data = [];
		$sql = "
			SELECT
				s.mobile_overwrite_alt_tag, 
				s.mobile_tab_name, 
				s.mobile_slide_title, 
				s.mobile_slide_extra_html,
				
				s.image_id, 
				s.overwrite_alt_tag, 
				s.rollover_overwrite_alt_tag, 
				s.tab_name, 
				s.slide_title,
				s.slide_extra_html, 
				s.slide_more_link_text,
				f.alt_tag,
				f.filename, 
				f.width, 
				f.height,
				s.transition_code,
				s.use_transition_code,
				
				s.id, 
				s.ordinal,
				s.target_loc, 
				s.open_in_new_window,
				s.dest_url,
				s.slide_visibility, 
				s.link_to_translation_chain,
				s.plugin_class, 
				s.method_name, 
				s.param_1, 
				s.param_2,
				s.field_id,
				s.hidden
			FROM  ". DB_PREFIX. ZENARIO_SLIDESHOW_2_PREFIX. "slides AS s
			INNER JOIN ". DB_PREFIX. "files AS f
				ON s.image_id = f.id
			WHERE s.instance_id = ". (int) $this->instanceId. "
			ORDER BY s.ordinal";
		$result = ze\sql::select($sql);
		while ($row1 = ze\sql::fetchAssoc($result)) {
			$row2 = [];
			$row3 = [];
			
			$url = "";
			$row1['true_height'] = $row1["height"];
			$row1['true_width'] = $row1['width'];
			ze\file::imageLink($row1["width"], $row1["height"], $url, $row1["image_id"], $this->setting("banner_width"), $this->setting("banner_height"), $this->setting('banner_canvas'));
			$row1['image_src'] = $url;
			
			// Get rollover image details
			$sql2 = "
				SELECT 
					count(s.rollover_image_id), 
					s.rollover_image_id, 
					f.height AS r_height, 
					f.width AS r_width, 
					f.filename AS r_filename,
					f.alt_tag AS r_alt_tag 
				FROM ". DB_PREFIX. ZENARIO_SLIDESHOW_2_PREFIX. "slides AS s
				INNER JOIN ". DB_PREFIX. "files AS f
					ON s.rollover_image_id = f.id
				WHERE s.instance_id = ". (int)$this->instanceId. "
					AND s.id = ". (int) $row1['id'];
			$result2 = ze\sql::select($sql2);
			$row2 = ze\sql::fetchAssoc($result2);
			
			$url2 = "";
			$row2['true_r_height'] = $row2['r_height'];
			$row2['true_r_width'] = $row2['r_width'];
			ze\file::imageLink($row2["r_width"], $row2["r_height"], $url2, $row2["rollover_image_id"], $this->setting("banner_width"), $this->setting("banner_height"), $this->setting('banner_canvas'));
			$row2['rollover_image_src'] = $url2;

			// Get mobile image details
			$sql3 = "
				SELECT 
					count(s.mobile_image_id), 
					s.mobile_image_id, 
					f.height AS m_height, 
					f.width AS m_width, 
					f.filename AS m_filename,
					f.alt_tag AS m_alt_tag 
				FROM ". DB_PREFIX. ZENARIO_SLIDESHOW_2_PREFIX. "slides AS s
				INNER JOIN ". DB_PREFIX. "files AS f
					ON s.mobile_image_id = f.id
				WHERE s.instance_id = ". (int) $this->instanceId. "
					AND s.id = ". (int) $row1['id'];
			$result3 = ze\sql::select($sql3);
			$row3 = ze\sql::fetchAssoc($result3);
			$url3 = "";
			$row3['true_m_width'] = $row3['m_width'];
			$row3['true_m_height'] = $row3['m_height'];
			
			if ($this->setting('mobile_options') == 'seperate_fixed') {
				ze\file::imageLink($row3["m_width"], $row3["m_height"], $url3, $row3["mobile_image_id"], $this->setting('mobile_width'), $this->setting('mobile_height'), $this->setting('mobile_canvas'));
			} elseif ($this->setting('mobile_options') == 'desktop_fixed') {
				ze\file::imageLink($row3['m_width'], $row3['m_height'], $url3, $row1["image_id"], $this->setting('mobile_width'), $this->setting('mobile_height'), $this->setting('mobile_canvas'));
			}
			
			$row3['mobile_image_src'] = $url3;
			if ($adminMode) {
				// Get main image thumbnails
				$url = "";
				$width = $row1["width"];
				$height = $row1["height"];
				
				ze\file::imageLink($width, $height, $url, $row1["image_id"], 300, 150);
				$row1['image_src_thumbnail_1'] = $url;
				
				$url = "";
				$width = $row1["width"];
				$height = $row1["height"];
				
				ze\file::imageLink($width, $height, $url, $row1["image_id"], 150, 150);
				$row1['image_src_thumbnail_2'] = $url;
				
				// Get rollover image thumbnails
				$url2 = "";
				$width = $row2["r_width"];
				$height = $row2["r_height"];
				
				ze\file::imageLink($width, $height, $url2, $row2["rollover_image_id"], 300, 150);
				$row2['rollover_image_src_thumbnail_1'] = $url2;
				
				// Get mobile image thumbnails
				$url3 = "";
				$width = $row3["m_width"];
				$height = $row3["m_height"];
				ze\file::imageLink($width, $height, $url3, $row3["mobile_image_id"], 300, 150);
				$row3['mobile_image_src_thumbnail_1'] = $url3;
				
				
				$row1['content_item_link'] = $row1['content_item_tag_id'] = $row1['external_link'] = '';
				if ($row1['target_loc'] == 'internal' && $row1['dest_url']) {
					$row1['content_item_link'] = ze\content::formatTagFromTagId($row1['dest_url']);
					$row1['content_item_tag_id'] = $row1['dest_url'];
				} elseif ($row1['target_loc'] == 'external' && $row1['dest_url']) {
					$row1['external_link'] = $row1['dest_url'];
				}
				
			}
			$row = array_merge($row1, $row2, $row3);
			//var_dump($row['m_width']);
			$id = $row['id'];
			if ($mainMode || $mobileMode) {
				$id = $row['ordinal'];
			}
			$data[$id] = $row;
			
			
		}
		return $data;
	}
	
	public function getNewImageDetails($id) {
		$sql = "
			SELECT id AS image_id, filename, alt_tag,  width, width AS true_width, height, height AS true_height
			FROM ". DB_PREFIX. "files
			WHERE id = ". (int) $id;
		$result = ze\sql::select($sql);
		$row = ze\sql::fetchAssoc($result);
		$url = "";
		$width = $row["width"];
		$height = $row["height"];
		//ze\file::imageLink($width, $height, $url, $row["image_id"], $row["width"], $row["height"]);
		//$row["image_src"] = $url;
		
		ze\file::imageLink($width, $height, $url, $row["image_id"], 300, 150);
		$row["image_src_thumbnail_1"] = $url;
		
		ze\file::imageLink($width, $height, $url, $row["image_id"], 150, 150);
		$row["image_src_thumbnail_2"] = $url;
		return $row;
	}
	
	public function fillAdminSlotControls(&$controls) {
		if (ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN') && isset($controls['actions']['settings'])) {
			$controls['actions']['settings']['label'] = ze\admin::phrase('Slideshow properties');
			$controls['actions']['slideshow_settings'] = [
				'ord' => 1.1,
				'label' => ze\admin::phrase('Choose slideshow images'),
				'page_modes' => $controls['actions']['settings']['page_modes'],
				'onclick' => 'zenario_slideshow_2.openImageManager(
					this, 
					slotName, 
					\''. ze\escape::js($this->pluginAJAXLink()). '\'
				);'
			];
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch($path){
			case 'plugin_settings':
				
				$this->showHideImageOptions($fields, $values, 'first_tab', false, 'banner_');
				
				$hidden = !ze::in($values['mobile/mobile_options'], 'desktop_fixed', 'seperate_fixed');
				$this->showHideImageOptions($fields, $values, 'mobile', $hidden, 'mobile_');
				
				$fields['mobile/desktop_resize_greater_than_image']['hidden'] = 
					$values['mobile/mobile_options'] != 'desktop_resize';
				
				break;
		}
	}
	
	public static function eventPluginInstanceDuplicated($oldInstanceId, $newInstanceId) {
		$oldSlideshowSettings = ze\row::getAssocs(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', true, ['instance_id' => $oldInstanceId]);
		foreach ($oldSlideshowSettings as $slideId => $slideDetails) {
			unset($slideDetails['id']);
			$slideDetails['instance_id'] = $newInstanceId;
			ze\row::insert(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', $slideDetails);
		}
	}
	
	public static function eventPluginInstanceDeleted($instanceId) {
		// Delete slides
		ze\row::delete(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', ['instance_id' => $instanceId]);
	}
	
	public static function removeContentItemFromSlideLinks($cID, $cType) {
		$tagID = ze\content::formatTag($cID, $cType, false);
		$result = ze\row::query(
			ZENARIO_SLIDESHOW_2_PREFIX . 'slides', 
			['id', 'target_loc', 'dest_url'], 
			['target_loc' => 'internal', 'dest_url' => $tagID]
		);
		while ($row = ze\sql::fetchAssoc($result)) {
			ze\row::update(
				ZENARIO_SLIDESHOW_2_PREFIX . 'slides', 
				[
					'target_loc' => 'none', 
					'dest_url' => null, 
					'link_to_translation_chain' => 0,
					'open_in_new_window' => 0
				], 
				['id' => $row['id']]
			);
		}
	}
	
	public static function eventContentTrashed($cID, $cType) {
		// Remove any internal links from slides to this content item
		self::removeContentItemFromSlideLinks($cID, $cType);
	}
	
	public static function eventContentDeleted($cID, $cType, $cVersion) {
		// Remove any internal links from slides to this content item if the content item has been fully deleted
		$contentItem = ze\row::get('content_items', ['status'], ['id' => $cID, 'type' => $cType]);
		if ($contentItem === false || $contentItem['status'] === 'deleted') {
			self::removeContentItemFromSlideLinks($cID, $cType);
		}
	}
	
}