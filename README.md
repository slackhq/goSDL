## goSDL

### About
goSDL is a web application tool that serves as a self-service entry point for following a Security Development Lifecycle checklist in a software development project. This tool collects relevant information about the feature, determines the risk rating, and generates the appropriate security requirements. The tool tailors the checklist to the developers’ specific needs, without providing unnecessary unrelated security requirements. Security experts can establish custom security guidance and requirements as checklist items for all developers. This checklist is used as a guide and reference for building secure software. This encourages a security mindset among developers when working on a project and can be used to easily track the completion of security goals for that project.



Goals:
- Self service : Provide self service tool for Project Lead or Developer to get a Security checklist related to their project. 
- Specific : Project Lead or Developer can pick and choose specific components related to their projects. The tool will tailor the checklist to their specific needs without providing unnecessary unrelated checklist items.
- Standardize : Security team can create a standardized risk assessment and checklist related items throughout the organization. 
- Pluggable and customized components : JSON base components that are easy to modify and update.


### General Usage

1. At the middle or near the end of completion of a project, have a technical person complete the SDL form. 

2. After the initial risk assessment is completed, please complete the Component checklist on the next page. The person filling out this form should check anything that is relevant to the code / feature (language-wise and context-wise) and uncheck anything that *they know* will always be irrelevant to the project. It's ok to check more things than you need, as there's a way to "uncheck" them later.

3. After the form is submitted there will be a JIRA ticket or Trello board created with the checklist items.

4. The goal of the SDL is to have *everything* checked off. If there is an issue with one of the items, please feel free to ask the Security team for advice and steps on how to move forward. Ideally, a fully-completed SDL checklist will expedite the security review requirement.


### Using Trello

Trello is a web-based project management application that has powerful checklist support to enable you to organize your projects. 

To use Trello as part of this tool, enable the Trello setting in the `include/.env`. You also need to generate your Trello application key from https://trello.com/app-key. When using Trello, you don't need to specify any other setting in this file.
	
	TRELLO=true
	TRELLO_API_KEY=xxxxxxxxxxxxxx
	
When the web page loads, it will require the user to authorize the app to get their access token to Trello. The output of this tool will create a link to a Trello board that contains security checklist items that can be used by the development team to follow the security guidelines.

### Using JIRA Enterprise

Currently, this tool only supports JIRA Enterprise (on Premise) and doesn't support JIRA Cloud. This is because we need the support from scriptrunner to create the additional REST API endpoint used to populate the checklist plugin. There are some Add-on dependencies required in your JIRA before using this tool:

1. [ScriptRunner for Jira](https://marketplace.atlassian.com/plugins/com.onresolve.jira.groovy.groovyrunner/server/overview)
	Required to create an additional JIRA API to update custom checklist in a ticket.
2. [Checklist for Jira](https://marketplace.atlassian.com/plugins/com.okapya.jira.checklist/server/overview)
	Enables the checklist custom field in JIRA tickets.

Settings:
1. Add a custom REST API in scriptrunner.
	- Go to "Administration" -> "Script Runner" -> "Custom Endpoint"
	- Fill out the `inline script` with the script in `scriptrunner/Scriptrunner_REST_API.groovy`

2. Create custom issue type
	- Go to "Administration" -> "Issues" -> "Add Issue Type"
	- Enter "SDL Checklist" as the name
	- Use "Standard Issue Type"
	- Update Issue Type Scheme with the custom issue type

3. Create the checklist custom field for each individual SDL component. These custom fields will be used as a placeholder template for the security checklist item.
	- Go to "Administration" -> "Issue" -> "Custom Fields" -> "add custom field"
	- Enter "SDL General" as the name. Configure the checklist custom field to not have a default option. You can also associate the new checklist custom field with a specific issue type.
	- Also note your custom field id when configuring the new custom field. You can get the id # from the URL (e.g https://your_domain.com/secure/admin/ConfigureCustomField!default.jspa?customFieldId=11909). The custom field id on this sample is "customfield_11909". This value is required when setting the `.env`.
	- Later you need to update your JIRA screen to include this new checklist custom field.
	Please reach out to your JIRA adminstrator to get more information on how to setup your project with custom checklist.

4. After setting your project, you need to set the `.env` file.
	Sample file:
	```
	JIRA_USERNAME=username
	JIRA_PASSWORD=password

	JIRA_PROJECT=PRODSEC
	JIRA_URL="https://your_domain.com"

	JIRA_GENERAL_FIELD=customfield_111
	JIRA_LANGUAGE_FIELD=customfield_112
	JIRA_NATIVE_FIELD=customfield_113
	JIRA_PARSING_FIELD=customfield_114
	JIRA_WEB_FIELD=customfield_115
	JIRA_THRIDPARTY_FIELD=customfield_116
	JIRA_LEGAL_FIELD=customfield_117
	JIRA_QA_FIELD=customfield_118
	```
	Description:
	- JIRA_USERNAME : username of you jira account. Highly recomended to use a service account in your jira 
	- JIRA_PASSWORD : your jira account password

	- JIRA_PROJECT : your JIRA project key (e.g. PRODSEC)
	- JIRA_URL : your JIRA enterprise API (e.g. "JIRA_URL="https://your_domain.com)

	- JIRA_GENERAL_FIEL : checklist custom field for SDL General (e.g. customfield_11909)
	- JIRA_LANGUAGE_FIELD : checklist custom field for SDL Language
	- JIRA_NATIVE_FIELD : checklist custom field for SDL Native Clients
	- JIRA_PARSING_FIELD : checklist custom field for SDL Parsing
	- JIRA_WEB_FIELD : checklist custom field for SDL Web
	- JIRA_THRIDPARTY_FIELD : checklist custom field for SDL Third Party and External
	- JIRA_LEGAL_FIELD : checklist custom field for SDL Legal & Policy
	- JIRA_QA_FIELD : checklist custom field for SDL QA


### Usage
1. `git clone git@github.com:slackhq/goSDL.git`

2. `composer install`

3. `cp include/env-sample include/.env` then modify the `.env` setting to fit with your enviroment.

	```
	TRELLO=true
	TRELLO_API_KEY=

	JIRA_USERNAME=
	JIRA_PASSWORD=

	JIRA_PROJECT=
	JIRA_URL=

	JIRA_GENERAL_FIELD=
	JIRA_LANGUAGE_FIELD=
	JIRA_NATIVE_FIELD=
	JIRA_PARSING_FIELD=
	JIRA_WEB_FIELD=
	JIRA_THRIDPARTY_FIELD=
	JIRA_LEGAL_FIELD=
	JIRA_QA_FIELD=
	```

4. `cd www`

5. `php -S localhost:8000`

6. Visit http://localhost:8000/sdl.php

### Usage with docker
1. Build locally: `docker build -t gosdl .`

2. Run it: `docker run -ti --rm --env-file <your dotenv> -p 8080:8080 gosdl`

3. Visit http://localhost:8000/sdl.php

### Customize the checklist contents
Follow this [guide](https://github.com/slackhq/goSDL/tree/master/www/sdl) to understand the structures of the SDL contents. 
