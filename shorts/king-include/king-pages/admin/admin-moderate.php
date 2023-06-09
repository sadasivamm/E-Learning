<?php
/*

	File: king-include/king-page-admin-moderate.php
	Description: Controller for admin page showing questions, answers and comments waiting for approval


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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	require_once QA_INCLUDE_DIR.'king-app/admin.php';
	require_once QA_INCLUDE_DIR.'king-db/selects.php';
	require_once QA_INCLUDE_DIR.'king-app/format.php';


//	Find queued questions, answers, comments

	$userid=qa_get_logged_in_userid();

	list($queuedquestions, $queuedanswers, $queuedcomments)=qa_db_select_with_pending(
		qa_db_qs_selectspec($userid, 'created', 0, null, null, 'Q_QUEUED', true),
		qa_db_recent_a_qs_selectspec($userid, 0, null, null, 'A_QUEUED', true),
		qa_db_recent_c_qs_selectspec($userid, 0, null, null, 'C_QUEUED', true)
	);


//	Check admin privileges (do late to allow one DB query)

	if (qa_user_maximum_permit_error('permit_moderate')) {
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return $qa_content;
	}


//	Check to see if any were approved/rejected here

	$pageerror=qa_admin_check_clicks();


//	Combine sets of questions and remove those this user has no permission to moderate

	$questions=qa_any_sort_by_date(array_merge($queuedquestions, $queuedanswers, $queuedcomments));

	if (qa_user_permit_error('permit_moderate')) // if user not allowed to moderate all posts
		foreach ($questions as $index => $question)
			if (qa_user_post_permit_error('permit_moderate', $question))
				unset($questions[$index]);


//	Get information for users

	$usershtml=qa_userids_handles_html(qa_any_get_userids_handles($questions));


//	Prepare content for theme

	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/recent_approve_title');
	$qa_content['error']=isset($pageerror) ? $pageerror : qa_admin_page_error();


	$html = '<form method="post" action="'.qa_self_html().'">';
	
	$html .= '<table class="editusers-table">';
	if (count($questions)) {
		foreach ($questions as $question) {
			$postid=qa_html(isset($question['opostid']) ? $question['opostid'] : $question['postid']);
			$elementid='p'.$postid;

			$htmloptions=qa_post_html_options($question);
			$htmloptions['voteview']=false;
			$htmloptions['tagsview']=!isset($question['opostid']);
			$htmloptions['answersview']=false;
			$htmloptions['viewsview']=false;
			$htmloptions['contentview']=false;
			$htmloptions['elementid']=$elementid;

			$htmlfields=qa_any_to_q_html_fields($question, $userid, qa_cookie_get(), $usershtml, null, $htmloptions);

			if (isset($htmlfields['what_url'])) {
				$htmlfields['url'] = $htmlfields['what_url'];
			}
			$acontent = isset($htmlfields['raw']['ocontent']) ? $htmlfields['raw']['ocontent'] : '';
			$type     = isset($htmlfields['raw']['obasetype']) ? $htmlfields['raw']['obasetype'] : $htmlfields['raw']['basetype'];
			$author   = isset($htmlfields['who']['data']) ? $htmlfields['who']['data'] : $htmlfields['who']['handle'];

			switch ($type) {
				case 'A':
					$typet = qa_lang_html('misc/m_comment');
					break;
				case 'C':
					$typet = qa_lang_html('misc/m_reply');
					break;
				default:
					$typet = qa_lang_html('misc/m_post');
					break;
			}
			$html .= '<tr class="kingeditli" id="p' . $postid . '">';
			$html .= '<td><strong>' . qa_html($typet) . '</strong></td>';
			$html .= '<td>' . qa_sanitize_html($author) . '</td>';
			$html .= '<td><strong>' . qa_html($question['title']) . '</strong><div>' . qa_html($acontent) . '</div></td>';
			$html .= '<td><a href="' . qa_html($htmlfields['url']) . '" class="king-edit-button" target="_blank"><i class="fas fa-external-link-alt"></i></a></td>';
			$html .= '<td><input name="admin_' . $postid . '_approve" onclick="return qa_admin_click(this);" value="' . qa_lang_html('question/approve_button') . '" title="[question/approve_q_popup]" type="submit" class="king-edit-button"></td>';
			$html .= '<td><input name="admin_' . $postid . '_reject" onclick="return qa_admin_click(this);" value="' . qa_lang_html('question/reject_button') . '" type="submit" class="king-edit-button"></td>';
			$html .= '</tr>';
		}

	} else {
		$qa_content['title']=qa_lang_html('admin/no_approve_found');
	}
	$html .= '<input type="hidden" name="code" value="'.qa_get_form_security_code('admin/click').'">';
	$html .= '</table>';
	$html .= '</form>';

	$qa_content['custom'] = $html;
	$qa_content['navigation']['sub']=qa_admin_sub_navigation();
	$qa_content['script_rel'][]='king-content/king-admin.js?'.QA_VERSION;


	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/