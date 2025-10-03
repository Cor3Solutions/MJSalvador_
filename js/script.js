(function($) { 
    // init Chocolat light box
    var initChocolat = function() {
      Chocolat(document.querySelectorAll('.image-link'), {
        imageSize: 'contain',
        loop: true,
      })
    }
 
    $(document).ready(function() {
      searchPopup();
      initChocolat();
      initExitPopup();
      
      AOS.init({
        duration: 1200,
        once: true
      })
  
      $(".youtube").colorbox({
        iframe: true,
        innerWidth: 960,
        innerHeight: 585
      });

      var swiper = new Swiper(".main-banner", {
        slidesPerView: 5,
        spaceBetween: 10,
        autoplay: {
          delay: 5000,
        },
        pagination: {
          el: "#mobile-products .swiper-pagination",
          clickable: true,
        },
        breakpoints: {
          0: {
            slidesPerView: 3,
            spaceBetween: 20,
          },
          980: {
            slidesPerView: 4,
            spaceBetween: 20,
          },
          1200: {
            slidesPerView: 5,
            spaceBetween: 20,
          }
        },
      });

      var swiper = new Swiper(".testimonial-swiper", {
        slidesPerView: 3,
        spaceBetween: 20,
        pagination: {
          el: ".swiper-pagination",
          clickable: true,
        },
        breakpoints: {
          0: {
            slidesPerView: 1,
            spaceBetween: 20,
          },
          1200: {
            slidesPerView: 3,
            spaceBetween: 20,
          }
        },
      });

    }); // End of a document ready

})(jQuery);