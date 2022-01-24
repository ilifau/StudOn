/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

il.ExcManagement = {
	ajax_url: '',
	
	init: function (url) {
		this.ajax_url = url;		
		
		$('form[id*="form_excasscomm_"]').submit(function(event) {
			var form_id = $(this).attr("id");
			var form_id_parts = form_id.split("_");
			var ass_id = form_id_parts[2];
			var member_id = form_id_parts[3];					
			var modal_id = form_id_parts[1] + "_" + form_id_parts[2] + "_" + form_id_parts[3];			
			if(ass_id && member_id)	{

				$("#" + modal_id).modal("hide");

				var comment = $('#lcomment_'+ass_id+'_'+member_id).val();

				// fau: exPlag - set form values
				var plag_flag;
				var plag_comment;

				if ($('#plag_toggle_'+ass_id+'_'+member_id).is(':checked')) {
					plag_flag = $('#plag_flag_'+ass_id+'_'+member_id).val();
					plag_comment = $('#plag_comment_'+ass_id+'_'+member_id).val();
				}
				else {
					plag_flag = 'none';
					plag_comment = '';
				}
				// fau.

				$.ajax({
					url: il.ExcManagement.ajax_url,
					dataType: 'json',
					type: 'POST',
					data: {
						ass_id: ass_id,
						mem_id: member_id,
						comm: comment,
						// fau: exPlag - add data to ajax call
						plag_flag: plag_flag,
						plag_comment: plag_comment
						// fau.
					},
					success: function (response) {		
						$("#"+form_id.substr(5)+"_snip").html(response.snippet);

						// fau: exPlag - extended row update after saving
						if (response.set_plag) {
							$("#"+form_id.substr(5)+"_plag_info").html(response.plag_info);
							$("#"+form_id.substr(5)+"_plag_comment").html(response.plag_comment);
							if (response.plag_flag === 'detected') {
								$("#"+form_id.substr(5)+"_effective_status").show();
								$("#"+form_id.substr(5)+"_effective_mark").show();
							}
							else {
								$("#"+form_id.substr(5)+"_effective_status").hide();
								$("#"+form_id.substr(5)+"_effective_mark").hide();
							}
						}
						// fau.
					}
				}).fail(function() {

				});
			}			

			event.preventDefault();
		});
	},
	
	showComment: function (id) {
		$("#" + id).modal('show');
		return false;
	}
}