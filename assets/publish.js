(function($) {
	$(document).ready(function() {
		$("ol.content-field-duplicator")
			.symphonyDuplicator({
				orderable: true,
				collapsible: true
			})
			.on('constructshow.duplicator', function() {
				$('.tags', this).symphonyTags();
				$('textarea.size-auto', this)
					.autoResize({
						animate: 100,
						extraSpace: 25
					});
			});

		$('ol.content-field-duplicator textarea.size-auto')
			.autoResize({
				animate: 100,
				extraSpace: 25
			});
	});
})(jQuery);