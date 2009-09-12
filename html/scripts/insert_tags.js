//
// Для вставки html-тегов и псевдотегов.
// Используется в Logbook при создании текста записи.
//
// @package    webacula
// @thanks http://alexking.org/projects/js-quicktags
// @author Yuri Timofeev <tim4dev@gmail.com>
// @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
//


function insert_tag(el, tag)
{
	var tag1;
	var tag2;

	switch ( tag.toLowerCase() )   {
		case "a":
			tag1 = "<a href=\"http://\">";
			tag2 = "</a>";
			break;
		case "br":
			tag1 = "";
			tag2 = "<br>";
			break;
		case "bacula_jobid=":
			tag1 = "";
			tag2 = " " + tag;
			break;
		case "logbook_id=":
			tag1 = "";
			tag2 = " " + tag;
			break;
		default :
			tag1 = "<"  + tag + ">";
			tag2 = "</" + tag + ">";
	}

	//IE support
	if (document.selection) {
		el.focus();
	    sel = document.selection.createRange();
		if (sel.text.length > 0) {
			sel.text = tag1 + sel.text + tag2;
		}
		else {
			sel.text = tag1 + tag2;
		}
		el.focus();
	}
	//MOZILLA/NETSCAPE support
	else if (el.selectionStart || el.selectionStart == '0') {
		var startPos = el.selectionStart;
		var endPos   = el.selectionEnd;
		var cursorPos = endPos;
		var scrollTop = el.scrollTop;

		el.value = el.value.substring(0, startPos)
		           + tag1
		           + el.value.substring(startPos, endPos)
		           + tag2
		           + el.value.substring(endPos, el.value.length);
		cursorPos += tag1.length + tag2.length;

		el.focus();
		el.selectionStart = cursorPos;
		el.selectionEnd = cursorPos;
		el.scrollTop = scrollTop;
	}
	else {
		var cursorPos = el.selectionEnd;
		el.value  += tag1 + tag2;
		cursorPos += tag1.length + tag2.length;
		el.focus();
	}
}




