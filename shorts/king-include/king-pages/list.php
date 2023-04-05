<?php
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

More about this license: LICENCE.html
 */

if (!defined('QA_VERSION')) {
	// don't allow this page to be requested directly from browser
	header('Location: ../');
	exit;
}

require_once QA_INCLUDE_DIR . 'king-app/format.php';
require_once QA_INCLUDE_DIR . 'king-app/limits.php';
require_once QA_INCLUDE_DIR . 'king-db/selects.php';
require_once QA_INCLUDE_DIR . 'king-util/sort.php';
require_once QA_INCLUDE_DIR . 'king-app-video.php';

//    Check whether this is a follow-on question and get some info we need from the database

$in = array();

$followpostid     = qa_get('follow');
$in['categoryid'] = qa_clicked('doask') ? qa_get_category_field_value('category') : qa_get('cat');
$userid           = qa_get_logged_in_userid();

list($categories, $followanswer, $completetags) = qa_db_select_with_pending(
	qa_db_category_nav_selectspec($in['categoryid'], true),
	isset($followpostid) ? qa_db_full_post_selectspec($userid, $followpostid) : null,
	qa_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS)
);

if (!isset($categories[$in['categoryid']])) {
	$in['categoryid'] = null;
}

if (@$followanswer['basetype'] != 'A') {
	$followanswer = null;
}

//    Check for permission error

$permiterror = qa_user_maximum_permit_error('permit_post_q', QA_LIMIT_QUESTIONS);

if ($permiterror || qa_opt( 'disable_list' )) {
	$qa_content = qa_content_prepare();

	// The 'approve', 'login', 'confirm', 'limit', 'userblock', 'ipblock' permission errors are reported to the user here
	// The other option ('level') prevents the menu option being shown, in qa_content_prepare(...)

	switch ($permiterror) {
		case 'login':
		$qa_content['error']=qa_lang_html('users/no_permission');
		$econtent = qa_insert_login_links(qa_lang_html('question/ask_must_login'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
		break;

		case 'confirm':
		$qa_content['error']=qa_lang_html('users/no_permission');
		$econtent = qa_insert_login_links(qa_lang_html('question/ask_must_confirm'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
		break;

		case 'limit':
		$qa_content['error']=qa_lang_html('users/no_permission');
		$econtent=qa_lang_html('question/ask_limit');
		break;

		case 'membership':
		$qa_content['error']=qa_lang_html('users/no_permission');
		$econtent=qa_insert_login_links(qa_lang_html('misc/mem_message'));
		$qa_content['custom'] = '<div class="nopost"><i class="fa-solid fa-fingerprint fa-4x"></i><p>'.$econtent.'</p><a href="'. qa_path_html( 'membership' ) .'" class="meme-button">'.qa_lang_html('misc/see_plans').'</a></div>';
		break;

		case 'approve':
		$qa_content['error']=qa_lang_html('users/no_permission');
		$econtent=qa_lang_html('question/ask_must_be_approved');
		break;

		default:
		$econtent=qa_lang_html('users/no_permission');
		$qa_content['error']=qa_lang_html('users/no_permission');
		break;
	}
	if (empty($qa_content['custom'] )) {
		$qa_content['custom'] = '<div class="nopost"><i class="fa-solid fa-circle-user fa-4x"></i>'.$econtent.'</div>';
	}
	return $qa_content;
}
if ( qa_opt( 'disable_list' ) ) {
	$qa_content          = qa_content_prepare();
	$qa_content['error'] = qa_lang_html('users/no_permission');
	return $qa_content;
}


$captchareason = qa_user_captcha_reason();

$in['title'] = qa_get_post_title('title'); // allow title and tags to be posted by an external form
$in['extra'] = '';
$in['pcontent'] = qa_post_text('pcontent');
if (qa_using_tags()) {
	$in['tags'] = qa_get_tags_field_value('tags');
}

if (qa_clicked('doask')) {
	require_once QA_INCLUDE_DIR . 'king-app/post-create.php';
	require_once QA_INCLUDE_DIR . 'king-util/string.php';

	$categoryids = array_keys(qa_category_path($categories, @$in['categoryid']));
	$userlevel   = qa_user_level_for_categories($categoryids);

	$in['name']   = qa_post_text('name');
	$in['notify'] = strlen((string)qa_post_text('notify')) > 0;
	$in['nsfw']   = qa_post_text('nsfw');
	$in['email']  = qa_post_text('email');
	$in['queued'] = qa_user_moderation_reason($userlevel) !== false;

	qa_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);

	$errors = array();

	if (!qa_check_form_security_code('ask', qa_post_text('code'))) {
		$errors['page'] = qa_lang_html('misc/form_security_again');
	} else {
		$filtermodules = qa_load_modules_with('filter', 'filter_question');
		foreach ($filtermodules as $filtermodule) {
			$oldin = $in;
			$filtermodule->filter_question($in, $errors, null);
			qa_update_post_text($in, $oldin);
		}

		if (qa_using_categories() && count($categories) && (!qa_opt('allow_no_category')) && !isset($in['categoryid'])) {
			$errors['categoryid'] = qa_lang_html('question/category_required');
		}
		// check this here because we need to know count($categories)
		elseif (qa_user_permit_error('permit_post_q', null, $userlevel)) {
			$errors['categoryid'] = qa_lang_html('question/category_ask_not_allowed');
		}

		if ($captchareason) {
			require_once QA_INCLUDE_DIR . 'king-app/captcha.php';
			qa_captcha_validate_post($errors);
		}


			$kingcontent = qa_post_text('news_thumb');

			if (!isset($_POST['out']) || !is_array($_POST['out'])) {
				$in['poll'] = null;
			} else {
				$in['poll'] =qa_gpc_to_string($_POST['out']);
				$poll = serialize($in['poll']);
			}
			
			
			
		if (empty($errors)) {
			$cookieid = isset($userid) ? qa_cookie_get() : qa_cookie_get_create(); // create a new cookie if necessary

			$questionid = qa_question_create($followanswer, $userid, qa_get_logged_in_handle(), $cookieid,
				$in['title'], $kingcontent, $in['format'], $in['text'], isset($in['tags']) ? qa_tags_to_tagstring($in['tags']) : '',
				$in['notify'], $in['email'], $in['categoryid'], $in['extra'], $in['queued'], $in['name'], 'list', $in['pcontent'], $in['nsfw']);

			qa_db_query_sub(
					'INSERT INTO ^poll (postid, content, extra, created) VALUES (#, $, $, NOW())',
					$questionid, $poll, 'list'
				);
			qa_redirect(qa_q_request($questionid, $in['title'])); // our work is done here
		}
	}
}

$qa_content = qa_content_prepare(false, array_keys(qa_category_path($categories, @$in['categoryid'])));

$qa_content['title'] = qa_lang_html(isset($followanswer) ? 'question/ask_follow_title' : 'misc/king_list');
$qa_content['error'] = @$errors['page'];

$editorname = isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_qs');
$editor     = qa_load_editor(@$in['content'], @$in['format'], $editorname);

$field          = qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], 'content', 12, false);
$field['label'] = qa_lang_html('question/q_content_label');
$field['error'] = qa_html(@$errors['content']);

$custom = qa_opt('show_custom_ask') ? trim(qa_opt('custom_ask')) : '';
$poll   = '<div ng-model="inputs" ui-sortable="sortableOptions">
<div class="king-ang" ng-repeat="input in inputs track by $index | limitTo:24">
	<div class="kingp-left">
		<div class="kingp-leftin">
		<div class="kingp-tabs" ng-init="input.polltab=\'grid1\'">
			<div class="number-list">{{$index+1}}</div>
			<label><input class="hide" type="radio" ng-model="input.polltab" id="grid1" value="grid1" name="grid[{{$index+1}}]"><i class="fas fa-video"></i></label>
			<label><input class="hide" type="radio" ng-model="input.polltab" id="grid2" value="grid2" name="grid[{{$index+1}}]"><i class="fas fa-image"></i></label>
			<label><i ng-click="removeInput(inputs, $index)" ng-if="!$first" class="far fa-trash-alt"></i></label>
			<div class="pbutton listhandle"><i class="fas fa-arrows-alt"></i></div>
			</div>
		</div>
		<div ng-if="$last" class="tblack" ng-click="addList()"><i class="fas fa-plus"></i></div>
	</div>
	<div class="inputarea">
		<div  class="king-lists">
			<div  class="king-list">
				<div class="inright">
					<input class="king-form-tall-text" type="text" ng-model="input.choices" name="out[{{$index+1}}][choices]" required autocomplete="off" maxlength="200"/>
						<div ng-show="input.polltab !== \'grid1\'">
							<div ng-model="input.files" id="dropzone1" class="dropzone king-poll-file" dropzone="dropzoneConfig" ng-dropzone></div>
							<input type="text" ng-model="input.img" class="hide" name="out[{{$index+1}}][img]" autocomplete="off" maxlength="20"/>
						</div>
						<div ng-show="input.polltab !== \'grid2\'">
							<input class="king-form-tall-text" type="text" ng-model="input.video" name="out[{{$index+1}}][video]" autocomplete="off" maxlength="250" placeholder="'.qa_lang_html('misc/video_url').'" onchange="video_add(this, this.value)" onInput="video_add(this, this.value)"/>
							<div id="videoembed"><i class="fas fa-compact-disc"></i></div>
						</div>
					<input class="hide" type="text" ng-model="input.id" name="out[{{$index+1}}][id]" value="{{$index+1}}"/>
					<textarea class="king-form-tall-text" ng-model="input.desc" type="textarea" name="out[{{$index+1}}][desc]" maxlength="400"></textarea>
			</div>
		</div>
	</div>
	</div>
	</div>
	</div>';

if (qa_opt('enable_aws')) {
	$awscla = 'pcontentaws';
} else {
	$awscla = 'pconteno';
}
$qa_content['form'] = array(
	'tags'    => 'name="ask" method="post" ENCTYPE="multipart/form-data" action="' . qa_self_html() . '" ng-controller="MyCtrl" ng-app="plunker"',

	'style'   => 'tall',

	'fields'  => array(
		'custom'  => array(
			'type' => 'custom',
			'html' => '<div class="snote">' . $custom . '</div>',
		),

		'imgprev' => array(
			'type'  => 'custom',
			'html'  => '<div id="newsthumb" class="dropzone king-poll-file"></div>',
		),

		'title'   => array(
			'label' => qa_lang_html('question/q_title_label'),
			'tags'  => 'name="title" id="title" autocomplete="off" minlength="'.qa_opt('min_len_q_title').'" required',
			'value' => qa_html(@$in['title']),
			'error' => qa_html(@$errors['title']),
		),

		'similar' => array(
			'type' => 'custom',
			'html' => '<span id="similar"></span>',
		),

		'content' => array(
			'tags'  => 'name="content" id="content" autocomplete="off" class="hide"',
			'value' => qa_html(@$in['content']),
			'error' => qa_html(@$errors['content']),
		),

		'tiny'    => array(
			'label' => qa_lang_html('main/news_content'),
			'type'  => 'custom',
			'html'  => '<div id="pcontent" class="'.$awscla.'">' . @$in['pcontent'] . '</div>',
		),

		'poll'    => array(
			'type' => 'custom',
			'html' => $poll,
		),

	),

	'buttons' => array(
		'ask' => array(
			'tags'  => 'onclick="qa_show_waiting_after(this, false); ' .
			(method_exists($editor, 'update_script') ? $editor->update_script('content') : '') . '"',
			'label' => qa_lang_html('question/ask_button'),
		),
	),

	'hidden'  => array(
		'editor' => qa_html($editorname),
		'code'   => qa_get_form_security_code('ask'),
		'doask'  => '1',
	),
);
script_options($qa_content);
if (!strlen($custom)) {
	unset($qa_content['form']['fields']['custom']);
}

if (qa_opt('do_ask_check_qs') || qa_opt('do_example_tags')) {
	$qa_content['script_rel'][] = 'king-content/king-ask.js?' . QA_VERSION;
	$qa_content['form']['fields']['title']['tags'] .= ' onchange="qa_title_change(this.value);"';

	if (strlen(@$in['title'])) {
		$qa_content['script_onloads'][] = 'qa_title_change(' . qa_js($in['title']) . ');';
	}

}

if (isset($followanswer)) {
	$viewer = qa_load_viewer($followanswer['content'], $followanswer['format']);

	$field = array(
		'type'  => 'static',
		'label' => qa_lang_html('question/ask_follow_from_a'),
		'value' => $viewer->get_html($followanswer['content'], $followanswer['format'], array('blockwordspreg' => qa_get_block_words_preg())),
	);

	qa_array_insert($qa_content['form']['fields'], 'title', array('follows' => $field));
}

if (qa_using_categories() && count($categories)) {
	$field = array(
		'label' => qa_lang_html('question/q_category_label'),
		'error' => qa_html(@$errors['categoryid']),
	);

	qa_set_up_category_field($qa_content, $field, 'category', $categories, $in['categoryid'], true, qa_opt('allow_no_sub_category'));

	if (!qa_opt('allow_no_category')) // don't auto-select a category even though one is required
	{
		$field['options'][''] = '';
	}

	qa_array_insert($qa_content['form']['fields'], 'content', array('category' => $field));
}

if (qa_using_tags()) {
	$field = array(
		'error' => qa_html(@$errors['tags']),
	);

	qa_set_up_tag_field($qa_content, $field, 'tags', isset($in['tags']) ? $in['tags'] : array(), array(),
		qa_opt('do_complete_tags') ? array_keys($completetags) : array(), qa_opt('page_size_ask_tags'));

	qa_array_insert($qa_content['form']['fields'], null, array('tags' => $field));
}

if (!isset($userid)) {
	qa_set_up_name_field($qa_content, $qa_content['form']['fields'], @$in['name']);
}

if ( qa_opt('enable_nsfw') ) {
	$field = array(
		'type' => 'custom',
		'html' => '<input name="nsfw" id="king_nsfw" type="checkbox" value="'.qa_html(@$in['nsfw']).'"><label for="king_nsfw" class="king-nsfw">'.qa_lang_html('misc/nsfw').'</label>'
	);
	qa_array_insert($qa_content['form']['fields'], null, array('nsfw' => $field));
}

if ($captchareason) {
	require_once QA_INCLUDE_DIR . 'king-app/captcha.php';
	qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors, qa_captcha_reason_note($captchareason));
}

$qa_content['focusid'] = 'title';
$qa_content['header'] = $qa_content['title'];
return $qa_content;

/*
Omit PHP closing tag to help avoid accidental output
 */
