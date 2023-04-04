<script type="text/javascript">
//saving the current progress and starting from the saved progress
var newProgress;
var savedProgress;
var currentProgress = '<?php echo lesson_progress($lesson_id); ?>';
var lessonType = '<?php echo $lesson_details['lesson_type']; ?>';
var videoProvider = '<?php echo isset($provider) ? $provider : null; ?>';

function markThisLessonAsCompleted(lesson_id) {
  $('#lesson_list_area').hide();
  $('#lesson_list_loader').show();
  var course_id = "<?php echo $course_details['id']; ?>";

  $.ajax({
    type : 'POST',
    url : '<?php echo site_url('home/update_watch_history_manually'); ?>',
    data : {lesson_id : lesson_id, course_id:course_id},
    success : function(response){
      $('#lesson_list_area').show();
      $('#lesson_list_loader').hide();
      var responseVal = JSON.parse(response);
      // console.log(responseVal);
      // console.log(responseVal.course_progress);
    }
  });
}


$(document).ready(function() {
  if (lessonType == 'video' && videoProvider == 'html5') {
    var totalDuration = document.querySelector('#player').duration;

    if (currentProgress == 1 || currentProgress == totalDuration) {
      document.querySelector('#player').currentTime = 0;
    }else {
      document.querySelector('#player').currentTime = currentProgress;
    }
  }
});

var counter = 0;
player.on('canplay', event => {
  if (counter == 0) {
    if (currentProgress == 1) {
      document.querySelector('#player').currentTime = 0;
    }else{
      document.querySelector('#player').currentTime = currentProgress;
    }
  }
  counter++;
});


//const player = new Plyr('#player');
if(typeof player === 'object' && player !== null){
    let lesson_id = '<?php echo $lesson_id; ?>';
    let course_id = '<?php echo $course_details['id']; ?>';
    let previousSavedDuration = 0;
    let currentDuration = 0;
    setInterval(function(){
        if("<?php echo $lesson_details['lesson_type']; ?>" == "video"){
            currentDuration = parseInt(player.currentTime);
        }else{
            currentDuration = 0;
        }

        if (lesson_id && course_id && (currentDuration%5) == 0 && previousSavedDuration != currentDuration) {
            previousSavedDuration = currentDuration;

            $.ajax({
              type : 'POST',
              url : '<?php echo site_url('home/update_watch_history_with_duration'); ?>',
              data : {lesson_id : lesson_id, course_id : course_id, current_duration: currentDuration},
              success : function(response){
                var responseVal = JSON.parse(response);
                //console.log(responseVal);
                // console.log(responseVal.course_progress);

              }
            });
        }

        //console.log('Avoid Server Call'+currentDuration);
    }, 1000);
}


$(document).ready(function() {
  //Remove SRC
  setTimeout(function(){
    $('.remove_video_src').remove();
  }, 1000);
});

//Play from previous duration
var previous_duration = <?php echo ($current_duration_of_this_lesson > 0) ? $current_duration_of_this_lesson:0; ?>;
var previousTimeSetter = setInterval(function(){
  if (player.playing !== true && player.currentTime != previous_duration) {
    player.currentTime = previous_duration;
  }else{
    clearInterval(previousTimeSetter);
  }
}, 800);
</script>

<!-- Google analytics -->
<?php if(!empty(get_settings('google_analytics_id'))): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo get_settings('google_analytics_id'); ?>"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', '<?php echo get_settings('google_analytics_id'); ?>');
  </script>
<?php endif; ?>
<!-- Ended Google analytics -->

<!-- Meta pixel -->
<?php if(!empty(get_settings('meta_pixel_id'))): ?>
  <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?php echo get_settings('meta_pixel_id'); ?>');
    fbq('track', 'PageView');
  </script>
  <noscript>
    <img height="1" width="1" style="display:none" 
         src="https://www.facebook.com/tr?id=<?php echo get_settings('meta_pixel_id'); ?>&ev=PageView&noscript=1"/>
  </noscript>
<?php endif; ?>
<!-- Ended Meta pixel -->