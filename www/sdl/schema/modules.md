## Module Schema
Notes: 
- anything bolded is a keyword that needs to be there, anything in a parens () is just a note in the schema, and not part of the spec



## Schema
- Module name _(required; this shows up in the list on the site, and is default checked included)_
  - __Category__ _(optional; this is the name of the list that this card goes into.  if this doesn't exist, the card will default to the `General` list)_
    - Name of the list that this card will go to
  - __Description__ _(optional; if this is omitted, there will not be a description)_
    - This is what shows up in the description field of the generated trello card
  - name of checklist _(this is the name of a checklist that will be generated for the card)_
    - Question on checklist 1
      - Additional justification / explanation for the item 1 _(optional; this will get rolled up into the parent checklist item as a comment)_
      - Additional justification / explanation for the item 2 _(optional; this will get rolled up into the parent checklist item as a comment)_
    - Question on checklist 2
  - Name of another checklist group
    - Item 1
    - Item 2
      - Justification A
  - tags _(optional; used to tags module for preset)_
  - minimum_risk_required _(optional; minimum acceptance risk that makes this module mark with red label on trello card)_
  - __Submodules__ _(optional; this is a list of modules that will be available on the page (as a child of the parent), but are not selected by default.  this will inherit the field __Category__, and will be represented as another card in the list, but only containing the content within the submodule (and not the parent)_
    - Submodule Name
      - __Description__
        - Submodule description
      - Checklist Name 1
        - Checklist Item 1
          - Justification A
        - Checklist Item 2

        