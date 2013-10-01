
// Coded by George Merlocco 
//	 for http://androxfer.in
//


// Javascript Open in New Window (validation workaround)
//
function externalLinks() {
 if (!document.getElementsByTagName) return;
 var anchors = document.getElementsByTagName("a");
 for (var i=0; i<anchors.length; i++) {
   var anchor = anchors[i];
   if (anchor.getAttribute("href") &&
       anchor.getAttribute("rel") == "external")
     anchor.target = "_blank";
 }
}

function fixColumnHeight() {
	var pageHeight = jQuery(window).height();
	var pageWidth = jQuery(window).width();
	var headerHeight = pageHeight - 346;
	if (pageWidth > 760) {
		$('.andro-column ul').css({maxHeight: headerHeight + 'px' });
	} else {
	}
}

function fixFilesWidth() {
	var pageWidth = jQuery(window).width();
	var devFolders = $('#devs').outerWidth() + $('#folders').outerWidth();
	var fileTable = pageWidth - devFolders;
	if (pageWidth > 1280) {
		$('#files table').css({width: '960px' });
	} else if (pageWidth < 760) {
		$('#files table').css({width: '100%' });
	} else {
		$('#files table').css({width: fileTable - 3 + 'px' });
	}
}

// Execute when the DOM is ready
//
$(document).ready(function() {	

	fixColumnHeight();
	fixFilesWidth();
	externalLinks();
	
	$(window).resize(function() {
		fixColumnHeight();
		fixFilesWidth();
	});	
	
	$("#filelisting").tablesorter({
		sortList: [[3,1]],
		headers: { 
            0: { 
                sorter: false 
            }, 
            1: { 
                sorter: false 
            } 
        },
		cssAsc: "headerSortUp",
		cssDesc: "headerSortDown"
	});	
	
});
