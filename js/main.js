
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
	var headerHeight = jQuery(".header-container").outerHeight();
	var footerHeight = jQuery(".footer-container").outerHeight();
	var filesTableHeight = jQuery("#filelisting").outerHeight();
	var h2Height = jQuery("#devs h2").outerHeight();
	var listHeight = pageHeight - headerHeight - footerHeight - h2Height - 31; // number is the padding in the UL
	if (pageWidth > 769) {
		$('.andro-column ul').css({maxHeight: listHeight + 'px' });
	}
}

function fixFilesWidth() {
	var pageWidth = jQuery(window).width();
	var devFolders = $('#devs').outerWidth() + $('#folders').outerWidth();
	var fileTable = pageWidth - devFolders;
	if (pageWidth > 1280) {
		$('#files table').css({width: '960px' });
	} else if (pageWidth < 769) {
		$('#files table').css({width: '100%' });
	} else {
		$('#files table').css({width: fileTable - 3 + 'px' });
	};
	fixColumnHeight();
}

function fixDownloadFilesWidth() {
	var pageWidth = jQuery(window).width();
	var devFolders = $('#devs').outerWidth();
	var fileTable = pageWidth - devFolders;
	if (pageWidth > 1280) {
		$('#links').css({width: '960px' });
	} else if (pageWidth < 769) {
		$('#links').css({width: '100%' });
	} else {
		$('#links').css({width: fileTable - 10 + 'px' });
	};
	fixColumnHeight();
}

// Execute when the DOM is ready
//
$(document).ready(function() {	

	fixFilesWidth();
	fixDownloadFilesWidth();
	externalLinks();
	
	$(window).resize(function() {
		fixFilesWidth();
		fixDownloadFilesWidth();
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
