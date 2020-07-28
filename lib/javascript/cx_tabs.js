/* integration example

	<a id="CLASSNAME_DIVNAME1" class="active" href="javascript:{}" onclick="selectTab(DIVNAME1)">DIVNAME1</a>
	<a id="CLASSNAME_DIVNAME2" href="javascript:{}" onclick="selectTab(DIVNAME2)">DIVNAME2</a>
	
	<div id="DIVNAME1" class="CLASSNAME"></div>
	<div id="DIVNAME2" class="CLASSNAME"></div>
*/

function selectTab(tabName, updateLegend, formId)
{
    if (typeof updateLegend === 'undefined') {
        updateLegend = false;
    }

    if (document.getElementById(tabName).style.display != "block") {
        document.getElementById(tabName).style.display = "block";
        strClass = document.getElementById(tabName).className;
        document.getElementById(strClass+"_"+tabName).className = "active";
        if (updateLegend && typeof formId !== 'undefined') {
            var title = document.getElementById(strClass+"_"+tabName).textContent;
            var legend = document.getElementById('form-' + formId + '-tab-legend');
            legend.textContent = title;
        }
        arrTags = document.getElementsByTagName("*");
        for (i=0;i<arrTags.length;i++) {
            if(arrTags[i].className == strClass && arrTags[i] != document.getElementById(tabName)) {
                if (typeof formId === 'undefined' || formId == arrTags[i].id.split('-')[1]) {
                    arrTags[i].style.display = "none";
                }

                if (document.getElementById(strClass+"_"+arrTags[i].getAttribute("id"))) {
                    document.getElementById(strClass+"_"+arrTags[i].getAttribute("id")).className = "";
                }
            }
        }
    }
}