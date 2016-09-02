il.MediaPool = {
	previewurl: '',

	setPreviewUrl: function (url) {
		il.MediaPool.ajaxurl = url;
		$('#ilMepPreview').on('shown.bs.modal', function () {
			il.MediaPool.resizePreview();
		});
		$('#ilMepPreview').on('hidden.bs.modal', function () {
			$('#ilMepPreviewContent').attr("src", "about:blank");
		});
	},

	preview: function (id) {
		$('#ilMepPreviewContent').attr("src", il.MediaPool.ajaxurl + "&mepitem_id="+ id);
		$('#ilMepPreview').modal('show');
	},

	resizePreview: function () {
		var vp = il.Util.getViewportRegion();
		var ifr = il.Util.getRegion('#ilMepPreviewContent');
		console.log(vp);
		console.log(ifr);

// fau: largeMediaPoolPreview - enlarge the modal for previewing media in media pool
		$('#ilMepPreviewContent').css("height", (vp.height - ifr.top + vp.top - 40) + "px");
        $('#ilMepPreview .modal-dialog').css("left", ('10') + "px");
        $('#ilMepPreview .modal-dialog').css("width", (vp.width - 100) + "px");
    }
// fau.
};