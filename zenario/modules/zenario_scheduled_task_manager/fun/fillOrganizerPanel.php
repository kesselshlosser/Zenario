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


switch ($path) {
	case 'zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks':
		
		if (ze::setting('jobs_enabled')) {
			unset($panel['collection_buttons']['master_switch_off']);
		} else {
			unset($panel['collection_buttons']['master_switch_on']);
		}
		
		$panel['collection_buttons']['copy_code']['onclick'] =
			//Attempt to copy the cannonical URL to the clipboard when the visitor presses this button
			'zenarioA.copy("'. ze\escape::js('* * * * *  php '. CMS_ROOT. ze::moduleDir('zenario_scheduled_task_manager', 'cron/run_every_minute.php'). ' 1'). '");'.
			//Small little hack here:
				//After the URL is copy/pasted, the dropdown stays open which is counter-intuative.
				//However the dropdown is powered by pure CSS and there's no way to close it using JavaScript.
				//So as a workaround, redraw the admin toolbar with the dropdown closed.
			'zenarioO.setButtons();';
		
		foreach ($panel['items'] as &$item) {
			
			$item['traits'] = [];
			
			if (ze::setting('jobs_enabled')) {
				if ($item['enabled']) {
					if ($item['status'] != 'rerun_scheduled') {
						$item['traits']['can_rerun'] = true;
					}
					
					$item['traits']['can_suspend'] = true;
				} else {
					$item['traits']['can_enable'] = true;
				}
			}
			
			$item['module'] = ze\module::getModuleDisplayNameByClassName($item['module']);
			$item['first_n_days_of_month'] = ze\admin::phrase(ze\ray::value(zenario_scheduled_task_manager::$firstNOptions, $item['first_n_days_of_month']));
			$item['status'] = ze\admin::phrase(ze\ray::value(zenario_scheduled_task_manager::$lastRunStatuses, $item['status']));
			
			if ($item['months'] == 'jan,feb,mar,apr,may,jun,jul,aug,sep,oct,nov,dec') {
				$item['months'] = ze\admin::phrase('No Filter');
			} else {
				$item['months'] = str_replace(',', ', ', $item['months']);
			}
		}
		
		break;
		
	case 'zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks/hidden_nav/log/panel':
		
		$panel['title'] = ze\admin::phrase('Logs for the task "[[job]]"', ['job' => ze\row::get('jobs', 'job_name', $refinerId)]);
		
		foreach ($panel['items'] as &$item) {
			$item['summary'] = nl2br(str_replace("\n\n", "\n", htmlspecialchars($item['summary'])));
			$item['status'] = ze\admin::phrase(ze\ray::value(zenario_scheduled_task_manager::$lastRunStatuses, $item['status']));
		}
		
		break;
}
