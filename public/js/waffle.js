var tabs;
var taskUrl;
$(function() {
	tabs = $("#taskTabs").tabs();
	$(".subcategory").effect("blind");
});

function toggleInNeed(inNeed) {
	if (!inNeed) {
		$("#taskTabs").removeClass("inNeed");
		$(".doneButton").show();
		$(".inNeedButton").hide();
	} else {
		$("#taskTabs").addClass("inNeed");
		$(".doneButton").hide();
		$(".inNeedButton").show();
	}
}

function toggleCategory(catId) {
	var curSrc = $("#disclosure_"+catId)[0].src.substring($("#disclosure_"+catId)[0].src.length - 24);
	if (curSrc == "/images/triangle-down.png") {
		$("#disclosure_"+catId)[0].src = "/images/triangle-right.png";
	} else {
		$("#disclosure_"+catId)[0].src = "/images/triangle-down.png";
	}
	$("#subcat-"+catId+"-tasks").toggle("blind");
}

function markTaskDone(taskId, red) {
	var extra = '';
	var extraId = "#extra-"+taskId;
	if (red) {
		extraId = "#red-extra-"+taskId;
	}
	if ($(extraId).length > 0) {
		extra = $(extraId).val();
	}
	var today = $("#date").val();
	var xhrPost = $.ajax({
		type: "POST",
		url: taskUrl,
		data: {action: 'did', id: taskId, extra: extra, doneOn: today},
		success: function (data) {
			if (data.error) {
				// TODO: errors
			} else {
				$("#last-"+taskId).text(data.doneOn+' by '+data.doneBy);
				$("#doneButton-"+taskId).attr('disabled','disabled');
				$("#red-last-"+taskId).text(data.doneOn+' by '+data.doneBy);
				$("#redDoneButton-"+taskId).attr('disabled','disabled');
				$("#task-"+taskId).removeClass('red');
				$("#red-task-"+taskId).removeClass('red');
			}
		},
		dataType: 'json'
	});
}

function markTaskInNeed(taskId) {
	var xhrPost = $.ajax({
		type: "POST",
		url: taskUrl,
		data: {action: 'inNeed', id: taskId},
		success: function (data) {
			if (data.error) {
				// TODO: errors
			} else if (data.inNeed) {
				$("#task-"+taskId).addClass('taskInNeed');
				$("#task-"+taskId).addClass('ui-state-highlight');
				$("#inNeedButton-"+taskId).text('Doesn\'t Need Doing');
			} else {
				$("#task-"+taskId).removeClass('taskInNeed');
				$("#task-"+taskId).removeClass('ui-state-highlight');
				$("#inNeedButton-"+taskId).text('Needs Doing Now');
			}
		},
		dataType: 'json'
	});
}