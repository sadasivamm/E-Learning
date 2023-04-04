<?php $forum =&get_instance();
$forum->load->model('addons/course_forum_model');
$number_of_total_questions = $forum->course_forum_model->get_course_wise_all_parent_questions($course_id)->num_rows();?>

<div class="row justify-content-center hide-search-processing">
	<?php foreach($questions as $key=>$question):
		$user_details = $this->user_model->get_all_user($question['user_id'])->row_array();
		if($question['upvoted_user_id'] == null || $question['upvoted_user_id'] == 'null'){
			$upvoted_user_ids = json_encode(array());
		}else{
			$upvoted_user_ids = $question['upvoted_user_id'];
		}
		if(in_array($this->session->userdata('user_id'), json_decode($upvoted_user_ids))){
			$upvoted_user = true;
		}else{
			$upvoted_user = false;
		}
		$question_comments = $forum->course_forum_model->get_child_question($question['id']);
		$commented_user = $forum->course_forum_model->get_child_question($question['id'], $this->session->userdata('user_id'))->num_rows();
		?>
		<div class="col-md-7 border-top user-course-questions py-4">
			<div class="row">
				<div class="col-md-10 col-lg-10 col-xl-11 cursor-pointer" onclick="question_comments('<?= $question['id']; ?>')">
					<h6><?= $question['title']; ?></h6>
					<p class="text-14"><?= $question['description']; ?></p>
				</div>
				<div class="col-md-2 col-lg-2 col-xl-1 p-0">
					<button class="border-0 mt-2 icon-upvot-comment <?php if($upvoted_user == true){ echo 'text-primary'; }else{ echo 'text-mute'; } ?>" onclick="user_vote('<?= $question['id']; ?>', this)"><span id="count-upvote-<?= $question['id']; ?>"><?= count(json_decode($upvoted_user_ids)); ?></span> <i class="far fa-thumbs-up width-10-px m-0"></i></button>

					<button class="border-0 mt-2 icon-upvot-comment <?php if($commented_user > 0){ echo 'text-primary'; }else{ echo 'text-mute'; } ?>"  onclick="question_comments('<?= $question['id']; ?>')"><span><?= $question_comments->num_rows(); ?></span> <i class="far fa-comment-alt width-10-px m-0"></i></button>
				</div>
			</div>
			<div class="row mt-3">
				<div class="col-md-12 text-14px">
					<p class="pe-2">
						<a class="fw-bold" href="<?= site_url('home/instructor_page/').$question['user_id']; ?>" target="_blank">
							<span>
								<img class="rounded-circle" src="<?php echo $this->user_model->get_user_image_url($question['user_id']); ?>" width="25" height="25">
							</span>
							<?= $user_details['first_name'].' '.$user_details['last_name']; ?>,
						</a>
						<span>
							<?= get_past_time($question['date_added']); ?>
						</span>
						<?php if($this->session->userdata('user_id') == $question['user_id']): ?>
							<a class="float-end text-mute" href="javascript:;" onclick="delete_question('<?= $question['id']; ?>')"><i class="far fa-trash-alt"></i></a>
						<?php endif; ?>
					</p>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
	<?php if($number_of_total_questions >= $starting_value): ?>
		<div class="col-md-7 p-0">
			<a href="javascript:;" class="btn btn-light w-100 fw-bold mt-5" onclick="show_more_questions(this, '<?= $course_id; ?>', '<?= $starting_value; ?>')"><?= site_phrase('show_more'); ?></a>
		</div>
	<?php endif; ?>
</div>