<link rel="stylesheet" href="<?php echo base_url().'assets/frontend/default/css/video.css'; ?>">
<link rel="stylesheet" href="<?php echo base_url().'assets/frontend/default/js/videojs/video-js.css'; ?>">
<section class="page-header-area my-course-area">
  <div class="container-fluid p-0 position-relative" style="background-image: url('<?php echo base_url('assets/frontend/default/img/my_courses.jpg'); ?>'); background-position: center;
    background-size: cover;">
    <div class="image-placeholder-2"></div>
    <div class="container" style="position: inherit;">
      <h1 class="page-title py-5 text-white print-hidden"><?php echo $page_title; ?></h1>
    </div>
  </div>
</section>

<section class="my-courses-area">
    <div class="container" style="height: 640px;">
        
    <iframe src="<?php echo site_url('shorts/index.php?qa=shorts'); ?>" height="100%" width="100%" title="" style="border:none" ></iframe>
    </div>
</section>