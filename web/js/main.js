$('.image-link').magnificPopup({
    type: 'image',
    mainClass: 'mfp-with-zoom', // this class is for CSS animation below

    retina: {
        ratio: 1, // Increase this number to enable retina image support.
        // Image in popup will be scaled down by this number.
        // Option can also be a function which should return a number (in case you support multiple ratios). For example:
        // ratio: function() { return window.devicePixelRatio === 1.5 ? 1.5 : 2  }


        replaceSrc: function(item, ratio) {
            return item.src.replace(/\.\w+$/, function(m) { return '@2x' + m; });
        } // function that changes image source
    },

    zoom: {
        enabled: true, // By default it's false, so don't forget to enable it

        duration: 300, // duration of the effect, in milliseconds
        easing: 'ease-in-out', // CSS transition easing function

        // The "opener" function should return the element from which popup will be zoomed in
        // and to which popup will be scaled down
        // By defailt it looks for an image tag:
        opener: function(openerElement) {
            // openerElement is the element on which popup was initialized, in this case its <a> tag
            // you don't need to add "opener" option if this code matches your needs, it's defailt one.
            return openerElement.is('img') ? openerElement : openerElement.find('img');
        }
    }

});