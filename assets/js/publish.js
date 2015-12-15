(function($){

	function string_to_slug(str) {
		str = str.replace(/^\s+|\s+$/g, ''); // trim
		str = str.toLowerCase();

		// remove accents, swap ñ for n, etc
		var from = "àáäâèéëêìíïîòóöôùúüûñç·/_,:;";
		var to   = "aaaaeeeeiiiioooouuuunc------";
		for (var i=0, l=from.length ; i<l ; i++) {
			str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
		}

		str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
			.replace(/\s+/g, '-') // collapse whitespace and replace by -
			.replace(/-+/g, '-'); // collapse dashes

		if (str.indexOf('-', str.length - 1) !== -1){
		str =str.slice(0,-1);
		}

		return str;
	}

	$(document).ready(function () {

		var $handleContainer = $('.url-entry-handle');
		var source = $('*[name="fields[' + $handleContainer.data('source') + ']"]');

		function updateHandle(){
			var handle = string_to_slug(source.val()).substring(0,$handleContainer.data('length'));
			$handleContainer.text(handle);

			$handleContainer.closest('label').find('input').val(handle);
		}

		function setPrefix(){
			var link = $handleContainer.closest('.frame').find('a').attr('href');
			if (!link) {

				var text = $handleContainer.closest('.frame').text().trim();

				$handleContainer.closest('.frame').contents().filter(function(){
					return this.nodeType === 3;
				}).remove();

				$handleContainer.before( "The handle will appear here: ");

				$handleContainer.closest('.frame').append("<span style='float:right'>"+text+"</span>");
				return;
			}
			var prefix = link.substr(0, link.indexOf($handleContainer.text()));
			$handleContainer.before(prefix);
		}

		function setPostfix(){
			var link = $handleContainer.closest('.frame').find('a').attr('href');
			if (!link) return;
			var postfix = link.substr(link.indexOf($handleContainer.text()) + $handleContainer.text().length );
			$handleContainer.after(postfix);
		}

		function syncHandle(){			
			updateHandle();
			
			$(document).on('change keyup','*[name="fields[' + $handleContainer.data('source') + ']"]',updateHandle);

			$(document).on('blur keyup','.url-entry-handle',function(){
				$(document).off('change keyup','*[name="fields[' + $handleContainer.data('source') + ']"]',updateHandle);
				if ($handleContainer.parent().find('.fa-refresh').length == 0)
					$handleContainer.parent().append('<i class="fa fa-refresh" style="color:#111;margin-left:5px;cursor:pointer;"></i>');
			})
		}

		//convert inserted text to handle
		$(document).on('blur','.url-entry-handle',function(){
			var handle = string_to_slug($handleContainer.text()).substring(0,$handleContainer.data('length'));
			$handleContainer.text(handle);

			$handleContainer.closest('label').find('input').val(handle);
		})

		//convert inserted text to handle
		$(document).on('click','.field-entry_url i.fa-refresh',function(){
			syncHandle();
			$(this).remove();
		})

		if ($handleContainer.hasClass('empty')){
			syncHandle();
			setPrefix();
			setPostfix();
		} else if ($handleContainer.data('sync') && $handleContainer.text() == string_to_slug(source.val()).substring(0,$handleContainer.data('length'))){
			//if has sync enabled and the text matches the source field
			syncHandle();
		} else {
			$handleContainer.parent().append('<i class="fa fa-refresh" style="color:#111;margin-left:5px;cursor:pointer;"></i>');
		}

		if ($handleContainer.length > 0){
			//temporary css
			$handleContainer.closest('label').contents().filter(function(){
			    return this.nodeType === 3;
			}).remove();
			$handleContainer.closest('label').css('margin-top','-1.5rem');

			$handleContainer.css('cursor','pointer');
			$handleContainer.css('color','#000');
			$handleContainer.parent().css('color','#888');
			$handleContainer.closest('.frame').find('a').css('float','right');
		}

	});

})(jQuery);