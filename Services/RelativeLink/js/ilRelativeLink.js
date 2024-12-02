/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: relativeLink - js functions for relative link
 * (anonymous constructor function)
 */
il.RelativeLink = new function() {

	/**
	 * Self reference for usage in event handlers
	 * @type object
	 * @private
	 */
	var self = this;
	

	/**
	 * Configuration
	 * Has to be provided as JSON when init() is called
	 * @type object
	 * @private
	 */		
	var config = {
		ajax_url: '',			// ajax_url
        show_link: ''           // show the link (otherwise the create btton is shown)
	};


    /**
     * Initialize the element
     * called from ilTemplate::addOnLoadCode,
     * added by ilRelativeLinkGUI::getHTML()
     * @param a_config
     */
	this.init = function(a_config) {
		config = a_config;

        $('#ilRelativeLinkLabel').click(self.selectLink);

        if (config.show_link) {
            $('#ilRelativeLinkInput').show();
            $('#ilRelativeLinkInput').click(self.selectLink);
        }
        else {
            $('#ilRelativeLinkCreate').show();
            $('#ilRelativeLinkCreate').click(self.createLink);
        }
	};

    /**
     * Select the link for copying
     */
    this.selectLink = function () {
        if (config.show_link) {
            $('#ilRelativeLinkInput').focus();
            $('#ilRelativeLinkInput').select();
        }
    }

	/** 
	 * Create a link per ajax
	 */
	this.createLink = function(event) {
        event.preventDefault();

        $.ajax({
            type: 'GET',		    // alwasy use POST for the api
            url: config.ajax_url,	// sync api url
            dataType: 'json',	    // expected response data type
            timeout: 60000	        // 60 seconds
        })

        .done(function(data) {

            console.log(data);
            $('#ilRelativeLinkCreate').hide();

            $('#ilRelativeLinkInput').val(data.link);
            $('#ilRelativeLinkInput').show();
            $('#ilRelativeLinkInput').click(self.selectLink);

            config.show_link = true;
            self.selectLink();
        });
	}
	
};
