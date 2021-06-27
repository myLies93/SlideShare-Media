(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.demoSlider = {
    attach: function attach(context) {
      $('.demo-slick-slider', context).once('demoSlider').each((index, el) => {
        $(el).slick({
          dots: true,
          arrows: false,
          infinite: true,
          speed: 500,
          fade: true
        });
      })
    }
  };

})(jQuery, Drupal, drupalSettings);
