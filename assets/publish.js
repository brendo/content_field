(function($) {
	$(document).ready(function() {
		$("ol.content-field-duplicator")
			.symphonyDuplicator({
				orderable: true,
				collapsible: true
			})
			.on('constructshow.duplicator', function() {
				$('.tags', this).symphonyTags();
			});

		$('div.field.field-content')
			.on('change autosize', 'textarea.size-auto', function() {
				var padding = this.offsetHeight - this.clientHeight;

				this.style.height = 'auto';
				this.style.height = (this.scrollHeight + padding) + 'px';
			})

			.on('cut paste drop keydown', 'textarea.size-auto', function() {
				var $textarea = $(this);

				setTimeout(function() {
					$textarea.trigger('autosize');
				}, 0);
			})

			.find('textarea.size-auto')
			.trigger('autosize');
	});
})(jQuery);