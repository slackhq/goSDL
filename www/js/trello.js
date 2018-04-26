var authenticationSuccess = function() { console.log("Successful authentication"); };
var authenticationFailure = function() { alert("Failed authentication to trello.  Please refresh the page to attempt to authorize again"); };

Trello.authorize({
  type: "redirect",
  name: "goSDL",
  persist: "true",
  scope: {
    read: true,
    write: true},
  expiration: "1hour",
  authenticationSuccess,
  authenticationFailure
});

if (Trello.authorized()){
  //we're going to redirect anyways, so make sure to not trigger more JS errors
  trello_populateUserTeams();
}


function errorHandler(thing){
  console.log("Error happened", thing);
}

function trelloLoadTeam(teamresp){
  $("#survey_trello_team").append($('<option/>', {
        value: teamresp.id,
        text : teamresp.displayName
  }));
}

function trello_getUserDeetsSuccess(obj){
  //iterate over teams user is subscribed to
  $.each(obj.idOrganizations, function(i, obj){
    Trello.organizations.get(obj, trelloLoadTeam, errorHandler);
  });  
}

function trello_populateUserTeams(){
  Trello.members.get("me", trello_getUserDeetsSuccess, trello_maybeExpiredAuth);
}

function trello_maybeExpiredAuth(respo){
  if (respo.status == 401){
    Trello.deauthorize();
    location.reload();
  } else {
    console.log(respo);
  }
}
