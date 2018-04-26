/*jslint browser: true*/
/*global $, jQuery, alert*/
 
$(document).ready(function () {
    $.ajaxSetup({ cache: false });
    console.log("ready!");
});

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

var riskAssessmentRoot = null;
var riskAssessmentCurrentQuestion = null;
var riskAssessmentCurrentQuestionNumber = 0;

function populateRiskAssessment(rootQ) {
  riskAssessmentRoot = rootQ;
  riskAssessmentCurrentQuestion = riskAssessmentRoot;
  riskAssessmentCurrentQuestionNumber = 1;
  layoutQuestion(rootQ);
}

var template_RiskQuestion = "<div class='panel panel-default riskAssessmentQuestion row'><div class='panel-heading'><h3 class='panel-title text'></h3></div><div class='panel-body'><p class='description'></p><div class='buttons btn-group' role='group'></div></div></div>";

function jqHTMLobj(html){
  return $('<div/>').html(html).contents();
}

function endSurvey(risk) {
  var questionbox = jqHTMLobj(template_RiskQuestion);
  $("#survey_risk_rating").attr('value', risk);
  $(questionbox).find(".text").first().text("Your project's Risk Ranking");
  $(questionbox).find(".description").first().html("Your project is <strong>" + risk + "</strong>!" +
'<form class="form-horizontal" onsubmit="return loadSurveyComponents();"><div class"centered text-center">' +
"<h3><input type='submit' id='riskAssessmentStartButton' class='btn btn-lg btn-success' href='#' role='button' value='Choose Components' /></h3></div></form>");

  $('.riskAssessmentQuestion:gt('+(riskAssessmentCurrentQuestionNumber-1)+')').remove();
  var newRiskBox = $(".riskQuestionnaire").append(questionbox);

  $('html, body').animate({
    scrollTop: $(newRiskBox).offset().top
  }, 100);

}

function layoutQuestion(question){
  var questionbox = jqHTMLobj(template_RiskQuestion);

  if (question.text !== undefined){
    $(questionbox).find(".text").first().text(question.text);
  }
  if (question.description !== undefined){
    $(questionbox).find(".description").first().text(question.description);
  }

  $.each(question.options, function(i, option){
    var a = document.createElement("a");
    $(a).addClass("btn btn-lg btn-default");
    $(a).text(option.text);
    $(a).attr("questionNum", riskAssessmentCurrentQuestionNumber);
    $(a).attr("data-toggle", "button");

    $(a).click(function(){
        $(this).addClass("current").siblings().removeClass("active");
      if (parseInt($(a).attr("questionNum"),10) < riskAssessmentCurrentQuestionNumber){
        riskAssessmentCurrentQuestionNumber = parseInt($(a).attr("questionNum"), 10);
      }
    });


    if (option.risk !== undefined){
      // ends survey
      $(a).click(function(){endSurvey(option.risk) });
    } else {
      $(a).click(function(){

        $('.riskAssessmentQuestion:gt('+(riskAssessmentCurrentQuestionNumber-1)+')').remove();

        riskAssessmentCurrentQuestion = option.question;
        riskAssessmentCurrentQuestionNumber += 1;
        layoutQuestion(riskAssessmentCurrentQuestion);
      });
    }

    $(questionbox).find("div.buttons").append(a);
  })


  var newRiskBox = $(".riskQuestionnaire").append(questionbox);
  $('html, body').animate({
    scrollTop: $(newRiskBox).offset().top
  }, 100);

}

function startSurvey(){
  if (validateSurvey()){
    $("#riskAssessmentStartButton").hide();
    $("#initialinstructions").hide();
    loadRiskAssessment("./sdl/riskassessment.json");
  }
  return false;
}


$('#myModal').on('show.bs.modal', function(e) {
    var value = $(".infogathering").find('*[id^="survey_jiraepic"]').val();
    $(".modal-body").find('*[id^="epic"]').text( value );
});

function loadRiskAssessment(path_to_riskassessment){
  $.getJSON(path_to_riskassessment, function( data ) {
    populateRiskAssessment(data);
  });
}

var template_courseSelectionButton = '<div class="centered text-center"><h3><input type="submit" id="riskAssessmentStartButton" onClick="finishSurvey();" class="btn btn-lg btn-success" href="#" role="button" value="Finish!" /></h3></div>';

function loadSurveyComponents(){
  $(".riskQuestionnaire").hide();
  $(".componentSurvey").html("<p>loading...<i class='glyphicon glyphicon-cog spinning'></i></p>");
  $.getJSON("sdl_api.php?method=sdl.listmodule", function( data ) {
    populateSurveyInformation(data);
  });
  return false;
}

var template_CourseModuleCheckbox = "<div class='checkbox courseModule'><label><input type='checkbox' class='courseCheckSelection' value='' tags=''><div class='checkDescription'></div><div class='checkDetails help-block'></div></label></div>";
var template_CourseModulePanel = "<div class='panel panel-default courseMoudlePanel row'><div class='panel-heading'><h3 class='panel-title text'></h3></div><div class='panel-body'><div class='buttons btn-group' role='group'></div></div></div>";

function gen_CheckboxGroup(metadata){
  var g = document.createElement("div");
  var a = jqHTMLobj(template_CourseModuleCheckbox);
  a.find(".courseCheckSelection").attr('value', metadata.filename);
  a.find(".courseCheckSelection").attr('tags', metadata.tags);
  a.find(".checkDescription").text(metadata.title);
  a.find(".checkDetails").text(metadata.description);
  a.find(".courseCheckSelection").prop("checked", metadata.checked);
  
  $(g).append(a);
  if (metadata.submodules !== undefined){
    $.each(metadata.submodules, function(i,k){
      var b = jqHTMLobj(template_CourseModuleCheckbox);
      $(b).addClass("indentedCheckbox");
      b.find(".courseCheckSelection").attr('value', k.filename);
      b.find(".courseCheckSelection").attr('tags', k.tags);
      b.find(".checkDescription").text(k.title);
      b.find(".checkDetails").text(k.description);
      b.find(".courseCheckSelection").prop("checked", k.checked);
      $(g).append(b);
    });
  }
  return g;
}

var template_RiskQuestionInstructions = "<div class='panel panel-info courseMoudlePanel row'><div class='panel-heading'><h3 class='panel-title text'>Instructions</h3></div><div class='panel-body'><p>Please start by selecting components that's relevent to your project. This will select the corresponding checklist which are relevant to project that you are working on. As a sample, you can select the “iOS” if your project related to feature / code changes in iOS mobile client. You can select as many components as you want as long as it relevant to your project.</p><p>It is highly recommended to check the checklist before submitting and also select or unselect any remaining modules/submodules that are relevant to your project (language choices, etc) even it’s not selected by the preset tags.</p><p>If you are completing this survey before a lot of code has been written (or any at all), and are unsure if some modules may apply to your feature down the road, please err on the side of inclusiveness. It's totally ok to select all of the options if you're unsure - we a way to mark the item as \”Not Applicable\” later if they end up not being relevant as your project progresses.</p></div></div>";
var template_RiskQuestionTags = "<div class='panel panel-default courseMoudleTags row'><div class='panel-body'><h3 class='panel-title text'>Choose all components that apply to your project</h3></div><div class='btn-group' data-toggle='buttons'></div></div>";

//Tags set
var tagsSet = new Set();

function populateTagsButton(survey_metadata){
  $.each(survey_metadata, function(k,v){
    $.each(v, function(ii, metadata){
      //Get tags in module
      if(metadata.tags != null){
        var moduleTag = metadata.tags.split(",");
        for(tag of moduleTag){
          tagsSet.add(tag.trim()); 
        }
      }

      if (metadata.submodules !== undefined){
        $.each(metadata.submodules, function(i,k){
          //Get tags in submodule
          if(k.tags != null){  
            var moduleTag = k.tags.split(",");
            for(tag of moduleTag){
             tagsSet.add(tag.trim()); 
            }
          }
        });
      }
    });
  });
  
  //Generate tags button
  var a = jqHTMLobj(template_RiskQuestionTags);
  for (let item of tagsSet.values()){
    var template_TagsButton = "<label class='btn btn-default'> <input type='checkbox' autocomplete='off' name=" + item + " onchange='actionPresetTags(this)'> " + item + "</label>";

    a.find(".btn-group").first().append(template_TagsButton);
  }
  $(".componentSurvey").append(a);     
}

var tagSelected = new Set();
function actionPresetTags(checkboxElem) {
  if (checkboxElem.checked) {
    tagSelected.add(checkboxElem.name);
    checkBoxPreset(tagSelected);
  } else {
    tagSelected.delete(checkboxElem.name);
    checkBoxPreset(tagSelected);
  }
}

function checkBoxPreset(tagSelected){
  var modulesObjs = $(".courseCheckSelection");
  modulesObjs.each(function(i, checkselection){
    if(checkselection.hasAttribute("tags")){
      var tags = checkselection.getAttribute("tags").split(",");
      isPreset = false;
      for(tag of tags){
        if(tagSelected.has(tag.trim())){
          isPreset = true;
          break;
        }  
      }
      if(isPreset){
        checkselection.checked = true;
      }else{
        checkselection.checked = false;          
      }
    }
    });
}


function populateSurveyInformation(survey_metadata){
  survey_metadata = survey_metadata.list;
  $(".componentSurvey").text("");
  $(".componentSurvey").append(template_RiskQuestionInstructions);
  
  populateTagsButton(survey_metadata);

  $.each(survey_metadata, function(k,v){
    //each category
    var cat_class = jqHTMLobj(template_CourseModulePanel);
    $(cat_class).find(".panel-title").text(k);
    $.each(v, function(ii, vv){
      $(cat_class).find(".buttons").append(gen_CheckboxGroup(vv));
    });
    $(".componentSurvey").append(cat_class);

  });

  $(".componentSurvey").append(jqHTMLobj(template_courseSelectionButton));

}

function validateExistence(idname, othervalidationfunc){
  var isValid = true;
  if (!$(idname).val()){
    isValid = false;
    $(idname).closest(".input-group").addClass("has-error");
  } else {
    $(idname).closest(".input-group").removeClass("has-error");
  }
  return isValid;
}

function validateSurvey(){
  var isValid = true;
  //Get field with option fields
  var objs = $('input,select').filter('[req]');

  objs.each(function(i, requiredField){
    if (!validateExistence(requiredField)) isValid = false;
  });

  return isValid;
}

function finishSurvey(){
  if (validateSurvey()){

    //Create information blob
    var information_blob = {};

    //Get information gathering
    var info = $(".infogathering");
    info.each(function(i,infogathering){
      var i = {};
      i.text = $(infogathering).find('.control-label').text();
      i.value = $(infogathering).find('*[id^="survey"]').val();
      information_blob[$(infogathering).find('*[id^="survey"]')[0].name] = i;
    });

    //Get risk assessment responses
    var responses = [];
    var riskassessment = $('.riskAssessmentQuestion');
    riskassessment.each(function(i, assessment){
      r = {};
      r.text = $(assessment).find('.text').first().text();
      r.response = $(assessment).find('.active').first().text();
      responses.push(r);
    });

    //Remove the last entry which is button
    responses.pop();

    information_blob.riskassessment = responses;
    
    //Get selected modules
    var modules = $(".courseCheckSelection:checkbox:checked").map(function() { return this.value; }).get();
    if (modules.length == 0){
      alert("Please select at least one module!");
      return;
    }

    information_blob.list_of_modules = modules;

    //Get selected tags
    information_blob.tags = Array.from(tagSelected);      

    //Get Trello info
    if(trello){
      information_blob.trello_key = Trello.key();
      information_blob.trello_token = Trello.token();
    }

    create_Ticket(information_blob);
  }
}


//Populate the information gathering form fields from json file
function generateInformationGathering(){
    var path_to_formFields = "./sdl/information_gathering.json";

    $.getJSON(path_to_formFields, function( data ) {
      populateInformationGathering(data);
    });

}


var template_informationGathering = '<div class="form-group infogathering"> <label for="" class="col-sm-4 control-label"></label> <div class="col-sm-8 input-group"> </div> </div>';

function populateInformationGathering(form_metadata){
  //Create form
  var igForm = document.getElementById("informationGatheringForm");
  var createform = document.createElement('form'); // Create New Element Form
  createform.setAttribute("class","form-horizontal");  // Setting Attribute on Form
  createform.setAttribute("onsubmit","return startSurvey();");
  createform.setAttribute("role", "form");
  igForm.appendChild(createform);


  //Populate Form fields for each json metadata
  $.each(form_metadata, function(k,v){
    var a = jqHTMLobj(template_informationGathering);

    if(v.type == "input"){
      var field = '<input type="text" name="" id="" class="form-control" placeholder="" >'  
      if(v.name == "user"){
        var field = '<span class="input-group-addon" id="sizing-addonSlackUsername">@</span>' + field;
      }
    }
    else if(v.type == "select"){
      var field = '<select name="" id="" class="form-control">'
      for ( var option of v.options){
        field = field + '<option value='+ option.value +'>'+ option.text +'</option>'
      }
    }

    a.find(".input-group").first().html(field);
    a.find(".control-label").attr('for', v.name);
    a.find(".control-label").text(v.text);
    a.find(".form-control").attr('name', v.name);
    a.find(".form-control").attr('id', "survey_"+v.name);
    a.find(".form-control").attr('placeholder', v.placeholder);
    
    if(v.required == true && v.required != undefined ){
      a.find(".form-control").attr('req' , "true");
    }

    if(v.readonly == true && v.readonly != undefined ){
      a.find(".form-control").attr('readonly' , true);
      a.find(".form-control").attr('disable' , true); 
    }

    $(createform).append(a);
  });

  //Submit Button
  var buttonDiv = document.createElement('div');
  buttonDiv.setAttribute("class", "centered text-center");
  var h3submit = document.createElement('h3');
  var submitelement = document.createElement('input');
  submitelement.setAttribute("type", "submit");
  submitelement.setAttribute("id", "riskAssessmentStartButton");
  submitelement.setAttribute("class", "btn btn-lg btn-success");
  submitelement.setAttribute("href", "#");
  submitelement.setAttribute("role", "button");
  submitelement.setAttribute("value", "Let's Go!");
  h3submit.append(submitelement);
  buttonDiv.append(h3submit);
  createform.appendChild(buttonDiv);

}

//Start generating the form
generateInformationGathering();


function create_Ticket(information_blob){

  $(".componentSurvey").hide();
  
  if(trello){
    $(".jiraCompletion").html("<p>Currently creating your new Trello Board. This may take a bit! <i class='glyphicon glyphicon-cog spinning'></i></p>");
  }else{
    $(".jiraCompletion").html("<p>Currently creating your new JIRA Ticket. This may take a bit! <i class='glyphicon glyphicon-cog spinning'></i></p>");
  }

  $.ajax({
    type: "POST",
    url: "./sdl_api.php?method=sdl.generateboard&cachebuster="+Math.round(new Date().getTime() / 1000),
    data: JSON.stringify(information_blob),
    dataType: "json",
    success: boardFinished,
    error: errorHandlerPost
  });
}

function errorHandlerPost(thing){
  $(".jiraCompletion").html("There was a catastropic error.  Sorry :(");
  console.log(thing);
}

function boardFinished(boardStatus){
  var link = boardStatus['link'];
  if (boardStatus.status == 200){

    if(trello){
      var t = jqHTMLobj(template_TrelloCreatedLinkToSlack);
    }else{
      var t = jqHTMLobj(template_JiraCreatedLinkToSlack);
    }
    $(t).find("#checklistLink").attr('href', link);
    $(t).find("#checklistLink").text(boardStatus['link']);

    $(".jiraCompletion").html(t);
    $(".jumbotron").hide();

  } else {
    $(".jiraCompletion").html("There was a catastropic error creating your jira ticket.  Sorry :(");
  }
}

var template_JiraCreatedLinkToSlack = "<p>Your JIRA ticket is created!<p>Now, please go visit the JIRA ticket: <a id='checklistLink' target=\"_blank\"></a> and follow the Instructions";
var template_TrelloCreatedLinkToSlack = "<p>Your Trello board is created!<p>Now, please go visit the Trello board: <a id='checklistLink' target=\"_blank\"></a> and follow the Instructions";