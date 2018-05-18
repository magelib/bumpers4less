/**
* Copyright © 2016 ITORIS INC. All rights reserved.
* See license agreement for details
*/
if (typeof ItorisHelper == 'undefined') {
	ItorisHelper = {};
}

ItorisHelper.toogleFieldEditMode = function(id, container, depends, reverse) {
	var disabled = $(id).checked;
	if (depends && !disabled) {
		if ($(depends)) {
			$(container).disabled = $(depends).checked;
		}
	} else {
		$(container).disabled = reverse ? !disabled : disabled;
	}
	return;
};

ItorisHelper.checkParentEditMode = function(parentId, id, addObserver) {
	if ($('check_' + id) && !$('check_' + id).checked || !$('check_' + id)) {
		ItorisHelper.toogleFieldEditMode(parentId, id, null, false);
	}
	if (addObserver) {
		Event.observe($(parentId), 'click', function() { ItorisHelper.checkParentEditMode(parentId, id, false, true); });
	}
};

ItorisHelper.toggleDependent = function(parent, dependent) {
	$(dependent).disabled = !$(parent).checked;
};