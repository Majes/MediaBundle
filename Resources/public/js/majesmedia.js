CoreMedia = {

    init: function(){

        $(".fancyboxvideo").fancybox({
      maxWidth: 700,
            height: 'auto',
            tpl: {
              // wrap template with custom inner DIV: the empty player container
              wrap: '<div class="fancybox-wrap" tabIndex="-1">' +
                  '<div class="fancybox-skin">' +
                  '<div class="fancybox-outer">' +
                  '<div id="player" style="width: 700px; height: auto">' + // player container replaces fancybox-inner
                  '</div></div></div></div>' 
          },
    
          beforeShow: function () {     
              // install player into empty container
              $("#player").flowplayer({
                splash: true,
                playlist: [
                  [
                    { flash: $(this.element[0]).data('src') }
                  ]
                ]
              });
              flowplayer("#player").play(0);
    
          },
          beforeClose: function () {
              // important! unload the player
              flowplayer("#player").unload();
          }
        });

        $(".fancyboxembed").fancybox({
            maxWidth: 700,
            height: 'auto',
            tpl: {
              // wrap template with custom inner DIV: the empty player container
              wrap: '<div class="fancybox-wrap" tabIndex="-1">' +
                  '<div class="fancybox-skin">' +
                  '<div class="fancybox-outer">' +
                  '<div id="embed" style="width: 700px; height: auto">' + // player container replaces fancybox-inner
                  '</div></div></div></div>' 
            },
    
          beforeShow: function () {

              var embedded = $(this.element[0]).parent().prev().html();
              // install player into empty container
              $("#embed").html(embedded);
    
          },
          beforeClose: function () {
              // important! unload the player
              $("#embed").html('');
          }
        });


    }
}

$(document).ready(function(){
  CoreMedia.init();
});