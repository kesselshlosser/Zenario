<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


//This file works like local.inc.php, but should contain any updates for user-related tables




//Increase the length of the ip column on the users table
	revision(26790
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `ip` varchar(255) NOT NULL default ''
_sql


);	revision(26820
//Automatically create any missing tabs
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]custom_dataset_tabs`
	(dataset_id, name, ord, label)
	SELECT dataset_id, tab_name, 101, REPLACE(label, ':', '')
	FROM `[[DB_NAME_PREFIX]]custom_dataset_fields`
	WHERE tab_name LIKE '__custom_tab_%'
	ORDER BY ord
_sql

//Add a parent_id column to the tabs table, so tabs can be hidden or shown
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_tabs`
	ADD COLUMN `parent_field_id` int(10) NOT NULL default 0
	AFTER `label`
_sql


);	revision(26850
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	ADD COLUMN `status` enum('active', 'disabled') NOT NULL default 'active'
	AFTER `label`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	ADD KEY (`status`)
_sql


);	revision(26910
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `password_needs_changing` tinyint(1) NOT NULL default 0
	AFTER `password`
_sql


//Drop the status column as it wasn't wanted
);	revision(26940
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	DROP COLUMN `status`
_sql


);	revision(26980
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `protected` tinyint(1) NOT NULL default 0
	AFTER `is_system_field`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD KEY (`protected`)
_sql


);	revision(26985
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	ADD COLUMN `view_priv` varchar(255) CHARACTER SET ascii NOT NULL default ''
	AFTER `extends_organizer_panel`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	ADD COLUMN `edit_priv` varchar(255) CHARACTER SET ascii NOT NULL default ''
	AFTER `view_priv`
_sql

); revision (27342

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `send_email_to_user` tinyint(1) NOT NULL default 0,
	ADD COLUMN `user_email_field` varchar(255) DEFAULT NULL,
	ADD COLUMN `user_email_template` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `send_email_to_admin` tinyint(1) NOT NULL default 0,
	ADD COLUMN `admin_email_addresses` text DEFAULT NULL,
	ADD COLUMN `admin_email_template` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `admin_reply_to` tinyint(1) NOT NULL default 0
_sql

); revision (27343

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `save_data` tinyint(1) NOT NULL default 0,
	ADD COLUMN `send_signal` tinyint(1) NOT NULL default 0,
	ADD COLUMN `signal_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `redirect_after_submission` tinyint(1) NOT NULL default 0,
	ADD COLUMN `redirect_location` varchar(255) DEFAULT NULL
_sql

); revision (27344

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE `user_email_template` `user_email_template` varchar(255) DEFAULT NULL,
	CHANGE `admin_email_template` `admin_email_template` varchar(255) DEFAULT NULL
_sql

); revision (27345

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `reply_to_email_field` varchar(255) DEFAULT NULL,
	ADD COLUMN `reply_to_first_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `reply_to_last_name` varchar(255) DEFAULT NULL,
	CHANGE `admin_reply_to` `reply_to` tinyint(1) NOT NULL default 0
_sql

); revision (27346

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `label` varchar(255) DEFAULT NULL,
	ADD COLUMN `size` enum('small', 'medium', 'large'),
	ADD COLUMN `default_value` varchar(255) DEFAULT NULL,
	ADD COLUMN `note_to_user` varchar(255) DEFAULT NULL,
	ADD COLUMN `css_classes` varchar(255) DEFAULT NULL,
	ADD COLUMN `required_error_message` varchar(255) DEFAULT NULL,
	ADD COLUMN `validation` enum('email', 'URL', 'integer', 'number', 'floating_point')
_sql

); revision (27347

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	CHANGE `size` `size` enum('small', 'medium', 'large') DEFAULT 'medium'
_sql

); revision (27348

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `validation_error_message` varchar(255) DEFAULT NULL
_sql

); revision (27349

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `save_record` tinyint(1) NOT NULL default 0 AFTER `save_data`
_sql

); revision (27350

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE `save_data` `save_data` tinyint(1) NOT NULL default 1
_sql

); revision (27351

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `create_new_user` tinyint(1) NOT NULL default 0,
	ADD COLUMN `add_user_to_group` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `user_status` enum('active', 'contact') DEFAULT NULL,
	ADD COLUMN `on_duplicate_send_email` tinyint(1) NOT NULL default 0,
	ADD COLUMN `on_duplicate_update_user` tinyint(1) NOT NULL default 0,
	ADD COLUMN `on_duplicate_update_response` tinyint(1) NOT NULL default 0
_sql

); revision (27352

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `show_success_messsage` tinyint(1) NOT NULL default 0,
	ADD COLUMN `success_message` varchar(255) DEFAULT NULL,
	DROP COLUMN `signal_name`
_sql

); revision (27353

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE `show_success_messsage` `show_success_message` tinyint(1) NOT NULL default 0
_sql

); revision (27354

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE `user_status` `user_status` enum('active', 'contact') DEFAULT 'contact'
_sql

); revision (27355

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE `admin_email_addresses` `admin_email_addresses` varchar(255) DEFAULT NULL
_sql

//Create a table to store the ids of users who have been synced to "spoke" sites
);	revision(27490
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_sync_log`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_sync_log` (
		`user_id` int(10) unsigned NOT NULL,
		`last_synced_timestamp` timestamp NOT NULL,
		PRIMARY KEY (`user_id`),
		KEY (`last_synced_timestamp`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision(27491

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN duplicate_action enum('merge', 'overwrite', 'ignore') DEFAULT 'merge'
_sql

); revision(27492

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE duplicate_action user_duplicate_email_action enum('merge', 'overwrite', 'ignore') DEFAULT 'merge'
_sql

); revision(27493

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN duplicate_user_email tinyint(1) NOT NULL default 0 AFTER success_message
_sql

); revision(27494

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	DROP `on_duplicate_send_email`,
	DROP `on_duplicate_update_user`,
	DROP `on_duplicate_update_response`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `create_another_form_submission_record` tinyint(1) NOT NULL default 0,
	ADD COLUMN `send_another_email` tinyint(1) NOT NULL default 0
_sql

); revision(28052

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms` 
	ADD COLUMN `admin_email_use_template` tinyint(1) NOT NULL default 0 AFTER send_email_to_admin
_sql

); revision(28711

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `title` varchar(255) DEFAULT ''
_sql

); revision(28712

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_forms`
	SET `title` = `name`
_sql

); revision(28713

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	DROP `duplicate_user_email`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	CHANGE `user_field_id` `user_field_id` int(10) unsigned DEFAULT 0,
	ADD COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','select','text','textarea','url') DEFAULT NULL
_sql
); revision(28714

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	DROP INDEX `user_form_id`,
	MODIFY COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','select','text','textarea','url') DEFAULT NULL AFTER `ordinal`
_sql

); revision(28715

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `captcha` tinyint(1) NOT NULL DEFAULT 0
_sql

); revision(28716

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE `captcha` `use_captcha` tinyint(1) NOT NULL DEFAULT 0,
	ADD COLUMN `captcha_type` enum('word', 'math') DEFAULT 'word',
	ADD COLUMN `extranet_users_use_captcha` tinyint(1) NOT NULL DEFAULT 0
_sql

//Add a password salt column to the users table
); revision(28800
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `password_salt` varchar(8) default NULL
	AFTER `password`
_sql

); revision(28801

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `reset_password_time` datetime default NULL
	AFTER `password_needs_changing`
_sql



//Up the length of the tab name column in the dataset tables as there are names longer than the current value!
); revision( 28890
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_tabs`
	MODIFY COLUMN `name` varchar(64) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	MODIFY COLUMN `tab_name` varchar(64) CHARACTER SET ascii NOT NULL
_sql


//Some sites were missing the global_id and last_updated_timestamp columns from the Users table, possibly due to an incomplete svn up, but we're not sure.
//This used to be revisions 27340/28651, but I've moved it down and marked it as a later revision to forcibly reapply it
);	revision(29040
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `global_id` int(10) unsigned NOT NULL default 0
	AFTER `id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD KEY (`global_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `last_updated_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
    ON UPDATE CURRENT_TIMESTAMP
	AFTER `suspended_date`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD KEY (`last_updated_timestamp`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `terms_and_conditions_accepted` tinyint(1) NOT NULL DEFAULT '0'
	AFTER `last_updated_timestamp`
_sql

); revision(29043

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	DROP COLUMN `create_new_user`
_sql

); revision(29044

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	MODIFY COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','select','text','textarea','url', 'attachment') DEFAULT NULL
_sql

); revision( 29191

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `screen_name_confirmed` tinyint(1) NOT NULL default 0
	AFTER `screen_name`
_sql

); revision( 29231

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms` 
	ADD COLUMN `log_user_in` tinyint(1) NOT NULL default 0 AFTER `user_status`
_sql

); revision( 29232

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields` 
	ADD COLUMN `placeholder` varchar(255) DEFAULT NULL AFTER `size`
_sql

); revision( 29234

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields` 
	ADD COLUMN `name` varchar(255) NOT NULL AFTER `is_required`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_form_fields`
	SET `name` = IF(`label` IS NOT NULL, `label`, '')
_sql

); revision( 29237

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms` 
	ADD COLUMN `status` enum('active','archived') DEFAULT 'active' AFTER `name`
_sql

); revision( 29240

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `required_field` int(10) unsigned DEFAULT 0 AFTER `is_required`,
	ADD COLUMN `required_value` varchar(255) DEFAULT NULL AFTER `required_field`
_sql

); revision( 29571

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `values_source_filter` varchar(255) NOT NULL DEFAULT '' AFTER `values_source`
_sql

); revision( 29589

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `identifier` varchar(50) NULL AFTER `session_id`,
	ADD KEY (`identifier`)
_sql

); revision( 30280

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `visibility` enum('visible', 'hidden', 'visible_on_condition') DEFAULT 'visible' AFTER `required_value`,
	ADD COLUMN `visible_condition_field_id` int(10) unsigned DEFAULT 0 AFTER `visibility`,
	ADD COLUMN `visible_condition_field_value` varchar(255) DEFAULT NULL AFTER `visible_condition_field_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	CHANGE `required_field` `mandatory_condition_field_id` int(10) unsigned DEFAULT 0,
	CHANGE `required_value` `mandatory_condition_field_value` varchar(255) DEFAULT NULL
_sql

); revision( 30660

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `default_value_class_name` varchar(255) DEFAULT NULL AFTER `default_value`,
	ADD COLUMN `default_value_method_name` varchar(255) DEFAULT NULL AFTER `default_value_class_name`,
	ADD COLUMN `default_value_param_1` varchar(255) DEFAULT NULL AFTER `default_value_method_name`,
	ADD COLUMN `default_value_param_2` varchar(255) DEFAULT NULL AFTER `default_value_param_1`
_sql

);	revision( 30680

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms` 
	ADD COLUMN translate_text tinyint(1) NOT NULL default 1
_sql

); revision( 30730

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	MODIFY COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','select','text','textarea','url','attachment','page_break') DEFAULT NULL,
	ADD COLUMN `next_button_text` varchar(255) DEFAULT NULL,
	ADD COLUMN `previous_button_text` varchar(255) DEFAULT NULL
_sql

); revision( 30740

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `div_wrap_class` varchar(255) DEFAULT NULL AFTER `css_classes`,
	MODIFY COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','select','text','textarea','url','attachment','page_break', 'section_description') DEFAULT NULL,
	ADD COLUMN `description` varchar(255) DEFAULT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `submit_button_text` varchar(255) DEFAULT NULL,
	ADD COLUMN `default_next_button_text` varchar(255) DEFAULT NULL,
	ADD COLUMN `default_previous_button_text` varchar(255) DEFAULT NULL
_sql

); revision( 30750

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	MODIFY COLUMN `submit_button_text` varchar(255) DEFAULT 'Submit',
	MODIFY COLUMN `default_next_button_text` varchar(255) DEFAULT 'Next',
	MODIFY COLUMN `default_previous_button_text` varchar(255) DEFAULT 'Back'
_sql

); revision( 30760

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	MODIFY COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','select','text','textarea','url','attachment','page_break', 'section_description', 'calculated', 'restatement') DEFAULT NULL,
	ADD COLUMN `numeric_field_1` int(10) unsigned DEFAULT 0,
	ADD COLUMN `numeric_field_2` int(10) unsigned DEFAULT 0,
	ADD COLUMN `calculation_type` enum('+', '-') DEFAULT NULL,
	ADD COLUMN `restatement_field` int(10) unsigned DEFAULT 0
_sql

); revision (30850

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `log_user_in_cookie` tinyint(1) NOT NULL default 0
_sql

); revision (31270

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	MODIFY COLUMN `user_duplicate_email_action` enum('merge', 'overwrite', 'ignore', 'stop') DEFAULT 'merge',
	ADD COLUMN `duplicate_email_address_error_message` varchar(255) DEFAULT NULL
_sql

); revision (31280

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	DROP COLUMN `send_another_email`
_sql

); revision( 31300

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_tabs`
	ADD COLUMN `default_label` varchar(32) NOT NULL DEFAULT '' AFTER `label`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `default_label` varchar(64) NOT NULL DEFAULT '' AFTER `label`
_sql

); revision( 31390

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_tabs`
	ADD COLUMN `is_system_field` tinyint(1) NOT NULL DEFAULT '0'
_sql

); revision( 31530

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `include_in_export` tinyint(1) NOT NULL DEFAULT '0'
_sql


//Create a table for storing smart group rules in the new format
);	revision( 31740
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]smart_group_rules`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]smart_group_rules` (
		`smart_group_id` int(10) unsigned NOT NULL,
		`ord` int(10) unsigned NOT NULL,
		`field_id` int(10) unsigned NOT NULL,
		`field2_id` int(10) unsigned NOT NULL default 0,
		`field3_id` int(10) unsigned NOT NULL default 0,
		`not` tinyint(1) NOT NULL default 0,
		`value` text,
		PRIMARY KEY (`smart_group_id`, `ord`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


); revision( 31760

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `create_index` tinyint(1) NOT NULL DEFAULT 0 AFTER `show_in_organizer`
_sql

); revision( 32463

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	MODIFY COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','attachment','page_break','section_description','calculated','restatement') DEFAULT NULL
_sql

); revision( 32466

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `values_source` varchar(255) NOT NULL DEFAULT '',
	ADD COLUMN `values_source_filter` varchar(255) NOT NULL DEFAULT ''
_sql

); revision( 32474

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `title_tag` enum('h1','h2','h3','h4','h5','h6','p') DEFAULT 'h2' AFTER `title`
_sql

); revision( 32475

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	CHANGE `ordinal` `ord` int(10) unsigned NOT NULL DEFAULT '0'
_sql

); revision( 32476

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN profanity_filter_text BOOLEAN NOT NULL DEFAULT 0
_sql


//Add the option to pick more groups in smart groups
); revision( 33550
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]smart_group_rules`
	ADD COLUMN `field4_id` int(10) unsigned NOT NULL default 0
	AFTER `field3_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]smart_group_rules`
	ADD COLUMN `field5_id` int(10) unsigned NOT NULL default 0
	AFTER `field4_id`
_sql

); revision( 33946
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	MODIFY COLUMN `captcha_type` enum('word', 'math','pictures') DEFAULT 'word'
_sql



//Add the option to have foreign keys in datasets
); revision( 34000
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	MODIFY COLUMN `type`
		enum('group','checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','other_system_field','dataset_select','dataset_picker')
	NOT NULL default 'other_system_field'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `dataset_foreign_key_id` int(10) unsigned NOT NULL default 0
	AFTER `values_source_filter`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	ADD COLUMN `label_field_id` int(10) unsigned NOT NULL default 0
	AFTER `edit_priv`
_sql

); revision( 34056
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `side_note` text AFTER `note_below`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `admin_box_visibility` enum('show', 'show_on_condition', 'hide') NOT NULL DEFAULT 'show' AFTER `db_column`
_sql


//Create a table for storing picked files in datasets
);	revision( 34400
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_dataset_files_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]custom_dataset_files_link` (
		`dataset_id` int(10) NOT NULL,
		`field_id` int(10) NOT NULL,
		`linking_id` int(10) NOT NULL,
		`file_id` int(10) NOT NULL,
		PRIMARY KEY (`field_id`,`linking_id`,`file_id`),
		KEY (`dataset_id`),
		KEY (`linking_id`),
		KEY (`file_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Add the options for file pickers in datasets
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	MODIFY COLUMN `type`
		enum('group','checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','other_system_field','dataset_select','dataset_picker','file_picker')
	NOT NULL default 'other_system_field'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `multiple_select` tinyint(1) NOT NULL default 0
	AFTER `dataset_foreign_key_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `store_file` enum('in_docstore', 'in_database') NULL default NULL
	AFTER `multiple_select`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `extensions` varchar(255) NOT NULL default ''
	AFTER `store_file`
_sql

//Add a column for indent level for dataset fields
);	revision( 34402
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `indent` int(10) unsigned NOT NULL DEFAULT 0
_sql


//Add a "must match all/any" option for Smart Groups
); revision( 34460
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]smart_groups`
	ADD COLUMN `must_match` enum('all', 'any') NOT NULL default 'all'
	AFTER `name`
_sql

//Add a column for autocomplete text fields
); revision( 34756
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `autocomplete` tinyint(1) NOT NULL default 0
	AFTER `include_in_export`
_sql

//Add a column for form special types
); revision( 34758
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `type` enum('standard', 'profile', 'registration') NOT NULL default 'standard'
	AFTER `name`
_sql

//Add column for repeat form field details
); revision( 35072
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `show_repeat_field` tinyint(1) NOT NULL DEFAULT 0,
	ADD COLUMN `repeat_field_label` varchar(255)
_sql

// Add column for repeat field error
); revision( 35073
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `repeat_error_message` varchar(255)
_sql


//Fix some more bugs with the global_id and last_updated_timestamp columns, where my last attempted fixes had left some
//sites with duplicated keys as a result
);	revision(35800
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP KEY `global_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP KEY `global_id_2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD KEY (`global_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP KEY `last_updated_timestamp`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP KEY `last_updated_timestamp_2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD KEY (`last_updated_timestamp`)
_sql


//Add a missing key to the users table, if it doesn't exist
); revision( 35850
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP KEY `status`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD KEY (`status`)
_sql


//Make the default label column a little bigger on the custom_dataset_tabs table
//to avoid database errors
); revision( 35880
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_tabs`
	MODIFY COLUMN `default_label` varchar(255) NOT NULL DEFAULT ''
_sql

//Add a new column to allow system fields to be hidden from the dataset editor. Must be set to allow changing the visibility.
); revision( 36073
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `allow_admin_to_change_visibility` tinyint(1) NOT NULL DEFAULT 0
_sql

); revision( 36075
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `organizer_visibility` enum('none', 'hide', 'show_by_default', 'always_show') NOT NULL DEFAULT 'none' AFTER `admin_box_visibility`,
	ADD KEY (`organizer_visibility`)
	
_sql

// Add a column for a custom code name, used so modules can reference a field by this unique name without having to know the id of the field
); revision( 36080
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `custom_code_name` varchar(255) DEFAULT NULL AFTER `name`,
	ADD COLUMN `protected` tinyint(1) NOT NULL DEFAULT 0 AFTER `field_type`
_sql

// Add columns to store a static method to get a list of values to autocomplete a text field
); revision( 36085
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `autocomplete_class_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `autocomplete_method_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `autocomplete_param_1` varchar(255) DEFAULT NULL,
	ADD COLUMN `autocomplete_param_2` varchar(255) DEFAULT NULL
_sql

// Add flag for autocomplete
); revision( 36086
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `autocomplete` tinyint(1) NOT NULL DEFAULT 0
_sql

// Add flag for autocomplete
); revision( 36088
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `autocomplete_no_filter_placeholder` varchar(255) DEFAULT NULL
_sql

);	revision(36390
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	ADD UNIQUE KEY (`label`)
_sql

);







