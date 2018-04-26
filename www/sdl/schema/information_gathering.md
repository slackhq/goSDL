## Information Gathering Form Schema

Array of form fields for user information gathering  


## Schema
- __text__ Text label for the field
- __name__ Used for html name's and id's attribute. html "id" attribute will have "survey_" text appended infront of the __name__ (e.g: __name__ : projectname, will have id="survey_project_name")      
- __type__ Html type for the form. Can be "input" / "select" tags.
- __placeholder__ (optional; used for "placeholder" attribute for the "input" type)
- __options__ (only for "select" type, options for the drop down select field)
  - option1
    - text 
    - value
  - option2
  	- text
  	- value
- __required__ (optional; mark field as required, default value "false)
- __readonly__ (optional; mark field as readonly, default value "false)