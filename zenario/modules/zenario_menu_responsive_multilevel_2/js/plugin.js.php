<?php

require CMS_ROOT. 'zenario/libs/manually_maintained/mit/slimmenu/jquery.slimmenu.min.js';

echo "\n/**/\n";

?>

//Maybe I should disable the resizeWidth in 

zenario_menu_responsive_multilevel_2.init = function(id, resizeWidth, easingEffect, animSpeed) {
	$('#' + id)
		.css('visibility', 'visible')
		.slimmenu({
			resizeWidth: 99999,
			collapserTitle: ' ',
			easingEffect: easingEffect || 'easeInOutQuint',
			animSpeed: animSpeed || 'medium',
			indentChildren: true,
			childrenIndenter: ' '
		});
};
