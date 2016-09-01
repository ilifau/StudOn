/* fim: [webform] new script. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Restores the values of the given form data objects into the according forms
*/
function restoreForms(formsdata) {

	for(f=0; f < formsdata.length; f++) {
   
		var formName = formsdata[f].id;
		var action = formsdata[f].action;
		var fields = formsdata[f].fields;
		
		// Unescape
		for (k in fields) {
			val = fields[k];
                        try{ val = decodeURIComponent(val);}
                        catch (e) { val = unescape(val);}
                        fields[k] = val;
		}

		var formobject = document.getElementById(formName);
		
		// set the action
		formobject.action = action;

		// Define variables for the indexes of textfields/areas with the same "name"attribute
		var textfieldindex = new Object;

		for(i=0; i < formobject.elements.length; i++) {

			var elem = formobject.elements[i];
			var elemtype = formobject.elements[i].type;
			var elemname = formobject.elements[i].name;

			if (fields[elemname] == null)
				continue;

			if(elemtype == 'select-one') {
				for (j=0; j < elem.options.length; j++) {
					if (elem.options[j].value && elem.options[j].value == fields[elemname])
					{
						elem.selectedIndex = j;
					}
					else if (elem.options[j].text == fields[elemname])
					{
						elem.selectedIndex = j;
					}
				}
			}

			else if(elemtype == 'select-multiple') {
				var field = array2assoc(fields[elemname]);
				for (j=0; j < elem.options.length; j++) {
					if (typeof(field) == 'object') {
						if (field[elem.options[j].value] != null) {
							elem.options[j].selected = true;
						}
					}
					else if (typeof(field) == 'string') {
						if (elem.options[j].value == field)
						{
							elem.options[j].selected = true;
						}
					}
				}
			}

			else if(elemtype == 'checkbox') {
				var field = array2assoc(fields[elemname]);
				if (typeof(field) == 'string' && field == elem.value) {
					elem.checked = true;
				}
				else if (typeof(field) == 'object') {
					if (field[elem.value] != null)
						elem.checked = true;
				}
			}

			else if(elemtype == 'radio' && fields[elemname] == elem.value) {
				elem.checked = true;
			}

			else if(elemtype == 'text' || elemtype == 'textarea') {
				var field = fields[elemname];
				if (typeof(field) == 'string') {
					elem.value = field;
				}
				else if (typeof(field) == 'object') {
					if (typeof(textfieldindex[elemname]) == 'undefined')
						textfieldindex[elemname] = -1;
					textfieldindex[elemname]++;
					if (field[textfieldindex[elemname]] != null)
						elem.value = field[textfieldindex[elemname]];
				}
			}

		} // formobject.elements

	}  // formsdata
}

/**
* Convert a linear array to an assoc array
*
* Key will be the content, value will be the numeric index.  
* Leave all other values as they are.
*/
function array2assoc(arr) {
	var obj = new Object;
	var i;
	if (typeof(arr) != 'object' || typeof(arr.length) == 'undefined')
		return arr;

	for (i=0; i < arr.length; i++) {
		obj[arr[i]] = i;
	}
	return obj;
}
