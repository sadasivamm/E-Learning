<script type="text/javascript">

  function set_js_flashdata(url){
    $.ajax({
      url: url,
      success: function(){}
    });
  }

  function togglePriceFields(elem) {
    if($("#"+elem).is(':checked')){
      $('.paid-course-stuffs').slideUp();
    }else
      $('.paid-course-stuffs').slideDown();
    }
</script>

<?php if ($page_name == 'courses-server-side'): ?>
  <script type="text/javascript">
  // jQuery(document).ready(function($) {
  //       $.fn.dataTable.ext.errMode = 'throw';
  //       $('#course-datatable-server-side').DataTable({
  //           "processing": true,
  //           "serverSide": true,
  //           "ajax":{
  //               "url": "<?php echo site_url(strtolower($this->session->userdata('role')).'/get_courses') ?>",
  //               "dataType": "json",
  //               "type": "POST",
  //               "data" : {selected_category_id : '<?php echo $selected_category_id; ?>',
  //                         selected_status : '<?php echo $selected_status ?>',
  //                         selected_instructor_id : '<?php echo $selected_instructor_id ?>',
  //                         selected_price : '<?php echo $selected_price ?>'}
  //           },
  //           "columns": [
  //               { "data": "#" },
  //               { "data": "title" },
  //               { "data": "category" },
  //               { "data": "lesson_and_section" },
  //               { "data": "enrolled_student" },
  //               { "data": "status" },
  //               { "data": "price" },
  //               { "data": "actions" },
  //           ],
  //           "columnDefs": [{
  //               targets: "_all",
  //               orderable: false
  //            }]
  //       });
  //   });

  $(document).ready(function () {
     var table = $('#course-datatable-server-side').DataTable({
      responsive: true,
      "processing": true,
      "serverSide": true,
      "ajax":{
        "url": "<?php echo site_url(strtolower($this->session->userdata('role')).'/get_courses') ?>",
        "dataType": "json",
        "type": "POST",
        "data":{selected_category_id : '<?php echo $selected_category_id; ?>',
                selected_status : '<?php echo $selected_status ?>',
                selected_instructor_id : '<?php echo $selected_instructor_id ?>'}
      },
      "columns": [
        { "data": "#" },
        { "data": "title" },
        { "data": "category" },
        { "data": "lesson_and_section" },
        { "data": "enrolled_student" },
        { "data": "status" },
        { "data": "actions" },
      ]   
    });
   });

    $(".server-side-select2" ).each(function() {
      var actionUrl = $(this).attr('action');
      $(this).select2({
        ajax: {
          url: actionUrl,
          dataType: 'json',
          delay: 1000,
          data: function (params) {
            return {
              searchVal: params.term // search term
            };
          },
          processResults: function (response) {
            return {
              results: response
            };
          }
        },
        placeholder: 'Search here',
        minimumInputLength: 1,
      });
    });
  </script>
<?php endif; ?>

<script type="text/javascript">
  function refreshServersideTable(tableId){
    $('#'+tableId).DataTable().ajax.reload();
  }

  function switch_language(language) {
      $.ajax({
          url: '<?php echo site_url('home/site_language'); ?>',
          type: 'post',
          data: {language : language},
          success: function(response) {
              setTimeout(function(){ location.reload(); }, 500);
          }
      });
  }


  function div_add()
  {
    $.NotificationApp.send("<?php echo get_phrase('successfully'); ?>!", '<?php echo get_phrase('Div added to bottom ')?>' ,"top-right","rgba(0,0,0,0.2)","info");

  }

  function div_remove()
  {
    $.NotificationApp.send("<?php echo get_phrase('successfully'); ?>!", '<?php echo get_phrase('Div has been deleted ')?>' ,"top-right","rgba(0,0,0,0.2)","error");

  }
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