import com.onresolve.scriptrunner.runner.rest.common.CustomEndpointDelegate
import groovy.json.JsonBuilder
import groovy.transform.BaseScript
import groovy.json.JsonSlurper
import javax.ws.rs.core.MultivaluedMap
import javax.ws.rs.core.Response

import com.atlassian.jira.component.ComponentAccessor
import com.atlassian.jira.issue.CustomFieldManager
import com.atlassian.jira.issue.Issue
import com.atlassian.jira.ComponentManager
import com.atlassian.jira.issue.IssueManager
import com.atlassian.jira.issue.ModifiedValue
import com.atlassian.jira.issue.util.DefaultIssueChangeHolder
import org.codehaus.jackson.map.ObjectMapper

@BaseScript CustomEndpointDelegate delegate

addChecklistItems(httpMethod: "POST", groups: ["jira-software-users"]) { MultivaluedMap queryParams, String body ->

    // Retrieve POST data
    def mapper = new ObjectMapper()
    def inputJson = mapper.readValue(body, Checklist)

	// Check input data 
    assert inputJson.issue_id // must provide issue_id
    assert inputJson.customfield_id // must provide customfield_id
    assert inputJson.items // must provide items
   
    IssueManager issueManager = ComponentAccessor.getIssueManager();
    Issue issue = issueManager.getIssueObject(inputJson.issue_id)
    def customFieldManager = ComponentAccessor.getCustomFieldManager();
    def cf = customFieldManager.getCustomFieldObject(inputJson.customfield_id);
    def cfType = cf.getCustomFieldType()
    
    def checklist_items = []
    def itemtoAdd
    def value
    def rank = 1
    // Iterate item to add
    inputJson.items.each { item ->
        itemtoAdd = '{"name" : "' + item + '", "checked" : false, "mandatory" : false, "rank" : "' + rank + '"}'
        value = cfType.getSingularObjectFromString(itemtoAdd)
        checklist_items.add(value)
        rank++
    }

    issue.setCustomFieldValue(cf, checklist_items)

    def changeHolder = new DefaultIssueChangeHolder()
    cf.updateValue(null, issue, new ModifiedValue(issue.getCustomFieldValue(cf), checklist_items),changeHolder)

    // log.warn(issue.getCustomFieldValue(cf))

    return Response.ok(new JsonBuilder([issue_id: inputJson.issue_id]).toString()).build();
}

class Checklist {                       

    Long issue_id                      
    String customfield_id
    String[] items

}