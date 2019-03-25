<?php
	include("lib_jira.php");
	$dotenv = new Dotenv\Dotenv(__DIR__);
	$dotenv->load();
    error_reporting(0);

	#
	# Retrieves JSON summary of all available modules
	# 
	function sdl_module_list(){
		$list = array();
		foreach (_sdl_valid_modules() as $filename){
			$parsed = json_decode(file_get_contents($filename), true);
			$category = (isset($parsed['category'])) ? $parsed['category'] : "General";
			if (@!is_array($list[$category])){
				$list[$category] = array();
			}

			$infoobj = array(
				"filename" => $filename,
				"title" => $parsed['title'],
				"description" => $parsed['description'],
				"tags" => $parsed['tags'],
			);

			if (@is_array($parsed['submodules'])){
				$infoobj['submodules'] = array();
				foreach ($parsed['submodules'] as $submod){
					$infoobj2 = array(
						"filename" => $filename.md5($filename.$submod['title']),
						"title" => $submod['title'],
						"description" => $submod['description'],
					);

					if (isset($submod['tags'])){
						$infoobj2['tags'] = $submod['tags'];
					}

					$infoobj['submodules'][] = $infoobj2;
				}
			}

			$list[$category][] = $infoobj;
		}

		array_multisort($list);

		$results = array(
			'ok'  => true,
			'list' => $list,
		);

		return $results;
	}

	#
	# Generate the SDL JIRA tickets
	#
	function sdl_generate($input){

		# Retrieve information
		$project_name = $input["project_name"]["value"];
		$user = $input["user"]["value"];
		$risk_rating = $input["risk_rating"]["value"];
		$jiraEpicId = $input["jiraepic"]["value"];
		$list_of_modules = $input["list_of_modules"];


		#Check Trello or Jira
		if (strtolower(getenv('TRELLO')) === 'true'){
			# -- Trello -- 
 
			#Get Trello information
			$trello_team = $input["trello_team"]["value"];
		    $trello_key = $input["trello_key"];
		    $trello_token = $input["trello_token"];

			$board = _sdl_generate_trello_board($user, $project_name, $risk_rating, $list_of_modules);
			$created = null;
			try {
			  $created = _sdl_trello_board_create($board, $trello_token, $trello_key, $trello_team);
			} catch (Exception $e){
			  print_r($e);
			  $created = -1;
			}
			if ($created == -1){
			   return array(
					"ok" => false,
					"status" => 200,
				);
			} else {
			   $trellolink = "https://trello.com/b/" . $created;

			   return array(
					"ok" => true,
					"status" => 200,
					"name" => "SDL: ".$project_name,
					"link" => $trellolink,
				);
			}
		}else{
			# -- JIRA -- 
			# Check if the given issue is an EPIC or non-EPIC
			$isEpic = _sdl_check_jira_type($jiraEpicId);

			# Create checklist ticket
			$ret = _sdl_create_jira_checklist_ticket($jiraEpicId, $risk_rating, $list_of_modules, $project_name, $isEpic);
			if (! $ret['ok']){ return $ret;
			}else{
				$jiraChecklist = $ret["response"];
				$ret = _sdl_populate_checklist($ret["response"], $risk_rating);
			}

			# Create ticket for seurity team
			$ret = _sdl_create_jira_ticket_prodsec($input, $jiraChecklist["key"]);
			if (! $ret['ok']) return $ret;

			return array(
				"ok" => true,
				"status" => 200,
				"name" => "SDL: ".$project_name,
				"link" => getenv('JIRA_URL') . "/browse/". $jiraChecklist["key"],
			);
		}
	}

	#
	#  Returns a list of valid (and includable) modules
	#
	function _sdl_valid_modules(){
		return _sdl_rsearch("../www/sdl/modules/", "/.*\.json/");
	}


	function _sdl_valid_choosable_modules(){
		$list = array();

		foreach (_sdl_valid_modules() as $filename){
			$parsed = json_decode(file_get_contents($filename), true);
			$list[] = $filename;

			if (isset($parsed['submodules']) && is_array($parsed['submodules'])){
				foreach ($parsed['submodules'] as $submod){
					$list[] = $filename.md5($filename.$submod['title']);
				}
			}
		}

		return $list;
	}

	#
	# Recursively searches a folder for a matching regex file pattern
	#
	function _sdl_rsearch($folder, $pattern){
		$dir = new RecursiveDirectoryIterator($folder);
		$ite = new RecursiveIteratorIterator($dir);
		$files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
		$fileList = array();

		foreach ($files as $file){
			$fileList = array_merge($fileList, $file);
		}
		return $fileList;
	}

	#
	# Check if the JIRA issue is an EPIC
	#
	function _sdl_check_jira_type($epic){

		# Regex to check EPIC key
		if (preg_match("/^[A-Z0-9]+-[0-9]/", $epic)){
			$ret = jira_new_issue_get($epic);
			if ($ret['ok']){
				if ($ret['body']['fields']['issuetype']['name'] === "Epic"){
					return true;
				}else{
					return false;
				}
			}
		}

		return false;
	}


	#
	# Function to create SDL checklist jira ticket
	#
	function _sdl_create_jira_checklist_ticket($epicId, $risk_rating, $list_of_modules, $project_name, $isEpic){

		$project_risk = _sdl_determineRiskValue($risk_rating);


		$our_modules = array_intersect(_sdl_valid_choosable_modules(), $list_of_modules);
		$lists = array();

		foreach ($our_modules as $filename){
			if (!in_array($filename, _sdl_valid_modules())){
				continue; //skip for the submodule identifiers
			}
			$parsed = json_decode(file_get_contents($filename), true);
			$category = (isset($parsed['category'])) ? $parsed['category'] : "General";
			if (!isset($lists[$category])){
				$lists[$category] = array();
			}

			$infoobj = array(
				"title" => $parsed['title'],
				"description" => $parsed['description'],
				"minimum_risk_required" => $parsed['minimum_risk_required'],
				"lists" => _sdl_get_lists_from_sdl($parsed['questions']),
			);
			$submodules = array();

			if (isset($parsed['submodules'])){
				foreach ($parsed['submodules'] as $submod){
					$infoobj2 = array(
					"filename" => $filename.md5($filename.$submod['title']),
					"title" => $submod['title'],
					"description" => $submod['description'],
					"minimum_risk_required" => $submod['minimum_risk_required'],
					"lists" => _sdl_get_lists_from_sdl($submod['questions']),
					);

					if (in_array($infoobj2['filename'], $our_modules)){
						$submodules[] = $infoobj2;
					}
				}
			}

			$lists[$category][] = $infoobj;
			foreach ($submodules as $submod){
				$lists[$category][] = $submod;
			}
		}

		#
		#  Normal Editor
		#  
		$category_html = '{panel:title=(on) Instruction|bgColor=#FFFFCE}
		Please complete the above checklist in the "*SDL*" tabs.
		Mark the items to complete the checklist. Once all of the cheklist is completed then move this ticket\'s status to "*Done*" and enter resolution "*Done*" 
		If you end up over-scoping and have too many checklist, and you end up not needing it, you can mark the item as "*Not Applicable*" by putting the "*N/A*" status to the checklist items.
		If you have question completing a specific checklist, feel free to reach out to security team for any help or pointers.

		Your initial risk assessment questionnaire came back with a rating of *'. $risk_rating .'*.
		Due to this risk rating, you must complete the items that are tagged with a star (*).
		{panel}

		h1. Component Selected'. "\n";
		# Create HTML
		foreach ($lists as $category => $cards){
			$category_html = $category_html . '*' . $category . '*';
			$category_html = $category_html . "\n";

			$card_html = "";
			foreach ($cards as $card){
				# Add label to cards depeding on risk level
				if (isset($card["minimum_risk_required"])){
				$card_risk = _sdl_determineRiskValue($card["minimum_risk_required"]);

					if ($project_risk >= $card_risk){
						$card_html = $card_html . '* *' . $card['title'] . '* (*)' . "\n";
					}else{
						$card_html = $card_html . '* *' . $card['title'] . '*' . "\n";
					}
				}

				$card_html = $card_html . $card['description'] . "\n\n";
			}

			$category_html = $category_html . $card_html;
		}

		if ($isEpic){

			$data = array(
				'fields' => array(
					'project' => array(
						'key' => getenv('JIRA_PROJECT'),
					),
					'summary' => 'SDL Checklist - ' . $project_name,
					'description' => $category_html,
					"issuetype" => array(
						"name" => 'SDL Checklist',
					),
				),
			);
		}else{
			$data = array(
				'fields' => array(
					'project' => array(
						'key' => getenv('JIRA_PROJECT'),
					),
					'summary' => 'SDL Checklist - '. $project_name,
					'description' => $category_html,
					"issuetype" => array(
						"name" => 'SDL Checklist',
					),
				),
			);
		}

		$ret = jira_new_issue_create($data);
		if (! $ret['ok']) return $ret;
		$response = $ret['body'];
		$response["list"] = $lists;

		return  array(
			"ok" => true,
			"response" => $response,
		);
	}


	#
	#  Risk level to integer for minimum risk requirement comparison
	#
	function _sdl_determineRiskValue($risk_rating){
		$normalize = trim(strtolower($risk_rating));
		if ($normalize === "low risk") return 1;
		if ($normalize === "medium risk") return 2;
		if ($normalize === "high risk") return 3;
		return 4;
	}


	#
	# Retrive questions list from metadata
	#
	function _sdl_get_lists_from_sdl($parsed_questions){
		$lists = array();
		foreach ($parsed_questions as $question_cat => $question_arr){
			if (!array_key_exists($question_cat, $lists)){
				$lists[$question_cat] = array();
			}

			foreach ($question_arr as $question){
				if (is_string($question)){
					$lists[$question_cat][] = $question;
				}else{
					# Question is an object (with explanation)
					$text = $question['text'];
					if ($question['explanation']){
						$text .= " ({$question['explanation']})";
					}
					$lists[$question_cat][] = $text;
				}
			}
		}
		return $lists;
	}


	#
	# Populate the checklist item with the selected questions
	#
	function _sdl_populate_checklist($jiraChecklist, $risk_rating){

		$issue_id = $jiraChecklist["id"];
		$lists = $jiraChecklist["list"];
		$project_risk = _sdl_determineRiskValue($risk_rating);

		foreach ($lists as $category => $cards){
			$customfield_id = _sdl_getCustomfield($category);
			$checklist_items=array();

			foreach ($cards as $card){
				//Add label to cards depeding on risk level
				if (isset($card["minimum_risk_required"])){
					$card_risk = _sdl_determineRiskValue($card["minimum_risk_required"]);
				}

				if (isset($card['lists'])){
					foreach ($card['lists'] as $questiongroup => $questionlist){
						foreach ($questionlist as $questioncheckbox){
							if ($project_risk >= $card_risk){
								$checklist_items[] = '**' . $card['title'] . '** (*) - '. $questioncheckbox;
							}else{
								$checklist_items[] = '**' . $card['title'] . '** - '. $questioncheckbox;
							}
						}
					}
				}
			}

			# Call REST API
			$ret = jira_new_issue_add_checklistitem($issue_id, $customfield_id, $checklist_items);
			if (! $ret['ok']) return $ret;

		}

		return array(
			"ok" => true,
		);
	}

	#
	# Get Custom Fields ID
	#
	function _sdl_getCustomfield($category){
		switch($category){
			case 'General':
				return getenv('JIRA_GENERAL_FIELD');
				break;
			case 'Language':
				return getenv('JIRA_LANGUAGE_FIELD');
				break;
			case 'Native Clients':
				return getenv('JIRA_NATIVE_FIELD');
				break;
			case 'Parsing':
				return getenv('JIRA_PARSING_FIELD');
				break;
			case 'Web':
				return getenv('JIRA_WEB_FIELD');
				break;
			case 'Third-Party & External':
				return getenv('JIRA_THRIDPARTY_FIELD');
				break;
			case 'Legal & Policy':
				return getenv('JIRA_LEGAL_FIELD');
				break;
			case 'QA':
				return getenv('JIRA_QA_FIELD');
				break;
		}
	}

	#
	# Function to create jira ticket using the jira API for Prodsec team to review the SDL process
	#
	function _sdl_create_jira_ticket_prodsec($input, $jiraChecklistId){
		$project_name = $input["project_name"]["value"];
		$risk_rating = $input["risk_rating"]["value"];

		# Create text info blob for jira
		$info_blob = "Information Gathering \n";
		$info_blob = $info_blob . "========================== \n";

		foreach ($input as $key=>$value){
			if (is_array($value) && isset($value["value"])){
				if ($value["text"] != "Trello Team"){
					$info_blob = $info_blob . $value["text"] . " : " . $value["value"] . "\n";
				}
			}
		}

		$selected_tags = "Selected Components: ";
		for ($i = 0; $i < count($input["tags"]); ++$i){
			if ($i == 0){
				$selected_tags = $selected_tags . $input["tags"][$i];
			}else{
				$selected_tags = $selected_tags . ", " . $input["tags"][$i];
			}
		}

		$info_blob = $info_blob . $selected_tags . " \n" ;

		$info_blob = $info_blob . "\nRisk Assessment Responses \n";
		$info_blob = $info_blob . "========================== \n";
		foreach ($input["riskassessment"] as $value){
			$info_blob = $info_blob . $value["text"] . " \n" . $value["response"] . "\n\n";
		}

		$data = array(
			'fields' => array(
				'project' => array(
					'key' => 'PRODSEC',
				),
				'labels' => array('sdl'),
				'components' => array(
					array(
						'name' => 'Identification',
					),
				),
				'summary' => $risk_rating. ': Review: ' . $project_name,
				'description' => "Review for {$project_name}\nJIRA SDL Checklist at : {$jiraChecklistId}\n\n{$info_blob}",
				"issuetype" => array(
					"name" => 'Task',
				),
			),
		);

		$ret = jira_new_issue_create($data);
		if (! $ret['ok']) return $ret;

		$response = $ret['body'];

		return  array(
			"ok" => true,
			"response" => $response,
		);

	}


	/**
	 * Generate Trello board
	 */
	function _sdl_generate_trello_board($user, $project_name, $risk_rating, $modules){
	  $lists = array();

	  $our_modules = array_intersect(_sdl_valid_choosable_modules(), $modules);

	  $lists['Instructions and Information'] = array();
	  $lists['Instructions and Information'][] = _sdl_generate_project_card($user, $project_name, $risk_rating);
	  $lists['Instructions and Information'][] = _sdl_generate_instruction_card($project_name);


	  foreach ($our_modules as $filename) {
	    if (!in_array($filename, _sdl_valid_modules())){
	      continue; //skip for the submodule identifiers
	    }
	    $parsed = json_decode(file_get_contents($filename), true);
	    $category = (isset($parsed['category'])) ? $parsed['category'] : "General";
	    if (!isset($lists[$category])) {
	      $lists[$category] = array();
	    }

	    $infoobj = array(
	      "title" => $parsed['title'],
	      "description" => $parsed['description'],
	      "minimum_risk_required" => $parsed['minimum_risk_required'],
	      "lists" => _sdl_get_lists_from_sdl($parsed['questions'])
	    );
	    $submodules = array();

	    if (isset($parsed['submodules'])){
	      foreach ($parsed['submodules'] as $submod){
	        $infoobj2 = array(
	          "filename" => $filename.md5($filename.$submod['title']),
	          "title" => $submod['title'],
	          "description" => $submod['description'],
	          "minimum_risk_required" => $submod['minimum_risk_required'],
	          "lists" => _sdl_get_lists_from_sdl($submod['questions'])
	        );

	        if (in_array($infoobj2['filename'], $our_modules)){
	          $submodules[] = $infoobj2;
	        }

	      }
	    }

	    $lists[$category][] = $infoobj;
	    foreach ($submodules as $submod){
	      $lists[$category][] = $submod;
	    }

	  }

	  $lists['Not Applicable'] = array();

	  return array(
	    "user" => $user,
	    "project_name" => $project_name,
	    "risk_rating" => $risk_rating,
	    "board" => $lists);
	}



	/**
	 * Generate Trello project card content
	 */
	function _sdl_generate_project_card($user, $project_name, $risk_rating){
	    return array(
	      "title" => "Risk Assessment and Instructions",
	      "description" => "Your initial risk assessment questionnaire completed by $user came back with a rating of **$risk_rating**.\n\n".
	                        "Due to this risk rating, you must complete the components that are tagged with a red label on this trello board\n\n".
	                        "Please contact the security team if you have any questions about the items on this trello board. We're happy to help out!\n\n");
	}

	/**
	 * Generate Trello instruction card content
	 */
	function _sdl_generate_instruction_card($project_name){
	  return array(
	    "title" => "(Optional) Link me to Slack!",
	    "description" => "To maximize the SDL process, please link this trello board to your slack feature channel. This is important for your team to see the ongoing completion of the SDL process and enable them to comment, or discuss about the completion of the SDL as it's happening.\n",
	    "lists" => array(
	      "How to link this Trello board to Slack" => array(
	          "Go to the [trello integration](https://my.slack.com/services/new/trello) page on slack to create a new Trello integration",
	          "Authenticate your trello account to slack if you have not done so yet",
	          "Select your channel to link the SDL board to, or create a new one for your feature",
	          "Click \"Add Integration\"",
	          "Select this Trello board, **SDL: $project_name** to link",
	          "Select the following triggers: **Checklist Item marked complete/incomplete**, and **Comment added to card**",
	          "Feel free to rename the integration to **SDL**"
	        )
	    )
	  );
	}


	/**
	 * Trello board creation
	 */
	function _sdl_trello_board_create($boardtocreate, $token, $key, $team){
	  $client = new Stevenmaguire\Services\Trello\Client(array(
	      'key' => $key,
	      'token' => $token
	  ));

	  $boarddetails = array(
	    "name" => "SDL: " . $boardtocreate['project_name'],
	    "keepFromSource" => '',
	    "idBoardSource" => "565a50e28640576d9c34c636", #empty board
	    "desc" => "Self-SDL-generated board, created by " . $boardtocreate['user'] . ' for ' . $boardtocreate['project_name']
	  );

	  if (isset($team) && $team != ""){
	    $boarddetails['idOrganization'] = $team;
	    $boarddetails['prefs_permissionLevel'] = "org";
	  }

	  $board = $client->addBoard($boarddetails);
	  $project_risk = _sdl_determineRiskValue($boardtocreate['risk_rating']);
	  foreach($boardtocreate['board'] as $category => $cards){
	    $curlist = $client->addList(array(
	      "idBoard" => $board->id,
	      "pos" => "bottom",
	      "name" => $category
	    ));

	    foreach($cards as $card){
	      $curCard = $client->addCard(array(
	        "idList" => $curlist->id,
	        "name" => $card['title'],
	        "desc" => $card['description']
	      ));

	      //Add label to cards depeding on risk level
	      if(isset($card["minimum_risk_required"])){
	        $card_risk = _sdl_determineRiskValue($card["minimum_risk_required"]);

	        if($project_risk >= $card_risk){
	          $curLabel = $client->addCardLabel($curCard->id, array(
	              "color" => 'red'
	          ));
	        }
	      }
	      if(isset($card['lists'])){
	        foreach($card['lists'] as $questiongroup => $questionlist){
	          $curChecklist = $client->addChecklist(array(
	            "idCard" => $curCard->id,
	            "name" => $questiongroup
	          ));
	          foreach ($questionlist as $questioncheckbox){
	            $curCheckbox = $client->addChecklistCheckItem($curChecklist->id, array(
	              "name" => $questioncheckbox
	            ));
	          }
	        }
	      }
	    }
	  }
	  return $board->id;
	}
