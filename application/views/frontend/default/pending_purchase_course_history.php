<section class="purchase-history-list-area">
    <div class="container">
        <div class="row">
            <div class="col">
                <ul class="purchase-history-list">
                    <li class="purchase-history-list-header">
                        <div class="row">
                            <div class="col-sm-6"><h4 class="purchase-history-list-title"> <?php echo get_phrase('pending_purchase_course'); ?> </h4></div>
                            <div class="col-sm-6 hidden-xxs hidden-xs">
                                <div class="row">
                                    <div class="col-sm-3"> <?php echo get_phrase('date'); ?> </div>
                                    <div class="col-sm-3"> <?php echo get_phrase('total_price'); ?> </div>
                                    <div class="col-sm-4"> <?php echo get_phrase('payment_type'); ?> </div>
                                    <div class="col-sm-2"> <?php echo get_phrase('status'); ?> </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <?php foreach($pending_offline_payment_history as $pending_offline_payment):
        				foreach(json_decode($pending_offline_payment['course_id']) as $course_id):
                            $course_details = $this->crud_model->get_course_by_id($course_id)->row_array();?>
                            <li class="purchase-history-items mb-2">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="purchase-history-course-img">
                                            <img src="<?php echo $this->crud_model->get_course_thumbnail_url($course_details['id']);?>" class="img-fluid">
                                        </div>
                                        <a class="purchase-history-course-title" href="<?php echo site_url('home/course/'.slugify($course_details['title']).'/'.$course_details['id']); ?>" >
                                            <?php
                                            echo $course_details['title'];
                                            ?>
                                        </a>
                                    </div>
                                    <div class="col-sm-6 purchase-history-detail">
                                        <div class="row">
                                            <div class="col-sm-3 date">
                                                <?php echo date('D, d-M-Y', $pending_offline_payment['timestamp']); ?>
                                            </div>
                                            <div class="col-sm-3 price"><b>
                                                <?php echo currency($pending_offline_payment['amount']); ?>
                                            </b></div>
                                            <div class="col-sm-4 payment-type">
                                                <?php echo get_phrase('offline'); ?>
                                            </div>
                                            <div class="col-sm-2">
                                                <span class="ofline-payment-pending"><?php echo get_phrase('pending'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                    	<?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>