<?php namespace Main\Database;

/**
 * Voting topic data functions.
 */
class VotingTopicDao {
	
	/**
	 * Core Dao.
	 */
	private $coreDao;
	
	/**
	 * Constructs the user dao.
	 */
	public function __construct() {
		$this->coreDao = CoreDao::getInstance();
	}
	
	/**
	 * Looks up the topic by the user id.
	 * 
	 * @param MongoId $userId User id to lookup by.
	 * @return null|VotingTopicDao The voting topics object.
	 */
	public function lookupTopicViaUserId($userId) {
		$userDao = new \Main\Database\UserDao();
		$result = $this->coreDao->getVoting_topics()->findOne(array("users" => $userId));
		$convertedResult = $this->convertVotingTopicDataDocToVotingTopicData($result);
		if(!is_null($convertedResult)) {
			//Load base users
			$usersIter = $userDao->loadUsers($convertedResult->getUsers());
			$users = $this->convertUserIteratorToUserDataArray($usersIter);
			//$convertedResult["users"] = $users;
			
			//Load options
			$optionsIter = $this->coreDao->getOptions()->find(array(
	    		'_id' => array('$in' => $convertedResult->getOptions())));
			$options = array();
			
			foreach($optionsIter as $option) {
				
				$option = $this->convertVotingOptionsDataDocToVotingOptionsData($option);
				$optionUserList = array();
				foreach($option->getUsers() as $optionUserId) {
					foreach($users as $user) {
						if($optionUserId == $user->getId()) {
							array_push($optionUserList, $user);
						}
					}
				}
				$option->setUsers($optionUserList);
				array_push($options, $option);
			}
			
			var_dump($options);
			$convertedResult->setOptions($options);
			$convertedResult->setUsers(array());
		}
		return $convertedResult;
	}
	
	/**
	 * Maps the iterator results to an array of UserData objects.
	 * 
	 * @param Iterator $usersIter The iterator version of the user objects.
	 * @return array A converted array of user objects.
	 */
	private function convertUserIteratorToUserDataArray($usersIter) {
		$convertedUsers = array();
		
		foreach($usersIter as $userDataDoc) {
			$user = UserDao::convertUserDataDocToUserData($userDataDoc);
			array_push($convertedUsers, $user);
		}
		
		return $convertedUsers;
	}
	
	/**
	 * Converts mongo document array to VotingTopicData.
	 * 
	 * @param array $votingTopicDataDoc The mongoDocument version of the VotingTopicData doc.
	 * @return null|VotingTopicData The converted Voting Topic  object.
	 */
	 private function convertVotingTopicDataDocToVotingTopicData($votingTopicDataDoc) {
	 	$votingTopicData = null;
	 	if(!empty($votingTopicDataDoc)) {
	 		$votingTopicData = new \Main\To\VotingTopicData(
				$votingTopicDataDoc["_id"],
				$votingTopicDataDoc["description"],
				$votingTopicDataDoc["status"],
				$votingTopicDataDoc["options"],
				$votingTopicDataDoc["users"]
			);
		}
		
		return $votingTopicData;
	 }
	 
	 /**
	 * Converts mongo options document array to VotingOptionsData.
	 * 
	 * @param array $votingOptionsDataDoc The mongoDocument version of the VotingTopicData doc.
	 * @return null|VotingOptionsData The converted Voting options object.
	 */
	 private function convertVotingOptionsDataDocToVotingOptionsData($votingOptionsDataDoc) {
	 	$votingOptionsData = null;
	 	if(!empty($votingOptionsDataDoc)) {
	 		$votingOptionsData = new \Main\To\VotingOptionsData(
				$votingOptionsDataDoc["_id"],
				$votingOptionsDataDoc["description"],
				$votingOptionsDataDoc["users"],
				$votingOptionsDataDoc["messages"]
			);
		}
		
		return $votingOptionsData;
	 }
	 
	 /**
	  * Changes a user's vote in the system.
	  * 
	  * @param VotingTopicData $votinTopicData The current voting topic.
	  * @param UserData $userData The user's information.
	  * @param string $newVote The new option to switch the user to.
	  * @return array The updated list of options.
	  */
	 public function updateUserVote($votingTopicData, $userData, $newVote) {
	 	$this->removeUserVote($votingTopicData, $userData);
		$this->addUserVote($votingTopicData, $userData, $newVote);
		$this->getVotingTopicOptions($votingTopicData);
	 }
	 
	 /**
	  * Removes the users vote from any option.
	  * @param VotingTopicData $votinTopicData The current voting topic.
	  * @param MongoId $userId The user's id number.
	  * @return bool false if there was a database issue.
	  */
	 private function removeUserVote($votingTopicData, $userId) {
	 	$this->coreDao->getOptions()->update(array("users" => $userId), array('$pull' => array("users" => $userId)));
	 }
	 
	 /**
	  * Removes the users vote from an option.
	  * @param MongoId $optionId The voting topic option id.
	  * @param MongoId $userId The user's id number.
	  * @return bool false if there was a database issue.
	  */
	 private function addUserVote($optionId, $userId) {
	 	$this->coreDao->getOptions()->update(array("_id" => $optionId), array('$push' => array("users" => $userId)));
	 }
	 
	 // /**
	  // * Gets the update voting options.
	  // * @param VotingTopicData $votinTopicData The current voting topic.
	  // * @return array The updated list of options.
	  // */
	 // private function getVotingTopicOptions($votingTopicData) {
// 	 	
	 // }
}

?>